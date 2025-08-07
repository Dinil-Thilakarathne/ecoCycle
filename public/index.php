<?php

// Custom autoloader for our framework
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $path = __DIR__ . '/../src/' . $file . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

// Load environment configuration FIRST
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Define env function early
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

// Load helper functions
require_once __DIR__ . '/../src/helpers.php';

// import auto-reloader 
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Application;

try {
    $app = new Application();

    // Load routes
    require_once __DIR__ . '/../config/routes.php';

    // Run the application
    $app->run();
} catch (Exception $e) {
    // Simple error handling for development
    echo "<h1>Application Error</h1>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}