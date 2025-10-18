<?php

namespace Models;

class BiddingRound extends BaseModel
{
    protected string $table = 'bidding_rounds';

    public function availableWasteOverview(): array
    {
        $sql = "SELECT
                    wc.name AS category,
                    SUM(br.quantity) AS total_quantity,
                    COALESCE(br.unit, 'kg') AS unit
                FROM {$this->table} br
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE br.status = 'active'
                GROUP BY wc.id, wc.name, unit
                HAVING total_quantity IS NOT NULL
                ORDER BY total_quantity DESC";

        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'category' => $row['category'] ?? 'Unknown',
                'quantity' => isset($row['total_quantity']) ? (float) $row['total_quantity'] : 0.0,
                'unit' => $row['unit'] ?? 'kg',
            ];
        }, $rows);
    }

    public function highestBidsByCategory(): array
    {
        $sql = "SELECT
                    br.id,
                    br.lot_id,
                    br.quantity,
                    COALESCE(br.unit, 'kg') AS unit,
                    br.current_highest_bid,
                    br.status,
                    wc.name AS category,
                    br.waste_category_id
                FROM {$this->table} br
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE br.current_highest_bid = (
                    SELECT MAX(br2.current_highest_bid)
                    FROM {$this->table} br2
                    WHERE br2.waste_category_id = br.waste_category_id
                )
                AND br.waste_category_id IS NOT NULL";

        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        $seen = [];
        $result = [];
        foreach ($rows as $row) {
            $categoryId = (int) ($row['waste_category_id'] ?? 0);
            if (isset($seen[$categoryId])) {
                continue;
            }
            $seen[$categoryId] = true;

            $result[] = [
                'category' => $row['category'] ?? 'Unknown',
                'lotId' => $row['lot_id'] ?? ($row['id'] ?? null),
                'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : 0.0,
                'unit' => $row['unit'] ?? 'kg',
                'currentHighestBid' => isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0,
                'status' => $row['status'] ?? 'active',
            ];
        }

        return $result;
    }

    public function activeLots(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT br.*, wc.name AS waste_category_name
             FROM {$this->table} br
             LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
             WHERE br.status = 'active'
             ORDER BY br.end_time ASC, br.created_at DESC"
        );

        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'lotId' => $row['lot_id'] ?? $row['id'],
                'category' => $row['waste_category_name'] ?? 'Unknown',
                'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : 0.0,
                'unit' => $row['unit'] ?? 'kg',
                'currentHighestBid' => isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0,
                'status' => $row['status'] ?? 'active',
                'endTime' => $row['end_time'] ?? null,
            ];
        }, $rows);
    }

    public function companyRounds(int $companyId, ?string $status = null, int $limit = 20): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);

        $sql = "SELECT br.*, wc.name AS waste_category_name
                FROM {$this->table} br
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE br.leading_company_id = ?";
        $params = [$companyId];

        if ($status !== null) {
            $sql .= " AND br.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY COALESCE(br.end_time, br.updated_at, br.created_at) DESC LIMIT {$limit}";

        $rows = $this->db->fetchAll($sql, $params);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'lotId' => $row['lot_id'] ?? $row['id'],
                'category' => $row['waste_category_name'] ?? 'Unknown',
                'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : 0.0,
                'unit' => $row['unit'] ?? 'kg',
                'currentHighestBid' => isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0,
                'status' => $row['status'] ?? 'active',
                'endTime' => $row['end_time'] ?? null,
                'startingBid' => isset($row['starting_bid']) ? (float) $row['starting_bid'] : 0.0,
            ];
        }, $rows);
    }

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
