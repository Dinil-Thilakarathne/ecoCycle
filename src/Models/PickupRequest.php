<?php

namespace Models;

class PickupRequest extends BaseModel
{
    protected string $table = 'pickup_requests';

    public function listAll(?string $timeSlot = null): array
    {
        $sql = "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, col.name AS collector_name
                FROM {$this->table} pr
                LEFT JOIN users c ON c.id = pr.customer_id
                LEFT JOIN users col ON col.id = pr.collector_id";
        $params = [];
        if ($timeSlot !== null && $timeSlot !== '') {
            $sql .= " WHERE pr.time_slot = ?";
            $params[] = $timeSlot;
        }
        $sql .= " ORDER BY pr.created_at DESC";
        $rows = $this->db->fetchAll($sql, $params);
        if (!$rows) {
            return [];
        }

        $ids = array_column($rows, 'id');
        $wasteMap = $this->wasteCategoriesForPickups($ids);

        return array_map(fn(array $row) => $this->formatRow($row, $wasteMap), $rows);
    }

    public function find(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        $row = $this->db->fetch(
            "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, col.name AS collector_name
             FROM {$this->table} pr
             LEFT JOIN users c ON c.id = pr.customer_id
             LEFT JOIN users col ON col.id = pr.collector_id
             WHERE pr.id = ?
             LIMIT 1",
            [$id]
        );

        if (!$row) {
            return null;
        }

        $wasteMap = $this->wasteCategoriesForPickups([$row['id']]);

        return $this->formatRow($row, $wasteMap);
    }

    public function exists(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        $row = $this->db->fetch("SELECT id FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return (bool) $row;
    }

    public function update(string $id, array $data): bool
    {
        $allowed = ['collector_id', 'collector_name', 'status', 'time_slot', 'scheduled_at', 'address'];
        $filtered = [];
        foreach ($data as $column => $value) {
            if (in_array($column, $allowed, true)) {
                $filtered[$column] = $value;
            }
        }

        if (empty($filtered)) {
            return true;
        }

        $setParts = [];
        $params = [];

        foreach ($filtered as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $params[] = $value;
        }

        if (!array_key_exists('updated_at', $filtered)) {
            $setParts[] = "`updated_at` = NOW()";
        }

        $params[] = $id;

        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $setParts) . ' WHERE id = ? LIMIT 1';

        return $this->db->query($sql, $params);
    }

    public function countByStatuses(array $statuses): int
    {
        if (empty($statuses)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $row = $this->db->fetch("SELECT COUNT(*) AS total FROM {$this->table} WHERE status IN ({$placeholders})", $statuses);
        return (int) ($row['total'] ?? 0);
    }

    public function recent(int $limit = 5): array
    {
        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll(
            "SELECT pr.id, pr.status, pr.created_at, pr.time_slot, c.name AS customer_name
             FROM {$this->table} pr
             LEFT JOIN users c ON c.id = pr.customer_id
             ORDER BY pr.created_at DESC
             LIMIT {$limit}"
        );
        return $rows ?: [];
    }

    public function listTimeSlots(): array
    {
        $slots = $this->db->fetchAll("SELECT DISTINCT time_slot FROM {$this->table} WHERE time_slot IS NOT NULL AND time_slot != '' ORDER BY time_slot ASC");
        $values = array_values(array_filter(array_map(fn($row) => $row['time_slot'] ?? null, $slots)));
        if (!empty($values)) {
            return $values;
        }

        $aggregate = $this->db->fetch("SELECT value FROM analytics_aggregates WHERE `key` = 'time_slots' LIMIT 1");
        if ($aggregate && !empty($aggregate['value'])) {
            $decoded = json_decode($aggregate['value'], true);
            if (is_array($decoded) && !empty($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00'];
    }

    private function wasteCategoriesForPickups(array $pickupIds): array
    {
        $pickupIds = array_values(array_filter($pickupIds, fn($id) => $id !== null));
        if (empty($pickupIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($pickupIds), '?'));
        $sql = "SELECT prw.pickup_id, wc.name
                FROM pickup_request_wastes prw
                INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
                WHERE prw.pickup_id IN ({$placeholders})
                ORDER BY wc.name";
        $rows = $this->db->fetchAll($sql, $pickupIds);
        if (!$rows) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $pid = $row['pickup_id'];
            if (!isset($map[$pid])) {
                $map[$pid] = [];
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '' && !in_array($name, $map[$pid], true)) {
                $map[$pid][] = $name;
            }
        }
        return $map;
    }

    private function formatRow(array $row, array $wasteMap): array
    {
        $pickupId = $row['id'];
        $waste = $wasteMap[$pickupId] ?? [];

        return [
            'id' => $pickupId,
            'customerId' => $row['customer_id'],
            'customerName' => $row['customer_name'] ?? '',
            'address' => $row['address'] ?? ($row['customer_address'] ?? ''),
            'timeSlot' => $row['time_slot'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'collectorId' => $row['collector_id'],
            'collectorName' => $row['collector_name'] ?? '',
            'wasteCategories' => $waste,
            'createdAt' => $row['created_at'] ?? null,
        ];
    }
}
