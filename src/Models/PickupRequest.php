<?php

namespace Models;

class PickupRequest extends BaseModel
{
    protected string $table = 'pickup_requests';

    public function listForCustomer(int $customerId, ?string $status = null): array
    {
        $sql = "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, col.name AS collector_name
                FROM {$this->table} pr
                LEFT JOIN users c ON c.id = pr.customer_id
                LEFT JOIN users col ON col.id = pr.collector_id
                WHERE pr.customer_id = ?";
        $params = [$customerId];

        if ($status !== null && $status !== '') {
            $sql .= " AND pr.status = ?";
            $params[] = $status;
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

    public function listForCollector(int $collectorId, ?string $status = null, ?string $timeSlot = null): array
    {
        $collectorId = (int) $collectorId;
        if ($collectorId <= 0) {
            return [];
        }

        $sql = "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, col.name AS collector_name
                FROM {$this->table} pr
                LEFT JOIN users c ON c.id = pr.customer_id
                LEFT JOIN users col ON col.id = pr.collector_id
                WHERE pr.collector_id = ?";
        $params = [$collectorId];

        if ($status !== null && $status !== '') {
            $sql .= " AND pr.status = ?";
            $params[] = $status;
        }

        if ($timeSlot !== null && $timeSlot !== '') {
            $sql .= " AND pr.time_slot = ?";
            $params[] = $timeSlot;
        }

        $sql .= " ORDER BY pr.scheduled_at IS NULL ASC, pr.scheduled_at ASC, pr.created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);
        if (!$rows) {
            return [];
        }

        $ids = array_column($rows, 'id');
        $wasteMap = $this->wasteCategoriesForPickups($ids);

        return array_map(fn(array $row) => $this->formatRow($row, $wasteMap), $rows);
    }

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

    private function wasteCategoriesForPickups(array $pickupIds): array
    {
        $pickupIds = array_values(array_filter($pickupIds, fn($id) => $id !== null));
        if (empty($pickupIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($pickupIds), '?'));
        $sql = "SELECT prw.pickup_id, prw.waste_category_id, prw.weight, prw.unit, wc.name
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
                $map[$pid] = [
                    'names' => [],
                    'details' => [],
                ];
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '' && !in_array($name, $map[$pid]['names'], true)) {
                $map[$pid]['names'][] = $name;
            }

            if (isset($row['waste_category_id'])) {
                $map[$pid]['details'][] = [
                    'id' => (int) $row['waste_category_id'],
                    'name' => $name,
                    'weight' => $row['weight'] !== null ? (float) $row['weight'] : null,
                    'unit' => $row['unit'] ?? null,
                ];
            }
        }
        return $map;
    }

    private function formatRow(array $row, array $wasteMap): array
    {
        $pickupId = $row['id'];
        $wasteEntry = $wasteMap[$pickupId] ?? ['names' => [], 'details' => []];
        $names = $wasteEntry['names'] ?? [];
        $details = $wasteEntry['details'] ?? [];

        $status = $this->normalizeStatusValue($row['status'] ?? 'pending');

        return [
            'id' => $pickupId,
            'customerId' => $row['customer_id'],
            'customerName' => $row['customer_name'] ?? '',
            'address' => $row['address'] ?? ($row['customer_address'] ?? ''),
            'timeSlot' => $row['time_slot'] ?? '',
            'status' => $status,
            'statusRaw' => $row['status'] ?? 'pending',
            'collectorId' => $row['collector_id'],
            'collectorName' => $row['collector_name'] ?? '',
            'wasteCategories' => $names,
            'wasteCategoryDetails' => $details,
            'weight' => isset($row['weight']) ? (float)$row['weight'] : null,   // pickup_requests weight
            'price' => isset($row['price']) ? (float)$row['price'] : null,      // pickup_requests price
            'createdAt' => $row['created_at'] ?? null,
            'scheduledAt' => $row['scheduled_at'] ?? null,
        ];
    }

    private function replaceWasteCategories(string $pickupId, array $categories, bool $clearExisting = true): void
    {
        if ($clearExisting) {
            $this->db->query('DELETE FROM pickup_request_wastes WHERE pickup_id = ?', [$pickupId]);
        }

        foreach ($categories as $category) {
            $categoryId = $category['id'] ?? null;
            if ($categoryId === null) continue;

            $weight = $category['weight'] ?? null;
            if ($weight !== null) $weight = (float) $weight;

            $unit = $category['unit'] ?? null;

            $this->db->query(
                'INSERT INTO pickup_request_wastes (pickup_id, waste_category_id, weight, unit) VALUES (?, ?, ?, ?)',
                [$pickupId, $categoryId, $weight, $unit]
            );
        }
    }

    public function updateStatus(int $pickupId, string $status): void
    {
        $this->db->query(
            "UPDATE {$this->table}
             SET status = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$status, $pickupId]
        );
    }

  private function generateId(): string
    {
        do {
            $id = 'PR' . strtoupper(bin2hex(random_bytes(5)));
        } while ($this->exists($id));

        return $id;
    }

    private function normalizeStatusValue(string $status): string
    {
        $normalized = strtolower(trim($status));

        switch ($normalized) {
            case 'in_progress':
            case 'in-progress':
                return 'in progress';
            case 'in progress':
            case 'pending':
            case 'assigned':
            case 'completed':
            case 'cancelled':
            case 'confirmed':
                return $normalized;
            default:
                return $normalized;
        }
    }

    private function isCustomerEditableStatus(string $status): bool
    {
        $status = strtolower($status);
        return in_array($status, ['pending'], true);
    }

    private function isCustomerCancellableStatus(string $status): bool
    {
        $status = strtolower($status);
        return in_array($status, ['pending', 'assigned', 'confirmed'], true);
    }

    /**
 * Get all pickups for a collector (for dashboard)
 * Includes waste categories, weights, and price per unit
 */
public function listForCollectorDashboard(int $collectorId, ?string $status = null): array
{
    $sql = "SELECT pr.id, pr.collector_id, pr.customer_id, pr.address, pr.time_slot, pr.status, pr.created_at, pr.scheduled_at,
                   c.name AS customer_name,
                   wc.id AS waste_category_id, wc.name AS waste_category_name, wc.unit, wc.price_per_unit,
                   prw.weight
            FROM {$this->table} pr
            LEFT JOIN users c ON c.id = pr.customer_id
            LEFT JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
            LEFT JOIN waste_categories wc ON prw.waste_category_id = wc.id
            WHERE pr.collector_id = ?";

    $params = [$collectorId];

    if ($status !== null && $status !== '') {
        $sql .= " AND pr.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY pr.scheduled_at IS NULL ASC, pr.scheduled_at ASC, pr.created_at DESC";

    $rows = $this->db->fetchAll($sql, $params);
    if (!$rows) {
        return [];
    }

    // Map pickups
    $map = [];
    foreach ($rows as $row) {
        $pid = $row['id'];
        if (!isset($map[$pid])) {
            $map[$pid] = [
                'id' => $pid,
                'customerId' => $row['customer_id'],
                'customerName' => $row['customer_name'] ?? '',
                'collectorId' => $row['collector_id'],
                'address' => $row['address'] ?? '',
                'timeSlot' => $row['time_slot'] ?? '',
                'status' => $this->normalizeStatusValue($row['status'] ?? 'pending'),
                'createdAt' => $row['created_at'] ?? null,
                'scheduledAt' => $row['scheduled_at'] ?? null,
                'wasteCategories' => [],
                'weight' => 0,
                'price' => 0
            ];
        }

        if (isset($row['waste_category_id'])) {
            $map[$pid]['wasteCategories'][] = [
                'id' => $row['waste_category_id'],
                'name' => $row['waste_category_name'],
                'weight' => $row['weight'] !== null ? (float)$row['weight'] : 0,
                'unit' => $row['unit'] ?? null,
                'price_per_unit' => (float)$row['price_per_unit']
            ];
            $map[$pid]['weight'] += $row['weight'] !== null ? (float)$row['weight'] : 0;
        }
    }

    return array_values($map);
}

}
