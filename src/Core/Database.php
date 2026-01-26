<?php

namespace Core;

/**
 * Lightweight Database wrapper around PDO.
 * Adapts to the config/database.php structure (connections array + default).
 */
class Database
{
    private string $driver = 'mysql';
    private string $host;
    private string $port = '3306';
    private ?string $socket = null;
    private string $db;
    private string $user;
    private string $pass;
    private string $charset = 'utf8mb4';
    private ?\PDO $pdo = null;
    private $stmt;

    public function __construct(?string $connection = null)
    {
        $this->loadConfig($connection);
        $this->connect();
    }

    private function loadConfig(?string $connection = null): void
    {
        // Load full config file
        $config = require __DIR__ . '/../../config/database.php';

        $default = $config['default'] ?? 'mysql';
        $connName = $connection ?: $default;
        $connections = $config['connections'] ?? [];

        if (!isset($connections[$connName])) {
            throw new \RuntimeException("Database connection '{$connName}' not configured.");
        }

        $conn = $connections[$connName];
        $this->driver = $conn['driver'] ?? 'mysql';
        $this->host = $conn['host'] ?? '127.0.0.1';
        $defaultPort = $this->driver === 'pgsql' ? '5432' : '3306';
        $this->port = (string) ($conn['port'] ?? $defaultPort);
        $this->db = $conn['database'] ?? '';
        $this->user = $conn['username'] ?? '';
        $this->pass = $conn['password'] ?? '';
        $this->charset = $conn['charset'] ?? $this->charset;
        $this->socket = $conn['unix_socket'] ?? null;
    }

    private function connect(): void
    {
        $dsn = '';
        switch ($this->driver) {
            case 'mysql':
                if ($this->socket) {
                    $dsn = "mysql:unix_socket={$this->socket};dbname={$this->db};charset={$this->charset}";
                } else {
                    $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset={$this->charset}";
                }
                break;
            case 'pgsql':
                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db}";
                break;
            default:
                throw new \RuntimeException("Unsupported database driver: {$this->driver}");
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new \PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException('DB connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    public function pdo(): \PDO
    {
        if (!$this->pdo) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): bool
    {
        $this->stmt = $this->pdo()->prepare($sql);
        return $this->stmt->execute($params);
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $this->query($sql, $params);
        return $this->stmt->fetchAll();
    }

    public function fetch(string $sql, array $params = []): array|false
    {
        $this->query($sql, $params);
        return $this->stmt->fetch();
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        $this->query($sql, $params);
        return $this->stmt->fetchColumn($column);
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo()->lastInsertId();
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function isPgsql(): bool
    {
        return $this->driver === 'pgsql';
    }

    public function isMysql(): bool
    {
        return $this->driver === 'mysql';
    }

    /**
     * Simple connectivity test.
     * Returns ['ok'=>bool,'error'=>string|null]
     */
    public static function ping(?string $connection = null): array
    {
        try {
            $db = new self($connection);
            // Driver-agnostic lightweight query
            $db->query('SELECT 1');
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}