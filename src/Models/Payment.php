<?php

namespace Models;

class Payment extends BaseModel
{
    protected string $table = 'payments';

    public function findById(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        $row = $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return $row ? $this->mapRow($row) : null;
    }

    public function record(array $data): array
    {
        $payload = [
            'id' => $data['id'] ?? $this->generateId(),
            'txn_id' => $data['txn_id'] ?? $data['txnId'] ?? null,
            'type' => $data['type'] ?? 'payment',
            'amount' => isset($data['amount']) ? (float) $data['amount'] : 0.0,
            'recipient_id' => $data['recipient_id'] ?? $data['recipientId'] ?? null,
            'recipient_name' => $data['recipient_name'] ?? $data['recipientName'] ?? null,
            'status' => $data['status'] ?? 'completed',
            'date' => $data['date'] ?? date('Y-m-d H:i:s'),
            'gateway_response' => $data['gateway_response'] ?? $data['gatewayResponse'] ?? null,
        ];

        if (is_array($payload['gateway_response'])) {
            $payload['gateway_response'] = json_encode($payload['gateway_response'], JSON_UNESCAPED_UNICODE);
        }

        $sql = "INSERT INTO {$this->table} (id, txn_id, type, amount, recipient_id, recipient_name, date, status, gateway_response, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $params = [
            $payload['id'],
            $payload['txn_id'],
            $payload['type'],
            $payload['amount'],
            $payload['recipient_id'],
            $payload['recipient_name'],
            $payload['date'],
            $payload['status'],
            $payload['gateway_response'],
        ];

        $this->db->query($sql, $params);

        return $this->findById($payload['id']) ?? [];
    }

    public function update(string $id, array $data): bool
    {
        $id = trim($id);
        if ($id === '') {
            return false;
        }

        $fields = [];
        $params = [];

        if (array_key_exists('txnId', $data)) {
            $fields[] = 'txn_id = ?';
            $params[] = $data['txnId'];
        }
        if (array_key_exists('type', $data)) {
            $fields[] = 'type = ?';
            $params[] = $data['type'];
        }
        if (array_key_exists('amount', $data)) {
            $fields[] = 'amount = ?';
            $params[] = (float) $data['amount'];
        }
        if (array_key_exists('recipientId', $data)) {
            $fields[] = 'recipient_id = ?';
            $params[] = $data['recipientId'];
        }
        if (array_key_exists('recipientName', $data)) {
            $fields[] = 'recipient_name = ?';
            $params[] = $data['recipientName'];
        }
        if (array_key_exists('status', $data)) {
            $fields[] = 'status = ?';
            $params[] = $data['status'];
        }
        if (array_key_exists('date', $data)) {
            $fields[] = 'date = ?';
            $params[] = $data['date'];
        }
        if (array_key_exists('gatewayResponse', $data)) {
            $fields[] = 'gateway_response = ?';
            $val = $data['gatewayResponse'];
            if (is_array($val)) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            $params[] = $val;
        }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $fields[] = 'updated_at = NOW()';
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;

        return $this->db->query($sql, $params);
    }

    public function listRecent(int $limit = 50): array
    {
        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY date DESC, created_at DESC LIMIT {$limit}");
        if (!$rows) {
            return [];
        }

        return array_map(fn(array $row): array => $this->mapRow($row), $rows);
    }

    public function getSummary(): array
    {
        $row = $this->db->fetch(
            "SELECT
                SUM(CASE WHEN type = 'payout' AND status = 'completed' THEN amount ELSE 0 END) AS total_payouts,
                SUM(CASE WHEN type = 'payment' AND status = 'completed' THEN amount ELSE 0 END) AS total_payments,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count
            FROM {$this->table}"
        );

        return [
            'total_payouts' => isset($row['total_payouts']) ? (float) $row['total_payouts'] : 0.0,
            'total_payments' => isset($row['total_payments']) ? (float) $row['total_payments'] : 0.0,
            'pending_count' => isset($row['pending_count']) ? (int) $row['pending_count'] : 0,
        ];
    }

    public function sumCompletedPaymentsForMonth(int $year, int $month): float
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-d H:i:s', strtotime("{$start} +1 month"));
        $row = $this->db->fetch(
            "SELECT SUM(amount) AS total
             FROM {$this->table}
            WHERE type = 'payment' AND status = 'completed' AND date >= ? AND date < ?",
            [$start, $end]
        );
        return isset($row['total']) ? (float) $row['total'] : 0.0;
    }

    public function listForRecipient(int $recipientId, ?string $type = null, int $limit = 20, ?string $status = null): array
    {
        if ($recipientId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);
        $sql = "SELECT * FROM {$this->table} WHERE recipient_id = ?";
        $params = [$recipientId];

        if ($type !== null) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY COALESCE(date, created_at) DESC LIMIT {$limit}";
        $rows = $this->db->fetchAll($sql, $params);

        if (!$rows) {
            return [];
        }

        return array_map(fn(array $row): array => $this->mapRow($row), $rows);
    }

    public function listCustomerPayments(int $customerId, int $limit = 20, ?string $status = null): array
    {
        return $this->listForRecipient($customerId, 'payout', $limit, $status);
    }

    public function listCompanyInvoices(int $companyId, int $limit = 20, ?string $status = null): array
    {
        return $this->listForRecipient($companyId, 'payment', $limit, $status);
    }

    public function companyPayments(int $companyId, int $limit = 20): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE recipient_id = ?
             ORDER BY COALESCE(date, created_at) DESC
             LIMIT {$limit}",
            [$companyId]
        );

        if (!$rows) {
            return [];
        }

        return array_map(fn(array $row): array => $this->mapRow($row), $rows);
    }

    public function companyTotals(int $companyId): array
    {
        if ($companyId <= 0) {
            return ['totalAmount' => 0.0, 'active' => 0, 'completed' => 0];
        }

        $row = $this->db->fetch(
            "SELECT
                SUM(amount) AS total_amount,
                SUM(CASE WHEN status != 'completed' THEN 1 ELSE 0 END) AS active_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders
            FROM {$this->table}
            WHERE recipient_id = ?",
            [$companyId]
        );

        return [
            'totalAmount' => isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0,
            'activeOrders' => isset($row['active_orders']) ? (int) $row['active_orders'] : 0,
            'completedOrders' => isset($row['completed_orders']) ? (int) $row['completed_orders'] : 0,
        ];
    }

    private function mapRow(array $row): array
    {
        return [
            'id' => $row['id'] ?? null,
            'txnId' => $row['txn_id'] ?? null,
            'type' => $row['type'] ?? '',
            'amount' => isset($row['amount']) ? (float) $row['amount'] : 0.0,
            'recipient' => $row['recipient_name'] ?? '',
            'recipientName' => $row['recipient_name'] ?? '',
            'recipientId' => $row['recipient_id'] ?? null,
            'status' => $row['status'] ?? 'pending',
            'date' => $row['date'] ?? $row['created_at'] ?? null,
            'gatewayResponse' => $this->decodeJsonField($row['gateway_response'] ?? null),
        ];
    }

    private function decodeJsonField(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function generateId(): string
    {
        return 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
    }
}
