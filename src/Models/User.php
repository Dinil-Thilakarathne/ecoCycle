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
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `username` VARCHAR(100) DEFAULT NULL,
            `password_hash` VARCHAR(255) DEFAULT NULL,
            `role_id` INT DEFAULT NULL,
            `status` VARCHAR(30) DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
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

        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = 'INSERT INTO users (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        $ok = $this->db->query($sql, array_values($data));
        return $ok ? $this->db->lastInsertId() : false;
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
