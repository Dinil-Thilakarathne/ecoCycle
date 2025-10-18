<?php

namespace Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Role-based Authorization Middleware
 * 
 * Ensures that only users with specific roles can access protected routes.
 * 
 * @package Middleware
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class RoleMiddleware
{
    /**
     * Required roles for access
     * 
     * @var array
     */
    protected array $roles;

    /**
     * Create new RoleMiddleware instance
     * 
     * @param array $roles Required roles
     */
    public function __construct(array $roles = [])
    {
        $this->roles = $roles;
    }

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

        // Check if user is authenticated first
        if (!$session->isAuthenticated()) {
            if ($request->expectsJson()) {
                return Response::errorJson('Unauthenticated', 401);
            }
            return Response::redirect('/login');
        }

        // Get user data from session
        $userData = $session->userData();
        $userRole = $userData['role'] ?? null;

        // Check if user has required role
        if (!empty($this->roles) && !in_array($userRole, $this->roles)) {
            if ($request->expectsJson()) {
                return Response::errorJson('Forbidden - Insufficient permissions', 403);
            }

            // Redirect to appropriate page based on role
            return redirect("/login");
        }

        // Proceed to the next middleware or request handler
        return $next($request);
    }

    /**
     * Redirect user based on their role
     * 
     * @param string|null $userRole
     * @return Response
     */
    protected function redirectBasedOnRole(?string $userRole): Response
    {
        switch ($userRole) {
            case 'customer':
                return Response::redirect('/customer');
            case 'collector':
                return Response::redirect('/collector');
            case 'company':
                return Response::redirect('/company');
            case 'admin':
                return Response::redirect('/admin');
            default:
                return Response::redirect('/dashboard');
        }
    }

    /**
     * Set required roles
     * 
     * @param array $roles
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }
}