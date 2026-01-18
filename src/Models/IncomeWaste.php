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
     * ONE weight → ONE amount
     */
    public function saveWeightAndCalculateSingle(string $pickupId, float $weight): float
    {
        $row = $this->db->fetch(
            "SELECT wc.price_per_unit
             FROM pickup_request_wastes prw
             JOIN waste_categories wc ON wc.id = prw.waste_category_id
             WHERE prw.pickup_id = ?
             LIMIT 1",
            [$pickupId]
        );

        if (!$row) {
            throw new \Exception('No waste found');
        }

        $pricePerUnit = (float)$row['price_per_unit'];
        $amount       = $weight * $pricePerUnit;

        // update ALL wastes with same weight & amount
        $this->db->query(
            "UPDATE pickup_request_wastes
             SET weight = ?, amount = ?
             WHERE pickup_id = ?",
            [$weight, $amount, $pickupId]
        );

        // update main pickup
        $this->db->query(
            "UPDATE pickup_requests
             SET weight = ?, price = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$weight, $amount, $pickupId]
        );

        return $amount;
    }
}
