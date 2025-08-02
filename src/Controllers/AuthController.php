<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Authentication Controller
 * 
 * Handles user authentication and authorization
 */
class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function showLogin(): Response
    {
        return $this->view('auth/login');
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request): Response
    {
        $email = $request->input('email');
        $password = $request->input('password');

        // TODO: Implement actual authentication logic
        // For now, let's create a demo login system

        $demoUsers = [
            'admin@ecocycle.com' => [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@ecocycle.com',
                'role' => 'admin',
                'password' => 'admin123'
            ],
            'customer@ecocycle.com' => [
                'id' => 2,
                'name' => 'John Customer',
                'email' => 'customer@ecocycle.com',
                'role' => 'customer',
                'password' => 'customer123'
            ],
            'collector@ecocycle.com' => [
                'id' => 3,
                'name' => 'Jane Collector',
                'email' => 'collector@ecocycle.com',
                'role' => 'collector',
                'password' => 'collector123'
            ],
            'company@ecocycle.com' => [
                'id' => 4,
                'name' => 'ABC Company',
                'email' => 'company@ecocycle.com',
                'role' => 'company',
                'password' => 'company123'
            ]
        ];

        if (isset($demoUsers[$email]) && $demoUsers[$email]['password'] === $password) {
            $user = $demoUsers[$email];

            // Set session data using SessionManager's put method
            session()->put('user_id', $user['id']);
            session()->put('user_name', $user['name']);
            session()->put('user_email', $user['email']);
            session()->put('user_role', $user['role']);

            // Redirect to appropriate dashboard
            return dashboard_redirect($user);
        }

        // Login failed
        return $this->view('auth/login', [
            'error' => 'Invalid email or password'
        ]);
    }

    /**
     * Handle logout
     */
    public function logout(): Response
    {
        session()->destroy();
        return redirect('/login');
    }

    /**
     * Show registration form
     */
    public function showRegister(): Response
    {
        return $this->view('auth/register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request): Response
    {
        // TODO: Implement registration logic
        return $this->view('auth/register', [
            'message' => 'Registration functionality coming soon!'
        ]);
    }
}
