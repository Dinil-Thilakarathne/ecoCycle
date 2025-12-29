<?php

namespace Models;

class WasteCategory extends BaseModel
{
    protected string $table = 'waste_categories';

    public function listAll(): array
    {
        $rows = $this->db->fetchAll("SELECT id, name, color, unit, price_per_unit FROM {$this->table} ORDER BY name ASC");
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
            ];
        }, $rows);
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->fetch(
            "SELECT id, name, color, unit, price_per_unit FROM {$this->table} WHERE id = ? LIMIT 1",
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
        ];
    }

    public function findByName(string $name): ?array
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return null;
        }

        $row = $this->db->fetch(
            "SELECT id, name, color, unit, price_per_unit FROM {$this->table} WHERE LOWER(name) = LOWER(?) LIMIT 1",
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
        ];
    }

    public function updatePrice(int $id, float $price): bool
    {
        return $this->db->query(
            "UPDATE {$this->table} SET price_per_unit = ?, updated_at = NOW() WHERE id = ?",
            [$price, $id]
        );
    }

    public function exists(int $id): bool
    {
        $row = $this->db->fetch("SELECT id FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return (bool) $row;
    }
}
