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
     * Save measured weight for a pickup and return single calculated amount
     *
     * @param string $pickupId
     * @param float $enteredWeight
     * @return float amount
     * @throws \Exception
     */
    public function saveWeightAndCalculateSingle(string $pickupId, float $enteredWeight): float
    {
        if ($pickupId === '' || $enteredWeight <= 0) {
            throw new \Exception('Invalid pickup ID or weight');
        }

        // Get first waste's price_per_unit
        $sql = "
            SELECT wc.price_per_unit
            FROM pickup_request_wastes prw
            JOIN waste_categories wc ON wc.id = prw.waste_category_id
            WHERE prw.pickup_id = ?
            LIMIT 1
        ";
        $row = $this->db->fetch($sql, [$pickupId]);
        if (!$row) {
            throw new \Exception('No waste found for this pickup');
        }

        $pricePerUnit = (float)$row['price_per_unit'];
        $amount = $enteredWeight * $pricePerUnit;

        // Update all wastes with same weight and amount
        $this->db->query(
            "UPDATE pickup_request_wastes SET weight = ?, amount = ? WHERE pickup_id = ?",
            [$enteredWeight, $amount, $pickupId]
        );

        // Update pickup_requests table weight and price
        $this->db->query(
            "UPDATE pickup_requests SET weight = ?, price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$enteredWeight, $amount, $pickupId]
        );

        return $amount;
    }

    /**
     * Get pickup details including waste category details
     */
    public function getPickupDetails(string $pickupId): ?array
    {
        $pickup = $this->db->fetch(
            "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address
             FROM pickup_requests pr
             LEFT JOIN users c ON c.id = pr.customer_id
             WHERE pr.id = ?
             LIMIT 1",
            [$pickupId]
        );

        if (!$pickup) return null;

        $wastes = $this->db->fetchAll(
            "SELECT prw.id AS waste_id, wc.name AS category_name, prw.weight, wc.unit, wc.price_per_unit, prw.amount
             FROM pickup_request_wastes prw
             JOIN waste_categories wc ON wc.id = prw.waste_category_id
             WHERE prw.pickup_id = ?",
            [$pickupId]
        );

        return [
            'pickup' => $pickup,
            'wastes' => $wastes
        ];
    }
}
