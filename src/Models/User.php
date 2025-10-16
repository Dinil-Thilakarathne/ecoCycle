<?php

namespace Models;

use Core\Database;

class User
{
    protected Database $db;
    protected string $table = 'users';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: new Database();
    }

    public function createTableIfNotExists(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `type` ENUM('customer','company','collector','admin') NOT NULL DEFAULT 'customer',
            `name` VARCHAR(255) DEFAULT NULL,
            `username` VARCHAR(100) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `profile_image_path` VARCHAR(255) DEFAULT NULL,
            `password_hash` VARCHAR(255) DEFAULT NULL,
            `role_id` INT DEFAULT NULL,
            `vehicle_id` INT DEFAULT NULL,
            `status` VARCHAR(32) DEFAULT 'active',
            `total_pickups` INT DEFAULT 0,
            `total_earnings` DECIMAL(12,2) DEFAULT 0.00,
            `total_bids` INT DEFAULT 0,
            `total_purchases` INT DEFAULT 0,
            `metadata` JSON DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY `users_email_unique` (`email`),
            INDEX `idx_users_type` (`type`),
            INDEX `idx_users_status` (`status`),
            INDEX `idx_users_role` (`role_id`),
            INDEX `idx_users_vehicle` (`vehicle_id`),
            FOREIGN KEY (`role_id`) REFERENCES roles(id) ON DELETE SET NULL ON UPDATE CASCADE,
            FOREIGN KEY (`vehicle_id`) REFERENCES vehicles(id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        return $this->db->query($sql);
    }

    public function createUser(array $data): int|false
    {
        $password = $data['password'] ?? null;
        if ($password) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            unset($data['password']);
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE);
        }

        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = 'INSERT INTO users (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        $ok = $this->db->query($sql, array_values($data));
        return $ok ? $this->db->lastInsertId() : false;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->fetch(
            "SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1",
            [$id]
        );

        if (!$row) {
            return null;
        }

        if (array_key_exists('metadata', $row) && is_string($row['metadata'])) {
            $decoded = json_decode($row['metadata'], true);
            $row['metadata'] = is_array($decoded) ? $decoded : [];
        }

        return $row;
    }

    public function updateUser(int $id, array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE);
        }

        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $params[] = $value;
        }

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = ? LIMIT 1';

        return $this->db->query($sql, $params);
    }

    public function updateProfileImagePath(int $id, ?string $path): bool
    {
        return $this->updateUser($id, ['profile_image_path' => $path]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = ?';
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $row = $this->db->fetch($sql, $params);
        return (bool) $row;
    }

    public function findByEmail(string $email): array|null
    {
        $row = $this->db->fetch("SELECT u.*, r.name AS role_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.email = ? LIMIT 1", [$email]);
        return $row ?: null;
    }

    public function findByUsername(string $username): array|null
    {
        $row = $this->db->fetch("SELECT u.*, r.name AS role_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.username = ? LIMIT 1", [$username]);
        return $row ?: null;
    }

    public function verifyPassword(array $user, string $password): bool
    {
        // Assuming password_hash stored in password_hash column
        if (!isset($user['password_hash'])) {
            return false;
        }
        // For now allow fallback to plain (dev/demo) if hash not starting with $2y$ etc.
        if (!preg_match('/^\$2y\$/', $user['password_hash'])) {
            return hash_equals($user['password_hash'], $password);
        }
        return password_verify($password, $user['password_hash']);
    }
}
