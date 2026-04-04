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

$router->get('/api/vehicles/available', 'Controllers\Api\VehicleController@listAvailable', [
    'Middleware\AuthMiddleware',
]);

// ---------------------------------------------
// Collector Availability Management (New)
// ---------------------------------------------

// Collector updates their own daily availability
$router->post('/api/collector/availability', 'Controllers\Api\CollectorAvailabilityController@updateMyAvailability', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Get collector's own availability status
$router->get('/api/collector/availability', 'Controllers\Api\CollectorAvailabilityController@getMyStatus', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Get collector's availability history
$router->get('/api/collector/availability/history', 'Controllers\Api\CollectorAvailabilityController@getMyHistory', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Admin: Get all collectors' availability for today
$router->get('/api/admin/collectors/availability', 'Controllers\Api\CollectorAvailabilityController@getTodayAvailability', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Admin: Check specific collector's availability
$router->get('/api/admin/collectors/availability/{id}', 'Controllers\Api\CollectorAvailabilityController@checkCollectorAvailability', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Admin: Reset daily availability (can also be called by cron)
$router->post('/api/admin/vehicles/daily-reset', 'Controllers\Api\CollectorAvailabilityController@resetDailyAvailability', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// ---------------------------------------------
// DEPRECATED: Old vehicle assignment endpoints
// These will be removed in a future version
// Use collector availability endpoints instead
// ---------------------------------------------

$router->post('/api/vehicles/assign-self', 'Controllers\Api\VehicleController@assignSelf', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

$router->post('/api/vehicles/release-self', 'Controllers\Api\VehicleController@releaseSelf', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

$router->post('/api/users', 'Controllers\\Api\\UserController@createUser', [
    'Middleware\\AuthMiddleware',
    'Middleware\\Roles\\AdminOnly',
]);


$router->post('/api/users/suspend', 'Controllers\Api\UserController@suspend', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/users/assign-vehicle', 'Controllers\Api\UserController@assignVehicle', [
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

$router->post('/api/bidding/reject', 'Controllers\\Api\\BiddingController@reject', [
    'Middleware\\AuthMiddleware',
    'Middleware\\Roles\\AdminOnly',
]);

// Explicit ID-based expiry — stops relying on DB NOW() comparison
$router->post('/api/bidding/{id}/expire', 'Controllers\Api\BiddingController@expire', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);



// ---------------------------------------------
// Waste Inventory Management API Routes
// ---------------------------------------------

// Get waste inventory status
$router->get('/api/admin/waste-inventory', 'Controllers\Api\Admin\WasteInventoryController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Get detailed inventory for a specific category
$router->get('/api/admin/waste-inventory/{id}', 'Controllers\Api\Admin\WasteInventoryController@show', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Create bidding round from collected waste
$router->post('/api/admin/waste-inventory/create-bidding-round', 'Controllers\Api\Admin\WasteInventoryController@createBiddingRound', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Get waste collection statistics
$router->get('/api/admin/waste-inventory/stats', 'Controllers\Api\Admin\WasteInventoryController@stats', [
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

// Customer dashboard API
$router->get('/api/customer/dashboard/stats', 'Controllers\Api\Customer\DashboardController@stats', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

$router->get('/api/customer/dashboard/material-prices', 'Controllers\Api\Customer\DashboardController@materialPrices', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

// Customer collector ratings
$router->post('/api/customer/collector-ratings', 'Controllers\\Api\\Customer\\CollectorRatingController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

// $router->put('/api/collector/pickup-requests/{id}/status', 'Controllers\Api\Collector\PickupRequestController@updateStatus', [
//     'Middleware\AuthMiddleware',
//     'Middleware\CsrfMiddleware',
//     'Middleware\Roles\CollectorOnly',
// ]);

// Real-time pickup request event polling
$router->get('/api/pickup-requests/updates', 'Controllers\Api\PickupRequestUpdatesController@getUpdates', [
    'Middleware\AuthMiddleware',
]);
$router->get('/api/pickup-requests/server-time', 'Controllers\Api\PickupRequestUpdatesController@getServerTime', [
    'Middleware\AuthMiddleware',
]);

// Customer income report (completed pickups)
$router->get('/api/reports/customer-income', 'Controllers\Api\ReportUpdatesController@customerIncome', [
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CustomerOnly',
]);

// $router->put('/api/collector/pickup-requests/{id}/status', 'Controllers\Api\Collector\PickupRequestController@updateStatus', [
//     'Middleware\AuthMiddleware',
//     'Middleware\CsrfMiddleware',
//     'Middleware\Roles\CollectorOnly',
// ]);

$router->get('/api/payments', 'Controllers\Api\PaymentController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/reports/customer-income/export', 'Controllers\Api\ReportUpdatesController@exportCustomerIncome', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Collector-scoped Customer Income report (for collectors to view their own completed pickups)
$router->get('/api/collector/reports/customer-income', 'Controllers\Api\ReportUpdatesController@collectorCustomerIncome', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

$router->get('/api/payments', 'Controllers\Api\PaymentController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/payments', 'Controllers\Api\PaymentController@store', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->put('/api/payments/{id}', 'Controllers\Api\PaymentController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
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

$router->get('/api/company/invoices', 'Controllers\\Api\\PaymentController@companyInvoices', [
    'Middleware\\AuthMiddleware',
    'Middleware\\Roles\\CompanyOnly',
]);

$router->post('/api/company/invoices/{id}/pay', 'Controllers\\Api\\PaymentController@submitPayment', [
    'Middleware\\AuthMiddleware',
    'Middleware\\Roles\\CompanyOnly',
]);

$router->get('/api/collector/payments', 'Controllers\Api\PaymentController@collectorPayments', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// ---------------------------------------------
// PayHere Payment Gateway Routes
// ---------------------------------------------

// Company initiates a PayHere checkout for a pending invoice
// Returns signed form payload; frontend auto-submits it to PayHere
$router->post('/api/payhere/checkout/{id}', 'Controllers\Api\PayHereController@initiateCheckout', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CompanyOnly',
]);

// PayHere server-to-server payment notification (webhook)
// NOTE: NO auth middleware — this is called directly by PayHere servers
// Verifies md5sig checksum before processing any data
$router->post('/api/payhere/notify', 'Controllers\Api\PayHereController@notify');



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

// Email verification routes
$router->get('/verify-email', 'AuthController@verifyEmail');
$router->post('/resend-verification-email', 'AuthController@resendVerificationEmail');
$router->post('/api/auth/resend-verification', 'AuthController@resendVerification');

// Password reset routes
$router->post('/api/auth/send-password-reset-link', 'AuthController@sendPasswordResetLink');
$router->get('/reset-password', 'AuthController@showResetPassword');
$router->post('/api/auth/reset-password', 'AuthController@resetPassword');

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
$router->get('/collector/profile', 'Controllers\\Collector\\ProfileController@show', [
    'Middleware\\AuthMiddleware',
    'Middleware\\Roles\\CollectorOnly'
]);

$router->post('/collector/profile', 'Controllers\Collector\ProfileController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CollectorOnly'
]);


$router->post('/company/profile/photo', 'Controllers\Company\ProfilePhotoController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CompanyOnly'
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
            'api_debug_routes' => '/debug/api-routes',
            'api_debug_json' => '/api/debug/api-routes'
        ]
    ]);
});

// Premium UI route for API Explorer
$router->get('/debug/api-routes', function () use ($router) {
    if (class_exists('Core\Router') && method_exists($router, 'getRoutes')) {
        $allRoutes = $router->getRoutes();
    } else {
        $allRoutes = [];
    }

    $apiRoutes = [];
    foreach ($allRoutes as $route) {
        if (strpos($route['path'], '/api') === 0) {
            $requiresAuth = in_array('Middleware\AuthMiddleware', $route['middleware'] ?? []);
            $roles = [];
            foreach ($route['middleware'] ?? [] as $mw) {
                if (strpos($mw, 'Middleware\Roles\\') === 0) {
                    $roles[] = str_replace('Middleware\Roles\\', '', $mw);
                }
            }

            $description = 'API endpoint for ' . $route['path'];
            if (is_string($route['action'])) {
                $parts = explode('@', $route['action']);
                if (count($parts) === 2) {
                    $controllerName = basename(str_replace('\\', '/', $parts[0]));
                    $methodName = $parts[1];
                    $description = "{$methodName} operation in {$controllerName}";
                }
            }

            $apiRoutes[] = [
                'method' => $route['method'],
                'path' => $route['path'],
                'requires_auth' => $requiresAuth,
                'roles_allowed' => empty($roles) && $requiresAuth ? ['All Authenticated Users'] : (empty($roles) ? ['Public'] : $roles),
                'description' => $description,
                'action' => is_string($route['action']) ? $route['action'] : 'Closure'
            ];
        }
    }

    return view('debug/api_routes', ['routes' => $apiRoutes]);
});

// Toast Test Page
$router->get('/test/toast', 'Controllers\TestController@index');

// Debug route to list all registered routes
$router->get('/api/debug/routes', function () use ($router) {
    if (class_exists('Core\Router') && method_exists($router, 'getRoutes')) {
        $routes = $router->getRoutes();
    } else {
        $routes = [];
    }

    return view('debug/routes', ['routes' => $routes]);
});

// Debug route to list all API routes with details as JSON
$router->get('/api/debug/api-routes', function () use ($router) {
    if (class_exists('Core\Router') && method_exists($router, 'getRoutes')) {
        $allRoutes = $router->getRoutes();
    } else {
        $allRoutes = [];
    }

    $apiRoutes = [];
    foreach ($allRoutes as $route) {
        // Only include API routes
        if (strpos($route['path'], '/api') === 0) {
            $requiresAuth = in_array('Middleware\AuthMiddleware', $route['middleware'] ?? []);
            $roles = [];
            foreach ($route['middleware'] ?? [] as $mw) {
                if (strpos($mw, 'Middleware\Roles\\') === 0) {
                    $roles[] = str_replace('Middleware\Roles\\', '', $mw);
                }
            }

            $description = 'API endpoint for ' . $route['path'];
            if (is_string($route['action'])) {
                $parts = explode('@', $route['action']);
                if (count($parts) === 2) {
                    $controllerName = basename(str_replace('\\', '/', $parts[0]));
                    $methodName = $parts[1];
                    $description = "{$methodName} operation in {$controllerName}";
                }
            }

            $apiRoutes[] = [
                'method' => $route['method'],
                'path' => $route['path'],
                'requires_auth' => $requiresAuth,
                'roles_allowed' => empty($roles) && $requiresAuth ? ['All Authenticated Users'] : (empty($roles) ? ['Public'] : $roles),
                'description' => $description,
                'action' => is_string($route['action']) ? $route['action'] : 'Closure'
            ];
        }
    }

    return response()->json([
        'status' => 'success',
        'count' => count($apiRoutes),
        'routes' => $apiRoutes
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

// Lightweight DB connectivity check (no exception bubbling)
$router->get('/debug/db/ping.json', function () {
    $result = \Core\Database::ping();
    return response()->json($result + ['timestamp' => date('c')]);
});

// Debug route to test waste inventory system
$router->get('/debug/waste-inventory', function () {
    $db = new Database();
    $results = [];

    // Test 1: Check if bidding_round_sources table exists
    try {
        $tableCheck = $db->fetch(
            "SELECT table_name FROM information_schema.tables 
             WHERE table_schema = 'public' AND table_name = 'bidding_round_sources'"
        );
        $results['bidding_round_sources_table'] = [
            'exists' => !empty($tableCheck),
            'data' => $tableCheck
        ];
    } catch (\Throwable $e) {
        $results['bidding_round_sources_table'] = ['error' => $e->getMessage()];
    }

    // Test 2: Check if waste_inventory view exists
    try {
        $viewCheck = $db->fetch(
            "SELECT table_name FROM information_schema.views 
             WHERE table_schema = 'public' AND table_name = 'waste_inventory'"
        );
        $results['waste_inventory_view'] = [
            'exists' => !empty($viewCheck),
            'data' => $viewCheck
        ];
    } catch (\Throwable $e) {
        $results['waste_inventory_view'] = ['error' => $e->getMessage()];
    }

    // Test 3: Query waste_inventory view
    try {
        $inventory = $db->fetchAll('SELECT * FROM waste_inventory LIMIT 10');
        $results['waste_inventory_data'] = [
            'count' => count($inventory),
            'data' => $inventory
        ];
    } catch (\Throwable $e) {
        $results['waste_inventory_data'] = ['error' => $e->getMessage()];
    }

    // Test 4: Test WasteInventory model
    try {
        $wasteInventory = new \Models\WasteInventory();
        $status = $wasteInventory->getInventoryStatus();
        $results['waste_inventory_model'] = [
            'loaded' => true,
            'inventory_count' => count($status),
            'sample' => array_slice($status, 0, 3)
        ];
    } catch (\Throwable $e) {
        $results['waste_inventory_model'] = ['error' => $e->getMessage()];
    }

    // Test 5: Test PickupRequest unallocated waste
    try {
        $pickupRequest = new \Models\PickupRequest();
        $unallocated = $pickupRequest->getUnallocatedWaste();
        $results['unallocated_waste'] = [
            'count' => count($unallocated),
            'data' => $unallocated
        ];
    } catch (\Throwable $e) {
        $results['unallocated_waste'] = ['error' => $e->getMessage()];
    }

    // Test 6: Check routes are registered
    $results['routes_registered'] = [
        'waste_inventory_index' => route_exists('/api/admin/waste-inventory'),
        'waste_inventory_show' => route_exists('/api/admin/waste-inventory/{id}'),
        'create_bidding_round' => route_exists('/api/admin/waste-inventory/create-bidding-round'),
        'waste_inventory_stats' => route_exists('/api/admin/waste-inventory/stats'),
    ];

    return response()->json([
        'status' => 'success',
        'message' => 'Waste Inventory System Test Results',
        'timestamp' => date('c'),
        'results' => $results
    ]);
});

function route_exists($path)
{
    try {
        $router = app('router');
        if (method_exists($router, 'getRoutes')) {
            $routes = $router->getRoutes();
            foreach ($routes as $route) {
                if (isset($route['path']) && $route['path'] === $path) {
                    return true;
                }
            }
        }
        return false;
    } catch (\Throwable $e) {
        return 'error: ' . $e->getMessage();
    }
}


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

$router->post('/api/profile/update', 'Controllers\Api\profileController@updateProfile', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware'
]);

$router->get('/api/profile/delete', 'Controllers\Api\profileController@deleteProfile', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware'
]);

$router->post('/api/profile/bankDetails', 'Controllers\Api\profileController@updateBankDetails', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware'
]);

$router->post('/api/profile/password', 'Controllers\Api\profileController@changePassword', [
    'Middleware\AuthMiddleware',
    // 'Middleware\CsrfMiddleware'
]);
// ---------------------------------------------
// Analytics & Reporting API Routes
// ---------------------------------------------

// Role-specific analytics dashboard
$router->get('/api/analytics/dashboard', 'Controllers\Api\AnalyticsController@dashboard', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Collector feedback and ratings
$router->get('/api/analytics/collector-feedback', 'Controllers\Api\AnalyticsController@getCollectorFeedback', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Waste collection statistics
$router->get('/api/analytics/waste-stats', 'Controllers\Api\AnalyticsController@getWasteStats', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Comprehensive analytics metrics
$router->get('/api/analytics/metrics', 'Controllers\Api\AnalyticsController@getMetrics', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Add new feedback entry
$router->post('/api/analytics/feedback', 'Controllers\Api\AnalyticsController@addFeedback', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Waste collection report
$router->get('/api/reports/waste-collection', 'Controllers\Api\ReportsController@wasteCollection', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Bidding analytics report
$router->get('/api/reports/bidding', 'Controllers\Api\ReportsController@bidding', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Revenue reports
$router->get('/api/reports/revenue', 'Controllers\Api\ReportsController@revenue', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);


// Export report (CSV / PDF)
$router->post('/api/reports/export', 'Controllers\Api\ReportsController@export', [
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

// Collector Pickup Requests API
$router->get(
    '/api/collector/pickup-requests',
    'Controllers\Collector\CollectorDashboardController@index',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

$router->get(
    '/api/collector/pickup-requests/{id}',
    'Controllers\Collector\CollectorDashboardController@show',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

// Save weight for a pickup
$router->put(
    '/api/collector/pickup-requests/{id}/weight',
    'Controllers\Collector\CollectorDashboardController@saveWeight',
    [
        'Middleware\AuthMiddleware',
        'Middleware\CsrfMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

// Update status of a pickup
$router->put(
    '/api/collector/pickup-requests/{id}/status',
    'Controllers\Collector\CollectorDashboardController@updateStatus',
    [
        'Middleware\AuthMiddleware',
        'Middleware\CsrfMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

// Collector dashboard quick stats (used by collector dashboard UI)
$router->get('/api/collector/stats', 'Controllers\Api\CollectorStatsController@stats', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector material prices (used by collector dashboard UI)
$router->get('/api/collector/material-prices', 'Controllers\Api\CollectorStatsController@materialPrices', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector material collection (used by collector dashboard UI)
$router->get('/api/collector/material-collection', 'Controllers\Api\CollectorStatsController@materialCollection', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

$router->get('/api/notifications/unread-count', 'Controllers\Api\NotificationController@unreadCount', [
    'Middleware\AuthMiddleware',
]);

$router->delete('/api/notifications/{id}', 'Controllers\Api\NotificationController@destroy', [
    'Middleware\AuthMiddleware',
]);


// user managemnet api routes
$router->get('/api/users/{id}', 'Controllers\Api\UserController@findById', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/users', 'Controllers\Api\UserController@findAll', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

// Collector feedback routes
$router->get(
    '/api/collector/feedback',
    'Controllers\Collector\CollectorDashboardController@getFeedback',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

$router->post(
    '/api/collector/feedback',
    'Controllers\Collector\CollectorDashboardController@addFeedback',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

// Collector waste collection
$router->get(
    '/api/collector/waste-collection',
    'Controllers\Collector\CollectorDashboardController@getWasteCollection',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

// Collector metrics
$router->get(
    '/api/collector/metrics',
    'Controllers\Collector\CollectorDashboardController@getMetrics',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

// Collector notifications routes
$router->get(
    '/api/collector/notifications',
    'Controllers\Collector\CollectorDashboardController@notifications',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

$router->put(
    '/api/collector/notifications/{id}/read',
    'Controllers\Collector\CollectorDashboardController@markNotificationRead',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

$router->put(
    '/api/collector/notifications/read-all',
    'Controllers\Collector\CollectorDashboardController@markAllNotificationsRead',
    [
        'Middleware\AuthMiddleware',
        'Middleware\Roles\CollectorOnly',
    ]
);

// Bidding availability and history
$router->get('/api/bidding/availability', 'Controllers\\Api\\BiddingController@checkAvailability', [
    'Middleware\\AuthMiddleware',
    'Middleware\\Roles\\AdminOnly',
]);

$router->get('/api/bidding/bid-history', 'Controllers\\Api\\BiddingController@getBidHistory', [
    'Middleware\\AuthMiddleware',
    'Middleware\\Roles\\AdminOnly',
]);

// ---------------------------------------------
// Collector Dashboard Routes
// ---------------------------------------------

// Collector main dashboard
$router->get('/collector', 'Controllers\Collector\CollectorDashboardController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector tasks / pickup assignments page
$router->get('/collector/tasks', 'Controllers\Collector\CollectorDashboardController@tasks', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector analytics / reporting
$router->get('/collector/analytics', 'Controllers\Collector\CollectorDashboardController@analytics', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector notifications page
$router->get('/collector/notifications', 'Controllers\Collector\CollectorDashboardController@notification', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector settings page
$router->get('/collector/setting', 'Controllers\Collector\CollectorDashboardController@setting', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector profile page
$router->get('/collector/profile', 'Controllers\Collector\CollectorDashboardController@profile', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// Collector earnings page
$router->get('/collector/earnings', 'Controllers\Collector\CollectorDashboardController@earnings', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// API: Save weight for a pickup (PUT)
$router->put('/api/collector/pickup-requests/{id}/weight', 'Controllers\Collector\CollectorDashboardController@saveWeight', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// API: Update status of a pickup (PUT)
$router->put('/api/collector/pickup-requests/{id}/status', 'Controllers\Collector\CollectorDashboardController@updateStatus', [
    'Middleware\AuthMiddleware',
    'Middleware\CsrfMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// API: Get assigned pickups (tasks) for dashboard
$router->get('/api/collector/pickup-requests', 'Controllers\Collector\CollectorDashboardController@tasks', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);

// API: Get single pickup details
$router->get('/api/collector/pickup-requests/{id}', 'Controllers\Collector\CollectorDashboardController@show', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\CollectorOnly',
]);
