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

public function getWasteCollectionForCollector(int $collectorId, int $limit = 50): array
    {
        $sql = "
            SELECT 
                pr.id AS pickup_id,
                pr.customer_id,
                u.name AS customer_name,
                wc.name AS category,
                prw.weight,
                prw.amount
            FROM pickup_requests pr
            LEFT JOIN users u ON u.id = pr.customer_id
            LEFT JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
            LEFT JOIN waste_categories wc ON wc.id = prw.waste_category_id
            WHERE pr.collector_id = ?
            ORDER BY pr.id DESC
            LIMIT ?
        ";

        return $this->db->fetchAll($sql, [$collectorId, $limit]);
    }
}
