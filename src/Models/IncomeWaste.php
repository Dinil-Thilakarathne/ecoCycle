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

   
public function saveWeightAndCalculateSingle(string $pickupId, float $weight): float
{
    // 1️⃣ Get ONE predefined price_per_unit
    $row = $this->db->fetch(
        "SELECT wc.price_per_unit
         FROM pickup_request_wastes prw
         JOIN waste_categories wc ON wc.id = prw.waste_category_id
         WHERE prw.pickup_id = ?
         ORDER BY prw.id ASC
         LIMIT 1",
        [$pickupId]
    );

    if (!$row) {
        throw new \Exception('No waste category found');
    }

    $pricePerUnit = (float)$row['price_per_unit'];
    $amount = $weight * $pricePerUnit;

    // 2️⃣ Update all wastes with same weight & amount
    $this->db->query(
        "UPDATE pickup_request_wastes
         SET weight = ?, amount = ?
         WHERE pickup_id = ?",
        [$weight, $amount, $pickupId]
    );

    // 3️⃣ Update pickup summary (NO total logic)
    $this->db->query(
        "UPDATE pickup_requests
         SET weight = ?, price = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ?",
        [$weight, $amount, $pickupId]
    );

    // ✅ RETURN ONLY THE AMOUNT
    return $amount;
}


}
