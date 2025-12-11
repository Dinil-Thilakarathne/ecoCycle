<?php
// Verify Notification API Script

// Custom autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'EcoCycle\\') === 0) {
        $class = substr($class, 9); // Remove prefix
    }
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $path = __DIR__ . '/src/' . $file . '.php';
    if (file_exists($path)) {
        require_once $path;
    } else {
        // Fallback for non-namespaced or root Core classes
         $path = __DIR__ . '/src/' . $file . '.php';
         if (file_exists($path)) { require_once $path; }
    }
});

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

use Core\Application;
use Core\Http\Request;
use Core\Database;
use Controllers\Api\NotificationController;
use Models\Notification;

// Mock Session
class MockSession {
    private $data = [];
    public function has($key) { return isset($this->data[$key]); }
    public function get($key, $default = null) { return $this->data[$key] ?? $default; }
    public function put($key, $value) { $this->data[$key] = $value; }
    public function getToken() { return 'mock_token'; }
    public function getFlash($key, $default = []) { return $default; }
}

// Mock Database
class MockDatabase extends Database {
    public function __construct() {} // Override constructor to avoid connection
    public function isPgsql(): bool { return false; }
    public function fetchAll(string $sql, array $params = []): array {
        // Mock data for notifications
        // We simulate a join result where 'status' is calculated
        return [
            [
                'id' => 1,
                'type' => 'info',
                'title' => 'Test Notification',
                'message' => 'This is a test',
                'created_at' => '2023-01-01 12:00:00',
                'sent_at' => null,
                'status' => 'pending', // Default from DB
                'recipients' => null,
                'recipient_group' => 'all'
            ]
        ];
    }
    public function fetch(string $sql, array $params = []): array|false {
        if (strpos($sql, 'COUNT(*)') !== false) {
            return ['count' => 5];
        }
        return false;
    }
    public function query(string $sql, array $params = []): bool {
        // Mock successful query execution
        echo "DB Query Executed: " . substr($sql, 0, 50) . "...\n";
        return true;
    }
}

try {
    $app = new Application();
    $mockSession = new MockSession();
    
    // Bind mock session
    $app->container()->singleton('session', function() use ($mockSession) {
        return $mockSession;
    });

    // Create Mock Model with Mock DB
    $mockDb = new MockDatabase();
    $mockModel = new Notification($mockDb);
    
    // Create Controller with Mock Model
    $controller = new NotificationController($mockModel);

    // Mock Request
    $mockRequest = new class extends Request {
        public function __construct() {} // Override to avoid parent constructor
        public function input($key = null, $default = null) {
            if ($key === 'limit') return 5;
            return $default;
        }
    };

    // Test Mark as Read
    echo "Testing Mark as Read...\n";
    $mockSession->put('user_id', 2);
    $mockSession->put('user_role', 'customer');

    $response = $controller->markAsRead(1);
    $content = json_decode($response->getContent(), true);

    if (isset($content['success']) && $content['success'] === true) {
        echo "SUCCESS: Notification marked as read.\n";
    } else {
        echo "FAILURE: Could not mark as read.\n";
        print_r($content);
    }

    // Test Unread Count
    echo "\nTesting Unread Count...\n";
    $response = $controller->unreadCount();
    $content = json_decode($response->getContent(), true);

    if (isset($content['count'])) {
        echo "SUCCESS: Unread count retrieved: " . $content['count'] . "\n";
    } else {
        echo "FAILURE: Could not get unread count.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
