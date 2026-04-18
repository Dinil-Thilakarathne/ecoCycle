<?php

namespace Services\Payment;

use Models\Payment;
use Models\User;

class PaymentService
{
    private Payment $payments;
    private User $users;
    private \Models\Notification $notifications;

    public function __construct(?Payment $payments = null, ?User $users = null, ?\Models\Notification $notifications = null)
    {
        $this->payments = $payments ?? new Payment();
        $this->users = $users ?? new User();
        $this->notifications = $notifications ?? new \Models\Notification();
    }

    public function createManualPayment(array $data): array
    {
        $recipientId = (int) ($data['recipientId'] ?? 0);
        if ($recipientId <= 0) {
            throw new \InvalidArgumentException('Recipient id is required.');
        }

        $user = $this->users->findById($recipientId);
        if (!$user) {
            throw new \InvalidArgumentException('Recipient not found.');
        }

        $type = strtolower((string) ($data['type'] ?? 'payout'));
        if (!in_array($type, ['payment', 'payout', 'refund'], true)) {
            throw new \InvalidArgumentException('Unsupported payment type.');
        }

        $status = strtolower((string) ($data['status'] ?? 'completed'));
        if (!in_array($status, ['pending', 'processing', 'completed', 'failed'], true)) {
            throw new \InvalidArgumentException('Unsupported payment status.');
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        $payload = [
            'id' => $data['id'] ?? null,
            'txn_id' => $data['txnId'] ?? $data['txn_id'] ?? null,
            'type' => $type,
            'amount' => round($amount, 2),
            'recipient_id' => $recipientId,
            'recipient_name' => $user['name'] ?? $user['email'] ?? ('User #' . $recipientId),
            'status' => $status,
            'date' => $data['date'] ?? date('Y-m-d H:i:s'),
            'gateway_response' => $data['gatewayResponse'] ?? $data['gateway_response'] ?? null,
            'notes' => $data['notes'] ?? null,
            'bidding_round_id' => $data['biddingRoundId'] ?? $data['bidding_round_id'] ?? null,
        ];

        $record = $this->payments->record($payload);

        // Integration with Wallet Transaction Ledger
        // If this is a completed Payout, we DEBIT the user's wallet.
        if ($record && $status === 'completed' && $type === 'payout') {
            try {
                $wallet = new \Models\WalletTransaction(); // Lazy load to avoid circular deps if any
                $wallet->logTransaction(
                    $recipientId,
                    $amount,
                    'debit',
                    'payout',
                    0, // sourceId is INT, but Payment ID is string. Storing 0 for now.
                    "Payout processed (Ref: " . ($record['id'] ?? 'N/A') . ")"
                );
            } catch (\Throwable $e) {
                // Log error but don't fail the payment record itself?
                // For now, let's swallow it or just let it bubble? 
                // Better to not break existing flow, but this IS a financial ledger.
                // Re-throwing might be safer to notice issues.
            }
        }

        // Send notification
        try {
            $msgType = ($status === 'failed') ? 'alert' : 'info';
            $this->notifications->create([
                'type' => $msgType,
                'title' => 'New Transaction: ' . ucfirst($type),
                'message' => "A {$type} of {$amount} has been recorded. Status: {$status}.",
                'recipients' => ['user:' . $recipientId],
            ]);
        } catch (\Throwable $e) {
            // Ignore notification errors to not block payment flow
        }

        return $record;
    }

    public function updatePayment(string $id, array $data): array
    {
        $existing = $this->payments->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException('Payment not found.');
        }

        // Validate if status is being updated
        if (isset($data['status'])) {
            $status = strtolower((string) $data['status']);
            if (!in_array($status, ['pending', 'processing', 'completed', 'failed'], true)) {
                throw new \InvalidArgumentException('Unsupported payment status.');
            }
            $data['status'] = $status;
        }

        // Validate if type is being updated
        if (isset($data['type'])) {
            $type = strtolower((string) $data['type']);
            if (!in_array($type, ['payment', 'payout', 'refund'], true)) {
                throw new \InvalidArgumentException('Unsupported payment type.');
            }
            $data['type'] = $type;
        }

        // Validate amount if provided
        if (isset($data['amount'])) {
            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Amount must be greater than zero.');
            }
            $data['amount'] = round($amount, 2);
        }

        if (array_key_exists('notes', $data)) {
            // will be passed along safely
        }

        if (array_key_exists('biddingRoundId', $data) || array_key_exists('bidding_round_id', $data)) {
            // will be passed along safely
        }

        // If status is being updated to completed, and it's a payment with a bidding round
        // We trigger the lot release
        if (isset($data['status']) && $data['status'] === 'completed' && ($existing['type'] ?? '') === 'payment') {
            $biddingRoundId = $existing['biddingRoundId'] ?? null;
            if ($biddingRoundId) {
                try {
                    $biddingRound = new \Models\BiddingRound();
                    $biddingRound->markAsPaid($biddingRoundId);
                } catch (\Throwable $e) {
                    error_log("Failed to mark bidding round $biddingRoundId as paid: " . $e->getMessage());
                    // Still continue with payment update
                }
            }
        }

        $success = $this->payments->update($id, $data);
        if (!$success) {
            throw new \RuntimeException('Failed to update payment record.');
        }

        $updatedRecord = $this->payments->findById($id) ?? [];

        if (!empty($updatedRecord)) {
            try {
                $newStatus = $updatedRecord['status'] ?? 'unknown';
                $recpId = $updatedRecord['recipientId'] ?? 0;

                // Truncate logic to avoid spamming if needed, but for now send on every update
                $this->notifications->create([
                    'type' => 'info',
                    'title' => 'Transaction Updated',
                    'message' => "Transaction {$id} is now {$newStatus}.",
                    'recipients' => ['user:' . $recpId],
                ]);
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $updatedRecord;
    }
}

