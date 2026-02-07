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

    public function forCompany(int $companyId, int $limit = 20): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);

        if ($this->db->isPgsql()) {
            $rows = $this->db->fetchAll(
                "SELECT *
                 FROM {$this->table}
                 WHERE recipient_group IN ('company','companies')
                    OR EXISTS (
                        SELECT 1
                        FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                        WHERE value = ? OR value = ?
                    )
                 ORDER BY COALESCE(sent_at, created_at) DESC
                 LIMIT {$limit}",
                [(string) $companyId, 'company:' . $companyId]
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT *
                 FROM {$this->table}
                 WHERE recipient_group IN ('company','companies')
                    OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CAST(? AS CHAR)))
                    OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('company:', CAST(? AS CHAR))))
                 ORDER BY COALESCE(sent_at, created_at) DESC
                 LIMIT {$limit}",
                [$companyId, $companyId]
            );
        }

        return $this->formatRows($rows);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (type, title, message, recipient_group, recipients, sent_at, created_at, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $recipients = isset($data['recipients']) ? json_encode($data['recipients']) : null;
        $sentAt = $data['sent_at'] ?? date('Y-m-d H:i:s');
        $createdAt = date('Y-m-d H:i:s');
        
        $this->db->query($sql, [
            $data['type'] ?? 'info',
            $data['title'] ?? '',
            $data['message'] ?? '',
            $data['recipient_group'] ?? null,
            $recipients,
            $sentAt,
            $createdAt,
            $data['status'] ?? 'pending'
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function forUser(int $userId, string $role, int $limit = 20): array
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

        if ($this->db->isPgsql()) {
            $rows = $this->db->fetchAll(
                "SELECT *
                 FROM {$this->table}
                 WHERE recipient_group IN ('all', 'users', ?, ?)
                    OR EXISTS (
                        SELECT 1
                        FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                        WHERE value = ?
                    )
                 ORDER BY COALESCE(sent_at, created_at) DESC
                 LIMIT {$limit}",
                [$role, $roleGroup, 'user:' . $userId]
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT *
                 FROM {$this->table}
                 WHERE recipient_group IN ('all', 'users', ?, ?)
                    OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                 ORDER BY COALESCE(sent_at, created_at) DESC
                 LIMIT {$limit}",
                [$role, $roleGroup, $userId]
            );
        }

        return $this->formatRows($rows);
    }

    public function markAsRead(int $id, int $userId): bool
    {
        return $this->db->query(
            "UPDATE {$this->table} SET status = 'read' WHERE id = ?",
            [$id]
        );
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

    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
}
