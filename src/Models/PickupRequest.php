<?php 

namespace Models;

class PickupRequest extends BaseModel
{
    protected string $table = 'pickup_requests';

    /**
     * List pickup requests for a specific customer
     */
    public function listForCustomer(int $customerId, ?string $status = null): array
    {
        $sql = "SELECT pr.*, 
                       c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, 
                       col.name AS collector_name 
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

        if (!$rows) return [];

        $ids = array_column($rows, 'id');
        $wasteMap = $this->wasteCategoriesForPickups($ids);

        return array_map(fn(array $row) => $this->formatRow($row, $wasteMap), $rows);
    }

    /**
     * List pickup requests for a specific collector
     */
    public function listForCollector(int $collectorId, ?string $status = null, ?string $timeSlot = null): array
    {
        if ($collectorId <= 0) return [];

        $sql = "SELECT pr.*, 
                       c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, 
                       col.name AS collector_name 
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

        if (!$rows) return [];

        $ids = array_column($rows, 'id');
        $wasteMap = $this->wasteCategoriesForPickups($ids);

        return array_map(fn(array $row) => $this->formatRow($row, $wasteMap), $rows);
    }

    /**
     * List all pickup requests
     */
    public function listAll(?string $timeSlot = null): array
    {
        $sql = "SELECT pr.*, 
                       c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, 
                       col.name AS collector_name 
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

        if (!$rows) return [];

        $ids = array_column($rows, 'id');
        $wasteMap = $this->wasteCategoriesForPickups($ids);

        return array_map(fn(array $row) => $this->formatRow($row, $wasteMap), $rows);
    }

    /**
     * Find a single pickup request by ID
     */
    public function find(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') return null;

        $sql = "SELECT pr.*, 
                       c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, 
                       col.name AS collector_name 
                  FROM {$this->table} pr
                  LEFT JOIN users c ON c.id = pr.customer_id
                  LEFT JOIN users col ON col.id = pr.collector_id
                 WHERE pr.id = ? 
                 LIMIT 1";

        $row = $this->db->fetch($sql, [$id]);
        if (!$row) return null;

        $wasteMap = $this->wasteCategoriesForPickups([$row['id']]);
        return $this->formatRow($row, $wasteMap);
    }

    /**
     * Check if pickup request exists
     */
    public function exists(string $id): bool
    {
        if ($id === '') return false;
        $row = $this->db->fetch("SELECT id FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return (bool) $row;
    }

    /**
     * Create a pickup request for a customer
     */
    public function createForCustomer(int $customerId, array $payload): array
    {
        $id = $this->generateId();
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $address = $payload['address'] ?? null;
            $timeSlot = $payload['timeSlot'] ?? null;
            $scheduledAt = $payload['scheduledAt'] ?? null;
            if ($scheduledAt === '') $scheduledAt = null;

            if ($scheduledAt === null) {
                $this->db->query(
                    "INSERT INTO {$this->table} 
                    (id, customer_id, address, time_slot, status, collector_id, collector_name, scheduled_at, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 'pending', NULL, NULL, NULL, NOW(), NOW())",
                    [$id, $customerId, $address, $timeSlot]
                );
            } else {
                $this->db->query(
                    "INSERT INTO {$this->table} 
                    (id, customer_id, address, time_slot, status, collector_id, collector_name, scheduled_at, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 'pending', NULL, NULL, ?, NOW(), NOW())",
                    [$id, $customerId, $address, $timeSlot, $scheduledAt]
                );
            }

            if (!empty($payload['wasteCategories'])) {
                $this->replaceWasteCategories($id, $payload['wasteCategories'], false);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $this->find($id) ?? [];
    }

    /**
     * Update pickup request by ID
     */
    public function update(string $id, array $data): bool
    {
        $allowed = ['collector_id', 'collector_name', 'status', 'time_slot', 'scheduled_at', 'address'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        if (empty($filtered)) return true;

        $setParts = [];
        $params = [];
        foreach ($filtered as $column => $value) {
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }

        if (!array_key_exists('updated_at', $filtered)) {
            $setParts[] = "updated_at = CURRENT_TIMESTAMP";
        }

        $params[] = $id;
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $setParts) . ' WHERE id = ?';

        return $this->db->query($sql, $params);
    }

    /**
     * Update a pickup request for a customer
     */
    public function updateForCustomer(string $id, int $customerId, array $payload): bool
    {
        $current = $this->db->fetch(
            "SELECT status FROM {$this->table} WHERE id = ? AND customer_id = ? LIMIT 1",
            [$id, $customerId]
        );

        if (!$current || !$this->isCustomerEditableStatus($current['status'] ?? '')) {
            return false;
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $fields = [];
            $params = [];

            if (isset($payload['address'])) {
                $fields[] = 'address = ?';
                $params[] = $payload['address'];
            }
            if (isset($payload['timeSlot'])) {
                $fields[] = 'time_slot = ?';
                $params[] = $payload['timeSlot'];
            }
            if (array_key_exists('scheduledAt', $payload)) {
                if ($payload['scheduledAt'] === '' || $payload['scheduledAt'] === null) {
                    $fields[] = 'scheduled_at = NULL';
                } else {
                    $fields[] = 'scheduled_at = ?';
                    $params[] = $payload['scheduledAt'];
                }
            }

            if (!empty($fields)) {
                $fields[] = 'updated_at = CURRENT_TIMESTAMP';
                $params[] = $id;
                $params[] = $customerId;
                $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $fields) . ' WHERE id = ? AND customer_id = ?';
                $this->db->query($sql, $params);
            }

            if (isset($payload['wasteCategories'])) {
                $this->replaceWasteCategories($id, $payload['wasteCategories']);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Update pickup status for collector
     */
    public function updateStatusForCollector(string $id, int $collectorId, string $status): bool
    {
        $id = trim($id);
        if ($id === '' || $collectorId <= 0) return false;

        $args = func_get_args();
        $weight = $args[3] ?? null;
        if ($weight !== null) $weight = (float) $weight;

        if ($weight === null) {
            return $this->db->query(
                "UPDATE {$this->table} SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND collector_id = ?",
                [$status, $id, $collectorId]
            );
        }

        return $this->db->query(
            "UPDATE {$this->table} SET status = ?, weight = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND collector_id = ?",
            [$status, $weight, $id, $collectorId]
        );
    }

    /**
     * Cancel a pickup request for a customer
     */
    public function cancelForCustomer(string $id, int $customerId): bool
    {
        $current = $this->db->fetch(
            "SELECT status FROM {$this->table} WHERE id = ? AND customer_id = ? LIMIT 1",
            [$id, $customerId]
        );

        if (!$current || !$this->isCustomerCancellableStatus($current['status'] ?? '')) return false;

        return $this->db->query(
            "UPDATE {$this->table} SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND customer_id = ?",
            [$id, $customerId]
        );
    }

    /**
     * Count pickup requests by status
     */
    public function countByStatuses(array $statuses): int
    {
        if (empty($statuses)) return 0;

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $row = $this->db->fetch("SELECT COUNT(*) AS total FROM {$this->table} WHERE status IN ({$placeholders})", $statuses);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Get recent pickup requests
     */
    public function recent(int $limit = 5): array
    {
        $limit = max(1, $limit);
        $rows = $this->db->fetchAll(
            "SELECT pr.id, pr.status, pr.created_at, pr.time_slot, c.name AS customer_name 
             FROM {$this->table} pr 
             LEFT JOIN users c ON c.id = pr.customer_id 
             ORDER BY pr.created_at DESC 
             LIMIT {$limit}"
        );

        return $rows ?: [];
    }

    /**
     * Get predefined time slots
     */
    public function listTimeSlots(): array
    {
        return ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00'];
    }

    /* ----------------------------------------------------------------
     * Private helper methods
     * ---------------------------------------------------------------- */

    private function wasteCategoriesForPickups(array $pickupIds): array
    {
        $pickupIds = array_values(array_filter($pickupIds, fn($id) => $id !== null));
        if (empty($pickupIds)) return [];

        $placeholders = implode(',', array_fill(0, count($pickupIds), '?'));
        $sql = "SELECT prw.pickup_id, prw.waste_category_id, prw.quantity, prw.unit, wc.name
                  FROM pickup_request_wastes prw
                  INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
                 WHERE prw.pickup_id IN ({$placeholders})
              ORDER BY wc.name";

        $rows = $this->db->fetchAll($sql, $pickupIds);
        if (!$rows) return [];

        $map = [];
        foreach ($rows as $row) {
            $pid = $row['pickup_id'];
            if (!isset($map[$pid])) {
                $map[$pid] = ['names' => [], 'details' => []];
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '' && !in_array($name, $map[$pid]['names'], true)) {
                $map[$pid]['names'][] = $name;
            }

            if (isset($row['waste_category_id'])) {
                $map[$pid]['details'][] = [
                    'id' => (int) $row['waste_category_id'],
                    'name' => $name,
                    'quantity' => $row['quantity'] !== null ? (float) $row['quantity'] : null,
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

        return [
            'id' => $pickupId,
            'customerId' => $row['customer_id'],
            'customerName' => $row['customer_name'] ?? '',
            'address' => $row['address'] ?? ($row['customer_address'] ?? ''),
            'timeSlot' => $row['time_slot'] ?? '',
            'status' => $this->normalizeStatusValue($row['status'] ?? 'pending'),
            'statusRaw' => $row['status'] ?? 'pending',
            'collectorId' => $row['collector_id'],
            'collectorName' => $row['collector_name'] ?? '',
            'wasteCategories' => $wasteEntry['names'] ?? [],
            'wasteCategoryDetails' => $wasteEntry['details'] ?? [],
            'weight' => isset($row['weight']) ? (float) $row['weight'] : null,
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

            $quantity = $category['quantity'] ?? null;
            if ($quantity !== null) $quantity = (float) $quantity;

            $unit = $category['unit'] ?? null;

            $this->db->query(
                'INSERT INTO pickup_request_wastes (pickup_id, waste_category_id, quantity, unit) VALUES (?, ?, ?, ?)',
                [$pickupId, $categoryId, $quantity, $unit]
            );
        }
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
        return match($normalized) {
            'in_progress', 'in-progress' => 'in progress',
            'in progress', 'pending', 'assigned', 'completed', 'cancelled', 'confirmed' => $normalized,
            default => $normalized,
        };
    }

    private function isCustomerEditableStatus(string $status): bool
    {
        return in_array(strtolower($status), ['pending'], true);
    }

    private function isCustomerCancellableStatus(string $status): bool
    {
        return in_array(strtolower($status), ['pending', 'assigned', 'confirmed'], true);
    }

    /**
     * Find a pickup request for a specific collector
     */
    public function findForCollector(int $pickupId, int $collectorId): ?array
    {
        $query = "SELECT * FROM pickup_requests WHERE id = ? AND collector_id = ?";
        return $this->db->fetchOne($query, [$pickupId, $collectorId]);
    }

    /**
     * Update pickup request by ID
     */
    public function updateById(int $pickupId, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $val) {
            $fields[] = "$key = ?";
            $values[] = $val;
        }

        $values[] = $pickupId;
        $sql = "UPDATE pickup_requests SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->execute($sql, $values);
    }
}
