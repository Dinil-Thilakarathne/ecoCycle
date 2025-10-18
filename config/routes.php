<?php

/**
 * Application Routes - Next.js Style
 * 
 * Define all application routes here.
 * Routes are automatically loaded by the framework.
 */

use Core\PageRouter;
use EcoCycle\Core\Navigation\RouteConfig;

$router = app('router');

// Auto-register page routes (like Next.js pages/ folder)
PageRouter::registerPageRoutes($router);

// Auto-register API routes (like Next.js api/ folder)  
PageRouter::registerApiRoutes($router);

// Root redirect to navigation page for development
$router->get('/', 'Controllers\NavigationController@index');

// Dashboard navigation page
$router->get('/dashboards', 'Controllers\NavigationController@index');

// Authentication routes
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');

// Auto-register all dashboard routes based on NavigationConfig
// This ensures consistency between navigation and routes
RouteConfig::registerDashboardRoutes($router);

// Development and utility routes
$router->get('/routes/list', function () use ($router) {
    $routes = RouteConfig::getAllDashboardRoutes();
    return response()->json([
        'status' => 'success',
        'message' => 'All registered dashboard routes',
        'routes' => $routes
    ]);
});

$router->get('/routes/validate', function () {
    $missing = RouteConfig::validateRoutes();
    return response()->json([
        'status' => empty($missing) ? 'success' : 'warning',
        'message' => empty($missing) ? 'All routes are valid' : 'Some routes are missing controller methods',
        'missing_methods' => $missing,
        'method_stubs' => empty($missing) ? [] : RouteConfig::generateMissingMethodStubs()
    ]);
});

// Legacy routes for backward compatibility
$router->get('/legacy', 'HomeController@index');
$router->get('/legacy/about', 'HomeController@about');

$router->get('/example', 'ExampleController@index');
$router->post('/example', 'ExampleController@store');
$router->get('/example/{id}', 'ExampleController@show');


// New page routes with correct paths

// User profile route (example with parameters)
$router->get('/user/{username}', 'PageController@userProfile');

// Error handling routes
$router->get('/404', function () {
    return response('Page Not Found', 404);
});

$router->get('/403', function () {
    return response('Forbidden', 403);
});

$router->get('/500', function () {
    return response('Internal Server Error', 500);
});

// Test route to check if system is working
$router->get('/test', function () {
    $userTypes = EcoCycle\Core\Navigation\NavigationConfig::getAvailableUserTypes();
    $dashboards = [];

    foreach ($userTypes as $userType) {
        $dashboards[$userType] = "/{$userType}";
    }

    return response()->json([
        'status' => 'success',
        'message' => 'EcoCycle Framework is working!',
        'navigation_system' => 'Centralized NavigationConfig',
        'auto_routes' => 'Enabled via RouteConfig',
        'dashboards' => $dashboards,
        'auth' => [
            'login' => '/login',
            'register' => '/register'
        ],
        'utilities' => [
            'routes_list' => '/routes/list',
            'routes_validate' => '/routes/validate',
            'diagnostic' => '/diagnostic'
        ]
    ]);
});

// Route diagnostic page
$router->get('/diagnostic', function () {
    ob_start();
    include base_path('public/diagnostic.php');
    $content = ob_get_clean();

    return response()->setContent($content)->setHeader('Content-Type', 'text/html');
});

// Simple development test routes (GET + POST)
$testHandler = function () {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $body = file_get_contents('php://input');
    $parsedBody = null;

    if (!empty($body)) {
        $json = json_decode($body, true);
        $parsedBody = json_last_error() === JSON_ERROR_NONE ? $json : $body;
    }

    return response()->json([
        'status' => 'ok',
        'route' => '/dev/test',
        'method' => $method,
        'query' => $_GET,
        'body' => $parsedBody
    ]);
};

$router->get('/dev/test', function () {
    ob_start();
    include base_path('public/dev_test.php');
    $content = ob_get_clean();

    return response()->setContent($content)->setHeader('Content-Type', 'text/html');
});

$router->post('/dev/test', $testHandler);
