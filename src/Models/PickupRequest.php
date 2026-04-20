<?php

namespace Models;

class PickupRequest extends BaseModel
{
    protected string $table = 'pickup_requests';
    private ?bool $vehicleIdColumnExists = null;

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

        $vehicleSelect = $this->hasVehicleIdColumn() ? ', v.plate_number AS vehicle_plate, v.type AS vehicle_type' : ', NULL AS vehicle_plate, NULL AS vehicle_type';
        $vehicleJoin = $this->hasVehicleIdColumn() ? ' LEFT JOIN vehicles v ON v.id = pr.vehicle_id' : '';

        $sql = "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, col.name AS collector_name{$vehicleSelect}
                FROM {$this->table} pr
                LEFT JOIN users c ON c.id = pr.customer_id
                LEFT JOIN users col ON col.id = pr.collector_id{$vehicleJoin}
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

    public function listAll(?string $timeSlot = null, ?string $date = null, ?string $status = null, string $dateOperator = '='): array
    {
        $vehicleSelect = $this->hasVehicleIdColumn() ? ', v.plate_number AS vehicle_plate, v.type AS vehicle_type' : ', NULL AS vehicle_plate, NULL AS vehicle_type';
        $vehicleJoin = $this->hasVehicleIdColumn() ? ' LEFT JOIN vehicles v ON v.id = pr.vehicle_id' : '';

        $sql = "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, col.name AS collector_name{$vehicleSelect}
                FROM {$this->table} pr
                LEFT JOIN users c ON c.id = pr.customer_id
                LEFT JOIN users col ON col.id = pr.collector_id{$vehicleJoin}";

        $conditions = [];
        $params = [];

        if ($timeSlot !== null && $timeSlot !== '' && $timeSlot !== 'all') {
            $conditions[] = 'pr.time_slot = ?';
            $params[] = $timeSlot;
        }

        if ($date !== null && $date !== '') {
            $conditions[] = "DATE(pr.scheduled_at) {$dateOperator} ?";
            $params[] = $date;
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            $conditions[] = 'pr.status = ?';
            $params[] = $status;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY pr.scheduled_at IS NULL ASC, pr.scheduled_at ASC, pr.created_at DESC';

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

        $vehicleSelect = $this->hasVehicleIdColumn() ? ', v.plate_number AS vehicle_plate, v.type AS vehicle_type' : ', NULL AS vehicle_plate, NULL AS vehicle_type';
        $vehicleJoin = $this->hasVehicleIdColumn() ? ' LEFT JOIN vehicles v ON v.id = pr.vehicle_id' : '';

        $row = $this->db->fetch(
            "SELECT pr.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address, col.name AS collector_name{$vehicleSelect}
             FROM {$this->table} pr
             LEFT JOIN users c ON c.id = pr.customer_id
             LEFT JOIN users col ON col.id = pr.collector_id
             {$vehicleJoin}
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

    public function createForCustomer(int $customerId, array $payload): array
    {
        $id = $this->generateId();
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $address = $payload['address'] ?? null;
            $timeSlot = $payload['timeSlot'] ?? null;
            $scheduledAt = $payload['scheduledAt'] ?? null;
           
            if ($scheduledAt === '') {
                $scheduledAt = null;
            }

           
            if ($scheduledAt === null) {
                $this->db->query(
                    "INSERT INTO {$this->table} (id, customer_id, address, time_slot, status, collector_id, collector_name, scheduled_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'pending', NULL, NULL, NULL, NOW(), NOW())",
                    [$id, $customerId, $address, $timeSlot]
                );
            } else {
                $this->db->query(
                    "INSERT INTO {$this->table} (id, customer_id, address, time_slot, status, collector_id, collector_name, scheduled_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'pending', NULL, NULL, ?, NOW(), NOW())",
                    [$id, $customerId, $address, $timeSlot, $scheduledAt]
                );
            }

            $categories = $payload['wasteCategories'] ?? [];
            if (!empty($categories)) {
                $this->replaceWasteCategories($id, $categories, false);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $this->find($id) ?? [];
    }

    public function update(string $id, array $data): bool
    {
        $allowed = ['collector_id', 'collector_name', 'vehicle_id', 'status', 'time_slot', 'scheduled_at', 'address'];
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

    public function updateForCustomer(string $id, int $customerId, array $payload): bool
    {
        $current = $this->db->fetch(
            "SELECT status FROM {$this->table} WHERE id = ? AND customer_id = ? LIMIT 1",
            [$id, $customerId]
        );

        if (!$current) {
            return false;
        }

        if (!$this->isCustomerEditableStatus((string) ($current['status'] ?? ''))) {
            return false;
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $fields = [];
            $params = [];

            if (array_key_exists('address', $payload)) {
                $fields[] = 'address = ?';
                $params[] = $payload['address'];
            }

            if (array_key_exists('timeSlot', $payload)) {
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

            if (array_key_exists('wasteCategories', $payload)) {
                $this->replaceWasteCategories($id, $payload['wasteCategories']);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return true;
    }

    public function updateStatusForCollector(string $id, int $collectorId, string $status, $weights = null): bool
    {
        $id = trim($id);
        $collectorId = (int) $collectorId;
        if ($id === '' || $collectorId <= 0) {
            return false;
        }

        if ($weights === null) {
            $updated = $this->db->query(
                "UPDATE {$this->table} SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND collector_id = ?",
                [$status, $id, $collectorId]
            );

            if ($updated && $status === 'completed') {
                $this->releaseVehicleIfLinked($id);
            }

            return $updated;
        }

        
        if (is_array($weights)) {
            $pdo = $this->db->pdo();
            $pdo->beginTransaction();

            try {
             
                $totalWeight = 0.0;
                $totalPrice = 0.0;

                foreach ($weights as $item) {
                    $catId = (int) ($item['category_id'] ?? 0);
                    $weight = (float) ($item['weight'] ?? 0);

                    if ($catId <= 0)
                        continue;

                 
                    $catRow = $this->db->fetch("SELECT price_per_unit FROM waste_categories WHERE id = ?", [$catId]);
                    $pricePerUnit = 0.0;
                    if ($catRow) {
                        $pricePerUnit = (float) ($catRow['price_per_unit'] ?? 0);
                    }

                    $amount = $weight * $pricePerUnit;
                    $totalWeight += $weight;
                    $totalPrice += $amount;

                    
                    $this->db->query(
                        "UPDATE pickup_request_wastes 
                         SET weight = ?, amount = ? 
                         WHERE pickup_id = ? AND waste_category_id = ?",
                        [$weight, $amount, $id, $catId]
                    );
                }

                error_log("Updating pickup {$id}: status={$status}, weight={$totalWeight}, price={$totalPrice}, collector_id={$collectorId}");

                $updateResult = $this->db->query(
                    "UPDATE {$this->table} 
                     SET status = ?, weight = ?, price = ?, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ? AND collector_id = ?",
                    [$status, $totalWeight, $totalPrice, $id, $collectorId]
                );

                if (!$updateResult) {
                    throw new \Exception("Failed to update pickup request. Pickup may not be assigned to collector {$collectorId}");
                }

               
                if ($status === 'completed') {
                    $this->releaseVehicleIfLinked($id);
                }

                $pdo->commit();
                return true;

            } catch (\Throwable $e) {
                $pdo->rollBack();
             
                error_log("Failed updating pickup weights: " . $e->getMessage());
                throw $e;
            }
        }

 
        $legacyResult = $this->db->query(
            "UPDATE {$this->table} SET status = ?, weight = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND collector_id = ?",
            [$status, (float) $weights, $id, $collectorId]
        );

        if ($legacyResult && $status === 'completed') {
            $this->releaseVehicleIfLinked($id);
        }

        return $legacyResult;
    }

    public function cancelForCustomer(string $id, int $customerId): bool
    {
        $current = $this->db->fetch(
            "SELECT status FROM {$this->table} WHERE id = ? AND customer_id = ? LIMIT 1",
            [$id, $customerId]
        );

        if (!$current) {
            return false;
        }

        if (!$this->isCustomerCancellableStatus((string) ($current['status'] ?? ''))) {
            return false;
        }

        return $this->db->query(
            "UPDATE {$this->table} SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND customer_id = ?",
            [$id, $customerId]
        );
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
        return ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00']; 
    }

    private function wasteCategoriesForPickups(array $pickupIds): array
    {
        $pickupIds = array_values(array_filter($pickupIds, fn($id) => $id !== null));
        if (empty($pickupIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($pickupIds), '?'));
        $sql = "SELECT prw.pickup_id, prw.waste_category_id, prw.weight, prw.unit, wc.name, wc.price_per_unit
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
                    'price_per_unit' => isset($row['price_per_unit']) ? (float) $row['price_per_unit'] : 0.0,
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
            'vehicleId' => $row['vehicle_id'] ?? null,
            'vehiclePlate' => $row['vehicle_plate'] ?? '',
            'vehicleType' => $row['vehicle_type'] ?? '',
            'wasteCategories' => $names,
            'wasteCategoryDetails' => $details,
            'weight' => isset($row['weight']) ? (float) $row['weight'] : null,   
            'price' => isset($row['price']) ? (float) $row['price'] : null,     
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
            if ($categoryId === null)
                continue;

            $weight = $category['weight'] ?? null;
            if ($weight !== null)
                $weight = (float) $weight;

            $unit = $category['unit'] ?? null;

            $this->db->query(
                'INSERT INTO pickup_request_wastes (pickup_id, waste_category_id, weight, unit) VALUES (?, ?, ?, ?)',
                [$pickupId, $categoryId, $weight, $unit]
            );
        }
    }

    public function updateStatus(string $pickupId, string $status): void
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
            case 'failed':
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

    private function hasVehicleIdColumn(): bool
    {
        if ($this->vehicleIdColumnExists !== null) {
            return $this->vehicleIdColumnExists;
        }

        try {
            if ($this->db->isPgsql()) {
                $row = $this->db->fetch(
                    "SELECT 1
                     FROM information_schema.columns
                     WHERE table_schema = current_schema()
                       AND table_name = ?
                       AND column_name = 'vehicle_id'
                     LIMIT 1",
                    [$this->table]
                );
            } else {
                $row = $this->db->fetch(
                    "SELECT 1
                     FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name = ?
                       AND column_name = 'vehicle_id'
                     LIMIT 1",
                    [$this->table]
                );
            }

            $this->vehicleIdColumnExists = (bool) $row;
        } catch (\Throwable $e) {
         
            $this->vehicleIdColumnExists = false;
        }

        return $this->vehicleIdColumnExists;
    }

    private function releaseVehicleIfLinked(string $pickupId): void
    {
        if (!$this->hasVehicleIdColumn()) {
            return;
        }

        $this->db->query(
            "UPDATE vehicles SET status = 'available', updated_at = CURRENT_TIMESTAMP
             WHERE id = (SELECT vehicle_id FROM {$this->table} WHERE id = ?)",
            [$pickupId]
        );
    }

   
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
                    'weight' => $row['weight'] !== null ? (float) $row['weight'] : 0,
                    'unit' => $row['unit'] ?? null,
                    'price_per_unit' => (float) $row['price_per_unit']
                ];
                $map[$pid]['weight'] += $row['weight'] !== null ? (float) $row['weight'] : 0;
            }
        }

        return array_values($map);
    }

  
    public function getUnallocatedWaste(): array
    {
        $sql = "SELECT 
                    wc.id AS category_id,
                    wc.name AS category_name,
                    wc.unit,
                    wc.price_per_unit,
                    SUM(COALESCE(prw.weight, 0)) AS total_weight,
                    SUM(COALESCE(prw.amount, 0)) AS total_value,
                    COUNT(DISTINCT pr.id) AS pickup_count
                FROM pickup_requests pr
                INNER JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
                INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
                WHERE pr.status = 'completed'
                AND prw.weight IS NOT NULL
                AND prw.weight > 0
                AND NOT EXISTS (
                    SELECT 1 FROM bidding_round_sources brs 
                    WHERE brs.pickup_id = pr.id
                )
                GROUP BY wc.id, wc.name, wc.unit, wc.price_per_unit
                ORDER BY wc.name";

        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'categoryId' => (int) $row['category_id'],
                'categoryName' => $row['category_name'] ?? 'Unknown',
                'unit' => $row['unit'] ?? 'kg',
                'pricePerUnit' => isset($row['price_per_unit']) ? (float) $row['price_per_unit'] : 0.0,
                'totalWeight' => isset($row['total_weight']) ? (float) $row['total_weight'] : 0.0,
                'totalValue' => isset($row['total_value']) ? (float) $row['total_value'] : 0.0,
                'pickupCount' => isset($row['pickup_count']) ? (int) $row['pickup_count'] : 0,
            ];
        }, $rows);
    }


    public function getUnallocatedPickupIds(int $categoryId, ?float $maxQuantity = null): array
    {
        $sql = "SELECT 
                    pr.id,
                    prw.weight
                FROM pickup_requests pr
                INNER JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
                WHERE pr.status = 'completed'
                AND prw.waste_category_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM bidding_round_sources brs 
                    WHERE brs.pickup_id = pr.id
                )
                ORDER BY pr.created_at ASC";

        $rows = $this->db->fetchAll($sql, [$categoryId]);
        if (!$rows) {
            return [];
        }

        $pickupIds = [];
        $totalAllocated = 0.0;

        foreach ($rows as $row) {
            if ($maxQuantity !== null && $totalAllocated >= $maxQuantity) {
                break;
            }

            $pickupIds[] = $row['id'];
            $totalAllocated += (float) ($row['weight'] ?? 0);
        }

        return $pickupIds;
    }

    public function hasOverlappingAssignment(int $collectorId, string $date, string $timeSlot, ?string $excludePickupId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} 
                WHERE collector_id = ? 
                AND time_slot = ? 
                AND DATE(scheduled_at) = DATE(?) 
                AND status NOT IN ('completed', 'cancelled')";
        $params = [$collectorId, $timeSlot, $date];

        if ($excludePickupId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludePickupId;
        }

        $stmt = $this->db->fetch($sql, $params);
        return (bool) $stmt;
    }

    public function hasOverlappingVehicleAssignment(int $vehicleId, string $date, string $timeSlot, ?string $excludePickupId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} 
                WHERE vehicle_id = ? 
                AND time_slot = ? 
                AND DATE(scheduled_at) = DATE(?) 
                AND status NOT IN ('completed', 'cancelled')";
        $params = [$vehicleId, $timeSlot, $date];

        if ($excludePickupId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludePickupId;
        }

        $stmt = $this->db->fetch($sql, $params);
        return (bool) $stmt;
    }

}
