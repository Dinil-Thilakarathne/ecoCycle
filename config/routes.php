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


// Customer Dashboard Routes (Public for development)
$router->get('/customer', 'Controllers\Customer\CustomerDashboardController@index');

// Collector Dashboard Routes (Public for development)
$router->get('/collector', 'Controllers\Collector\CollectorDashboardController@index');

// Company Dashboard Routes (Public for development)
$router->get('/company', 'Controllers\Company\CompanyDashboardController@index');

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
