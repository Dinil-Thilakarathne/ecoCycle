<?php

header('Content-Type: application/json');

// Accept only GET requests for listing notifications.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Use GET.',
    ]);
    exit;
}

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing or invalid user_id.',
    ]);
    exit;
}

$conn = require __DIR__ . '/../../db/db.php';

$stmt = $conn->prepare('SELECT id, user_id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC');

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement: ' . $conn->error,
    ]);
    exit;
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => $notifications,
]);

$stmt->close();
$conn->close();

