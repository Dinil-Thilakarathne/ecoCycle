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
        $basePath = app()->basePath();
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
        $response->setStatus($status);
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
     * @return Core\Http\Response
     */
    function view(string $view, array $data = []): Core\Http\Response
    {
        $response = app('response');

        // Extract data for view
        extract($data);

        // Start output buffering
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

        // Set the response content
        $response->setContent($content);
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