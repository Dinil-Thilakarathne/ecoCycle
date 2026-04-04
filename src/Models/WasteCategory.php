<?php

namespace Models;

class WasteCategory extends BaseModel
{
    protected string $table = 'waste_categories';

    /**
     * List all waste categories with price per unit
     * Used for dashboard amount per unit card
     */
    public function listAll(): array
    {
        $rows = $this->db->fetchAll("SELECT id, name, color, unit, price_per_unit, markup_percentage FROM {$this->table} ORDER BY name ASC");
        if (!$rows) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                'name' => (string) ($row['name'] ?? ''),
                'color' => $row['color'] ?? null,
                'unit' => $row['unit'] ?? 'kg',
                'pricePerUnit' => isset($row['price_per_unit']) ? (float) $row['price_per_unit'] : 0.0,
                'markupPercentage' => isset($row['markup_percentage']) ? (float) $row['markup_percentage'] : 0.0,
            ];
        }, $rows);
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->fetch(
            "SELECT id, name, color, unit, price_per_unit, markup_percentage FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id]
        );

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) ($row['name'] ?? ''),
            'color' => $row['color'] ?? null,
            'unit' => $row['unit'] ?? 'kg',
            'pricePerUnit' => isset($row['price_per_unit']) ? (float) $row['price_per_unit'] : 0.0,
            'markupPercentage' => isset($row['markup_percentage']) ? (float) $row['markup_percentage'] : 0.0,
        ];
    }

    public function findByName(string $name): ?array
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return null;
        }

        $row = $this->db->fetch(
            "SELECT id, name, color, unit, price_per_unit, markup_percentage FROM {$this->table} WHERE LOWER(name) = LOWER(?) LIMIT 1",
            [$trimmed]
        );

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) ($row['name'] ?? ''),
            'color' => $row['color'] ?? null,
            'unit' => $row['unit'] ?? 'kg',
            'pricePerUnit' => isset($row['price_per_unit']) ? (float) $row['price_per_unit'] : 0.0,
            'markupPercentage' => isset($row['markup_percentage']) ? (float) $row['markup_percentage'] : 0.0,
        ];
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (isset($data['unit'])) {
            $fields[] = 'unit = ?';
            $params[] = $data['unit'];
        }
        if (isset($data['color'])) {
            $fields[] = 'color = ?';
            $params[] = $data['color'];
        }
        if (isset($data['price_per_unit'])) {
            $fields[] = 'price_per_unit = ?';
            $params[] = (float) $data['price_per_unit'];
        }
        if (isset($data['markup_percentage'])) {
            $fields[] = 'markup_percentage = ?';
            $params[] = (float) $data['markup_percentage'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;

        return $this->db->query($sql, $params);
    }

    public function updatePrice(int $id, float $price): bool
    {
        return $this->update($id, ['price_per_unit' => $price]);
    }

    public function exists(int $id): bool
    {
        $row = $this->db->fetch("SELECT id FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return (bool) $row;
    }

    /**
     * Create a new waste category
     * Maps incoming payload keys to DB columns and returns the created record
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $sql = "INSERT INTO {$this->table} (name, color, default_minimum_bid, unit, price_per_unit, markup_percentage, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        // Map incoming keys: controller uses 'basePrice' so map it to default_minimum_bid
        $params = [
            $data['name'] ?? null,
            $data['color'] ?? null,
            isset($data['basePrice']) ? (float) $data['basePrice'] : ($data['defaultMinimumBid'] ?? null),
            $data['unit'] ?? 'kg',
            $data['price_per_unit'] ?? 0.0,
            $data['markup_percentage'] ?? 0.0,
        ];

        if ($this->db->isPgsql()) {
            $row = $this->db->fetch($sql . ' RETURNING id', $params);
            $id = $row && isset($row['id']) ? (int) $row['id'] : 0;
        } else {
            $this->db->query($sql, $params);
            $id = (int) $this->db->lastInsertId();
        }

        return $this->findById($id) ?? [];
    }

    /**
     * Get pricing tiers for all waste categories
     * Includes dynamic pricing brackets based on quantity thresholds
     * 
     * @return array Array of categories with pricing tier information
     */
    public function getPricingTiers(): array
    {
        $categories = $this->listAll();

        if (empty($categories)) {
            return [];
        }

        // Define pricing tier structure (quantity brackets and discounts)
        $pricingTiers = [
            ['min_kg' => 0, 'max_kg' => 100, 'discount_percent' => 0],
            ['min_kg' => 100, 'max_kg' => 500, 'discount_percent' => 5],
            ['min_kg' => 500, 'max_kg' => 1000, 'discount_percent' => 10],
            ['min_kg' => 1000, 'max_kg' => null, 'discount_percent' => 15],
        ];

        // Get base prices from database if available
        $basePricesRow = $this->db->fetchAll(
            "SELECT id, name, basePrice FROM waste_categories WHERE basePrice > 0 ORDER BY id ASC"
        );

        $basePrices = [];
        if ($basePricesRow) {
            foreach ($basePricesRow as $row) {
                $basePrices[$row['id']] = (float) ($row['basePrice'] ?? 50.00);
            }
        }

        // Build response with pricing tiers for each category
        return array_map(function (array $category) use ($pricingTiers, $basePrices): array {
            $categoryId = $category['id'];
            $basePrice = $basePrices[$categoryId] ?? 50.00;

            return [
                'id' => $categoryId,
                'name' => $category['name'],
                'basePrice' => $basePrice,
                'unit' => $category['unit'] ?? 'kg',
                'color' => $category['color'],
                'pricing_tiers' => array_map(function (array $tier) use ($basePrice): array {
                    $discountAmount = ($basePrice * $tier['discount_percent']) / 100;
                    $pricePerKg = $basePrice - $discountAmount;

                    return [
                        'min_kg' => $tier['min_kg'],
                        'max_kg' => $tier['max_kg'],
                        'price_per_kg' => round($pricePerKg, 2),
                        'discount_percent' => $tier['discount_percent'],
                        'total_for_min' => round($pricePerKg * ($tier['min_kg'] + 1), 2),
                    ];
                }, $pricingTiers),
            ];
        }, $categories);
    }

    /**
     * Get pricing tier for a specific category ID
     * 
     * @param int $categoryId The category ID
     * @return array|null Category with pricing tiers or null if not found
     */
    public function getPricingTierById(int $categoryId): ?array
    {
        $allTiers = $this->getPricingTiers();

        foreach ($allTiers as $tier) {
            if ($tier['id'] === $categoryId) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Calculate price for a specific quantity
     * Returns the appropriate price based on quantity bracket
     * 
     * @param int $categoryId The category ID
     * @param float $quantityKg The quantity in kilograms
     * @return array|null Price calculation details or null if category not found
     */
    public function calculatePrice(int $categoryId, float $quantityKg): ?array
    {
        $tier = $this->getPricingTierById($categoryId);

        if (!$tier) {
            return null;
        }

        // Find applicable pricing tier
        $applicable = null;
        foreach ($tier['pricing_tiers'] as $priceTier) {
            if ($quantityKg >= $priceTier['min_kg']) {
                if ($priceTier['max_kg'] === null || $quantityKg < $priceTier['max_kg']) {
                    $applicable = $priceTier;
                }
            }
        }

        if (!$applicable) {
            $applicable = $tier['pricing_tiers'][0]; // Default to first tier
        }

        return [
            'category_id' => $categoryId,
            'category_name' => $tier['name'],
            'quantity_kg' => $quantityKg,
            'unit_price' => $applicable['price_per_kg'],
            'discount_percent' => $applicable['discount_percent'],
            'total_price' => round($quantityKg * $applicable['price_per_kg'], 2),
            'base_price_without_discount' => round($quantityKg * $tier['basePrice'], 2),
            'total_discount' => round(($quantityKg * $tier['basePrice']) - ($quantityKg * $applicable['price_per_kg']), 2),
            'applicable_tier' => $applicable,
        ];
    }

    /**
     * Get statistics for a specific category
     * Includes collection data and pricing info
     * 
     * @param int $categoryId The category ID
     * @return array|null Category with statistics or null if not found
     */
    public function getCategoryStats(int $categoryId): ?array
    {
        $category = $this->findById($categoryId);

        if (!$category) {
            return null;
        }

        // Get collection statistics
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT prw.pickup_request_id) as total_collections,
                COALESCE(SUM(prw.quantity), 0) as total_collected_kg,
                COALESCE(AVG(prw.quantity), 0) as avg_per_collection
             FROM pickup_request_wastes prw
             WHERE prw.waste_category_id = ?",
            [$categoryId]
        );

        return [
            'category' => $category,
            'statistics' => [
                'total_collections' => (int) ($stats['total_collections'] ?? 0),
                'total_collected_kg' => (float) ($stats['total_collected_kg'] ?? 0),
                'avg_per_collection' => round((float) ($stats['avg_per_collection'] ?? 0), 2),
            ],
            'pricing_info' => $this->getPricingTierById($categoryId),
        ];
    }

    /**
     * Delete a waste category
     * 
     * @param int $id The category ID
     * @return bool True if deletion was successful, false otherwise
     */
    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
}
