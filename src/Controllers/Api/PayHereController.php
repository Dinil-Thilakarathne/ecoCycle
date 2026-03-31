<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\Payment;
use Models\User;
use Services\Payment\PayHereService;

/**
 * PayHere Payment Gateway Controller
 *
 * Handles two endpoints:
 *  1. POST /api/payhere/checkout/{id}  — Company initiates payment (returns signed form payload)
 *  2. POST /api/payhere/notify         — PayHere server callback (verifies signature, updates DB)
 */
class PayHereController extends BaseController
{
    private PayHereService $payhere;
    private Payment $payments;
    private User $users;

    public function __construct()
    {
        $this->payhere  = new PayHereService();
        $this->payments = new Payment();
        $this->users    = new User();
    }

    // -------------------------------------------------------------------------
    // Step 1 — Company clicks "Pay with PayHere"
    // POST /api/payhere/checkout/{id}
    // -------------------------------------------------------------------------
    public function initiateCheckout(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        // Resolve invoice ID from route param
        $invoiceId = $request->route('id') ?? $request->get('id') ?? '';
        if ($invoiceId === '') {
            return Response::errorJson('Invoice ID is required', 400);
        }

        // Load and validate the invoice
        $invoice = $this->payments->findById((string) $invoiceId);
        if (!$invoice) {
            return Response::errorJson('Invoice not found', 404);
        }

        // Ensure the invoice belongs to this company
        $companyId = (int) $user['id'];
        if ((int) ($invoice['recipientId'] ?? 0) !== $companyId) {
            return Response::errorJson('Forbidden — this invoice does not belong to your account', 403);
        }

        // Only allow payment on pending/processing invoices
        $status = strtolower($invoice['status'] ?? '');
        if (!in_array($status, ['pending', 'processing'], true)) {
            return Response::errorJson('This invoice cannot be paid (status: ' . $status . ')', 422);
        }

        // Build customer data from the authenticated user
        $customer = $this->buildCustomerData($user);

        try {
            $payload = $this->payhere->buildCheckoutPayload($invoice, $customer);
        } catch (\RuntimeException $e) {
            // Misconfigured — surface the message so the dev can fix it
            return Response::errorJson('Payment gateway not configured: ' . $e->getMessage(), 503);
        } catch (\InvalidArgumentException $e) {
            return Response::errorJson($e->getMessage(), 422);
        }

        return Response::json([
            'success' => true,
            'payload' => $payload,
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 2 — PayHere server-to-server notification
    // POST /api/payhere/notify   (NO authentication — called by PayHere servers)
    // -------------------------------------------------------------------------
    public function notify(Request $request): Response
    {
        // Suppress session cookie output — PayHere's server has no session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // PayHere sends application/x-www-form-urlencoded
        // $_POST is populated automatically by PHP for this content type
        $post = !empty($_POST) ? $_POST : $request->all();

        // Log every incoming notify for debugging
        error_log('[PayHere Notify] Received POST: ' . json_encode($post));

        if (empty($post)) {
            error_log('[PayHere Notify] ERROR: Empty POST body');
            // Still return 200 so PayHere doesn't keep retrying
            return Response::json(['status' => 'empty_body']);
        }

        // Verify the md5sig signature
        try {
            $verified = $this->payhere->verifyNotification($post);
        } catch (\Throwable $e) {
            error_log('[PayHere Notify] Verification exception: ' . $e->getMessage());
            return Response::json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        error_log('[PayHere Notify] Signature valid: ' . ($verified['valid'] ? 'YES' : 'NO')
            . ' | order_id: ' . ($verified['order_id'] ?? 'n/a')
            . ' | status_code: ' . ($verified['status_code'] ?? 'n/a'));

        if (!$verified['valid']) {
            error_log('[PayHere Notify] REJECTED — signature mismatch for order: ' . ($post['order_id'] ?? 'unknown'));
            // Return 200 to prevent PayHere retries
            return Response::json(['status' => 'signature_mismatch']);
        }

        $statusCode = (int) $verified['status_code'];

        if ($statusCode === 2) {
            // Payment successful
            $result = $this->payhere->handleSuccessfulPayment($verified);
            error_log('[PayHere Notify] handleSuccessfulPayment result: ' . ($result ? 'true' : 'false'));
        } elseif (in_array($statusCode, [-1, -2, -3], true)) {
            // Failed / cancelled / chargedback
            $result = $this->payhere->handleFailedPayment($verified);
            error_log('[PayHere Notify] handleFailedPayment result: ' . ($result ? 'true' : 'false'));
        } else {
            // status 0 = pending — wait for final notification
            error_log('[PayHere Notify] Pending payment (status=0) — no action taken');
        }

        return Response::json(['status' => 'ok']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build customer data for PayHere from the authenticated user record.
     * Falls back to sensible defaults if fields are missing.
     */
    private function buildCustomerData(array $user): array
    {
        // Try to load full user profile
        $fullUser = $this->users->findById((int) ($user['id'] ?? 0)) ?? $user;

        $name = $fullUser['name'] ?? $fullUser['username'] ?? '';
        $parts = explode(' ', trim($name), 2);

        return [
            'first_name' => $parts[0] ?? 'Company',
            'last_name'  => $parts[1] ?? 'User',
            'email'      => $fullUser['email'] ?? '',
            'phone'      => $fullUser['phone'] ?? '0000000000',
            'address'    => $fullUser['address'] ?? 'N/A',
            'city'       => $fullUser['city']    ?? 'Colombo',
        ];
    }
}
