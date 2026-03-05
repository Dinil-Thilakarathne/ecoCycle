<?php

namespace Models;

use Core\Database;

/**
 * Basic BaseModel utility for simple table operations and seeding
 */
class BaseModel
{
    protected Database $db;
    protected string $table = '';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: new Database();
    }

    protected function tableExists(?string $table = null): bool
    {
        $table = $table ?: $this->table;
        if ($table === '') {
            return false;
        }

        if ($this->db->isPgsql()) {
            try {
                $schema = 'public';
                $name = $table;
                if (str_contains($table, '.')) {
                    [$schema, $name] = explode('.', $table, 2);
                }

                $row = $this->db->fetch(
                    'SELECT EXISTS (
                        SELECT 1
                        FROM information_schema.tables
                        WHERE table_schema = ? AND table_name = ?
                    ) AS exists_flag',
                    [$schema, $name]
                );

                if (!$row) {
                    return false;
                }

                return $this->toBoolean($row['exists_flag'] ?? false);
            } catch (\Throwable $e) {
                return false;
            }
        }

        $sql = "SHOW TABLES LIKE ?";
        try {
            $res = $this->db->fetchAll($sql, [$table]);
            return !empty($res);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 't', 'yes', 'y'], true);
        }

        return false;
    }

    public function getDb(): \Core\Database
    {
        return $this->db;
    }

    protected function createTable(string $sql): bool
    {
        return $this->db->query($sql);
    }

    protected function insert(string $table, array $data): int|false
    {
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        $params = array_values($data);

        if ($this->db->isPgsql()) {
            $row = $this->db->fetch($sql . ' RETURNING id', $params);
            if (!$row || !array_key_exists('id', $row)) {
                return false;
            }
            return (int) $row['id'];
        }

        $ok = $this->db->query($sql, $params);
        if (!$ok) {
            return false;
        }

        $inserted = $this->db->lastInsertId();
        return $inserted !== false ? (int) $inserted : false;
    }
}
