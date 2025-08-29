<?php

/**
 * Application Routes - Next.js Style
 * 
 * Define all application routes here.
 * Routes are automatically loaded by the framework.
 */

use Core\PageRouter;
use EcoCycle\Core\Navigation\RouteConfig;
use Core\Database;
use Core\Http\Response;

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

// ---------------------------------------------
// Database debug routes (non-production helpers)
// ---------------------------------------------
$router->get('/debug/db/users.json', function () {
    $db = new Database();
    $users = $db->fetchAll("SELECT u.id, u.email, u.username, r.name AS role, u.status, u.created_at FROM users u INNER JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC LIMIT 100");
    return response()->json([
        'count' => count($users),
        'data' => $users,
    ]);
});

$router->get('/debug/db/roles.json', function () {
    $db = new Database();
    $roles = $db->fetchAll("SELECT * FROM roles ORDER BY id");
    return response()->json($roles);
});

$router->get('/debug/db/users', function () {
    $db = new Database();
    $users = $db->fetchAll("SELECT u.id, u.email, u.username, r.name AS role, u.status, u.created_at FROM users u INNER JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC LIMIT 100");
    ob_start();
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Users Debug</title></head><body>';
    echo '<h1>Users (latest 100)</h1>';
    echo '<p><a href="/debug/db/users.json">JSON</a> | <a href="/debug/db/roles.json">Roles JSON</a></p>';
    if (!$users) {
        echo '<p>No users found.</p>';
    } else {
        echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:monospace;font-size:14px;">';
        echo '<tr><th>ID</th><th>Email</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th></tr>';
        foreach ($users as $u) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($u['id']) . '</td>';
            echo '<td>' . htmlspecialchars($u['email']) . '</td>';
            echo '<td>' . htmlspecialchars($u['username']) . '</td>';
            echo '<td>' . htmlspecialchars($u['role']) . '</td>';
            echo '<td>' . htmlspecialchars($u['status']) . '</td>';
            echo '<td>' . htmlspecialchars($u['created_at']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    echo '</body></html>';
    $html = ob_get_clean();
    $resp = new Response();
    $resp->setHeader('Content-Type', 'text/html');
    $resp->setContent($html);
    return $resp;
});
