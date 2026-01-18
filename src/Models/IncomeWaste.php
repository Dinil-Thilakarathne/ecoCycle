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

    // Fetch wastes for a pickup
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

    // Calculate total price based on entered weight
    public function calculateTotalPrice(int $pickupId, float $weight): array
    {
        $wastes = $this->getWastesByPickup($pickupId);
        $sumOriginalQty = array_sum(array_column($wastes, 'quantity')) ?: 1;

        $totalPrice = 0;
        $breakdown = [];
        foreach ($wastes as $w) {
            $scaledQty = $w['quantity'] * $weight / $sumOriginalQty;
            $amount = $scaledQty * $w['price_per_unit'];
            $totalPrice += $amount;
            $breakdown[] = [
                'category_name' => $w['category_name'],
                'unit' => $w['unit'],
                'scaled_qty' => round($scaledQty, 2),
                'amount' => round($amount, 2),
            ];
        }

        return [
            'total_price' => round($totalPrice, 2),
            'breakdown' => $breakdown,
        ];
    }

    // Save weight and calculated price to pickup request
    public function saveWeightAndPrice(int $pickupId, float $weight, float $price): bool
    {
        $sql = "UPDATE pickup_requests SET weight = ?, price = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$weight, $price, $pickupId]);
    }

    // Update pickup status
    public function updateStatus(int $pickupId, string $status): bool
    {
        $sql = "UPDATE pickup_requests SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$status, $pickupId]);
    }
}
