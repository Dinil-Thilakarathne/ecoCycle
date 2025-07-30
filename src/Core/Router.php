<?php

namespace Core;

/**
 * Advanced Router Class
 * 
 * Handles URL routing with parameters, middleware, and route groups.
 * Similar to Laravel's routing functionality.
 * 
 * @package Core
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Router
{
    /**
     * Registered routes
     * 
     * @var array
     */
    protected array $routes = [];

    /**
     * Route groups stack
     * 
     * @var array
     */
    protected array $groupStack = [];

    /**
     * Current route parameters
     * 
     * @var array
     */
    protected array $parameters = [];

    /**
     * Add GET route
     * 
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function get(string $path, $action, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $action, $middleware);
    }

    /**
     * Add POST route
     * 
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function post(string $path, $action, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $action, $middleware);
    }

    /**
     * Add PUT route
     * 
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function put(string $path, $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $action, $middleware);
    }

    /**
     * Add DELETE route
     * 
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function delete(string $path, $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $action, $middleware);
    }

    /**
     * Add PATCH route
     * 
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function patch(string $path, $action, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $action, $middleware);
    }

    /**
     * Add route for multiple methods
     * 
     * @param array $methods
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function addMultiple(array $methods, string $path, $action, array $middleware = []): void
    {
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $action, $middleware);
        }
    }

    /**
     * Add route for all HTTP methods
     * 
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function any(string $path, $action, array $middleware = []): void
    {
        $this->addMultiple(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $action, $middleware);
    }

    /**
     * Create route group with shared attributes
     * 
     * @param array $attributes
     * @param callable $callback
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        call_user_func($callback, $this);
        array_pop($this->groupStack);
    }

    /**
     * Add route to collection
     * 
     * @param string $method
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    protected function addRoute(string $method, string $path, $action, array $middleware = []): void
    {
        $route = [
            'method' => strtoupper($method),
            'path' => $this->getGroupPrefix() . $path,
            'action' => $action,
            'middleware' => array_merge($this->getGroupMiddleware(), $middleware),
            'parameters' => []
        ];

        $this->routes[] = $route;
    }

    /**
     * Get group prefix from stack
     * 
     * @return string
     */
    protected function getGroupPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return $prefix;
    }

    /**
     * Get group middleware from stack
     * 
     * @return array
     */
    protected function getGroupMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }
        return $middleware;
    }

    /**
     * Match request against routes
     * 
     * @param string $path
     * @param string $method
     * @return array|null
     */
    public function match(string $path, string $method): ?array
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->pathMatches($route['path'], $path)) {
                $route['parameters'] = $this->parameters;
                return $route;
            }
        }

        return null;
    }

    /**
     * Check if path matches route pattern
     * 
     * @param string $routePath
     * @param string $requestPath
     * @return bool
     */
    protected function pathMatches(string $routePath, string $requestPath): bool
    {
        $this->parameters = [];

        // Exact match
        if ($routePath === $requestPath) {
            return true;
        }

        // Convert route path to regex pattern
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestPath, $matches)) {
            // Extract parameter names from route path
            preg_match_all('/\{([^}]+)\}/', $routePath, $paramMatches);

            if (isset($paramMatches[1])) {
                // Map parameter values to names
                for ($i = 0; $i < count($paramMatches[1]); $i++) {
                    if (isset($matches[$i + 1])) {
                        $this->parameters[$paramMatches[1][$i]] = $matches[$i + 1];
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get all registered routes
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get current route parameters
     * 
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Generate URL for named route
     * 
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function url(string $name, array $parameters = []): string
    {
        // This would be implemented with named routes
        // For now, return a placeholder
        return '/';
    }

    /**
     * Add named route
     * 
     * @param string $name
     * @param string $method
     * @param string $path
     * @param callable|string $action
     * @param array $middleware
     * @return void
     */
    public function name(string $name, string $method, string $path, $action, array $middleware = []): void
    {
        $route = [
            'name' => $name,
            'method' => strtoupper($method),
            'path' => $this->getGroupPrefix() . $path,
            'action' => $action,
            'middleware' => array_merge($this->getGroupMiddleware(), $middleware),
            'parameters' => []
        ];

        $this->routes[] = $route;
    }
}