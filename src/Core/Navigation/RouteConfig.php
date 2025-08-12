<?php

namespace EcoCycle\Core\Navigation;

/**
 * Route Configuration Generator
 * 
 * Automatically generates routes based on NavigationConfig
 * This ensures consistency between navigation and routing
 */
class RouteConfig
{
    /**
     * Register all dashboard routes based on NavigationConfig
     * 
     * @param mixed $router The router instance
     */
    public static function registerDashboardRoutes($router): void
    {
        $userTypes = NavigationConfig::getAvailableUserTypes();

        foreach ($userTypes as $userType) {
            $navigation = NavigationConfig::getNavigation($userType);

            foreach ($navigation as $navItem) {
                $url = $navItem['url'];
                $controller = self::getControllerForRoute($userType, $url);
                $method = self::getMethodForRoute($url);

                if ($controller && $method) {
                    $router->get($url, "{$controller}@{$method}");
                }
            }
        }
    }

    /**
     * Get controller class name for a route
     * 
     * @param string $userType The user type (admin, customer, etc.)
     * @param string $url The route URL
     * @return string|null Controller class name
     */
    private static function getControllerForRoute(string $userType, string $url): ?string
    {
        $controllerMap = [
            'admin' => 'Controllers\\Admin\\AdminDashboardController',
            'customer' => 'Controllers\\Customer\\CustomerDashboardController',
            'collector' => 'Controllers\\Collector\\CollectorDashboardController',
            'company' => 'Controllers\\Company\\CompanyDashboardController',
        ];

        return $controllerMap[$userType] ?? null;
    }

    /**
     * Get method name for a route URL
     * 
     * @param string $url The route URL
     * @return string Method name
     */
    private static function getMethodForRoute(string $url): string
    {
        // Extract the last part of the URL path
        $pathParts = array_filter(explode('/', $url));
        $lastPart = end($pathParts);

        // Handle dashboard root URLs
        if (in_array($url, ['/admin', '/customer', '/collector', '/company'])) {
            return 'index';
        }

        // Convert URL segments to camelCase method names
        $segments = array_slice($pathParts, 1); // Remove user type prefix
        if (empty($segments)) {
            return 'index';
        }

        // Convert kebab-case to camelCase
        $methodParts = [];
        foreach ($segments as $segment) {
            $methodParts[] = str_replace('-', '', ucwords($segment, '-'));
        }

        $methodName = lcfirst(implode('', $methodParts));

        // Map common method names
        $methodMapping = [
            'pickupRequests' => 'pickupRequest',  // URL: /admin/pickup-requests -> Method: pickupRequest
            'pickupRequest' => 'pickupRequest',
            'userManagement' => 'users',
            'users' => 'users',
            'schedulePickup' => 'schedulePickup',
            'pickupHistory' => 'pickupHistory',
            'myRewards' => 'rewards',
            'wasteManagement' => 'wasteManagement',
            'scheduleCollection' => 'scheduleCollection',
        ];

        return $methodMapping[$methodName] ?? $methodName;
    }

    /**
     * Get all routes that should be registered
     * 
     * @return array Array of route definitions
     */
    public static function getAllDashboardRoutes(): array
    {
        $routes = [];
        $userTypes = NavigationConfig::getAvailableUserTypes();

        foreach ($userTypes as $userType) {
            $navigation = NavigationConfig::getNavigation($userType);
            $controller = self::getControllerForRoute($userType, "/{$userType}");

            foreach ($navigation as $navItem) {
                $url = $navItem['url'];
                $method = self::getMethodForRoute($url);

                $routes[] = [
                    'method' => 'GET',
                    'url' => $url,
                    'controller' => $controller,
                    'action' => $method,
                    'title' => $navItem['title'],
                    'description' => $navItem['description'] ?? '',
                ];
            }
        }

        return $routes;
    }

    /**
     * Validate that all navigation routes have corresponding controller methods
     * 
     * @return array Array of missing methods
     */
    public static function validateRoutes(): array
    {
        $missing = [];
        $routes = self::getAllDashboardRoutes();

        foreach ($routes as $route) {
            $controller = $route['controller'];
            $method = $route['action'];

            if (!method_exists($controller, $method)) {
                $missing[] = [
                    'controller' => $controller,
                    'method' => $method,
                    'url' => $route['url'],
                    'title' => $route['title']
                ];
            }
        }

        return $missing;
    }

    /**
     * Generate controller method stubs for missing routes
     * 
     * @return array Array of method stub code
     */
    public static function generateMissingMethodStubs(): array
    {
        $missing = self::validateRoutes();
        $stubs = [];

        foreach ($missing as $route) {
            $methodName = $route['method'];
            $title = $route['title'];
            $url = $route['url'];

            $stub = "    /**\n";
            $stub .= "     * {$title}\n";
            $stub .= "     * Route: {$url}\n";
            $stub .= "     */\n";
            $stub .= "    public function {$methodName}(): Response\n";
            $stub .= "    {\n";
            $stub .= "        // TODO: Implement {$title} functionality\n";
            $stub .= "        return \$this->renderDashboard('{$methodName}', [\n";
            $stub .= "            'pageTitle' => '{$title}'\n";
            $stub .= "        ]);\n";
            $stub .= "    }\n\n";

            $stubs[$route['controller']][] = $stub;
        }

        return $stubs;
    }
}
