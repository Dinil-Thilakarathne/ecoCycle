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

    
    $this->db->query(
        "UPDATE pickup_request_wastes
         SET weight = ?, amount = ?
         WHERE pickup_id = ?",
        [$weight, $amount, $pickupId]
    );

   
    $this->db->query(
        "UPDATE pickup_requests
         SET weight = ?, price = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ?",
        [$weight, $amount, $pickupId]
    );

   
    return $amount;
}

public function getWasteCollectionForCollector(int $collectorId, int $limit = 50): array
{
    $limit = max(1, (int)$limit);

    $sql = "
        SELECT 
            pr.id AS pickup_id,
            pr.customer_id,
            u.name AS customer_name,
            pr.address AS location,
            wc.name AS category,
            prw.weight,
            prw.amount
        FROM pickup_requests pr
        LEFT JOIN users u ON u.id = pr.customer_id
        LEFT JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
        LEFT JOIN waste_categories wc ON wc.id = prw.waste_category_id
                WHERE pr.collector_id = ?
                    AND pr.status = 'completed'
        ORDER BY pr.id DESC
        LIMIT $limit
    ";

    return $this->db->fetchAll($sql, [$collectorId]);
}
}
