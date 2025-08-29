<?php

namespace Models;

use Core\Database;

class User
{
    protected Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: new Database();
    }

    public function findByEmail(string $email): array|null
    {
        $row = $this->db->fetch("SELECT u.*, r.name AS role_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.email = ? LIMIT 1", [$email]);
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
