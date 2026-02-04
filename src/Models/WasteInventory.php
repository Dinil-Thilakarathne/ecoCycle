<?php

namespace Models;

/**
 * WasteInventory Model
 * Manages waste inventory tracking and allocation
 */
class WasteInventory extends BaseModel
{
    protected string $table = 'waste_inventory'; // This is a view

    /**
     * Get current inventory status for all waste categories
     * Returns collected, committed, and available quantities
     * 
     * @return array Array of inventory records by category
     */
    public function getInventoryStatus(): array
    {
        $sql = "SELECT 
                    category_id,
                    category_name,
                    unit,
                    price_per_unit,
                    total_collected,
                    total_committed,
                    available_quantity,
                    pickup_count,
                    total_value
                FROM waste_inventory
                WHERE total_collected > 0
                ORDER BY category_name ASC";

        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'categoryId' => (int) $row['category_id'],
                'categoryName' => $row['category_name'] ?? 'Unknown',
                'unit' => $row['unit'] ?? 'kg',
                'pricePerUnit' => isset($row['price_per_unit']) ? (float) $row['price_per_unit'] : 0.0,
                'totalCollected' => isset($row['total_collected']) ? (float) $row['total_collected'] : 0.0,
                'totalCommitted' => isset($row['total_committed']) ? (float) $row['total_committed'] : 0.0,
                'availableQuantity' => isset($row['available_quantity']) ? (float) $row['available_quantity'] : 0.0,
                'pickupCount' => isset($row['pickup_count']) ? (int) $row['pickup_count'] : 0,
                'totalValue' => isset($row['total_value']) ? (float) $row['total_value'] : 0.0,
            ];
        }, $rows);
    }

    /**
     * Get available waste for a specific category
     * 
     * @param int $categoryId The waste category ID
     * @return array|null Inventory details or null if not found
     */
    public function getAvailableByCategory(int $categoryId): ?array
    {
        if ($categoryId <= 0) {
            return null;
        }

        $row = $this->db->fetch(
            "SELECT 
                category_id,
                category_name,
                unit,
                price_per_unit,
                total_collected,
                total_committed,
                available_quantity,
                pickup_count,
                total_value
            FROM waste_inventory
            WHERE category_id = ?
            LIMIT 1",
            [$categoryId]
        );

        if (!$row) {
            return null;
        }

        return [
            'categoryId' => (int) $row['category_id'],
            'categoryName' => $row['category_name'] ?? 'Unknown',
            'unit' => $row['unit'] ?? 'kg',
            'pricePerUnit' => isset($row['price_per_unit']) ? (float) $row['price_per_unit'] : 0.0,
            'totalCollected' => isset($row['total_collected']) ? (float) $row['total_collected'] : 0.0,
            'totalCommitted' => isset($row['total_committed']) ? (float) $row['total_committed'] : 0.0,
            'availableQuantity' => isset($row['available_quantity']) ? (float) $row['available_quantity'] : 0.0,
            'pickupCount' => isset($row['pickup_count']) ? (int) $row['pickup_count'] : 0,
            'totalValue' => isset($row['total_value']) ? (float) $row['total_value'] : 0.0,
        ];
    }

    /**
     * Check if sufficient waste is available for a bidding round
     * 
     * @param int $categoryId The waste category ID
     * @param float $quantity The quantity needed
     * @return bool True if sufficient waste is available
     */
    public function canAllocate(int $categoryId, float $quantity): bool
    {
        if ($categoryId <= 0 || $quantity <= 0) {
            return false;
        }

        $inventory = $this->getAvailableByCategory($categoryId);
        if (!$inventory) {
            return false;
        }

        return $inventory['availableQuantity'] >= $quantity;
    }

    /**
     * Get list of pickup requests that contributed to collected waste for a category
     * Returns unallocated pickups (not yet linked to bidding rounds)
     * 
     * @param int $categoryId The waste category ID
     * @param int|null $limit Maximum number of pickups to return
     * @return array Array of pickup details
     */
    public function getSourcePickups(int $categoryId, ?int $limit = null): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $sql = "SELECT 
                    pr.id,
                    pr.customer_id,
                    pr.created_at,
                    pr.scheduled_at,
                    u.name AS customer_name,
                    prw.weight,
                    prw.amount
                FROM pickup_requests pr
                INNER JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
                LEFT JOIN users u ON u.id = pr.customer_id
                WHERE pr.status = 'completed'
                AND prw.waste_category_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM bidding_round_sources brs 
                    WHERE brs.pickup_id = pr.id
                )
                ORDER BY pr.created_at DESC";

        $params = [$categoryId];

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = (int) $limit;
        }

        $rows = $this->db->fetchAll($sql, $params);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'pickupId' => $row['id'],
                'customerId' => (int) $row['customer_id'],
                'customerName' => $row['customer_name'] ?? 'Unknown',
                'weight' => isset($row['weight']) ? (float) $row['weight'] : 0.0,
                'amount' => isset($row['amount']) ? (float) $row['amount'] : 0.0,
                'createdAt' => $row['created_at'] ?? null,
                'scheduledAt' => $row['scheduled_at'] ?? null,
            ];
        }, $rows);
    }

    /**
     * Get waste collection statistics for a date range
     * 
     * @param string|null $startDate Start date (Y-m-d format)
     * @param string|null $endDate End date (Y-m-d format)
     * @return array Statistics summary
     */
    public function getCollectionStats(?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT pr.id) AS total_pickups,
                    COUNT(DISTINCT pr.customer_id) AS unique_customers,
                    COALESCE(SUM(prw.weight), 0) AS total_weight,
                    COALESCE(SUM(prw.amount), 0) AS total_value
                FROM pickup_requests pr
                LEFT JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
                WHERE pr.status = 'completed'";

        $params = [];

        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND DATE(pr.created_at) >= ?";
            $params[] = $startDate;
        }

        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND DATE(pr.created_at) <= ?";
            $params[] = $endDate;
        }

        $row = $this->db->fetch($sql, $params);

        return [
            'totalPickups' => isset($row['total_pickups']) ? (int) $row['total_pickups'] : 0,
            'uniqueCustomers' => isset($row['unique_customers']) ? (int) $row['unique_customers'] : 0,
            'totalWeight' => isset($row['total_weight']) ? (float) $row['total_weight'] : 0.0,
            'totalValue' => isset($row['total_value']) ? (float) $row['total_value'] : 0.0,
        ];
    }
}
