<?php

namespace Models;

class Vehicle extends BaseModel
{
    protected string $table = 'vehicles';

    public function find(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        if (!$row) {
            return null;
        }

        return $this->normalizeRow($row);
    }

    public function exists(int $id): bool
    {
        $row = $this->db->fetch("SELECT id FROM {$this->table} WHERE id = ?", [$id]);
        return (bool) $row;
    }

    public function listAll(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY id DESC");
        if (!$rows) {
            return [];
        }

        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function create(array $data): array
    {
        $sql = "INSERT INTO {$this->table} (plate_number, type, capacity, status, last_maintenance, next_maintenance, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $params = [
            $data['plate_number'] ?? null,
            $data['type'] ?? null,
            $data['capacity'] ?? null,
            $data['status'] ?? 'available',
            $data['last_maintenance'] ?? null,
            $data['next_maintenance'] ?? null,
            $data['notes'] ?? null,
        ];

        $this->db->query($sql, $params);
        $id = (int) $this->db->lastInsertId();

        return $this->find($id) ?? [];
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET plate_number = ?,
                    type = ?,
                    capacity = ?,
                    status = ?,
                    last_maintenance = ?,
                    next_maintenance = ?,
                    updated_at = NOW()
                WHERE id = ?";

        $params = [
            $data['plate_number'] ?? null,
            $data['type'] ?? null,
            $data['capacity'] ?? null,
            $data['status'] ?? null,
            $data['last_maintenance'] ?? null,
            $data['next_maintenance'] ?? null,
            $id,
        ];

        return $this->db->query($sql, $params);
    }

    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }

    private function normalizeRow(array $row): array
    {
        return [
            'id' => $row['id'],
            'plateNumber' => $row['plate_number'] ?? '',
            'type' => $row['type'] ?? '',
            'capacity' => isset($row['capacity']) ? (int) $row['capacity'] : 0,
            'status' => $row['status'] ?? 'available',
            'lastMaintenance' => $row['last_maintenance'] ?? null,
            'nextMaintenance' => $row['next_maintenance'] ?? null,
        ];
    }
}
