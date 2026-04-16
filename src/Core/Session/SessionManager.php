<?php

namespace Core\Session;

/**
 * Session Manager Class
 * 
 * Handles session management with multiple drivers and security features.
 * Similar to Laravel's session management functionality.
 * 
 * @package Core\Session
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class SessionManager
{
    /**
     * Session configuration
     * 
     * @var array
     */
    protected array $config;

    /**
     * Session started flag
     * 
     * @var bool
     */
    protected bool $started = false;

    /**
     * Create new SessionManager instance
     * 
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Try multiple session paths in order of preference
        $sessionPaths = [
            dirname(__DIR__, 3) . '/storage/sessions',  // Project sessions directory
            sys_get_temp_dir() . '/ecocycle_sessions',  // System temp with app prefix
            sys_get_temp_dir()                          // System temp directory
        ];

        $defaultSessionPath = null;
        foreach ($sessionPaths as $path) {
            // Try to create directory if it doesn't exist
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }

            // Check if directory is writable
            if (is_dir($path) && is_writable($path)) {
                $defaultSessionPath = $path;
                break;
            }
        }

        // Final fallback
        if (!$defaultSessionPath) {
            $defaultSessionPath = sys_get_temp_dir();
        }

        $this->config = array_merge([
            'driver' => 'file',
            'lifetime' => 120,
            'encrypt' => false,
            'name' => 'ecocycle_session',
            'path' => $defaultSessionPath,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'lax'
        ], $config);
    }

    /**
     * Start session
     * 
     * @return void
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        // Check if headers have already been sent
        if (headers_sent()) {
            error_log('Warning: Cannot start session - headers already sent');
            return;
        }

        $this->configure();

        // Try to start session with better error handling
        if (!session_start()) {
            $error = error_get_last();
            $errorMessage = $error ? $error['message'] : 'Unknown session error';
            throw new \RuntimeException("Failed to start session: {$errorMessage}. Session path: " . session_save_path());
        }

        $this->started = true;

        // Generate CSRF token if it doesn't exist
        if (!$this->has('_token')) {
            $this->put('_token', $this->generateToken());
        }

        // Clean up old flash data from previous request
        $this->cleanupFlashData();
    }

    /**
     * Configure session settings
     * 
     * @return void
     */
    protected function configure(): void
    {
        // Check if headers have already been sent - if so, skip configuration
        if (headers_sent()) {
            return;
        }

        ini_set('session.name', $this->config['name']);
        ini_set('session.gc_maxlifetime', (string) ($this->config['lifetime'] * 60));
        ini_set('session.cookie_lifetime', (string) ($this->config['lifetime'] * 60));
        ini_set('session.cookie_httponly', $this->config['http_only'] ? '1' : '0');
        ini_set('session.cookie_secure', $this->config['secure'] ? '1' : '0');
        ini_set('session.cookie_samesite', $this->config['same_site']);

        if ($this->config['driver'] === 'file') {
            $sessionPath = $this->config['path'];

            // Ensure session directory exists and is writable
            if (!is_dir($sessionPath)) {
                if (!@mkdir($sessionPath, 0777, true)) {
                    // If we can't create the directory, fall back to system temp
                    $sessionPath = sys_get_temp_dir();
                    $this->config['path'] = $sessionPath;
                }
            }

            if (!is_writable($sessionPath)) {
                // If directory is not writable, try to fix permissions
                @chmod($sessionPath, 0777);

                // If still not writable, fall back to system temp
                if (!is_writable($sessionPath)) {
                    $sessionPath = sys_get_temp_dir();
                    $this->config['path'] = $sessionPath;
                }
            }

            ini_set('session.save_path', $sessionPath);
        }
    }

    /**
     * Get session value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Put value in session
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * Alias for put() method (Laravel-like syntax)
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->put($key, $value);
    }

    /**
     * Check if session has key
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     * 
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * Flash data to session (available for next request only)
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function flash(string $key, $value): void
    {
        $flashKey = "_flash.{$key}";
        $this->put($flashKey, $value);

        $newFlashKeys = $this->get('_flash_new', []);
        if (!in_array($flashKey, $newFlashKeys, true)) {
            $newFlashKeys[] = $flashKey;
            $this->put('_flash_new', $newFlashKeys);
        }
    }

    /**
     * Get flash data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFlash(string $key, $default = null)
    {
        $flashKey = "_flash.{$key}";
        return $this->get($flashKey, $default);
    }

    /**
     * Get all session data
     * 
     * @return array
     */
    public function all(): array
    {
        $this->start();
        return $_SESSION;
    }

    /**
     * Clear all session data
     * 
     * @return void
     */
    public function flush(): void
    {
        $this->start();
        $_SESSION = [];
    }

    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOld
     * @return bool
     */
    public function regenerate(bool $deleteOld = true): bool
    {
        $this->start();
        return session_regenerate_id($deleteOld);
    }

    /**
     * Destroy session
     * 
     * @return bool
     */
    public function destroy(): bool
    {
        $this->start();
        $this->flush();
        $this->started = false;
        return session_destroy();
    }

    /**
     * Get session ID
     * 
     * @return string
     */
    public function getId(): string
    {
        $this->start();
        return session_id();
    }

    /**
     * Set session ID
     * 
     * @param string $id
     * @return void
     */
    public function setId(string $id): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Cannot set session ID when session is already active');
        }
        session_id($id);
    }

    /**
     * Get CSRF token
     * 
     * @return string
     */
    public function getToken(): string
    {
        $this->start();
        if (!$this->has('_token')) {
            $this->put('_token', $this->generateToken());
        }
        return $this->get('_token');
    }

    /**
     * Get CSRF token (alias for getToken)
     * 
     * @return string
     */
    public function token(): string
    {
        return $this->getToken();
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        $sessionToken = $this->getToken();
        return !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    /**
     * Generate new token
     * 
     * @return string
     */
    protected function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Clean up old flash data
     * 
     * @return void
     */
    protected function cleanupFlashData(): void
    {
        $this->start();

        // Remove flash keys that were created in the previous request.
        $oldFlashKeys = $this->get('_flash_old', []);
        foreach ($oldFlashKeys as $key) {
            unset($_SESSION[$key]);
        }

        // Promote current new flash keys so they survive this request and
        // are cleaned up on the following request.
        $newFlashKeys = $this->get('_flash_new', []);
        $this->put('_flash_old', $newFlashKeys);
        $this->put('_flash_new', []);
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->has('user_id');
    }

    /**
     * Log in user
     * 
     * @param int $userId
     * @param array $userData
     * @return void
     */
    public function login(int $userId, array $userData = []): void
    {
        // Regenerate session ID to prevent session fixation
        $this->regenerate();

        $this->put('user_id', $userId);
        $this->put('user_data', $userData);
        $this->put('login_time', time());
        $this->put('user_ip', $_SERVER['REMOTE_ADDR'] ?? '');
        $this->put('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    /**
     * Log out user
     * 
     * @return void
     */
    public function logout(): void
    {
        $this->forget('user_id');
        $this->forget('user_data');
        $this->regenerate();
    }

    /**
     * Get authenticated user ID
     * 
     * @return int|null
     */
    public function userId(): ?int
    {
        return $this->get('user_id');
    }

    /**
     * Get authenticated user data
     * 
     * @return array
     */
    public function userData(): array
    {
        return $this->get('user_data', []);
    }

    /**
     * Validate session security
     * 
     * @return bool
     */
    public function validateSession(): bool
    {
        if (!$this->isAuthenticated()) {
            return true; // Non-authenticated sessions don't need validation
        }

        // Check if IP address matches (optional security measure)
        $sessionIp = $this->get('user_ip', '');
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check if user agent matches (basic fingerprinting)
        $sessionAgent = $this->get('user_agent', '');
        $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check session timeout (if configured)
        $loginTime = $this->get('login_time', 0);
        $maxLifetime = $this->config['lifetime'] * 60;

        if ($loginTime > 0 && (time() - $loginTime) > $maxLifetime) {
            return false; // Session expired
        }

        // For now, just check user agent (IP checking can be too strict with proxies)
        return $sessionAgent === $currentAgent;
    }

    /**
     * Check if session is expired
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        $loginTime = $this->get('login_time', 0);
        if ($loginTime === 0) {
            return false; // No login time set
        }

        $maxLifetime = $this->config['lifetime'] * 60;
        return (time() - $loginTime) > $maxLifetime;
    }
}
