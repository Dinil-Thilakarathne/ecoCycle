<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\User;

/**
 * API Authentication Controller
 * 
 * Handles API-specific authentication (returns JSON only)
 */
class AuthController extends BaseController
{
    /**
     * API Login - Always returns JSON
     * 
     * @param Request $request
     * @return Response
     */
    public function login(Request $request): Response
    {
        try {
            // Get JSON input for API requests
            $data = $request->json() ?? [];

            $login = trim((string) ($data['email'] ?? $request->input('email') ?? $data['login'] ?? $request->input('login') ?? ''));
            $password = (string) ($data['password'] ?? $request->input('password') ?? '');

            if (empty($login) || empty($password)) {
                return Response::errorJson('Email and password are required', 422);
            }

            $userModel = new User();
            $user = null;

            try {
                // Try as email first
                $user = filter_var($login, FILTER_VALIDATE_EMAIL)
                    ? $userModel->findByEmail($login)
                    : $userModel->findByUsername($login);
            } catch (\Throwable $e) {
                // DB not ready; fall back to demo users
            }

            // Fallback to in-memory demo users if DB user not found
            if (!$user) {
                $demoUsers = config('auth.demo_users', []);
                foreach ($demoUsers as $demo) {
                    if (strcasecmp($demo['email'], $login) === 0 || strcasecmp($demo['username'] ?? '', $login) === 0) {
                        $user = $demo;
                        break;
                    }
                }

                if ($user) {
                    $valid = hash_equals($user['password_hash'], $password);
                    if (!$valid) {
                        $user = null;
                    }
                }
            } else {
                // Verify password for DB user
                if (!$userModel->verifyPassword($user, $password)) {
                    $user = null;
                }
            }

            if (!$user) {
                return Response::errorJson('Invalid email or password', 401);
            }

            // Login successful - create session
            $userData = [
                'id' => (int) $user['id'],
                'name' => $user['name'] ?? $user['username'] ?? $user['email'],
                'email' => $user['email'],
                'role' => $user['role_name'] ?? ($user['role'] ?? null)
            ];

            // Use SessionManager::login
            session()->login((int) $userData['id'], $userData);
            session()->put('user_name', $userData['name']);
            session()->put('user_email', $userData['email']);
            session()->put('user_role', $userData['role']);

            // Determine dashboard URL
            $dashboards = config('auth.dashboards', []);
            $redirectUrl = $dashboards[$userData['role']] ?? '/dashboard';

            return Response::json([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $userData['id'],
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'role' => $userData['role']
                ],
                'redirect' => $redirectUrl
            ]);

        } catch (\Throwable $e) {
            error_log('[API Auth] Login error: ' . $e->getMessage());
            return Response::errorJson('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API Logout - Always returns JSON
     * 
     * @return Response
     */
    public function logout(): Response
    {
        try {
            session()->destroy();

            return Response::json([
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => '/login'
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API Register - Always returns JSON
     * 
     * @param Request $request
     * @return Response
     */
    public function register(Request $request): Response
    {
        try {
            // Get JSON input for API requests
            $data = $request->json() ?? [];

            $name = trim((string) ($data['name'] ?? $request->input('name') ?? ''));
            $email = trim((string) ($data['email'] ?? $request->input('email') ?? ''));
            $password = (string) ($data['password'] ?? $request->input('password') ?? '');
            $passwordConfirm = (string) ($data['password_confirmation'] ?? $data['password_confirm'] ?? $request->input('password_confirmation') ?? $request->input('password_confirm') ?? '');
            $role = (string) ($data['role'] ?? $request->input('role') ?? 'customer');

            // Validation
            if (empty($name) || empty($email) || empty($password)) {
                return Response::errorJson('Name, email, and password are required', 422);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return Response::errorJson('Invalid email address', 422);
            }

            if (strlen($password) < 6) {
                return Response::errorJson('Password must be at least 6 characters', 422);
            }

            if ($password !== $passwordConfirm) {
                return Response::errorJson('Passwords do not match', 422);
            }

            // Check if user exists
            $userModel = new User();
            $existing = $userModel->findByEmail($email);

            if ($existing) {
                return Response::errorJson('An account with that email already exists', 422);
            }

            // Get role ID
            $roleId = null;
            try {
                $db = new \Core\Database();
                $row = $db->fetch('SELECT id FROM roles WHERE name = ? LIMIT 1', [$role]);
                if ($row && isset($row['id'])) {
                    $roleId = (int) $row['id'];
                }
            } catch (\Throwable $e) {
                // Role ID remains null
            }

            // Create user
            $data = [
                'name' => $name,
                'email' => $email,
                'type' => $role,
                'password' => $password,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ($roleId !== null) {
                $data['role_id'] = $roleId;
            }

            $newId = $userModel->createUser($data);

            if ($newId === false) {
                return Response::errorJson('Failed to create account. Please try again.', 500);
            }

            return Response::json([
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => $newId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role
                ]
            ], 201);

        } catch (\Throwable $e) {
            error_log('[API Auth] Register error: ' . $e->getMessage());
            return Response::errorJson('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get current authenticated user info
     * 
     * @return Response
     */
    public function me(): Response
    {
        if (!session()->isLoggedIn()) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $userData = session()->userData();

        return Response::json([
            'success' => true,
            'user' => [
                'id' => $userData['id'] ?? null,
                'name' => $userData['name'] ?? session()->get('user_name'),
                'email' => $userData['email'] ?? session()->get('user_email'),
                'role' => $userData['role'] ?? session()->get('user_role')
            ]
        ]);
    }
}
