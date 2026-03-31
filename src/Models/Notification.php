<?php

namespace Models;

class Notification extends BaseModel
{
    protected string $table = 'notifications';

    public function recent(int $limit = 10): array
    {
        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY COALESCE(sent_at, created_at) DESC LIMIT {$limit}"
        );
        return $this->formatRows($rows);
    }

    public function systemAlerts(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM system_alerts ORDER BY name");
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'name' => $row['name'] ?? '',
                'description' => $row['description'] ?? '',
                'status' => $row['status'] ?? 'inactive',
            ];
        }, $rows);
    }

    public function forCompany(int $companyId, string $createdAfter = '', int $limit = 20): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);

        $dateClause = '';
        $params = [];

        if (!empty($createdAfter)) {
            $dateClause = " AND (sent_at >= ? OR created_at >= ?)";
        }

        if ($this->db->isPgsql()) {
            $sql = "SELECT * FROM {$this->table} WHERE (recipient_group IN ('company','companies') OR EXISTS (SELECT 1 FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value) WHERE value = ? OR value = ?))" . $dateClause . " ORDER BY COALESCE(sent_at, created_at) DESC LIMIT {$limit}";
            $params = [(string) $companyId, 'company:' . $companyId];
            if (!empty($createdAfter)) {
                $params[] = $createdAfter;
                $params[] = $createdAfter;
            }
            $rows = $this->db->fetchAll($sql, $params);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE (recipient_group IN ('company','companies') OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CAST(? AS CHAR))) OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('company:', CAST(? AS CHAR)))))" . $dateClause . " ORDER BY COALESCE(sent_at, created_at) DESC LIMIT {$limit}";
            $params = [$companyId, $companyId];
            if (!empty($createdAfter)) {
                $params[] = $createdAfter;
                $params[] = $createdAfter;
            }
            $rows = $this->db->fetchAll($sql, $params);
        }

        return $this->formatRows($rows);
    }

    public function create(array $data): string
    {
        $sql = "INSERT INTO {$this->table} (type, title, message, recipient_group, recipients, sent_at, created_at, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $recipients = isset($data['recipients']) ? json_encode($data['recipients']) : null;
        $sentAt = $data['sent_at'] ?? date('Y-m-d H:i:s');
        $createdAt = date('Y-m-d H:i:s');

        $params = [
            $data['type'] ?? 'info',
            $data['title'] ?? '',
            $data['message'] ?? '',
            $data['recipient_group'] ?? null,
            $recipients,
            $sentAt,
            $createdAt,
            $data['status'] ?? 'pending'
        ];

        if ($this->db->isPgsql()) {
            $sql .= " RETURNING id";
            $row = $this->db->fetch($sql, $params);
            return (string) ($row['id'] ?? '');
        }

        $this->db->query($sql, $params);
        return (string) $this->db->lastInsertId();
    }

    public function forUser(int $userId, string $role, string $createdAfter = '', int $limit = 20): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);
        
        // Normalize role to lowercase to match predefined recipient groups (which are lowercase)
        $originalRole = $role;
        $role = strtolower($role);
        // Map singular role to plural group name if needed, or check both
        $roleGroup = $role . 's'; // e.g. customer -> customers
        
        // DEBUG LOGGING
        file_put_contents(__DIR__ . '/../../storage/logs/notification_debug.log', date('Y-m-d H:i:s') . " - forUser: userId=$userId originalRole=$originalRole normalizedRole=$role roleGroup=$roleGroup\n", FILE_APPEND);

        $dateClause = '';
        $params = [];

        if (!empty($createdAfter)) {
            $dateClause = " AND (sent_at >= ? OR created_at >= ?)";
        }

        if ($this->db->isPgsql()) {
            $sql = "SELECT * FROM {$this->table} WHERE (recipient_group IN ('all', 'users', ?, ?) OR EXISTS (SELECT 1 FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value) WHERE value = ?))" . $dateClause . " ORDER BY COALESCE(sent_at, created_at) DESC LIMIT {$limit}";
            $params = [$role, $roleGroup, 'user:' . $userId];
            if (!empty($createdAfter)) {
                $params[] = $createdAfter;
                $params[] = $createdAfter;
            }
            $rows = $this->db->fetchAll($sql, $params);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE (recipient_group IN ('all', 'users', ?, ?) OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR)))))" . $dateClause . " ORDER BY COALESCE(sent_at, created_at) DESC LIMIT {$limit}";
            $params = [$role, $roleGroup, $userId];
            if (!empty($createdAfter)) {
                $params[] = $createdAfter;
                $params[] = $createdAfter;
            }
            $rows = $this->db->fetchAll($sql, $params);
        }

        return $this->formatRows($rows);
    }

    public function markAsRead($id, int $userId): bool
    {
        error_log("Notification::markAsRead - Updating notification ID: {$id} for user: {$userId}");
        
        $sql = "UPDATE {$this->table} SET status = 'read' WHERE id = ?";
        $params = [$id];
        
        error_log("Notification::markAsRead - SQL: {$sql}");
        error_log("Notification::markAsRead - Params: " . json_encode($params));
        
        $result = $this->db->query($sql, $params);
        
        error_log("Notification::markAsRead - Result: " . ($result ? 'true' : 'false'));
        
        // Verify the update by fetching the notification
        $updated = $this->db->fetch("SELECT id, status FROM {$this->table} WHERE id = ?", [$id]);
        error_log("Notification::markAsRead - After update: " . json_encode($updated));
        
        return $result;
    }

    public function markAllAsRead(int $userId, string $role = ''): bool
    {
        $roleGroup = $role ? $role . 's' : '';
        // Same logic as forUser/getStats to target correct notifications
        
         if ($this->db->isPgsql()) {
             // PGSQL update with complex where
             return $this->db->query(
                "UPDATE {$this->table} 
                 SET status = 'read' 
                 WHERE status != 'read' 
                   AND (
                       recipient_group IN ('all', 'users', ?, ?)
                       OR EXISTS (
                            SELECT 1
                            FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                            WHERE value = ?
                        )
                   )",
                [$role, $roleGroup, 'user:' . $userId]
             );
         } else {
             // MySQL update
             return $this->db->query(
                "UPDATE {$this->table} 
                 SET status = 'read' 
                 WHERE status != 'read' 
                   AND (
                       recipient_group IN ('all', 'users', ?, ?)
                       OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                   )",
                [$role, $roleGroup, $userId]
             );
         }
    }

    public function findById($id): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );

        if (!$row) {
            return null;
        }

        return $this->formatRows([$row])[0];
    }


    public function getUnreadCount(int $userId, string $role = ''): int
    {
        $roleGroup = $role ? $role . 's' : '';

        if ($this->db->isPgsql()) {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count
                 FROM {$this->table}
                 WHERE status != 'read'
                   AND (
                       recipient_group IN ('all', 'users', ?, ?)
                       OR EXISTS (
                            SELECT 1
                            FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                            WHERE value = ?
                        )
                   )",
                [$role, $roleGroup, 'user:' . $userId]
            );
        } else {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count
                 FROM {$this->table}
                 WHERE status != 'read'
                   AND (
                       recipient_group IN ('all', 'users', ?, ?)
                       OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                   )",
                [$role, $roleGroup, $userId]
            );
        }
        
        
        return (int) ($result['count'] ?? 0);
    }

    public function getStats(int $userId, string $role = ''): array
    {
        $roleGroup = $role ? $role . 's' : '';
        
        // Base where clause for user targeting
        $userWhere = "
            (
                recipient_group IN ('all', 'users', ?, ?)
                OR 
        ";

        // DB specific JSON check
        if ($this->db->isPgsql()) {
            $userWhere .= "
                EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                    WHERE value = ?
                )
            )";
            $params = [$role, $roleGroup, 'user:' . $userId];
        } else {
            $userWhere .= "
                JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
            )";
            $params = [$role, $roleGroup, $userId];
        }

        // Queries
        // Total
        $totalSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$userWhere}";
        $total = $this->db->fetch($totalSql, $params)['count'] ?? 0;

        // Unread
        $unreadSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status != 'read' AND {$userWhere}";
        $unread = $this->db->fetch($unreadSql, $params)['count'] ?? 0;

        // Today
        if ($this->db->isPgsql()) {
            $todaySql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at::date = CURRENT_DATE AND {$userWhere}";
        } else {
            $todaySql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = CURDATE() AND {$userWhere}";
        }
        $today = $this->db->fetch($todaySql, $params)['count'] ?? 0;

        return [
            'total' => (int)$total,
            'unread' => (int)$unread,
            'today' => (int)$today
        ];
    }

    public function getAll(int $limit = 100): array
    {
        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY COALESCE(sent_at, created_at) DESC LIMIT {$limit}");
        return $this->formatRows($rows);
    }

    private function formatRows($rows): array
    {
        if (!$rows || !is_array($rows)) {
            return [];
        }

        return array_map(function (array $row): array {
            $recipients = [];
            if (!empty($row['recipients'])) {
                $decoded = json_decode($row['recipients'], true);
                if (is_array($decoded) && !empty($decoded)) {
                    $recipients = $decoded;
                }
            }
            if (empty($recipients) && !empty($row['recipient_group'])) {
                $recipients = [$row['recipient_group']];
            }

            return [
                'id' => $row['id'],
                'type' => $row['type'] ?? 'info',
                'title' => $row['title'] ?? '',
                'message' => $row['message'] ?? '',
                'timestamp' => $row['sent_at'] ?? $row['created_at'] ?? null,
                'status' => $row['status'] ?? 'pending',
                'recipients' => $recipients,
            ];
        }, $rows);
    }
    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            if ($this->db->isPgsql()) {
                $where[] = "(title ILIKE ? OR message ILIKE ?)";
            } else {
                $where[] = "(title LIKE ? OR message LIKE ?)";
            }
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['type'])) {
            $where[] = "type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['recipient_group'])) {
            $where[] = "recipient_group = ?";
            $params[] = $filters['recipient_group'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        $countResult = $this->db->fetch($countSql, $params);
        $total = (int) ($countResult['count'] ?? 0);

        // Get records
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";

        $rows = $this->db->fetchAll($sql, $params);

        return [
            'notifications' => $this->formatRows($rows),
            'total' => $total,
            'page' => floor($offset / $limit) + 1,
            'per_page' => $limit,
            'last_page' => ceil($total / $limit)
        ];
    }
}
