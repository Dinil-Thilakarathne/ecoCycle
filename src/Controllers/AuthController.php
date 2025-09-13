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

            // Determine dashboard URL for JSON response if requested
            $dashboards = config('auth.dashboards', []);
            $redirectUrl = $dashboards[$userData['role']] ?? '/dashboard';

            if ($request->expectsJson() || $request->isAjax()) {
                return \Core\Http\Response::json([
                    'success' => true,
                    'message' => 'Authenticated',
                    'redirect' => $redirectUrl
                ]);
            }

            return dashboard_redirect($userData);
        }

        // On failure: preserve the submitted login value and show an error message.
        // Use session flash so old() helper works and message survives the redirect for non-AJAX.
        session()->flash('old', ['login' => $login]);
        session()->flash('error', 'Invalid email or password');

        if ($request->expectsJson() || $request->isAjax()) {
            return \Core\Http\Response::errorJson('Invalid email or password', 422);
        }

        return redirect('/login');
    }

    /**
     * Handle logout
     */
    public function logout(): Response
    {
        // Use the global helper which performs a full session cleanup and redirect
        return \logout();
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
        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $passwordConfirm = (string) $request->input('password_confirm');
        $role = (string) $request->input('role') ?: 'customer';

        // preserve old input
        session()->flash('old', ['name' => $name, 'email' => $email]);

        // Basic validation
        if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            session()->flash('error', 'Please fill out all required fields.');
            return redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session()->flash('error', 'Please provide a valid email address.');
            return redirect('/register');
        }
        if (strlen($password) < 6) {
            session()->flash('error', 'Password must be at least 6 characters.');
            return redirect('/register');
        }
        if ($password !== $passwordConfirm) {
            session()->flash('error', 'Passwords do not match.');
            return redirect('/register');
        }

        $userModel = new User();

        // Check existing email
        try {
            $existing = $userModel->findByEmail($email);
            if ($existing) {
                session()->flash('error', 'An account with that email already exists.');
                return redirect('/register');
            }
        } catch (\Throwable $e) {
            // If DB not ready, return friendly error
            session()->flash('error', 'Unable to access database. Please try again later.');
            return redirect('/register');
        }

        // Resolve role_id if roles table populated
        $roleId = null;
        try {
            $db = new \Core\Database();
            $row = $db->fetch('SELECT id FROM roles WHERE name = ? LIMIT 1', [$role]);
            if ($row && isset($row['id'])) {
                $roleId = (int) $row['id'];
            }
        } catch (\Throwable $e) {
            // ignore - role id remains null
        }

        // Create user
        $data = [
            'name' => $name,
            'email' => $email,
            'type' => $role,
            'password' => $password,
        ];
        if ($roleId !== null) {
            $data['role_id'] = $roleId;
        }

        try {
            $newId = $userModel->createUser($data);
            if ($newId === false) {
                session()->flash('error', 'Failed to create account. Please try again.');
                return redirect('/register');
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to create account: ' . $e->getMessage());
            return redirect('/register');
        }

        session()->flash('success', 'Account created. Please sign in.');
        return redirect('/login');
    }
}
