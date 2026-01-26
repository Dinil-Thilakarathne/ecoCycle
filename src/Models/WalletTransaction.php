<?php

namespace Models;

class WalletTransaction extends BaseModel
{
    protected string $table = 'wallet_transactions';

    public function createTableIfNotExists(): bool
    {
        if ($this->db->isPgsql()) {
            $sql = "CREATE TABLE IF NOT EXISTS wallet_transactions (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                amount NUMERIC(12, 2) NOT NULL,
                type VARCHAR(10) NOT NULL CHECK (type IN ('credit', 'debit')),
                source_type VARCHAR(20) NOT NULL CHECK (source_type IN ('pickup', 'payout', 'adjustment')),
                source_id INT NOT NULL,
                balance_after NUMERIC(12, 2) NOT NULL,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_wt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
            );
            CREATE INDEX IF NOT EXISTS idx_wt_user ON wallet_transactions (user_id);
            CREATE INDEX IF NOT EXISTS idx_wt_source ON wallet_transactions (source_type, source_id);";

            return $this->db->query($sql);
        }

        // MySQL / MariaDB
        $sql = "CREATE TABLE IF NOT EXISTS `wallet_transactions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `amount` DECIMAL(12,2) NOT NULL,
            `type` ENUM('credit', 'debit') NOT NULL,
            `source_type` ENUM('pickup', 'payout', 'adjustment') NOT NULL,
            `source_id` INT NOT NULL,
            `balance_after` DECIMAL(12,2) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_wt_user` (`user_id`),
            INDEX `idx_wt_source` (`source_type`, `source_id`),
            CONSTRAINT `fk_wt_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        return $this->createTable($sql);
    }

    /**
     * Log a transaction and update the user's balance.
     * This operation should ideally be wrapped in a database transaction for safety,
     * but we will handle the logic sequentially here for now.
     */
    public function logTransaction(int $userId, float $amount, string $type, string $sourceType, int $sourceId, string $description = ''): array
    {
        // 1. Get current user balance to calculate balance_after
        // Using a lock would be better, but standard select for now
        $userModel = new User($this->db);
        $user = $userModel->findById($userId);

        if (!$user) {
            throw new \RuntimeException("User not found for ID: $userId");
        }

        $currentBalance = (float) ($user['totalEarnings'] ?? 0.00);

        // Validation: Ensure debit doesn't drop below zero if that's a rule (optional, skipping for now)

        // 2. Calculate new balance
        // If type is debit, amount should ideally be passed as positive, but we handle the sign logic here.
        // Assuming 'amount' passed is always positive magnitude.
        $netAmount = ($type === 'debit') ? -abs($amount) : abs($amount);
        $newBalance = $currentBalance + $netAmount;

        // 3. Insert specific transaction record
        $data = [
            'user_id' => $userId,
            'amount' => $netAmount,
            'type' => $type,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'balance_after' => $newBalance,
            'description' => $description
        ];

        $insertedId = $this->insert($this->table, $data);
        if (!$insertedId) {
            throw new \RuntimeException("Failed to log wallet transaction.");
        }

        // 4. Update User's 'total_earnings' (Wallet Balance)
        // We use direct query to set it to expected value to match ledger
        // NOTE: In the User model, 'total_earnings' is the column name for wallet balance.
        $updateSql = "UPDATE users SET total_earnings = ? WHERE id = ?";
        $this->db->query($updateSql, [$newBalance, $userId]);

        return $this->find((int) $insertedId) ?: [];
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return $row ?: null;
    }

    public function getBalance(int $userId): float
    {
        $row = $this->db->fetch(
            "SELECT balance_after FROM {$this->table} WHERE user_id = ? ORDER BY id DESC LIMIT 1",
            [$userId]
        );
        return $row ? (float) $row['balance_after'] : 0.00;
    }

    public function getHistory(int $userId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
