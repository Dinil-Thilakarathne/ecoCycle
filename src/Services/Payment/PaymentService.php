<?php

namespace Services\Payment;

use Models\Payment;
use Models\User;

class PaymentService
{
    private Payment $payments;
    private User $users;

    public function __construct(?Payment $payments = null, ?User $users = null)
    {
        $this->payments = $payments ?? new Payment();
        $this->users = $users ?? new User();
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
        ];

        return $this->payments->record($payload);
    }
}

