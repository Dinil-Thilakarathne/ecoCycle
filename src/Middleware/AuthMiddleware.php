<?php

namespace Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Authentication Middleware
 * 
 * Ensures that only authenticated users can access protected routes.
 * 
 * @package Middleware
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class AuthMiddleware
{
    /**
     * Handle the incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        // Get session instance
        $session = app('session');

        // Check if the user is authenticated
        if (!$session->isAuthenticated()) {
            // If request expects JSON, return JSON error
            if ($request->expectsJson()) {
                return Response::errorJson('Unauthenticated', 401);
            }

            // Otherwise redirect to login page
            return Response::redirect('/login');
        }

        // Proceed to the next middleware or request handler
        return $next($request);
    }
}