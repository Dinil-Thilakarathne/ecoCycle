<?php

/**
 * Simple MySQLi database connection helper.
 * Returns an active mysqli connection that can be reused
 * across all notification API endpoints.
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ecocycle';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error,
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

return $conn;

