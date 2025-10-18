<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Uploads\ProfileImageManager;
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
        try {
            $login = trim((string) $request->input('login'));
            $password = (string) $request->input('password');

            $userModel = new User();
            $user = null;

            try {
                // Try as email first
                $user = filter_var($login, FILTER_VALIDATE_EMAIL) ? $userModel->findByEmail($login) : $userModel->findByUsername($login);

                if ($user) {
                    error_log('LOGIN DEBUG user id=' . ($user['id'] ?? '??'));
                    error_log('LOGIN DEBUG hash=' . ($user['password_hash'] ?? 'null'));
                    error_log('LOGIN DEBUG verify=' . (new \Models\User())->verifyPassword($user, $password) ? 'true' : 'false');
                }
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
        } catch (\Throwable $e) {
            // Catch any unexpected errors and return JSON for AJAX requests
            if ($request->expectsJson() || $request->isAjax()) {
                return \Core\Http\Response::errorJson('Server error: ' . $e->getMessage(), 500);
            }
            // For non-AJAX, redirect with error
            session()->flash('error', 'An unexpected error occurred. Please try again.');
            return redirect('/login');
        }
    }    /**
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
        $rolesConfig = $this->getRegistrationRoles();
        $wantsJson = $request->expectsJson() || $request->isAjax();
        $roleInput = (string) $request->input('role');
        $role = $this->resolveRegistrationRole($roleInput, $rolesConfig);

        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $passwordConfirm = (string) $request->input('password_confirm');

        $roleDefinition = $rolesConfig[$role] ?? [];
        $roleFields = is_array($roleDefinition['fields'] ?? null) ? $roleDefinition['fields'] : [];

        $dynamicValues = [];
        foreach ($roleFields as $field) {
            $fieldName = $field['name'] ?? null;
            if (!$fieldName) {
                continue;
            }

            $raw = $request->input($fieldName);
            $dynamicValues[$fieldName] = is_array($raw) ? $raw : trim((string) $raw);
        }

        $oldInput = array_merge(
            ['name' => $name, 'email' => $email, 'role' => $role],
            $dynamicValues
        );

        if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            return $this->registrationErrorRedirect($oldInput, 'Please fill out all required fields.', $wantsJson);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->registrationErrorRedirect($oldInput, 'Please provide a valid email address.', $wantsJson);
        }

        if (strlen($password) < 6) {
            return $this->registrationErrorRedirect($oldInput, 'Password must be at least 6 characters.', $wantsJson);
        }

        if ($password !== $passwordConfirm) {
            return $this->registrationErrorRedirect($oldInput, 'Passwords do not match.', $wantsJson);
        }

        $fieldErrors = [];
        $userOverrides = [];
        $metadataPayload = [];

        foreach ($roleFields as $field) {
            $fieldName = $field['name'] ?? null;
            if (!$fieldName) {
                continue;
            }

            $value = $dynamicValues[$fieldName] ?? '';
            $fieldValidationErrors = $this->validateRegistrationField($field, $value);
            if (!empty($fieldValidationErrors)) {
                $fieldErrors = array_merge($fieldErrors, $fieldValidationErrors);
                continue;
            }

            $this->assignRegistrationFieldValue($field, $value, $userOverrides, $metadataPayload);
        }

        if (!empty($fieldErrors)) {
            return $this->registrationErrorRedirect(
                $oldInput,
                'Please review your details: ' . implode(' ', $fieldErrors),
                $wantsJson
            );
        }

        $userModel = new User();

        try {
            $existing = $userModel->findByEmail($email);
            if ($existing) {
                return $this->registrationErrorRedirect($oldInput, 'An account with that email already exists.', $wantsJson);
            }
        } catch (\Throwable $e) {
            return $this->registrationErrorRedirect($oldInput, 'Unable to access database. Please try again later.', $wantsJson);
        }

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

        $imageManager = new ProfileImageManager();
        $profileImagePath = null;

        if ($request->hasFile('profile_photo')) {
            $uploadResult = $imageManager->store($request->file('profile_photo') ?? []);
            if (!$uploadResult['ok']) {
                return $this->registrationErrorRedirect(
                    $oldInput,
                    $uploadResult['error'] ?? 'Failed to upload the profile photo.',
                    $wantsJson
                );
            }

            $profileImagePath = $uploadResult['path'] ?? null;
            if ($profileImagePath === null) {
                return $this->registrationErrorRedirect($oldInput, 'Failed to process the uploaded profile photo.', $wantsJson);
            }
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'type' => $role,
            'password' => $password,
        ];

        if ($roleId !== null) {
            $data['role_id'] = $roleId;
        }

        if ($profileImagePath !== null) {
            $data['profile_image_path'] = $profileImagePath;
        }

        if (!empty($userOverrides)) {
            foreach ($userOverrides as $column => $value) {
                $data[$column] = $value;
            }
        }

        if (!empty($metadataPayload)) {
            $data['metadata'] = $metadataPayload;
        }

        try {
            $newId = $userModel->createUser($data);
            if ($newId === false) {
                if ($profileImagePath !== null) {
                    $imageManager->delete($profileImagePath);
                }
                return $this->registrationErrorRedirect($oldInput, 'Failed to create account. Please try again.', $wantsJson);
            }
        } catch (\Throwable $e) {
            if ($profileImagePath !== null) {
                $imageManager->delete($profileImagePath);
            }
            return $this->registrationErrorRedirect($oldInput, 'Failed to create account: ' . $e->getMessage(), $wantsJson);
        }

        session()->flash('success', 'Account created. Please sign in.');
        if ($wantsJson) {
            return \Core\Http\Response::json([
                'success' => true,
                'message' => 'Account created. Please sign in.',
                'redirect' => '/login',
            ]);
        }
        return redirect('/login');
    }

    /**
     * Retrieve configured registration roles, falling back to defaults when absent.
     */
    private function getRegistrationRoles(): array
    {
        $roles = config('registration.roles', []);
        if (is_array($roles) && !empty($roles)) {
            return $roles;
        }

        return [
            'customer' => [
                'label' => 'Customer',
                'option' => 'Customer — Track recycling requests',
                'summary' => 'Ideal for residents scheduling and monitoring recycling pickups.',
                'fields' => [],
            ],
            'collector' => [
                'label' => 'Collector',
                'option' => 'Collector — Manage pickups & routes',
                'summary' => 'Optimized for field teams coordinating route assignments and pickups.',
                'fields' => [],
            ],
            'company' => [
                'label' => 'Company',
                'option' => 'Company — Operations & analytics',
                'summary' => 'Built for company managers supervising recycling performance and KPIs.',
                'fields' => [],
            ],
            'admin' => [
                'label' => 'Admin',
                'option' => 'Admin — Platform configuration',
                'summary' => 'Reserved for administrators configuring roles, permissions, and platform settings.',
                'fields' => [],
            ],
        ];
    }

    /**
     * Determine which role to use for registration.
     */
    private function resolveRegistrationRole(string $requestedRole, array $rolesConfig): string
    {
        if ($requestedRole !== '' && isset($rolesConfig[$requestedRole])) {
            return $requestedRole;
        }

        if (isset($rolesConfig['customer'])) {
            return 'customer';
        }

        $first = array_key_first($rolesConfig);
        return $first ?: 'customer';
    }

    /**
     * Validate a dynamic field and return error messages.
     */
    private function validateRegistrationField(array $field, $value): array
    {
        $rules = $this->normalizeFieldRules($field['rules'] ?? []);
        if (empty($rules)) {
            return [];
        }

        $label = $field['label'] ?? ($field['name'] ?? 'Field');
        $label = is_string($label) ? $label : 'Field';

        $candidate = $value;
        if (is_string($candidate)) {
            $candidate = trim($candidate);
        }

        $isRequired = in_array('required', $rules, true);
        $hasValue = !$this->isEmptyFieldValue($candidate);

        if ($isRequired && !$hasValue) {
            return [sprintf('%s is required.', $label)];
        }

        if (!$hasValue) {
            return [];
        }

        $errors = [];
        $stringValue = is_array($candidate) ? '' : (string) $candidate;

        foreach ($rules as $rule) {
            if ($rule === 'required' || $rule === 'nullable') {
                continue;
            }

            if ($rule === 'email') {
                if (!filter_var($stringValue, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = sprintf('%s must be a valid email address.', $label);
                }
                continue;
            }

            if (strpos($rule, 'min:') === 0) {
                $min = (int) substr($rule, 4);
                if ($min > 0 && mb_strlen($stringValue) < $min) {
                    $errors[] = sprintf('%s must be at least %d characters.', $label, $min);
                }
                continue;
            }

            if (strpos($rule, 'max:') === 0) {
                $max = (int) substr($rule, 4);
                if ($max > 0 && mb_strlen($stringValue) > $max) {
                    $errors[] = sprintf('%s may not be greater than %d characters.', $label, $max);
                }
                continue;
            }

            if (strpos($rule, 'equals:') === 0) {
                $expected = substr($rule, 7);
                if ($stringValue !== $expected) {
                    $errors[] = sprintf('%s is invalid.', $label);
                }
                continue;
            }

            if (strpos($rule, 'in:') === 0) {
                $allowed = array_map('trim', explode(',', substr($rule, 3)));
                if (!in_array($stringValue, $allowed, true)) {
                    $errors[] = sprintf('%s must be one of the allowed options.', $label);
                }
                continue;
            }

            if (strpos($rule, 'regex:') === 0) {
                $pattern = substr($rule, 6);
                if ($pattern !== '') {
                    $regex = $pattern;
                    if (@preg_match($regex, '') === false) {
                        $delimiter = substr($pattern, 0, 1);
                        if ($delimiter !== '' && substr($pattern, -1) === $delimiter) {
                            $regex = $pattern;
                        } else {
                            $regex = '/' . trim($pattern, '/') . '/';
                        }
                    }

                    if (@preg_match($regex, $stringValue) !== 1) {
                        $errors[] = sprintf('%s format is invalid.', $label);
                    }
                }
                continue;
            }
        }

        return $errors;
    }

    /**
     * Store validated field values into user columns or metadata payloads.
     */
    private function assignRegistrationFieldValue(array $field, $value, array &$userOverrides, array &$metadataPayload): void
    {
        $rules = $this->normalizeFieldRules($field['rules'] ?? []);
        $isNullable = in_array('nullable', $rules, true);
        $isEmpty = $this->isEmptyFieldValue($value);

        $store = $field['store'] ?? 'metadata';
        $column = $field['column'] ?? ($field['name'] ?? null);
        $metadataKey = $field['metadata_key'] ?? ($field['name'] ?? null);

        if ($store === 'user') {
            if (!$column) {
                return;
            }

            if ($isEmpty) {
                if ($isNullable) {
                    $userOverrides[$column] = null;
                }
                return;
            }

            $userOverrides[$column] = $value;
            return;
        }

        if (!$metadataKey) {
            return;
        }

        if ($isEmpty) {
            if ($isNullable) {
                $metadataPayload[$metadataKey] = null;
            }
            return;
        }

        $metadataPayload[$metadataKey] = $value;
    }

    /**
     * Normalize rule definitions into an array of strings.
     */
    private function normalizeFieldRules($rules): array
    {
        if (is_string($rules)) {
            $rules = array_map('trim', explode('|', $rules));
        }

        if (!is_array($rules)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($rule) {
            return is_string($rule) ? trim($rule) : '';
        }, $rules), static function ($rule) {
            return $rule !== '';
        }));
    }

    /**
     * Check whether a provided value should be considered empty.
     */
    private function isEmptyFieldValue($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isEmptyFieldValue($item)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Flash error state and redirect back to the registration page.
     */
    private function registrationErrorRedirect(array $oldInput, string $message, bool $wantsJson = false): Response
    {
        if ($wantsJson) {
            return \Core\Http\Response::errorJson($message, 422);
        }

        session()->flash('old', $oldInput);
        session()->flash('error', $message);

        return redirect('/register');
    }
}
