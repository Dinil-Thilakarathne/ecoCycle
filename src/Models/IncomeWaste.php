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
     * Save measured weight for a pickup, update each waste amount individually,
     * and return breakdown for UI display.
     *
     * @param string $pickupId
     * @param float $enteredWeight
     * @return array ['breakdown' => [...]]
     * @throws \Exception
     */
    public function saveWeightAndCalculate(string $pickupId, float $enteredWeight): array
    {
        if ($pickupId === '' || $enteredWeight <= 0) {
            throw new \Exception('Invalid pickup ID or weight');
        }

        // 1️⃣ Get all wastes for this pickup
        $sql = "
            SELECT prw.id AS pickup_waste_id,
                   prw.pickup_id,
                   prw.waste_category_id,
                   wc.name AS category_name,
                   wc.unit,
                   wc.price_per_unit
            FROM pickup_request_wastes prw
            JOIN waste_categories wc ON wc.id = prw.waste_category_id
            WHERE prw.pickup_id = ?
        ";
        $wastes = $this->db->fetchAll($sql, [$pickupId]);
        if (!$wastes) return ['breakdown' => []];

        $breakdown = [];

        // 2️⃣ Update each waste's weight and calculate individual amount
        foreach ($wastes as $w) {
            $amount = $enteredWeight * $w['price_per_unit'];

            $this->db->query(
                "UPDATE pickup_request_wastes
                 SET weight = ?, amount = ?
                 WHERE id = ?",
                [$enteredWeight, $amount, $w['pickup_waste_id']]
            );

            $breakdown[] = [
                'category_name' => $w['category_name'],
                'weight' => (float) $enteredWeight,
                'unit' => $w['unit'],
                'price_per_unit' => (float) $w['price_per_unit'],
                'amount' => (float) $amount
            ];
        }

        // 3️⃣ Update pickup_requests table weight
        $this->db->query(
            "UPDATE pickup_requests SET weight = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$enteredWeight, $pickupId]
        );

        // 4️⃣ Return breakdown for collector UI
        return ['breakdown' => $breakdown];
    }
}
