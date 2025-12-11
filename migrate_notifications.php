<?php
// Migration script to create notification_reads table

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
use Models\Notification;

try {
    $app = new Application();
    
    echo "Creating notification_reads table...\n";
    $notification = new Notification();
    if ($notification->createTableIfNotExists()) {
        echo "SUCCESS: Table created or already exists.\n";
    } else {
        echo "FAILURE: Could not create table.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
