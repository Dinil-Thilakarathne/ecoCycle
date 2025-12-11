<?php

namespace Models;

class Notification extends BaseModel
{
    protected string $table = 'notifications';
    protected string $readTable = 'notification_reads';

    public function createTableIfNotExists(): bool
    {
        if ($this->db->isPgsql()) {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->readTable} (
                    id SERIAL PRIMARY KEY,
                    notification_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    read_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_reads_notification FOREIGN KEY (notification_id) REFERENCES {$this->table}(id) ON DELETE CASCADE,
                    CONSTRAINT fk_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE(notification_id, user_id)
                );
                CREATE INDEX IF NOT EXISTS idx_reads_user ON {$this->readTable} (user_id);
                CREATE INDEX IF NOT EXISTS idx_reads_notification ON {$this->readTable} (notification_id);
            ";
        } else {
            $sql = "
                CREATE TABLE IF NOT EXISTS `{$this->readTable}` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `notification_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_user_notification` (`notification_id`, `user_id`),
                    INDEX `idx_reads_user` (`user_id`),
                    INDEX `idx_reads_notification` (`notification_id`),
                    FOREIGN KEY (`notification_id`) REFERENCES `{$this->table}`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
        }

        return $this->db->query($sql);
    }

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
                "SELECT n.*, 
                        CASE WHEN nr.id IS NOT NULL THEN 'read' ELSE 'pending' END as status
                 FROM {$this->table} n
                 LEFT JOIN {$this->readTable} nr ON n.id = nr.notification_id AND nr.user_id = ?
                 WHERE n.recipient_group IN ('company','companies')
                    OR EXISTS (
                        SELECT 1
                        FROM jsonb_array_elements_text(COALESCE(n.recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                        WHERE value = ? OR value = ?
                    )
                 ORDER BY COALESCE(n.sent_at, n.created_at) DESC
                 LIMIT {$limit}",
                [(string) $companyId, (string) $companyId, 'company:' . $companyId]
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT n.*, 
                        CASE WHEN nr.id IS NOT NULL THEN 'read' ELSE 'pending' END as status
                 FROM {$this->table} n
                 LEFT JOIN {$this->readTable} nr ON n.id = nr.notification_id AND nr.user_id = ?
                 WHERE n.recipient_group IN ('company','companies')
                    OR JSON_CONTAINS(COALESCE(n.recipients, JSON_ARRAY()), JSON_QUOTE(CAST(? AS CHAR)))
                    OR JSON_CONTAINS(COALESCE(n.recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('company:', CAST(? AS CHAR))))
                 ORDER BY COALESCE(n.sent_at, n.created_at) DESC
                 LIMIT {$limit}",
                [$companyId, $companyId, $companyId]
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

    public function forUser(int $userId, int $limit = 20): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);
        
        if ($this->db->isPgsql()) {
            $rows = $this->db->fetchAll(
                "SELECT n.*, 
                        CASE WHEN nr.id IS NOT NULL THEN 'read' ELSE 'pending' END as status
                 FROM {$this->table} n
                 LEFT JOIN {$this->readTable} nr ON n.id = nr.notification_id AND nr.user_id = ?
                 WHERE n.recipient_group IN ('all', 'users')
                    OR EXISTS (
                        SELECT 1
                        FROM jsonb_array_elements_text(COALESCE(n.recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                        WHERE value = ?
                    )
                 ORDER BY COALESCE(n.sent_at, n.created_at) DESC
                 LIMIT {$limit}",
                [$userId, 'user:' . $userId]
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT n.*, 
                        CASE WHEN nr.id IS NOT NULL THEN 'read' ELSE 'pending' END as status
                 FROM {$this->table} n
                 LEFT JOIN {$this->readTable} nr ON n.id = nr.notification_id AND nr.user_id = ?
                 WHERE n.recipient_group IN ('all', 'users')
                    OR JSON_CONTAINS(COALESCE(n.recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                 ORDER BY COALESCE(n.sent_at, n.created_at) DESC
                 LIMIT {$limit}",
                [$userId, $userId]
            );
        }

        return $this->formatRows($rows);
    }

    public function markAsRead(int $id, int $userId): bool
    {
        // Insert ignore or on conflict do nothing
        if ($this->db->isPgsql()) {
            return $this->db->query(
                "INSERT INTO {$this->readTable} (notification_id, user_id) VALUES (?, ?) ON CONFLICT (notification_id, user_id) DO NOTHING",
                [$id, $userId]
            );
        } else {
            return $this->db->query(
                "INSERT IGNORE INTO {$this->readTable} (notification_id, user_id) VALUES (?, ?)",
                [$id, $userId]
            );
        }
    }

    public function markAllAsRead(int $userId): bool
    {
         if ($this->db->isPgsql()) {
             // Find all unread notifications for user and insert into read table
             return $this->db->query(
                "INSERT INTO {$this->readTable} (notification_id, user_id)
                 SELECT n.id, ?
                 FROM {$this->table} n
                 WHERE NOT EXISTS (SELECT 1 FROM {$this->readTable} nr WHERE nr.notification_id = n.id AND nr.user_id = ?)
                   AND (
                        n.recipient_group IN ('all', 'users', 'company', 'companies')
                        OR EXISTS (
                            SELECT 1
                            FROM jsonb_array_elements_text(COALESCE(n.recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                            WHERE value = ? OR value = ?
                        )
                   )
                 ON CONFLICT (notification_id, user_id) DO NOTHING",
                [$userId, $userId, 'user:' . $userId, 'company:' . $userId] // Assuming simple company ID check for now, might need refinement
             );
         } else {
             return $this->db->query(
                "INSERT IGNORE INTO {$this->readTable} (notification_id, user_id)
                 SELECT n.id, ?
                 FROM {$this->table} n
                 WHERE NOT EXISTS (SELECT 1 FROM {$this->readTable} nr WHERE nr.notification_id = n.id AND nr.user_id = ?)
                   AND (
                        n.recipient_group IN ('all', 'users', 'company', 'companies')
                        OR JSON_CONTAINS(COALESCE(n.recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                        OR JSON_CONTAINS(COALESCE(n.recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('company:', CAST(? AS CHAR))))
                   )",
                [$userId, $userId, $userId, $userId]
             );
         }
    }

    public function getUnreadCount(int $userId): int
    {
        if ($this->db->isPgsql()) {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count
                 FROM {$this->table} n
                 WHERE NOT EXISTS (SELECT 1 FROM {$this->readTable} nr WHERE nr.notification_id = n.id AND nr.user_id = ?)
                   AND (
                       n.recipient_group IN ('all', 'users')
                       OR EXISTS (
                            SELECT 1
                            FROM jsonb_array_elements_text(COALESCE(n.recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                            WHERE value = ?
                        )
                   )",
                [$userId, 'user:' . $userId]
            );
        } else {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count
                 FROM {$this->table} n
                 WHERE NOT EXISTS (SELECT 1 FROM {$this->readTable} nr WHERE nr.notification_id = n.id AND nr.user_id = ?)
                   AND (
                       n.recipient_group IN ('all', 'users')
                       OR JSON_CONTAINS(COALESCE(n.recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                   )",
                [$userId, $userId]
            );
        }
        
        return (int) ($result['count'] ?? 0);
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
}
