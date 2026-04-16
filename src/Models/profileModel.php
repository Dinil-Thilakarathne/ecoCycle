<?php
namespace Models;

class profileModel extends BaseModel
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    public function updateProfile(int $userId,  array $payload): bool
    {
        if ($userId <=  0) {
            throw new \InvalidArgumentException('Invalid user ID');
        }

        $allowed = ['name', 'email', 'phone', 'address', 'metadata'];
        $updates = array_intersect_key($payload, array_flip($allowed));

        if (isset($updates['metadata']) && is_array($updates['metadata'])) {
            $updates['metadata'] = json_encode($updates['metadata'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($updates)) {
            return true;
        }

        // Build query
        $setParts = [];
        $params   = [];
        $i = 1;

        foreach ($updates as $column => $value) {
            $setParts[] = "$column = ?";
            $params[]   = $value;
            $i++;
        }

        // Only add the user ID for the WHERE clause
        $params[] = $userId;

        $sql = "
        UPDATE users
        SET " . implode(', ', $setParts) . "
        WHERE id = ?
        ";

       
        $stmt = $this->db->query($sql, $params);

        if (!$stmt) {
            throw new \RuntimeException('Failed to update profile for user ID ' . $userId);
        }

        return true;
    }


    public function getUserById(int $id): array
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetch($sql, [$id]) ?? [];
    }


    public function deleteProfile(int $id): bool
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid user id provided.');
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ? ";

        return $this->db->query($sql, [$id]);
    }


    public function updateBankDetails(int $userId, array $data): bool
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid user id provided.');
        }

        $allowed = ['bank_name', 'bank_account_number', 'bank_account_name', 'bank_branch'];

        $setParts = [];
        $params = [];

        foreach ($allowed as $field) {
            if (!empty($data[$field])) {
                $setParts[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($setParts)) {
            throw new \InvalidArgumentException('No valid bank details provided.');
        }

        // Add the user ID for the WHERE condition
        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";

        return $this->db->query($sql, $params);
    }

    public function updatePassword(int $userId, array $data):bool
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid user id provided.');
        }

        $allowed = ['password_hash'];

        $setParts = [];
        $params = [];

        foreach ($allowed as $field) {
            if (!empty($data[$field])) {
                $setParts[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($setParts)) {
            throw new \InvalidArgumentException('No valid password provided.');
        }

        // Add the user ID for the WHERE condition
        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";

        return $this->db->query($sql, $params);
    }

}
