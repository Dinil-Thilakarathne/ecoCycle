<?php

namespace Models;

class BiddingRound extends BaseModel
{
    protected string $table = 'bidding_rounds';

    public function listAll(int $limit = 100): array
    {
        $limit = max(1, (int) $limit);
        $sql = "SELECT br.*, wc.name AS waste_category_name, u.name AS company_name
                FROM {$this->table} br
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                LEFT JOIN users u ON u.id = br.leading_company_id
                ORDER BY
                    CASE WHEN br.status = 'active' THEN 0 ELSE 1 END,
                    br.end_time ASC
                LIMIT {$limit}";
        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'lotId' => $row['lot_id'] ?? $row['id'],
                'wasteCategory' => $row['waste_category_name'] ?? '',
                'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : 0.0,
                'unit' => $row['unit'] ?? 'kg',
                'currentHighestBid' => isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0,
                'biddingCompany' => $row['company_name'] ?? '',
                'status' => $row['status'] ?? 'active',
                'endTime' => $row['end_time'] ?? null,
            ];
        }, $rows);
    }

    public function stats(): array
    {
        $row = $this->db->fetch(
            "SELECT
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_rounds,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_rounds,
                SUM(current_highest_bid) AS total_bid_value
            FROM {$this->table}"
        );

        return [
            'active' => isset($row['active_rounds']) ? (int) $row['active_rounds'] : 0,
            'completed' => isset($row['completed_rounds']) ? (int) $row['completed_rounds'] : 0,
            'totalValue' => isset($row['total_bid_value']) ? (float) $row['total_bid_value'] : 0.0,
        ];
    }

    public function recent(int $limit = 5): array
    {
        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll(
            "SELECT br.id, br.status, br.end_time, br.lot_id, wc.name AS waste_category_name
             FROM {$this->table} br
             LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
             ORDER BY br.updated_at DESC, br.end_time DESC
             LIMIT {$limit}"
        );
        return $rows ?: [];
    }
}
