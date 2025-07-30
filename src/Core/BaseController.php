<?php

namespace Core;

use Core\Http\Request;
use Core\Http\Response;
use Core\Session\SessionManager;

/**
 * Base Controller Class
 * 
 * Provides common functionality for all controllers in the application.
 * Similar to Laravel's base controller functionality.
 * 
 * @package Core
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
abstract class BaseController
{
    /**
     * The container instance
     * 
     * @var Container
     */
    protected Container $container;

    /**
     * The request instance
     * 
     * @var Request
     */
    protected Request $request;

    /**
     * The session manager instance
     * 
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * Middleware to run before controller actions
     * 
     * @var array
     */
    protected array $middleware = [];

    /**
     * Create new controller instance
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container->make('request');
        $this->session = $container->make('session');
    }

    /**
     * Execute controller action with middleware
     * 
     * @param string $action
     * @param array $parameters
     * @return Response
     */
    public function execute(string $action, array $parameters = []): Response
    {
        // Run middleware stack
        foreach ($this->middleware as $middleware) {
            $result = $this->runMiddleware($middleware);
            if ($result instanceof Response) {
                return $result;
            }
        }

        // Execute controller action
        if (!method_exists($this, $action)) {
            return Response::error('Action not found', 404);
        }

        $result = call_user_func_array([$this, $action], $parameters);

        return $result instanceof Response ? $result : new Response($result);
    }

    /**
     * Run middleware
     * 
     * @param string $middleware
     * @return mixed
     */
    protected function runMiddleware(string $middleware)
    {
        $instance = $this->container->make($middleware);

        return $instance->handle($this->request, function ($request) {
            return $request;
        });
    }

    /**
     * Return JSON response
     * 
     * @param array|object $data
     * @param int $statusCode
     * @return Response
     */
    protected function json($data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    /**
     * Return success JSON response
     * 
     * @param string $message
     * @param array $data
     * @return Response
     */
    protected function success(string $message, array $data = []): Response
    {
        return Response::success($message, $data);
    }

    /**
     * Return error JSON response
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return Response
     */
    protected function error(string $message, int $statusCode = 400, array $errors = []): Response
    {
        return Response::errorJson($message, $statusCode, $errors);
    }

    /**
     * Return redirect response
     * 
     * @param string $url
     * @param int $statusCode
     * @return Response
     */
    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * Return view response
     * 
     * @param string $view
     * @param array $data
     * @return Response
     */
    protected function view(string $view, array $data = []): Response
    {
        return Response::view($view, $data);
    }

    /**
     * Validate request data
     * 
     * @param array $rules
     * @param array $messages
     * @return array
     * @throws ValidationException
     */
    protected function validate(array $rules, array $messages = []): array
    {
        $validator = new Validator($this->request->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->getErrors();

            if ($this->request->expectsJson()) {
                throw new ValidationException('Validation failed', $errors);
            }

            // Redirect back with errors
            $this->session->flash('errors', $errors);
            $this->session->flash('old_input', $this->request->all());

            throw new ValidationException('Validation failed', $errors);
        }

        return $validator->getValidatedData();
    }

    /**
     * Get authenticated user
     * 
     * @return array|null
     */
    protected function auth(): ?array
    {
        if ($this->session->isAuthenticated()) {
            return $this->session->userData();
        }

        return null;
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return $this->session->isAuthenticated();
    }

    /**
     * Require authentication
     * 
     * @return void
     * @throws AuthenticationException
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            throw new AuthenticationException('Authentication required');
        }
    }

    /**
     * Get request input
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function input(string $key, $default = null)
    {
        return $this->request->get($key, $default);
    }

    /**
     * Get all request input
     * 
     * @return array
     */
    protected function all(): array
    {
        return $this->request->all();
    }

    /**
     * Flash message to session
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function flash(string $key, $value): void
    {
        $this->session->flash($key, $value);
    }

    /**
     * Add middleware to controller
     * 
     * @param string $middleware
     * @return void
     */
    protected function addMiddleware(string $middleware): void
    {
        $this->middleware[] = $middleware;
    }
}

/**
 * Validation Exception
 */
class ValidationException extends \Exception
{
    protected array $errors;

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Authentication Exception
 */
class AuthenticationException extends \Exception
{
    // Exception for authentication failures
}
