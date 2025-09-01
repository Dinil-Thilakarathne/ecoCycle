<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Models\User;

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
        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');

        $userModel = new User();
        $user = null;

        try {
            // Try as email first
            $user = filter_var($login, FILTER_VALIDATE_EMAIL) ? $userModel->findByEmail($login) : $userModel->findByUsername($login);
        } catch (\Throwable $e) {
            // DB not ready; fall back to demo users
        }

        // Fallback to in-memory demo users if DB user not found
        if (!$user) {
            $demoUsers = config('auth.demo_users', []);
            foreach ($demoUsers as $demo) {
                if (strcasecmp($demo['email'], $login) === 0 || strcasecmp($demo['username'] ?? '', $login) === 0) {
                    $user = $demo; // plain password comparison below
                    break;
                }
            }
            if ($user) {
                $valid = hash_equals($user['password_hash'], $password);
                if (!$valid) {
                    $user = null; // invalidate if password mismatch
                }
            }
        } else {
            // Verify password (hashed or plain) for DB user
            if (!$userModel->verifyPassword($user, $password)) {
                $user = null;
            }
        }

        if ($user) {
            $userData = [
                'id' => (int) $user['id'],
                'name' => $user['username'] ?? $user['email'],
                'email' => $user['email'],
                'role' => $user['role_name'] ?? ($user['role'] ?? null)
            ];

            // Use SessionManager::login so userData() returns the role for middlewares
            session()->login((int) $userData['id'], $userData);

            // Keep individual keys for backward compatibility with helpers
            session()->put('user_name', $userData['name']);
            session()->put('user_email', $userData['email']);
            session()->put('user_role', $userData['role']);

            return dashboard_redirect($userData);
        }

        return $this->view('auth/login', ['error' => 'Invalid email or password']);
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

    /**phe
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
