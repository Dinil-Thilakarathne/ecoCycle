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
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && $userModel->verifyPassword($user, $password)) {
            session()->put('user_id', $user['id']);
            session()->put('user_name', $user['username'] ?? $user['email']);
            session()->put('user_email', $user['email']);
            session()->put('user_role', $user['role_name']);
            // Optional: update last_login_at
            // (new Database())->query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
            return dashboard_redirect([
                'id' => $user['id'],
                'name' => $user['username'] ?? $user['email'],
                'email' => $user['email'],
                'role' => $user['role_name']
            ]);
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
