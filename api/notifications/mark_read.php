<?php

header('Content-Type: application/json');

// Restrict to POST requests for marking notifications.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Use POST.',
    ]);
    exit;
}

$notificationId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$notificationId) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing or invalid notification id.',
    ]);
    exit;
}

$conn = require __DIR__ . '/../../db/db.php';

$stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement: ' . $conn->error,
    ]);
    exit;
}

$stmt->bind_param('i', $notificationId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Notification marked as read',
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Notification not found or already read.',
    ]);
}

$stmt->close();
$conn->close();

