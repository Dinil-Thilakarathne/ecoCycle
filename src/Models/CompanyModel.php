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
}
