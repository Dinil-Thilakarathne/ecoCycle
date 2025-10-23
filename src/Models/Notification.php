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
}
