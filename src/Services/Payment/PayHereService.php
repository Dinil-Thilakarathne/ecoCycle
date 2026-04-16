<?php

namespace Services\Payment;

use Models\Notification;
use Models\Payment;
use Services\Payment\PaymentService;


/**
 * PayHere Payment Gateway Service (Sandbox / Live)
 *
 * Handles hash generation for checkout and signature verification
 * for the server-side payment notification callback.
 *
 * Docs: https://support.payhere.lk/api-&-mobile-sdk/checkout-api
 */
class PayHereService
{
    private string $merchantId;
    private string $merchantSecret;
    private bool $sandbox;
    private string $checkoutUrl;
    private string $notifyUrl;
    private string $returnUrl;
    private string $cancelUrl;

    private Payment $payments;
    private Notification $notifications;
    private PaymentService $paymentService;


    public function __construct(?Payment $payments = null, ?Notification $notifications = null)
    {
        // Read env with full fallback chain: $_ENV -> $_SERVER -> direct .env/.env.local file read
        $this->merchantId     = $this->readEnv('PAYHERE_MERCHANT_ID');
        $this->merchantSecret = $this->readEnv('PAYHERE_MERCHANT_SECRET');
        $sandbox              = $this->readEnv('PAYHERE_SANDBOX', 'true');
        $this->sandbox        = ($sandbox === 'true' || $sandbox === '1' || $sandbox === true);
        // 'auto' means: fetch from ngrok API at checkout time
        $this->notifyUrl      = $this->readEnv('PAYHERE_NOTIFY_URL', 'auto');
        $this->returnUrl      = $this->readEnv('PAYHERE_RETURN_URL');
        $this->cancelUrl      = $this->readEnv('PAYHERE_CANCEL_URL');

        $this->checkoutUrl = $this->sandbox
            ? 'https://sandbox.payhere.lk/pay/checkout'
            : 'https://www.payhere.lk/pay/checkout';

        $this->payments       = $payments      ?? new Payment();
        $this->notifications  = $notifications ?? new Notification();
        // Route status updates through PaymentService so bidding-round & notification
        // business logic (markAsPaid, wallet ledger, etc.) always fires correctly
        $this->paymentService = new PaymentService($this->payments);
    }




    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Build the complete set of POST parameters needed to redirect the customer
     * to PayHere. The hash is generated server-side (never exposed to browser).
     *
     * @param  array  $invoice   Payment record from DB (id, amount, notes, recipientId, …)
     * @param  array  $customer  User record (first_name, last_name, email, phone, address, city)
     * @throws \RuntimeException if credentials are not configured
     */
    public function buildCheckoutPayload(array $invoice, array $customer): array
    {
        $this->assertConfigured();

        $orderId  = (string) ($invoice['id'] ?? '');
        $amount   = (float)  ($invoice['amount'] ?? 0);
        $currency = 'LKR';

        if ($orderId === '' || $amount <= 0) {
            throw new \InvalidArgumentException('Invalid invoice — missing id or amount.');
        }

        // Resolve the notify URL — fetches from ngrok API if set to 'auto'
        $notifyUrl = $this->resolveNotifyUrl();

        $hash = $this->generateHash($orderId, $amount, $currency);

        return [
            // Gateway
            'action_url'  => $this->checkoutUrl,
            // Required params
            'merchant_id' => $this->merchantId,
            'return_url'  => $this->returnUrl,
            'cancel_url'  => $this->cancelUrl,
            'notify_url'  => $notifyUrl,
            // Order
            'order_id'    => $orderId,
            'items'       => $invoice['notes'] ?? ('Invoice ' . $orderId),
            'currency'    => $currency,
            'amount'      => number_format($amount, 2, '.', ''),
            // Customer
            'first_name'  => $customer['first_name'] ?? $customer['name'] ?? 'Company',
            'last_name'   => $customer['last_name']  ?? 'User',
            'email'       => $customer['email']       ?? '',
            'phone'       => $customer['phone']       ?? '0000000000',
            'address'     => $customer['address']     ?? 'N/A',
            'city'        => $customer['city']        ?? 'Colombo',
            'country'     => 'Sri Lanka',
            // Security
            'hash'        => $hash,
            // Metadata — custom params forwarded back in notify
            'custom_1'    => $orderId,
            'custom_2'    => (string) ($invoice['recipientId'] ?? ''),
        ];
    }


    /**
     * Verify an incoming notify_url callback from PayHere.
     *
     * Returns ['valid' => bool, 'status_code' => int, 'payment_id' => string, ...]
     *
     * @param  array $post  Raw $_POST / request body from PayHere callback
     */
    public function verifyNotification(array $post): array
    {
        $merchantId    = $post['merchant_id']     ?? '';
        $orderId       = $post['order_id']        ?? '';
        $payhereAmount = $post['payhere_amount']  ?? '';
        $payhereCurr   = $post['payhere_currency'] ?? '';
        $statusCode    = (int) ($post['status_code'] ?? -99);
        $md5sig        = $post['md5sig']           ?? '';

        // Re-generate expected signature
        $localSig = strtoupper(md5(
            $merchantId
            . $orderId
            . $payhereAmount
            . $payhereCurr
            . $statusCode
            . strtoupper(md5($this->merchantSecret))
        ));

        $valid = hash_equals($localSig, strtoupper($md5sig));

        return [
            'valid'       => $valid,
            'status_code' => $statusCode,
            'order_id'    => $orderId,
            'payment_id'  => $post['payment_id']  ?? null,
            'amount'      => $payhereAmount,
            'currency'    => $payhereCurr,
            'method'      => $post['method']      ?? null,
            'raw'         => $post,
        ];
    }

    /**
     * Handle a verified successful PayHere notification (status_code == 2).
     * Routes through PaymentService so all business logic fires:
     *   - bidding round markAsPaid() (triggers lot release to "Ready for Collection")
     *   - company & admin notifications
     */
    public function handleSuccessfulPayment(array $verified): bool
    {
        $orderId   = $verified['order_id']   ?? '';
        $paymentId = $verified['payment_id'] ?? null;

        if ($orderId === '') {
            error_log('[PayHere] handleSuccessfulPayment: empty order_id');
            return false;
        }

        $invoice = $this->payments->findById($orderId);
        if (!$invoice) {
            error_log("[PayHere] handleSuccessfulPayment: Invoice not found for order_id={$orderId}");
            return false;
        }

        // Idempotency guard — don't double-process
        if (strtolower($invoice['status'] ?? '') === 'completed') {
            error_log("[PayHere] handleSuccessfulPayment: Invoice {$orderId} already completed, skipping");
            return true;
        }

        $gatewayData = json_encode([
            'gateway'    => 'payhere',
            'payment_id' => $paymentId,
            'method'     => $verified['method']   ?? null,
            'amount'     => $verified['amount']   ?? null,
            'currency'   => $verified['currency'] ?? 'LKR',
        ], JSON_UNESCAPED_UNICODE);

        try {
            // Route through PaymentService — this triggers:
            // 1. Payment model update (status → completed, txnId, gatewayResponse)
            // 2. BiddingRound::markAsPaid() → moves lot to "Ready for Collection"
            // 3. Company notification via PaymentService
            $this->paymentService->updatePayment($orderId, [
                'status'          => 'completed',
                'txnId'           => $paymentId,
                'gatewayResponse' => $gatewayData,
            ]);
        } catch (\Throwable $e) {
            error_log('[PayHere] handleSuccessfulPayment: updatePayment failed — ' . $e->getMessage());
            return false;
        }

        // Extra admin notification (PaymentService notifies company; we notify admin)
        try {
            $this->notifications->create([
                'type'            => 'success',
                'title'           => 'PayHere Payment Received',
                'message'         => "Invoice {$orderId} paid via PayHere (ID: {$paymentId}). Bidding round released.",
                'recipient_group' => 'admin',
                'status'          => 'pending',
            ]);
        } catch (\Throwable $e) {
            error_log('[PayHere] Admin notification error: ' . $e->getMessage());
        }

        return true;
    }


    /**
     * Handle a failed / cancelled PayHere notification.
     * Routes through PaymentService for consistent notification side-effects.
     */
    public function handleFailedPayment(array $verified): bool
    {
        $orderId    = $verified['order_id']    ?? '';
        $statusCode = (int) ($verified['status_code'] ?? -2);

        if ($orderId === '') {
            return false;
        }

        $invoice = $this->payments->findById($orderId);
        if (!$invoice) {
            return false;
        }

        // Only downgrade from pending/processing — never overwrite completed
        if (!in_array(strtolower($invoice['status'] ?? ''), ['pending', 'processing'], true)) {
            return true;
        }

        $statusMap = [-1 => 'failed', -2 => 'failed', -3 => 'failed'];
        $newStatus = $statusMap[$statusCode] ?? 'failed';

        $gatewayData = json_encode([
            'gateway'     => 'payhere',
            'status_code' => $statusCode,
            'method'      => $verified['method'] ?? null,
        ], JSON_UNESCAPED_UNICODE);

        try {
            $this->paymentService->updatePayment($orderId, [
                'status'          => $newStatus,
                'gatewayResponse' => $gatewayData,
            ]);
        } catch (\Throwable $e) {
            error_log('[PayHere] handleFailedPayment: updatePayment failed — ' . $e->getMessage());
            return false;
        }

        return true;
    }


    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Generate the PayHere checkout hash.
     * Formula: strtoupper(md5(merchant_id . order_id . formatted_amount . currency . strtoupper(md5(merchant_secret))))
     */
    private function generateHash(string $orderId, float $amount, string $currency): string
    {
        $formattedAmount = number_format($amount, 2, '.', '');
        $hashedSecret    = strtoupper(md5($this->merchantSecret));

        return strtoupper(md5(
            $this->merchantId
            . $orderId
            . $formattedAmount
            . $currency
            . $hashedSecret
        ));
    }

    /**
     * Returns current tunnel status — useful for a debug/status endpoint.
     * Returns ['active' => bool, 'url' => string|null, 'notify_url' => string|null]
     */
    public function getTunnelStatus(): array
    {
        $url = $this->fetchNgrokTunnelUrl();
        if ($url) {
            return [
                'active'     => true,
                'url'        => $url,
                'notify_url' => rtrim($url, '/') . '/api/payhere/notify',
                'source'     => 'ngrok',
            ];
        }
        if ($this->notifyUrl !== '' && $this->notifyUrl !== 'auto') {
            return [
                'active'     => true,
                'url'        => $this->notifyUrl,
                'notify_url' => $this->notifyUrl,
                'source'     => 'env',
            ];
        }
        return ['active' => false, 'url' => null, 'notify_url' => null, 'source' => null];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the notify URL.
     * If PAYHERE_NOTIFY_URL is 'auto' or empty, query ngrok's local API.
     * Falls back to the raw env value if ngrok is not running.
     */
    private function resolveNotifyUrl(): string
    {
        // If a concrete URL is configured, use it directly
        if ($this->notifyUrl !== '' && $this->notifyUrl !== 'auto') {
            return $this->notifyUrl;
        }

        // Try to auto-detect from ngrok's management API
        $tunnelBase = $this->fetchNgrokTunnelUrl();
        if ($tunnelBase) {
            $resolved = rtrim($tunnelBase, '/') . '/api/payhere/notify';
            error_log('[PayHere] Auto-detected notify URL from ngrok: ' . $resolved);
            return $resolved;
        }

        throw new \RuntimeException(
            'PAYHERE_NOTIFY_URL is set to "auto" but ngrok is not running or not reachable. '
            . 'Start Docker with ngrok service: docker compose -f docker-compose.dev.yml up -d'
        );
    }

    /**
     * Fetch the current public tunnel URL from ngrok's management API.
     * ngrok exposes this at http://ngrok:4040/api/tunnels inside Docker.
     * Returns null if ngrok is not running or not reachable.
     */
    private function fetchNgrokTunnelUrl(): ?string
    {
        // ngrok management API — 'ngrok' is the service name in docker-compose
        $apiUrls = [
            'http://ngrok:4040/api/tunnels',   // inside Docker
            'http://localhost:4040/api/tunnels', // running locally
        ];

        foreach ($apiUrls as $apiUrl) {
            try {
                $ctx = stream_context_create([
                    'http' => [
                        'timeout'        => 2,
                        'ignore_errors'  => true,
                    ]
                ]);
                $response = @file_get_contents($apiUrl, false, $ctx);
                if ($response === false) {
                    continue;
                }
                $data = json_decode($response, true);
                if (!is_array($data) || empty($data['tunnels'])) {
                    continue;
                }
                // Find the HTTPS tunnel
                foreach ($data['tunnels'] as $tunnel) {
                    $url = $tunnel['public_url'] ?? '';
                    if (str_starts_with($url, 'https://')) {
                        return $url;
                    }
                }
            } catch (\Throwable $e) {
                // ngrok not running — try next
                continue;
            }
        }

        return null;
    }

    private function assertConfigured(): void
    {
        if ($this->merchantId === '' || $this->merchantSecret === '') {
            throw new \RuntimeException(
                'PayHere is not configured. Set PAYHERE_MERCHANT_ID and PAYHERE_MERCHANT_SECRET in .env'
            );
        }
        // notify URL validation happens inside resolveNotifyUrl() at checkout time
    }


    /**
     * Robust env reader: tries $_ENV, $_SERVER, getenv(), then falls back to
     * reading .env.local and .env files directly. This guarantees the service
     * works regardless of framework boot order or opcode caching.
     */
    private function readEnv(string $key, string $default = ''): string
    {
        // 1. Check $_ENV (set by framework Environment::load)
        if (isset($_ENV[$key]) && $_ENV[$key] !== false && $_ENV[$key] !== null) {
            return (string) $_ENV[$key];
        }

        // 2. Check $_SERVER
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== false && $_SERVER[$key] !== null) {
            return (string) $_SERVER[$key];
        }

        // 3. Check getenv()
        $val = getenv($key);
        if ($val !== false) {
            return (string) $val;
        }

        // 4. Last resort: read the .env files directly
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        foreach (['.env.local', '.env'] as $filename) {
            $file = $basePath . '/' . $filename;
            if (!file_exists($file)) {
                continue;
            }
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach (array_reverse($lines) as $line) { // reverse to get last definition
                $line = trim($line);
                if (empty($line) || $line[0] === '#') {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    [$k, $v] = explode('=', $line, 2);
                    if (trim($k) === $key) {
                        $v = trim($v, '"\' ');
                        // Also populate $_ENV for subsequent calls
                        $_ENV[$key] = $v;
                        return $v;
                    }
                }
            }
        }

        return $default;
    }

    private function env(string $key, string $default = ''): string
    {
        return $this->readEnv($key, $default);
    }

}
