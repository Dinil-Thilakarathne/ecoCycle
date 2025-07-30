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
