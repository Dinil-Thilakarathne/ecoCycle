<?php
// Verify Routes View Script

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

// Mock Response class if needed, but we rely on helper functions
// We want to capture the output of the route handler

try {
    $app = new Application();
    
    // Load routes
    require_once __DIR__ . '/config/routes.php';

    $router = app('router');
    $routes = $router->getRoutes();

    // Find our route
    $targetRoute = null;
    foreach ($routes as $route) {
        if ($route['path'] === '/api/debug/routes') {
            $targetRoute = $route;
            break;
        }
    }
    
    if (!$targetRoute) {
        echo "FAILURE: Route not found.\n";
        exit(1);
    }
    
    // Execute the action
    if (is_callable($targetRoute['action'])) {
        $response = call_user_func($targetRoute['action']);
        $content = $response->getContent();
        
        if (strpos($content, '<!DOCTYPE html>') !== false && strpos($content, 'Registered Routes') !== false) {
             echo "SUCCESS: Route returned HTML content.\n";
             echo "Snippet: " . substr(strip_tags($content), 0, 50) . "...\n";
        } else {
            echo "FAILURE: Route did not return expected HTML.\n";
            echo "Content: " . substr($content, 0, 100) . "...\n";
            exit(1);
        }
    } else {
        echo "FAILURE: Route action is not a closure.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
