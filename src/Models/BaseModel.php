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
        $sql = "SHOW TABLES LIKE ?";
        try {
            $res = $this->db->fetchAll($sql, [$table]);
            return !empty($res);
        } catch (\Throwable $e) {
            return false;
        }
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
        $ok = $this->db->query($sql, array_values($data));
        return $ok ? $this->db->lastInsertId() : false;
    }
}
