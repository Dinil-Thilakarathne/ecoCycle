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

    // ----------------------------------
    // Fetch all wastes for a pickup
    // ----------------------------------
    public function getWastesByPickup(int $pickupId): array
    {
        $sql = "
            SELECT 
                prw.id AS pickup_waste_id,
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

    // ----------------------------------
    // 1️⃣ Preview amounts WITHOUT saving
    // ----------------------------------
    public function calculateAmountsForDisplay(int $pickupId, float $totalWeight): array
    {
        $wastes = $this->getWastesByPickup($pickupId);
        $sumQty = max(array_sum(array_map(fn($w) => (float)$w['quantity'], $wastes)), 1);

        $totalPrice = 0;
        $preview = [];

        foreach ($wastes as $waste) {
            $scaledQty = ($waste['quantity'] * $totalWeight) / $sumQty;
            $amount = round($scaledQty * $waste['price_per_unit'], 2);

            $preview[] = [
                'pickup_waste_id' => $waste['pickup_waste_id'],
                'category_name'   => $waste['category_name'],
                'quantity'        => round($scaledQty, 2),
                'unit'            => $waste['unit'],
                'amount'          => $amount
            ];

            $totalPrice += $amount;
        }

        return [
            'totalWeight' => round($totalWeight, 2),
            'totalPrice'  => round($totalPrice, 2),
            'wastes'      => $preview
        ];
    }

    // ----------------------------------
    // 2️⃣ Save weight & calculate amounts (DB update)
    // Status stays 'in_progress'
    // ----------------------------------
    public function saveWeightAndPrice(int $pickupId, float $totalWeight): array
    {
        $wastes = $this->getWastesByPickup($pickupId);
        $sumQty = max(array_sum(array_map(fn($w) => (float)$w['quantity'], $wastes)), 1);

        $totalPrice = 0;
        $breakdown = [];

        foreach ($wastes as $waste) {
            $scaledQty = ($waste['quantity'] * $totalWeight) / $sumQty;
            $amount = round($scaledQty * $waste['price_per_unit'], 2);

            // Update each waste row with scaled quantity & calculated amount
            $this->db->execute(
                "UPDATE pickup_request_wastes
                 SET quantity = ?, amount = ?
                 WHERE id = ?",
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

        // Update pickup request with total weight and price
        $this->db->execute(
            "UPDATE pickup_requests
             SET weight = ?, price = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [round($totalWeight, 2), round($totalPrice, 2), $pickupId]
        );

        return [
            'totalWeight' => round($totalWeight, 2),
            'totalPrice'  => round($totalPrice, 2),
            'breakdown'   => $breakdown
        ];
    }

    // ----------------------------------
    // 3️⃣ Complete pickup (status change)
    // No recalculation here
    // ----------------------------------
    public function completePickup(int $pickupId): void
    {
        $this->db->execute(
            "UPDATE pickup_requests
             SET status = 'completed',
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$pickupId]
        );
    }

    // ----------------------------------
    // Optional: Calculate total income for a collector
    // ----------------------------------
    public function getCollectorTotalIncome(
        int $collectorId,
        string $startDate = '',
        string $endDate = ''
    ): float {
        $startDate = $startDate ?: date('Y-m-01');
        $endDate   = $endDate ?: date('Y-m-d');

        $sql = "
            SELECT COALESCE(SUM(price),0) AS total_income
            FROM pickup_requests
            WHERE collector_id = ?
              AND status = 'completed'
              AND updated_at BETWEEN ? AND ?
        ";

        $row = $this->db->fetch($sql, [$collectorId, $startDate, $endDate]);
        return round((float)($row['total_income'] ?? 0), 2);
    }

    // ----------------------------------
    // 4️⃣ Optional: Preview + save weight + complete pickup in one go
    // ----------------------------------
    public function saveWeightAndComplete(int $pickupId, float $totalWeight): array
    {
        // 1️⃣ Save weight & calculate amounts
        $result = $this->saveWeightAndPrice($pickupId, $totalWeight);

        // 2️⃣ Complete pickup
        $this->completePickup($pickupId);

        return $result;
    }
}
