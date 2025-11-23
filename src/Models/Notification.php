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
        if (!$rows) {
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

        if (!$rows) {
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
                "SELECT *
                 FROM {$this->table}
                 WHERE recipient_group IN ('all', 'users')
                    OR EXISTS (
                        SELECT 1
                        FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                        WHERE value = ?
                    )
                 ORDER BY COALESCE(sent_at, created_at) DESC
                 LIMIT {$limit}",
                ['user:' . $userId]
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT *
                 FROM {$this->table}
                 WHERE recipient_group IN ('all', 'users')
                    OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                 ORDER BY COALESCE(sent_at, created_at) DESC
                 LIMIT {$limit}",
                [$userId]
            );
        }

        if (!$rows) {
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

    public function markAsRead(int $id, int $userId): bool
    {
        return $this->db->query(
            "UPDATE {$this->table} SET status = 'read' WHERE id = ?",
            [$id]
        );
    }

    public function markAllAsRead(int $userId): bool
    {
         if ($this->db->isPgsql()) {
             return $this->db->query(
                "UPDATE {$this->table} 
                 SET status = 'read' 
                 WHERE status != 'read' 
                   AND EXISTS (
                        SELECT 1
                        FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                        WHERE value = ?
                    )",
                ['user:' . $userId]
             );
         } else {
             return $this->db->query(
                "UPDATE {$this->table} 
                 SET status = 'read' 
                 WHERE status != 'read' 
                   AND JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))",
                [$userId]
             );
         }
    }

    public function getUnreadCount(int $userId): int
    {
        if ($this->db->isPgsql()) {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count
                 FROM {$this->table}
                 WHERE status != 'read'
                   AND (
                       recipient_group IN ('all', 'users')
                       OR EXISTS (
                            SELECT 1
                            FROM jsonb_array_elements_text(COALESCE(recipients::jsonb, '[]'::jsonb)) AS recipient(value)
                            WHERE value = ?
                        )
                   )",
                ['user:' . $userId]
            );
        } else {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count
                 FROM {$this->table}
                 WHERE status != 'read'
                   AND (
                       recipient_group IN ('all', 'users')
                       OR JSON_CONTAINS(COALESCE(recipients, JSON_ARRAY()), JSON_QUOTE(CONCAT('user:', CAST(? AS CHAR))))
                   )",
                [$userId]
            );
        }
        
        return (int) ($result['count'] ?? 0);
    }

    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table}");
    }
}
