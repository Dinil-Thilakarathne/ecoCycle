<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap the application
$app = require_once __DIR__ . '/../src/Core/Application.php';

use Models\Notification;
use Controllers\Api\NotificationController;

echo "Starting Notification API Verification...\n";

// Mock session/auth
// We need to simulate a logged in user for the controller to work.
// Since we are running CLI, we might need to manually set session data if the controller relies on it.
// The BaseController uses $this->auth() which calls $this->session->userData().

// Let's try to use the model directly first to verify DB interactions.
echo "1. Testing Model: Create Notification...\n";
$model = new Notification();
$data = [
    'type' => 'info',
    'title' => 'Test Notification',
    'message' => 'This is a test message',
    'recipient_group' => 'users',
    'recipients' => ['user:999'], // Assuming user 999 exists or we just use it for testing
    'status' => 'pending'
];

try {
    $id = $model->create($data);
    echo "   [PASS] Notification created with ID: $id\n";
} catch (\Throwable $e) {
    echo "   [FAIL] Failed to create notification: " . $e->getMessage() . "\n";
    exit(1);
}

echo "2. Testing Model: Fetch for User...\n";
$notifications = $model->forUser(999);
$found = false;
foreach ($notifications as $n) {
    if ($n['id'] == $id) {
        $found = true;
        break;
    }
}

if ($found) {
    echo "   [PASS] Created notification found for user 999\n";
} else {
    echo "   [FAIL] Created notification NOT found for user 999\n";
    print_r($notifications);
}

echo "3. Testing Model: Unread Count...\n";
$count = $model->getUnreadCount(999);
if ($count > 0) {
    echo "   [PASS] Unread count is $count (expected > 0)\n";
} else {
    echo "   [FAIL] Unread count is 0 (expected > 0)\n";
}

echo "4. Testing Model: Mark as Read...\n";
$model->markAsRead($id, 999);
$notifications = $model->forUser(999);
$isRead = false;
foreach ($notifications as $n) {
    if ($n['id'] == $id && $n['status'] === 'read') {
        $isRead = true;
        break;
    }
}

if ($isRead) {
    echo "   [PASS] Notification marked as read\n";
} else {
    echo "   [FAIL] Notification status is not 'read'\n";
}

echo "5. Testing Model: Mark All as Read...\n";
// Create another one
$model->create($data);
$model->markAllAsRead(999);
$count = $model->getUnreadCount(999);
if ($count === 0) {
    echo "   [PASS] All notifications marked as read (count is 0)\n";
} else {
    echo "   [FAIL] Unread count is $count after markAllAsRead (expected 0)\n";
}

echo "\nVerification Complete.\n";
