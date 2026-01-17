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

    // --- Fetch wastes for a pickup
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
        return $this->db->fetchAll($sql, [$pickupId]) ?: [];
    }

    // --- 1️⃣ Preview amounts for display (DB not updated)
    public function calculateAmountsForDisplay(int $pickupId, float $totalWeight = 0): array
    {
        $wastes = $this->getWastesByPickup($pickupId);

        $sumQty = array_sum(array_map(fn($w) => (float)$w['quantity'], $wastes));
        if ($sumQty <= 0) $sumQty = 1;

        $result = [];
        $totalPrice = 0;

        foreach ($wastes as $waste) {
            $scaledQty = $totalWeight > 0 ? ((float)$waste['quantity'] * $totalWeight / $sumQty) : (float)$waste['quantity'];
            $amount = round($scaledQty * $waste['price_per_unit'], 2);

            $result[] = [
                'pickup_waste_id'   => $waste['pickup_waste_id'],
                'waste_category_id' => $waste['waste_category_id'],
                'category_name'     => $waste['category_name'],
                'unit'              => $waste['unit'],
                'quantity'          => round($scaledQty, 2),
                'amount'            => $amount
            ];

            $totalPrice += $amount;
        }

        return [
            'totalPrice' => round($totalPrice, 2),
            'wastes'     => $result
        ];
    }

    // --- 2️⃣ Save weight & calculated price (real-time, before completion)
    public function saveWeightAndPrice(int $pickupId, float $totalWeight): array
    {
        $wastes = $this->getWastesByPickup($pickupId);

        $sumQty = array_sum(array_map(fn($w) => (float)$w['quantity'], $wastes));
        if ($sumQty <= 0) $sumQty = 1;

        $totalPrice = 0;
        $breakdown = [];

        foreach ($wastes as $waste) {
            $scaledQty = (float)$waste['quantity'] * $totalWeight / $sumQty;
            $amount = round($scaledQty * $waste['price_per_unit'], 2);

            // Update scaled quantity & amount in DB
            $this->db->execute(
                "UPDATE pickup_request_wastes SET quantity = ?, amount = ? WHERE id = ?",
                [round($scaledQty, 2), $amount, $waste['pickup_waste_id']]
            );

            $totalPrice += $amount;
            $breakdown[] = [
                'category_name' => $waste['category_name'],
                'quantity'      => round($scaledQty, 2),
                'unit'          => $waste['unit'],
                'amount'        => $amount
            ];
        }

        // Update total weight and price in pickup_requests (status not changed)
        $this->db->execute(
            "UPDATE pickup_requests SET weight = ?, price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [round($totalWeight, 2), round($totalPrice, 2), $pickupId]
        );

        return [
            'totalWeight' => round($totalWeight, 2),
            'totalPrice'  => round($totalPrice, 2),
            'breakdown'   => $breakdown
        ];
    }

    // --- 3️⃣ Complete pickup
    public function completePickup(int $pickupId, float $totalWeight): array
    {
        $result = $this->saveWeightAndPrice($pickupId, $totalWeight);

        // Set status to completed
        $this->db->execute(
            "UPDATE pickup_requests SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$pickupId]
        );

        return $result;
    }

    // --- Optional: collector income
    public function getCollectorTotalIncome(int $collectorId, string $startDate = '', string $endDate = ''): float
    {
        $startDate = $startDate ?: date('Y-m-01');
        $endDate   = $endDate ?: date('Y-m-d');

        $sql = "
            SELECT COALESCE(SUM(price),0) AS total_income
            FROM pickup_requests
            WHERE collector_id = ?
              AND status = 'completed'
              AND updated_at >= ?
              AND updated_at <= ?
        ";

        $row = $this->db->fetch($sql, [$collectorId, $startDate, $endDate]);
        return round((float)($row['total_income'] ?? 0), 2);
    }
}
