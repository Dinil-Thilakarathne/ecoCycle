<?php

namespace Core;

use Core\Http\Request;
use Core\Http\Response;
use Core\Session\SessionManager;
use Core\Events\EventDispatcher;

/**
 * Main Application Class
 * 
 * The core application class that bootstraps and runs the framework.
 * Handles request lifecycle, dependency injection, and application state.
 * 
 * @package Core
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Application
{
    /**
     * The application version
     */
    const VERSION = '1.0.0';

    /**
     * Application singleton instance
     * 
     * @var Application|null
     */
    protected static ?Application $instance = null;

    /**
     * The router instance
     * 
     * @var Router
     */
    protected Router $router;

    /**
     * The dependency injection container
     * 
     * @var Container
     */
    protected Container $container;

    /**
     * The session manager instance
     * 
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * The event dispatcher instance
     * 
     * @var EventDispatcher
     */
    protected EventDispatcher $events;

    /**
     * Application base path
     * 
     * @var string
     */
    protected string $basePath;

    /**
     * Booted service providers
     * 
     * @var array
     */
    protected array $bootedProviders = [];

    /**
     * Create new application instance
     * 
     * @param string $basePath The base path of the application
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?: dirname(__DIR__, 2);
        static::$instance = $this;
        $this->bootstrap();
    }

    /**
     * Bootstrap the application
     * 
     * @return void
     */
    protected function bootstrap(): void
    {
        // Set timeout configurations for development
        if (Environment::get('APP_ENV') === 'development') {
            ini_set('max_execution_time', '300');
            ini_set('default_socket_timeout', '300');
            ini_set('memory_limit', '256M');
        }

        // Load environment variables
        Environment::load($this->basePath);

        // Initialize core components
        $this->container = new Container();
        $this->router = new Router();
        $this->session = new SessionManager();
        $this->events = new EventDispatcher();

        // Bind core services to container
        $this->bindCoreServices();

        // Start session
        $this->session->start();

        // Load configuration
        $this->loadConfiguration();
    }

    /**
     * Bind core services to the container
     * 
     * @return void
     */
    protected function bindCoreServices(): void
    {
        $this->container->singleton('app', function () {
            return $this;
        });

        $this->container->singleton('router', function () {
            return $this->router;
        });

        $this->container->singleton('session', function () {
            return $this->session;
        });

        $this->container->singleton('events', function () {
            return $this->events;
        });

        $this->container->singleton('db', function () {
            return new Database();
        });

        $this->container->bind('response', function () {
            return new Response();
        });

        $this->container->bind('request', function () {
            return Request::createFromGlobals();
        });
    }

    /**
     * Load application configuration
     * 
     * @return void
     */
    protected function loadConfiguration(): void
    {
        $configPath = $this->basePath . '/config';
        $configFiles = ['app', 'database', 'session'];

        foreach ($configFiles as $file) {
            $filePath = $configPath . '/' . $file . '.php';
            if (file_exists($filePath)) {
                Config::load($filePath);
            }
        }
    }

    /**
     * Run the application
     * 
     * @return void
     */
    public function run(): void
    {
        try {
            $request = $this->createRequest();
            $response = $this->handleRequest($request);
            $this->sendResponse($response);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create request instance from globals
     * 
     * @return Request
     */
    protected function createRequest(): Request
    {
        return Request::createFromGlobals();
    }

    /**
     * Handle incoming request
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleRequest(Request $request): Response
    {
        $this->container->singleton('request', function () use ($request) {
            return $request;
        });

        $route = $this->router->match($request->getPath(), $request->getMethod());

        if (!$route) {
            return new Response('Not Found', 404);
        }

        return $this->executeRoute($route, $request);
    }

    /**
     * Execute matched route
     * 
     * @param array $route
     * @param Request $request
     * @return Response
     */
    protected function executeRoute(array $route, Request $request): Response
    {
        // Run middleware stack
        $middlewareStack = $route['middleware'] ?? [];

        foreach ($middlewareStack as $middleware) {
            $middlewareInstance = $this->container->make($middleware);
            $result = $middlewareInstance->handle($request, function ($req) {
                return $req;
            });

            if ($result instanceof Response) {
                return $result;
            }
        }

        // Execute controller action
        if (is_callable($route['action'])) {
            $result = call_user_func($route['action'], $request);
        } else {
            list($controllerName, $action) = explode('@', $route['action']);
            $controller = $this->container->make($controllerName);

            if (!method_exists($controller, $action)) {
                return new Response('Method not found', 500);
            }

            $result = call_user_func([$controller, $action], $request);
        }

        return $result instanceof Response ? $result : new Response($result);
    }

    /**
     * Send response to client
     * 
     * @param Response $response
     * @return void
     */
    protected function sendResponse(Response $response): void
    {
        $response->send();
    }

    /**
     * Handle application exceptions
     * 
     * @param \Throwable $e
     * @return void
     */
    protected function handleException(\Throwable $e): void
    {
        if (Environment::get('APP_DEBUG', false)) {
            echo "<h1>Error: " . $e->getMessage() . "</h1>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            http_response_code(500);
            echo "Internal Server Error";
        }
    }

    /**
     * Get application base path
     * 
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get container instance
     * 
     * @return Container
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Get router instance
     * 
     * @return Router
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Get application version
     * 
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Get singleton application instance
     * 
     * @return static|null
     */
    public static function getInstance(): ?static
    {
        return static::$instance;
    }
}