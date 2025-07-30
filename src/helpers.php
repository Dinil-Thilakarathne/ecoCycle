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
     * Get configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return app('config')->get($key, $default);
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

if (!function_exists('response')) {
    /**
     * Create a response instance (Next.js style)
     * 
     * @return \Core\Http\Response
     */
    function response()
    {
        return new class {
            public function json($data, $status = 200)
            {
                $response = new \Core\Http\Response();
                $response->setStatusCode($status);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode($data, JSON_PRETTY_PRINT));
                return $response;
            }

            public function html($content, $status = 200)
            {
                $response = new \Core\Http\Response();
                $response->setStatusCode($status);
                $response->setHeader('Content-Type', 'text/html');
                $response->setContent($content);
                return $response;
            }

            public function redirect($url, $status = 302)
            {
                $response = new \Core\Http\Response();
                $response->setStatusCode($status);
                $response->setHeader('Location', $url);
                return $response;
            }
        };
    }
}

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
        $response->setBody($content);
        $response->setStatus($status);

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

        // Set the response body
        $response->setBody($content);
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
        $response->setStatus($code);
        $response->setBody($message ?: "Error $code");

        // Send response and exit
        echo $response->getBody();
        exit($code);
    }
}