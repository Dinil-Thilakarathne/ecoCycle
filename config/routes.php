<?php

/**
 * Application Routes - Next.js Style
 * 
 * Define all application routes here.
 * Routes are automatically loaded by the framework.
 */

use Core\PageRouter;

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

// Admin Dashboard Routes (Public for development)
$router->get('/admin', 'Controllers\Admin\AdminDashboardController@index');
$router->get('/admin/users', 'Controllers\Admin\AdminDashboardController@users');
$router->get('/admin/settings', 'Controllers\Admin\AdminDashboardController@settings');
$router->get('/admin/reports', 'Controllers\Admin\AdminDashboardController@reports');
$router->get('/admin/content', 'Controllers\Admin\AdminDashboardController@content');

// Customer Dashboard Routes (Public for development)
$router->get('/customer', 'Controllers\Customer\CustomerDashboardController@index');
$router->get('/customer/schedule', 'Controllers\Customer\CustomerDashboardController@schedulePickup');
$router->get('/customer/history', 'Controllers\Customer\CustomerDashboardController@pickupHistory');
$router->get('/customer/rewards', 'Controllers\Customer\CustomerDashboardController@rewards');
$router->get('/customer/education', 'Controllers\Customer\CustomerDashboardController@education');
$router->get('/customer/profile', 'Controllers\Customer\CustomerDashboardController@profile');

// Collector Dashboard Routes (Public for development)
$router->get('/collector', 'Controllers\Collector\CollectorDashboardController@index');
$router->get('/collector/pickups', 'Controllers\Collector\CollectorDashboardController@pickups');
$router->get('/collector/routes', 'Controllers\Collector\CollectorDashboardController@routes');
$router->get('/collector/earnings', 'Controllers\Collector\CollectorDashboardController@earnings');
$router->get('/collector/reports', 'Controllers\Collector\CollectorDashboardController@reports');
$router->get('/collector/profile', 'Controllers\Collector\CollectorDashboardController@profile');

// Company Dashboard Routes (Public for development)
$router->get('/company', 'Controllers\Company\CompanyDashboardController@index');
$router->get('/company/waste', 'Controllers\Company\CompanyDashboardController@wasteManagement');
$router->get('/company/schedule', 'Controllers\Company\CompanyDashboardController@scheduleCollection');
$router->get('/company/analytics', 'Controllers\Company\CompanyDashboardController@analytics');
$router->get('/company/billing', 'Controllers\Company\CompanyDashboardController@billing');
$router->get('/company/sustainability', 'Controllers\Company\CompanyDashboardController@sustainability');
$router->get('/company/profile', 'Controllers\Company\CompanyDashboardController@profile');

// Legacy routes for backward compatibility
$router->get('/legacy', 'HomeController@index');
$router->get('/legacy/about', 'HomeController@about');

// Example routes for your application
$router->get('/example', 'ExampleController@index');
$router->post('/example', 'ExampleController@store');
$router->get('/example/{id}', 'ExampleController@show');

// HTML Page routes (old style)
$router->get('/old/home', 'PageController@home');
$router->get('/old/page', 'PageController@demo');
$router->get('/old/page/about', 'PageController@about');
$router->post('/old/page/submit', 'PageController@submitForm');

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
    return response()->json([
        'status' => 'success',
        'message' => 'EcoCycle Framework is working!',
        'dashboards' => [
            'admin' => '/admin',
            'customer' => '/customer',
            'collector' => '/collector',
            'company' => '/company'
        ],
        'login' => '/login'
    ]);
});
