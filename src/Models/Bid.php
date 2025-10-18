<?php

namespace Models;

class Bid extends BaseModel
{
    protected string $table = 'bids';

    public function companyHistory(int $companyId, int $limit = 20): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);

        $sql = "SELECT
                    b.id,
                    b.amount,
                    b.is_winner,
                    b.created_at,
                    br.lot_id,
                    br.quantity,
                    br.unit,
                    br.status AS round_status,
                    br.current_highest_bid,
                    br.leading_company_id,
                    br.end_time,
                    wc.name AS waste_category_name
                FROM {$this->table} b
                INNER JOIN bidding_rounds br ON br.id = b.bidding_round_id
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE b.company_id = ?
                ORDER BY b.created_at DESC
                LIMIT {$limit}";

        $rows = $this->db->fetchAll($sql, [$companyId]);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row) use ($companyId): array {
            $quantity = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
            $unit = $row['unit'] ?? 'kg';
            $category = $row['waste_category_name'] ?? 'Unknown';
            $lotId = $row['lot_id'] ?: ('BR-' . $row['id']);
            $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
            $createdAt = $row['created_at'] ?? null;
            $roundStatus = $row['round_status'] ?? 'pending';
            $leadingCompanyId = $row['leading_company_id'] ?? null;
            $isWinner = (int) ($row['is_winner'] ?? 0) === 1;

            $status = 'Pending';
            if ($roundStatus === 'active') {
                $status = ((int) $leadingCompanyId === $companyId) ? 'Leading' : 'Active';
            } elseif ($roundStatus === 'completed') {
                $status = $isWinner ? 'Won' : 'Lost';
            }

            if ($isWinner && $roundStatus !== 'completed') {
                $status = 'Won';
            }

            return [
                'id' => (int) $row['id'],
                'displayId' => 'BID' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT),
                'category' => $category,
                'quantity' => $quantity,
                'unit' => $unit,
                'amount' => $amount,
                'status' => $status,
                'roundStatus' => $roundStatus,
                'lotId' => $lotId,
                'createdAt' => $createdAt,
                'currentHighestBid' => isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0,
                'endTime' => $row['end_time'] ?? null,
            ];
        }, $rows);
    }

    public function monthlyCounts(int $companyId, int $months = 6): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $months = max(1, $months);
        $sql = "SELECT DATE_FORMAT(b.created_at, '%Y-%m') AS period,
                       COUNT(*) AS total,
                       SUM(CASE WHEN b.is_winner = 1 THEN 1 ELSE 0 END) AS won
                FROM {$this->table} b
                WHERE b.company_id = ?
                  AND b.created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY period
                ORDER BY period ASC";

        $rows = $this->db->fetchAll($sql, [$companyId, $months]);
        if (!$rows) {
            return [];
        }

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'period' => $row['period'],
                'total' => (int) ($row['total'] ?? 0),
                'won' => (int) ($row['won'] ?? 0),
            ];
        }

        return $data;
    }

    public function totals(int $companyId): array
    {
        if ($companyId <= 0) {
            return ['total' => 0, 'won' => 0];
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) AS won
             FROM {$this->table}
             WHERE company_id = ?",
            [$companyId]
        );

        return [
            'total' => isset($row['total']) ? (int) $row['total'] : 0,
            'won' => isset($row['won']) ? (int) $row['won'] : 0,
        ];
    }

    public function monthlyCategoryAmounts(int $companyId, int $months = 6): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $months = max(1, $months);
        $sql = "SELECT
                    DATE_FORMAT(b.created_at, '%Y-%m') AS period,
                    wc.name AS category,
                    SUM(b.amount) AS total_amount
                FROM {$this->table} b
                INNER JOIN bidding_rounds br ON br.id = b.bidding_round_id
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE b.company_id = ?
                  AND b.created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY period, category
                ORDER BY period ASC";

        $rows = $this->db->fetchAll($sql, [$companyId, $months]);
        if (!$rows) {
            return [];
        }

        $data = [];
        foreach ($rows as $row) {
            $period = $row['period'];
            $category = $row['category'] ?? 'Other';
            $total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
            if (!isset($data[$category])) {
                $data[$category] = [];
            }
            $data[$category][$period] = $total;
        }

        return $data;
    }
}
