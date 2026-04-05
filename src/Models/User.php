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
        if ($this->db->isPgsql()) {
            $tableSql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                type VARCHAR(32) NOT NULL DEFAULT 'customer',
                name VARCHAR(255) DEFAULT NULL,
                username VARCHAR(100) DEFAULT NULL,
                nic VARCHAR(30) DEFAULT NULL,
                email VARCHAR(150) DEFAULT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                address TEXT DEFAULT NULL,
                bank_account_name VARCHAR(255) DEFAULT NULL,
                bank_account_number VARCHAR(100) DEFAULT NULL,
                bank_name VARCHAR(150) DEFAULT NULL,
                bank_branch VARCHAR(150) DEFAULT NULL,
                profile_image_path VARCHAR(255) DEFAULT NULL,
                password_hash VARCHAR(255) DEFAULT NULL,
                role_id INTEGER DEFAULT NULL,
                vehicle_id INTEGER DEFAULT NULL,
                status VARCHAR(32) DEFAULT 'active',
                total_pickups INTEGER DEFAULT 0,
                total_earnings NUMERIC(12, 2) DEFAULT 0.00,
                total_bids INTEGER DEFAULT 0,
                total_purchases INTEGER DEFAULT 0,
                metadata JSONB DEFAULT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT NULL,
                CONSTRAINT users_email_unique UNIQUE (email),
                CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_users_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL ON UPDATE CASCADE
            );
            SQL;

            $created = $this->db->query($tableSql);
            if (!$created) {
                return false;
            }

            $indexes = [
                'CREATE INDEX IF NOT EXISTS idx_users_type ON users (type)',
                'CREATE INDEX IF NOT EXISTS idx_users_status ON users (status)',
                'CREATE INDEX IF NOT EXISTS idx_users_nic ON users (nic)',
                'CREATE INDEX IF NOT EXISTS idx_users_role ON users (role_id)',
                'CREATE INDEX IF NOT EXISTS idx_users_vehicle ON users (vehicle_id)',
            ];

            foreach ($indexes as $stmt) {
                $this->db->query($stmt);
            }

            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `type` ENUM('customer','company','collector','admin') NOT NULL DEFAULT 'customer',
            `name` VARCHAR(255) DEFAULT NULL,
            `username` VARCHAR(100) DEFAULT NULL,
            `nic` VARCHAR(30) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `bank_account_name` VARCHAR(255) DEFAULT NULL,
            `bank_account_number` VARCHAR(100) DEFAULT NULL,
            `bank_name` VARCHAR(150) DEFAULT NULL,
            `bank_branch` VARCHAR(150) DEFAULT NULL,
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
            INDEX `idx_users_nic` (`nic`),
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
        if ($password !== null) {
            if (!is_string($password)) {
                $password = (string) $password;
            }

            $passwordInfo = password_get_info($password);
            $isAlreadyHashed = is_array($passwordInfo) && ($passwordInfo['algo'] ?? 0) !== 0;

            $data['password_hash'] = $isAlreadyHashed ? $password : password_hash($password, PASSWORD_DEFAULT);
            unset($data['password']);
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE);
        }

        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $params = array_values($data);

        if ($this->db->isPgsql()) {
            $row = $this->db->fetch(
                'INSERT INTO users (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ') RETURNING id',
                $params
            );

            return $row && isset($row['id']) ? (int) $row['id'] : false;
        }

        $sql = 'INSERT INTO users (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        $ok = $this->db->query($sql, $params);
        if (!$ok) {
            return false;
        }

        $lastId = $this->db->lastInsertId();
        return $lastId !== false ? (int) $lastId : false;
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

        // Decode metadata if present and normalize keys for view usage
        if (array_key_exists('metadata', $row) && is_string($row['metadata'])) {
            $decoded = json_decode($row['metadata'], true);
            $row['metadata'] = is_array($decoded) ? $decoded : [];
        }

        return $this->normalizeRow($row);
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
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = ?';

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

    public function nicExists(string $nic, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE nic = ?';
        $params = [$nic];

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
        $row = $this->db->fetch("SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.email = ? LIMIT 1", [$email]);
        return $row ?: null;
    }

    public function findByUsername(string $username): array|null
    {
        $row = $this->db->fetch("SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.username = ? LIMIT 1", [$username]);
        return $row ?: null;
    }

    public function findByVehicleId(int $vehicleId): array|null
    {
        $row = $this->db->fetch("SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.vehicle_id = ? LIMIT 1", [$vehicleId]);
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

    public function listByType(?string $type = null, int $limit = 100): array
    {
        $limit = (int) $limit;
        
        $sql = "SELECT u.*, r.name AS role_name";
        
        if ($type === 'customer') {
            $sql .= ", (SELECT COUNT(id) FROM pickup_requests WHERE customer_id = u.id) AS computed_total_pickups";
            $sql .= ", (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE recipient_id = u.id AND type = 'payout' AND status = 'completed') AS computed_total_earnings";
        } elseif ($type === 'collector') {
            $sql .= ", (SELECT COUNT(id) FROM pickup_requests WHERE collector_id = u.id) AS computed_total_pickups";
            if ($this->db->isPgsql()) {
                $sql .= ", (SELECT COUNT(id) FROM pickup_requests WHERE collector_id = u.id AND DATE(created_at) = CURRENT_DATE) AS computed_today_pickups";
            } else {
                $sql .= ", (SELECT COUNT(id) FROM pickup_requests WHERE collector_id = u.id AND DATE(created_at) = CURDATE()) AS computed_today_pickups";
            }
        } elseif ($type === 'company') {
            $sql .= ", (SELECT COUNT(id) FROM payments WHERE recipient_id = u.id AND type = 'payment' AND status = 'completed') AS computed_total_purchases";
        }
        
        $sql .= " FROM users u LEFT JOIN roles r ON r.id = u.role_id";

        if ($type === null) {
            $sql .= " ORDER BY u.id DESC LIMIT {$limit}";
            $rows = $this->db->fetchAll($sql);
        } else {
            $sql .= " WHERE u.type = ? ORDER BY u.id DESC LIMIT {$limit}";
            $rows = $this->db->fetchAll($sql, [$type]);
        }
        
        return array_map(fn($r) => $this->normalizeRow($r), $rows ?: []);
    }

    public function countByType(string $type, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM users WHERE type = ?';
        $params = [$type];
        if ($status !== null) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $row = $this->db->fetch($sql, $params);
        return (int) ($row['total'] ?? 0);
    }

    public function sumColumnForType(string $column, string $type): float
    {
        $allowed = ['total_pickups', 'total_earnings', 'total_bids', 'total_purchases'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported column requested.');
        }

        $row = $this->db->fetch("SELECT SUM({$column}) AS total FROM users WHERE type = ?", [$type]);
        return isset($row['total']) ? (float) $row['total'] : 0.0;
    }

    /**
     * Normalize a DB row into the shape expected by the admin views.
     * - decode metadata JSON
     * - map snake_case columns to camelCase aliases used in views
     */
    private function normalizeRow(array $row): array
    {
        // Ensure metadata is decoded
        if (array_key_exists('metadata', $row) && is_string($row['metadata'])) {
            $decoded = json_decode($row['metadata'], true);
            $row['metadata'] = is_array($decoded) ? $decoded : [];
        }

        // Map common snake_case DB fields to camelCase keys used by views
        if (array_key_exists('computed_total_pickups', $row)) {
            $row['totalPickups'] = (int) $row['computed_total_pickups'];
        } elseif (array_key_exists('total_pickups', $row)) {
            $row['totalPickups'] = (int) $row['total_pickups'];
            if (!isset($row['todayPickups']) && ($row['type'] ?? null) === 'collector') {
                // Collectors page expects today's pickups; seed data currently stores it in total_pickups
                $row['todayPickups'] = (int) $row['total_pickups'];
            }
        }
        
        if (array_key_exists('computed_today_pickups', $row)) {
            $row['todayPickups'] = (int) $row['computed_today_pickups'];
        }

        if (array_key_exists('computed_total_earnings', $row)) {
            $row['totalEarnings'] = (float) $row['computed_total_earnings'];
        } elseif (array_key_exists('total_earnings', $row)) {
            // keep numeric float
            $row['totalEarnings'] = (float) $row['total_earnings'];
        }
        
        if (array_key_exists('total_bids', $row)) {
            $row['totalBids'] = (int) $row['total_bids'];
        }
        
        if (array_key_exists('computed_total_purchases', $row)) {
            $row['totalPurchases'] = (int) $row['computed_total_purchases'];
        } elseif (array_key_exists('total_purchases', $row)) {
            $row['totalPurchases'] = (int) $row['total_purchases'];
        }
        
        if (array_key_exists('vehicle_id', $row)) {
            $row['vehicleId'] = $row['vehicle_id'];
        }

        // Normalize some commonly-used names
        if (array_key_exists('profile_image_path', $row)) {
            $row['profileImagePath'] = $row['profile_image_path'];
        }

        if (array_key_exists('bank_account_name', $row)) {
            $row['bankAccountName'] = $row['bank_account_name'];
        }
        if (array_key_exists('bank_account_number', $row)) {
            $row['bankAccountNumber'] = $row['bank_account_number'];
        }
        if (array_key_exists('bank_name', $row)) {
            $row['bankName'] = $row['bank_name'];
        }
        if (array_key_exists('bank_branch', $row)) {
            $row['bankBranch'] = $row['bank_branch'];
        }

        // Keep id, name, email, phone as-is (existing column names match view expectations)

        // If metadata contains legacy fields (from seed) prefer those where applicable
        if (!empty($row['metadata']) && is_array($row['metadata'])) {
            if (!isset($row['todayPickups']) && isset($row['metadata']['todayPickups'])) {
                $row['todayPickups'] = (int) $row['metadata']['todayPickups'];
            }
            if (!isset($row['vehicle']) && isset($row['metadata']['vehicle'])) {
                $row['vehicle'] = $row['metadata']['vehicle'];
            }
            if (!isset($row['vehicleId']) && isset($row['metadata']['vehicleId'])) {
                $row['vehicleId'] = $row['metadata']['vehicleId'];
            }
            if (!isset($row['totalPickups']) && isset($row['metadata']['totalPickups'])) {
                $row['totalPickups'] = (int) $row['metadata']['totalPickups'];
            }
            if (!isset($row['totalEarnings']) && isset($row['metadata']['totalEarnings'])) {
                $row['totalEarnings'] = (float) $row['metadata']['totalEarnings'];
            }
        }

        return $row;
    }

    /**
     * Delete a user by id. This performs a hard delete.
     * If you prefer soft-delete, change to update the status column instead.
     */
    public function deleteUser(int $id): bool
    {
        return $this->db->query('DELETE FROM users WHERE id = ?', [$id]);
    }

    /**
     * Update status for a user (approve/suspend etc.)
     */
    public function setStatus(int $id, string $status): bool
    {
        return $this->updateUser($id, ['status' => $status]);
    }

    /**
     * Find all users
     */
    public function findAll(): array
    {
        return $this->db->fetchAll('SELECT * FROM users');
    }

    /**
     * Find user by email verification token
     *
     * @param string $token Verification token
     * @return array|null User data if found
     */
    public function findByVerificationToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email_verification_token = ? LIMIT 1";
        $user = $this->db->fetch($sql, [$token]);

        return $user ?: null;
    }

    /**
     * Mark user's email as verified
     *
     * @param int $userId User ID
     * @return bool Success status
     */
    public function markEmailAsVerified(int $userId): bool
    {
        $sql = "UPDATE {$this->table} 
                SET email_verified = TRUE, 
                    email_verification_token = NULL,
                    email_verification_sent_at = NULL
                WHERE id = ?";

        $this->db->query($sql, [$userId]);
        return true;
    }

    /**
     * Update user's password
     *
     * @param int $userId User ID
     * @param string $newPassword New password (will be hashed)
     * @return bool Success status
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE {$this->table} SET password_hash = ? WHERE id = ?";
        $this->db->query($sql, [$hashedPassword, $userId]);

        return true;
    }
}
