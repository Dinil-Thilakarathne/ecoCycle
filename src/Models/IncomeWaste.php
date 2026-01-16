<?php

namespace Models;

use Core\Database;

class IncomeWaste
{
    protected Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get all waste items for a specific pickup
     *
     * @param int $pickupId
     * @return array
     */
    public function getWastesByPickup(int $pickupId): array
    {
        $sql = "
            SELECT prw.id AS pickup_waste_id,
                   prw.pickup_id,
                   prw.waste_category_id,
                   prw.quantity,
                   wc.name AS category_name,
                   wc.unit,
                   wc.price_per_unit
            FROM pickup_request_wastes prw
            JOIN waste_categories wc ON wc.id = prw.waste_category_id
            WHERE prw.pickup_id = ?
        ";

        $rows = $this->db->fetchAll($sql, [$pickupId]);
        return $rows ?? [];
    }

    /**
     * Calculate total price for a pickup based on current quantities
     * Also updates pickup_request_wastes.amount automatically
     *
     * @param int $pickupId
     * @return float
     */
    public function calculateAndUpdateAmounts(int $pickupId): float
    {
        $wastes = $this->getWastesByPickup($pickupId);
        $totalPrice = 0;

        foreach ($wastes as $waste) {
            $ppu = (float) ($waste['price_per_unit'] ?? 0);
            $quantity = (float) ($waste['quantity'] ?? 0);

            $amount = round($quantity * $ppu, 2);

            // Update amount in pickup_request_wastes
            $sql = "UPDATE pickup_request_wastes SET amount = ? WHERE id = ?";
            $this->db->execute($sql, [$amount, $waste['pickup_waste_id']]);

            $totalPrice += $amount;
        }

        // Update total price in pickup_requests
        $sqlPickup = "UPDATE pickup_requests SET price = ? WHERE id = ?";
        $this->db->execute($sqlPickup, [$totalPrice, $pickupId]);

        return round($totalPrice, 2);
    }

    /**
     * Update the weight of a pickup
     */
    public function updatePickupWeight(int $pickupId, float $weight): void
    {
        $sql = "UPDATE pickup_requests SET weight = ? WHERE id = ?";
        $this->db->execute($sql, [round($weight, 2), $pickupId]);
    }

    /**
     * Get total income for a collector (sum of pickup prices)
     */
    public function getCollectorTotalIncome(int $collectorId, string $startDate = '', string $endDate = ''): float
    {
        $startDate = $startDate ?: date('Y-m-01');
        $endDate   = $endDate ?: date('Y-m-d');

        $sql = "
            SELECT COALESCE(SUM(price), 0) AS total_income
            FROM pickup_requests
            WHERE collector_id = ?
              AND status = 'completed'
              AND updated_at >= ?
              AND updated_at <= ?
        ";

        $row = $this->db->fetch($sql, [$collectorId, $startDate, $endDate]);
        return round((float) ($row['total_income'] ?? 0), 2);
    }

    /**
     * Get collector's waste collection summary
     */
    public function getCollectorWasteSummary(int $collectorId, string $startDate = '', string $endDate = ''): array
    {
        $startDate = $startDate ?: date('Y-m-01');
        $endDate   = $endDate ?: date('Y-m-d');

        $sql = "
            SELECT wc.id AS category_id,
                   wc.name,
                   wc.unit,
                   COALESCE(SUM(prw.quantity), 0) AS total_quantity,
                   COALESCE(SUM(prw.amount), 0) AS total_amount
            FROM pickup_requests pr
            JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
            JOIN waste_categories wc ON wc.id = prw.waste_category_id
            WHERE pr.collector_id = ?
              AND pr.status = 'completed'
              AND pr.updated_at >= ?
              AND pr.updated_at <= ?
            GROUP BY wc.id, wc.name, wc.unit
            ORDER BY wc.name ASC
        ";

        $rows = $this->db->fetchAll($sql, [$collectorId, $startDate, $endDate]);
        return $rows ?? [];
    }
}
