<?php

namespace Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * CSRF Protection Middleware
 * 
 * Protects against Cross-Site Request Forgery attacks by validating CSRF tokens.
 * 
 * @package Middleware
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class CsrfMiddleware
{
    /**
     * HTTP methods that require CSRF protection
     * 
     * @var array
     */
    protected array $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Handle the incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        // Skip CSRF protection for safe methods
        if (!in_array($request->getMethod(), $this->protectedMethods)) {
            return $next($request);
        }

        // Get session instance
        $session = app('session');

        // Get CSRF token from request
        $requestToken = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        // Get session token
        $sessionToken = $session->token();

        // Validate CSRF token
        if (!$requestToken || !hash_equals($sessionToken, $requestToken)) {
            if ($request->expectsJson()) {
                return Response::errorJson('CSRF token mismatch', 419);
            }

            // Redirect back with error
            $session->flash('error', 'CSRF token mismatch. Please try again.');
            return Response::redirect($request->header('Referer', '/'));
        }

        return $next($request);
    }
}
