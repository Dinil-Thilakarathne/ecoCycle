<?php
namespace Models;

class CompanyModel extends BaseModel
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    public function updateProfile(int $companyId, array $data): bool
    {
        if ($companyId <= 0) {
            throw new \InvalidArgumentException('Invalid company id provided.');
        }

        $allowed = ['name', 'email', 'phone', 'address', 'metadata'];
        $updates = array_intersect_key($data, array_flip($allowed));

        if (isset($updates['metadata']) && is_array($updates['metadata'])) {
            $updates['metadata'] = json_encode($updates['metadata'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($updates)) {
            return true;
        }

        $setParts = [];
        $params = [];

        foreach ($updates as $column => $value) {
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }

        $params[] = $companyId;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE {$this->primaryKey} = ? AND type = 'company'";

        return $this->db->query($sql, $params);
    }

    public function deleteProfile(int $companyId): bool
    {
        if ($companyId <= 0) {
            throw new \InvalidArgumentException('Invalid company id provided.');
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ? AND type = 'company'";

        return $this->db->query($sql, [$companyId]);
    }

    public function createBankDetails(int $userId, array $data): bool
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
