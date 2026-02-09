<?php

/**
 * Global Helper Functions
 * 
 * Provides convenient utility functions for the framework.
 */

if (!function_exists('dd')) {
    /**
     * Dump and die - for debugging
     * 
     * @param mixed ...$vars
     * @return void
     */
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die(1);
    }
}

if (!function_exists('material_min_bid')) {
    /**
     * Get minimum bid for a material/category
     *
     * @param string $type Material key (plastic, paper, metal, glass, organic)
     * @param mixed $default Default value if not set
     * @return float
     */
    function material_min_bid(string $type, $default = 0): float
    {
        return (float) \Core\Config::get("data.minimum_bids.{$type}", $default);
    }
}

if (!function_exists('material_color')) {
    /**
     * Get hex color for a material/category
     *
     * @param string $type Material key
     * @param string $default Default hex color
     * @return string
     */
    function material_color(string $type, string $default = '#000000'): string
    {
        return (string) \Core\Config::get("data.material_colors.{$type}", $default);
    }
}

if (!function_exists('format_rs')) {
    /**
     * Format a number as Rupees currency string
     *
     * @param float|int $amount
     * @return string
     */
    function format_rs($amount): string
    {
        return 'Rs. ' . number_format((float) $amount, 2);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variable - for debugging
     * 
     * @param mixed $var
     * @return void
     */
    function dump($var): void
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}

if (!function_exists('consoleLog')) {
    /**
     * Write values to the browser console from PHP.
     *
     * Usage: consoleLog('label', $var, $arr);
     *
     * @param mixed ...$args
     * @return void
     */
    function consoleLog(...$args): void
    {
        // Prepare JS-safe JSON fragments for each argument
        $parts = [];
        foreach ($args as $a) {
            $json = @json_encode($a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                // Fallback to string representation
                $json = json_encode((string) $a);
            }
            // Prevent closing the script tag if the data contains it
            $json = str_replace('</script>', '<\/script>', $json);
            $parts[] = $json;
        }

        $js = 'console.log(' . implode(', ', $parts) . ');';

        // Echo script tag to log in browser console
        echo "<script>" . $js . "</script>";
    }
}

if (!function_exists('app')) {
    /**
     * Get application instance or resolve from container
     * 
     * @param string|null $abstract
     * @return mixed
     */
    function app(?string $abstract = null)
    {
        $instance = Core\Application::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance->container()->make($abstract);
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value (static access to avoid container recursion)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return \Core\Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

// ...existing code... (removed duplicate anonymous response() helper)

if (!function_exists('base_path')) {
    /**
     * Get the base path of the application
     * 
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        // In CLI scripts the Application singleton may not be initialized.
        $basePath = null;

        if (class_exists('Core\Application')) {
            $app = Core\Application::getInstance();
            if ($app) {
                $basePath = $app->basePath();
            }
        }

        if (!$basePath) {
            // Fallback using file system heuristic: src/helpers.php -> src -> root
            // __DIR__ is .../src
            // dirname(__DIR__) is .../ (root)
            $basePath = dirname(__DIR__);
        }

        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage path
     * 
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        $storagePath = base_path('storage');
        return $path ? $storagePath . '/' . ltrim($path, '/') : $storagePath;
    }
}

if (!function_exists('request')) {
    /**
     * Get current request instance
     * 
     * @return Core\Http\Request
     */
    function request(): Core\Http\Request
    {
        return app('request');
    }
}

if (!function_exists('response')) {
    /**
     * Create a response instance
     * 
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return Core\Http\Response
     */
    function response(string $content = '', int $status = 200, array $headers = []): Core\Http\Response
    {
        $response = app('response');
        $response->setContent($content);
        $response->setStatusCode($status);

        foreach ($headers as $key => $value) {
            $response->setHeader($key, $value);
        }

        return $response;
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response
     * 
     * @param string $url
     * @param int $status
     * @return Core\Http\Response
     */
    function redirect(string $url, int $status = 302): Core\Http\Response
    {
        $response = app('response');
        $response->setStatusCode($status);
        $response->setHeader('Location', $url);

        return $response;
    }
}

if (!function_exists('session')) {
    /**
     * Get session manager instance or session value
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function session(?string $key = null, $default = null)
    {
        $session = app('session');

        if ($key === null) {
            return $session;
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('auth')) {
    /**
     * Get authenticated user
     * 
     * @return array|null
     */
    function auth(): ?array
    {
        $session = session();

        if (!$session->has('user_id')) {
            return null;
        }

        return [
            'id' => $session->get('user_id'),
            'name' => $session->get('user_name'),
            'email' => $session->get('user_email'),
            'role' => $session->get('user_role')
        ];
    }
}

if (!function_exists('view')) {
    /**
     * Render a view
     * 
     * @param string $view
     * @param array $data
     * @param string|null $layout Optional layout path under src/Views (e.g. 'layouts/app')
     * @return Core\Http\Response
     */
    function view(string $view, array $data = [], ?string $layout = null): Core\Http\Response
    {
        $response = app('response');

        // Extract data for view
        extract($data);

        // Start output buffering for the view content
        ob_start();

        // Include the view file
        $viewPath = app()->basePath() . "/src/Views/{$view}.php";

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new \Exception("View '{$view}' not found at {$viewPath}");
        }

        // Get the rendered content
        $content = ob_get_clean();

        // If a layout is requested, render the layout with $content available
        if ($layout) {
            ob_start();
            $layoutPath = app()->basePath() . "/src/Views/{$layout}.php";
            if (file_exists($layoutPath)) {
                // $content is in scope for the layout file
                include $layoutPath;
                $final = ob_get_clean();
            } else {
                // Layout missing — fall back to raw content
                $final = $content;
            }

            $response->setContent($final);
        } else {
            $response->setContent($content);
        }

        $response->setHeader('Content-Type', 'text/html');

        return $response;
    }
}

if (!function_exists('url')) {
    /**
     * Generate URL
     * 
     * @param string $path
     * @return string
     */
    function url(string $path = ''): string
    {
        $baseUrl = env('APP_URL', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Generate asset URL
     * 
     * @param string $path
     * @return string
     */
    function asset(string $path): string
    {
        return url($path);
    }
}

if (!function_exists('route')) {
    /**
     * Generate named route URL
     * 
     * @param string $name
     * @param array $parameters
     * @return string
     */
    function route(string $name, array $parameters = []): string
    {
        // This would integrate with named routes
        return url($name);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get CSRF token
     * 
     * @return string
     */
    function csrf_token(): string
    {
        return session()->getToken();
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, $default = null)
    {
        $oldData = session()->getFlash('old', []);
        return $oldData[$key] ?? $default;
    }
}

if (!function_exists('str_contains')) {
    /**
     * Check if string contains substring (PHP 8 compatibility)
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    function str_contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('abort')) {
    /**
     * Abort the request with an HTTP status code
     * 
     * @param int $code
     * @param string $message
     * @return void
     */
    function abort(int $code = 404, string $message = ''): void
    {
        $response = app('response');
        $response->setStatusCode($code);
        $response->setContent($message ?: "Error $code");

        // Send response and exit
        echo $response->getContent();
        exit($code);
    }
}

if (!function_exists('dashboard_redirect')) {
    /**
     * Redirect user to their appropriate dashboard based on role
     * 
     * @param array|null $user
     * @return Core\Http\Response
     */
    function dashboard_redirect(?array $user = null): Core\Http\Response
    {
        if (!$user) {
            $user = auth();
        }

        if (!$user) {
            return redirect('/login');
        }

        $dashboardUrls = [
            'admin' => '/admin',
            'customer' => '/customer',
            'collector' => '/collector',
            'company' => '/company'
        ];

        $role = $user['role'] ?? 'customer';
        $url = $dashboardUrls[$role] ?? '/customer';

        return redirect($url);
    }
}

if (!function_exists('can_access_dashboard')) {
    /**
     * Check if user can access a specific dashboard
     * 
     * @param string $dashboardType
     * @param array|null $user
     * @return bool
     */
    function can_access_dashboard(string $dashboardType, ?array $user = null): bool
    {
        if (!$user) {
            $user = auth();
        }

        if (!$user) {
            return false;
        }

        return $user['role'] === $dashboardType;
    }
}

if (!function_exists('getNavigation')) {
    /**
     * Get navigation items for a user type
     * 
     * @param string $userType The user role (admin, customer, collector, company)
     * @return array Navigation items
     */
    function getNavigation(string $userType): array
    {
        return \EcoCycle\Core\Navigation\NavigationConfig::getNavigation($userType);
    }
}

if (!function_exists('isActiveNavigation')) {
    /**
     * Check if a navigation URL is active
     * 
     * @param string $navUrl The navigation URL
     * @param string|null $currentUrl Current URL (optional, uses REQUEST_URI if not provided)
     * @return bool Whether the navigation item is active
     */
    function isActiveNavigation(string $navUrl, ?string $currentUrl = null): bool
    {
        $currentUrl = $currentUrl ?? ($_SERVER['REQUEST_URI'] ?? '');
        return \EcoCycle\Core\Navigation\NavigationConfig::isActiveUrl($navUrl, $currentUrl);
    }
}

if (!function_exists('getBreadcrumbs')) {
    /**
     * Get breadcrumb navigation for current page
     * 
     * @param string $userType The user role
     * @param string|null $currentUrl Current URL (optional, uses REQUEST_URI if not provided)
     * @return array Breadcrumb items
     */
    function getBreadcrumbs(string $userType, ?string $currentUrl = null): array
    {
        $currentUrl = $currentUrl ?? ($_SERVER['REQUEST_URI'] ?? '');
        return \EcoCycle\Core\Navigation\NavigationConfig::getBreadcrumbs($userType, $currentUrl);
    }
}

if (!function_exists('validateRoutes')) {
    /**
     * Validate that all navigation routes have corresponding controller methods
     * 
     * @return array Array of missing methods
     */
    function validateRoutes(): array
    {
        return \EcoCycle\Core\Navigation\RouteConfig::validateRoutes();
    }
}

if (!function_exists('listRoutes')) {
    /**
     * Get all dashboard routes
     * 
     * @return array Array of route definitions
     */
    function listRoutes(): array
    {
        return \EcoCycle\Core\Navigation\RouteConfig::getAllDashboardRoutes();
    }
}

if (!function_exists('getWasteCategories')) {
    /**
     * Get waste categories
     * 
     * @return array
     */
    function getWasteCategories(): array
    {
        return \Core\Config::get('data.wasteCategories', []);
    }
}

// Dummy data accessors (centralized)
if (!function_exists('dummy_data')) {
    /**
     * Retrieve a segment of dummy data (development only)
     * @param string|null $key
     * @return mixed
     */
    function dummy_data(?string $key = null)
    {
        static $data = null;
        if ($data === null) {
            $path = base_path('config/dummy.php');
            if (file_exists($path)) {
                $data = require $path;
            } else {
                $data = [];
            }
        }
        if ($key === null)
            return $data;
        return $data[$key] ?? null;
    }
}

// logout the user -> need to redirect to the login page with removing all cache data
if (!function_exists('logout')) {
    /**
     * Logout the current user
     * 
     * @return Core\Http\Response
     */
    function logout(): Core\Http\Response
    {
        $session = session();

        // Best-effort server-side session cleanup. The session manager in this app
        // may implement clear(), destroy(), regenerateToken(). Call whichever
        // exist to avoid fatal errors on different session implementations.
        if (is_object($session)) {
            if (method_exists($session, 'clear')) {
                $session->clear();
            }

            if (method_exists($session, 'destroy')) {
                // Some session managers provide a destroy method
                $session->destroy();
            }

            if (method_exists($session, 'regenerateToken')) {
                $session->regenerateToken();
            }
        }

        // Also attempt native PHP session cleanup in case session manager wraps PHP
        if (PHP_SAPI !== 'cli') {
            // If PHP session is active, clear and destroy it
            if (session_status() === PHP_SESSION_ACTIVE) {
                // Clear $_SESSION array
                $_SESSION = [];

                // Destroy session data on server
                @session_destroy();

                // Remove session cookie from client
                $sessionName = session_name();
                if (!empty($sessionName) && ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(
                        $sessionName,
                        '',
                        time() - 42000,
                        $params['path'] ?? '/',
                        $params['domain'] ?? '',
                        $params['secure'] ?? false,
                        $params['httponly'] ?? true
                    );
                }
            }
        }

        // Prevent caching of authenticated pages
        $response = redirect('/login');
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');

        return $response;
    }
}

if (!function_exists('mailer')) {
    /**
     * Get the Mailer instance
     * 
     * @return Core\Mail\Mailer
     */
    function mailer(): Core\Mail\Mailer
    {
        static $mailer = null;

        if ($mailer === null) {
            $transport = new Core\Mail\SmtpMailer();
            $mailer = new Core\Mail\Mailer($transport);
        }

        return $mailer;
    }
}

if (!function_exists('sendMail')) {
    /**
     * Send an email using a template
     * 
     * @param string $to Recipient email address
     * @param string $template Template name (without .html.php or .text.php extension)
     * @param array $data Data to pass to the template
     * @param string|null $subject Email subject (optional, can be in $data['subject'])
     * @return bool True on success, false on failure
     */
    function sendMail(string $to, string $template, array $data = [], ?string $subject = null): bool
    {
        try {
            return mailer()->sendTemplate($to, $template, $data, $subject);
        } catch (\Exception $e) {
            // Log the error
            error_log("Mail sending failed: " . $e->getMessage());
            return false;
        }
    }
}

// ============================================================================
// Email Verification & Password Reset Helper Functions
// ============================================================================

if (!function_exists('generateVerificationToken')) {
    /**
     * Generate a secure email verification token
     * 
     * @return string 64-character hex token
     */
    function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('generatePasswordResetToken')) {
    /**
     * Generate a secure password reset token
     * 
     * @return string 64-character hex token
     */
    function generatePasswordResetToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('createPasswordResetToken')) {
    /**
     * Create a password reset token in the database
     * 
     * @param string $email User email
     * @param string $token Reset token
     * @param int $expiresInHours Token expiration time in hours (default: 1)
     * @return bool True on success
     */
    function createPasswordResetToken(string $email, string $token, int $expiresInHours = 1): bool
    {
        try {
            $db = new \Core\Database();

            $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInHours * 3600));

            $db->query(
                'INSERT INTO password_reset_tokens (email, token, created_at, expires_at, used) 
                 VALUES (?, ?, NOW(), ?, FALSE)',
                [$email, $token, $expiresAt]
            );

            return true;
        } catch (\Exception $e) {
            error_log("Failed to create password reset token: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('validatePasswordResetToken')) {
    /**
     * Validate a password reset token
     * 
     * @param string $token Reset token
     * @return array|null Token data if valid, null otherwise
     */
    function validatePasswordResetToken(string $token): ?array
    {
        try {
            $db = new \Core\Database();

            $result = $db->fetch(
                'SELECT * FROM password_reset_tokens 
                 WHERE token = ? 
                 AND used = FALSE 
                 AND expires_at > NOW()
                 LIMIT 1',
                [$token]
            );

            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Failed to validate password reset token: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('markPasswordResetTokenAsUsed')) {
    /**
     * Mark a password reset token as used
     * 
     * @param string $token Reset token
     * @return bool True on success
     */
    function markPasswordResetTokenAsUsed(string $token): bool
    {
        try {
            $db = new \Core\Database();

            $db->query(
                'UPDATE password_reset_tokens 
                 SET used = TRUE, used_at = NOW() 
                 WHERE token = ?',
                [$token]
            );

            return true;
        } catch (\Exception $e) {
            error_log("Failed to mark password reset token as used: " . $e->getMessage());
            return false;
        }
    }
}