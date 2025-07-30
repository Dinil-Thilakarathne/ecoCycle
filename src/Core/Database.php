<?php

namespace Core;

class Database {
    private $host;
    private $db;
    private $user;
    private $pass;
    private $charset;
    private $pdo;
    private $stmt;

    public function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    private function loadConfig() {
        $config = require __DIR__ . '/../../config/database.php';
        $this->host = $config['host'];
        $this->db = $config['dbname'];
        $this->user = $config['user'];
        $this->pass = $config['password'];
        $this->charset = $config['charset'];
    }

    private function connect() {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function query($sql, $params = []) {
        $this->stmt = $this->pdo->prepare($sql);
        return $this->stmt->execute($params);
    }

    public function fetchAll($sql, $params = []) {
        $this->query($sql, $params);
        return $this->stmt->fetchAll();
    }

    public function fetch($sql, $params = []) {
        $this->query($sql, $params);
        return $this->stmt->fetch();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}