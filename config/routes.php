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

$router->get('/api/vehicles', 'Controllers\Api\VehicleController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/vehicles/{id}', 'Controllers\Api\VehicleController@show', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/vehicles', 'Controllers\Api\VehicleController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->put('/api/vehicles/{id}', 'Controllers\Api\VehicleController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->delete('/api/vehicles/{id}', 'Controllers\Api\VehicleController@destroy', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/bidding/rounds', 'Controllers\Api\BiddingController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/bidding/rounds', 'Controllers\Api\BiddingController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/bidding/rounds/{id}', 'Controllers\Api\BiddingController@show', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->put('/api/bidding/rounds/{id}', 'Controllers\Api\BiddingController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->delete('/api/bidding/rounds/{id}', 'Controllers\Api\BiddingController@destroy', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/bidding/approve', 'Controllers\Api\BiddingController@approve', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/bidding/reject', 'Controllers\Api\BiddingController@reject', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/company/bids', 'Controllers\Api\Company\BidController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CompanyOnly',
]);

$router->put('/api/company/bids/{id}', 'Controllers\Api\Company\BidController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CompanyOnly',
]);

$router->delete('/api/company/bids/{id}', 'Controllers\Api\Company\BidController@destroy', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CompanyOnly',
]);

// Customer pickup request APIs
$router->get('/api/customer/pickup-requests', 'Controllers\Api\Customer\PickupRequestController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

$router->post('/api/customer/pickup-requests', 'Controllers\Api\Customer\PickupRequestController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

$router->put('/api/customer/pickup-requests/{id}', 'Controllers\Api\Customer\PickupRequestController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

$router->delete('/api/customer/pickup-requests/{id}', 'Controllers\Api\Customer\PickupRequestController@destroy', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

$router->put('/api/collector/pickup-requests/{id}/status', 'Controllers\Api\Collector\PickupRequestController@updateStatus', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

$router->post('/api/payments', 'Controllers\Api\PaymentController@store', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/payments', 'Controllers\Api\PaymentController@showAll', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/payments/{id}', 'Controllers\Api\PaymentController@show', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/customer/payments', 'Controllers\Api\PaymentController@customerPayments', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

$router->get('/api/company/invoices', 'Controllers\Api\PaymentController@companyInvoices', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CompanyOnly',
]);

// Root redirect to navigation page for development
$router->get('/', 'Controllers\NavigationController@index');

// Dashboard navigation page
$router->get('/dashboards', 'Controllers\NavigationController@index');

// Authentication routes (Web - returns HTML)
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/forget-password', 'AuthController@showForgetPassword');

// API Authentication routes (Returns JSON only)
$router->post('/api/auth/login', 'Controllers\Api\AuthController@login');
$router->post('/api/auth/logout', 'Controllers\Api\AuthController@logout', [
    'Middleware\AuthMiddleware'
]);
$router->post('/api/auth/register', 'Controllers\Api\AuthController@register');
$router->get('/api/auth/me', 'Controllers\Api\AuthController@me', [
    'Middleware\AuthMiddleware'
]);

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


// New page routes with correct paths

// User profile route (example with parameters)
$router->get('/user/{username}', 'PageController@userProfile');

// Customer profile management
$router->post('/customer/profile', 'Controllers\Customer\ProfileController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CustomerOnly'
]);

// Collector profile management
$router->post('/collector/profile', 'Controllers\Collector\ProfileController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CollectorOnly'
]);

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
            'diagnostic' => '/diagnostic',
            'api_debug_routes' => '/api/debug/routes'
        ]
    ]);
});

// Debug route to list all registered routes
$router->get('/api/debug/routes', function () use ($router) {
    if (class_exists('Core\Router') && method_exists($router, 'getRoutes')) {
         $routes = $router->getRoutes();
    } else {
         $routes = [];
    }
   
    return view('debug/routes', ['routes' => $routes]);
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

// Lightweight DB connectivity check (no exception bubbling)
$router->get('/debug/db/ping.json', function () {
    $result = \Core\Database::ping();
    return response()->json($result + ['timestamp' => date('c')]);
});

// ---------------------------------------------
// Development bypass routes (local dev only)
// ---------------------------------------------
$router->get('/dev/login/{role}', function (\Core\Http\Request $request) {
    $path = trim($request->getPath(), '/');
    $parts = explode('/', $path);
    $role = $parts[2] ?? null;

    $validRoles = ['admin', 'customer', 'collector', 'company'];
    if (!$role || !in_array($role, $validRoles)) {
        return response("Invalid role: {$role}", 400);
    }

    // Set fake session for dev
    $userData = [
        'id' => 999,
        'name' => "Dev {$role}",
        'email' => "dev@{$role}.com",
        'role' => $role
    ];

    session()->login(999, $userData);
    session()->put('user_name', $userData['name']);
    session()->put('user_email', $userData['email']);
    session()->put('user_role', $userData['role']);

    // Redirect to dashboard
    return redirect("/{$role}");
});

$router->post('/api/company/profile/update', 'Controllers\Api\Company\CompanyProfileController@updateProfile', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware',
    'Middleware\Roles\CompanyOnly'
]);

$router->get('/api/company/profile/delete', 'Controllers\Api\Company\CompanyProfileController@deleteProfile', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware',
    'Middleware\Roles\CompanyOnly'
]);

$router->post('/api/company/profile/bankDetails', 'Controllers\Api\Company\CompanyProfileController@updateteBankDetails', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware',
    'Middleware\Roles\CompanyOnly'
]);

$router->post('/api/company/profile/password', 'Controllers\Api\Company\CompanyProfileController@changePassword', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware',
    'Middleware\Roles\CompanyOnly'
]);
// ---------------------------------------------
// Analytics & Reporting API Routes
// ---------------------------------------------

// Role-specific analytics dashboard
$router->get('/api/analytics/dashboard', 'Controllers\Api\AnalyticsController@dashboard', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Waste collection report
$router->get('/api/reports/waste-collection', 'Controllers\Api\ReportingController@wasteCollection', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Bidding analytics report
$router->get('/api/reports/bidding', 'Controllers\Api\ReportingController@bidding', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Revenue reports
$router->get('/api/reports/revenue', 'Controllers\Api\ReportingController@revenue', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);


// Export report (CSV / PDF)
$router->post('/api/reports/export', 'Controllers\Api\ReportingController@export', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// ---------------------------------------------
// Waste Management API Routes
// ---------------------------------------------

// Waste Category Management Routes
$router->get('/api/waste-categories', 'Controllers\Api\WasteManagementController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/waste-categories', 'Controllers\Api\WasteManagementController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);
// notification routes 

// example
$router->get('/api/notifications', 'Controllers\Api\NotificationController@index', [
    'Middleware\AuthMiddleware',
]);

$router->post('/api/notifications', 'Controllers\Api\NotificationController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->put('/api/notifications/{id}/read', 'Controllers\Api\NotificationController@markAsRead', [
    'Middleware\AuthMiddleware',
]);

$router->put('/api/notifications/read-all', 'Controllers\Api\NotificationController@markAllAsRead', [
    'Middleware\AuthMiddleware',
]);

$router->get('/api/notifications/unread-count', 'Controllers\Api\NotificationController@unreadCount', [
    'Middleware\AuthMiddleware',
]);

$router->put('/api/waste-categories/{id}', 'Controllers\Api\WasteManagementController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->delete('/api/waste-categories/{id}', 'Controllers\Api\WasteManagementController@destroy', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/waste-categories/pricing', 'Controllers\Api\WasteManagementController@pricing', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);