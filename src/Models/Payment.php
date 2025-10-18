<?php

namespace Models;

class Payment extends BaseModel
{
    protected string $table = 'payments';

    public function listRecent(int $limit = 50): array
    {
        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY `date` DESC, created_at DESC LIMIT {$limit}");
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'txnId' => $row['txn_id'] ?? null,
                'type' => $row['type'] ?? '',
                'amount' => isset($row['amount']) ? (float) $row['amount'] : 0.0,
                'recipient' => $row['recipient_name'] ?? '',
                'recipientId' => $row['recipient_id'] ?? null,
                'status' => $row['status'] ?? 'pending',
                'date' => $row['date'] ?? $row['created_at'] ?? null,
            ];
        }, $rows);
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
             WHERE type = 'payment' AND status = 'completed' AND `date` >= ? AND `date` < ?",
            [$start, $end]
        );
        return isset($row['total']) ? (float) $row['total'] : 0.0;
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
             ORDER BY COALESCE(`date`, created_at) DESC
             LIMIT {$limit}",
            [$companyId]
        );

        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'txnId' => $row['txn_id'] ?? null,
                'type' => $row['type'] ?? '',
                'amount' => isset($row['amount']) ? (float) $row['amount'] : 0.0,
                'status' => $row['status'] ?? 'pending',
                'date' => $row['date'] ?? $row['created_at'] ?? null,
            ];
        }, $rows);
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
}
