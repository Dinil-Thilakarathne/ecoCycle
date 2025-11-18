<?php

header('Content-Type: application/json');

// Allow only POST requests to create notifications.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Use POST.',
    ]);
    exit;
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate required fields.
if (!$userId || $title === '' || $message === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: user_id, title, message.',
    ]);
    exit;
}

// Establish a database connection.
$conn = require __DIR__ . '/../../db/db.php';

$stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement: ' . $conn->error,
    ]);
    exit;
}

$stmt->bind_param('iss', $userId, $title, $message);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Notification created',
        'notification_id' => $stmt->insert_id,
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create notification: ' . $stmt->error,
    ]);
}

$stmt->close();
$conn->close();

