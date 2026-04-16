<?php

namespace Models;

use Core\Database;

class Role extends BaseModel
{
    protected string $table = 'roles';

    public function createTableIfNotExists(): bool
    {
        if ($this->db->isPgsql()) {
            $sql = 'CREATE TABLE IF NOT EXISTS roles (
                id SERIAL PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                label VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )';

            return $this->db->query($sql);
        }

        $sql = "CREATE TABLE IF NOT EXISTS `roles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL UNIQUE,
            `label` VARCHAR(100) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        return $this->createTable($sql);
    }

    public function seed(array $roles): void
    {
        foreach ($roles as $r) {
            // ignore duplicates
            try {
                $this->insert($this->table, [
                    'name' => $r['name'],
                    'label' => $r['label'] ?? null,
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
}
