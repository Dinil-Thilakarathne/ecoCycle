<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Base Controller
 * 
 * Provides common functionality for all controllers.
 */
class BaseController
{
    /**
     * Render a view with data
     */
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): Response
    {
        $response = app('response');

        // Extract data for view
        extract($data);

        // Start output buffering for view content
        ob_start();

        // Include the view file
        $viewPath = __DIR__ . "/../Views/{$view}.php";

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new \Exception("View '{$view}' not found at {$viewPath}");
        }

        // Get the view content
        $content = ob_get_clean();

        // Now render with layout
        ob_start();

        // Include the layout file
        $layoutPath = __DIR__ . "/../Views/{$layout}.php";

        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            // Fallback to just content if layout not found
            echo $content;
        }

        // Get the final rendered output
        $finalOutput = ob_get_clean();

        // Set the response
        $response->setContent($finalOutput);
        $response->setHeader('Content-Type', 'text/html');

        return $response;
    }

    /**
     * Return a JSON response
     */
    protected function json(array $data, int $status = 200): Response
    {
        $response = app('response');
        $response->setStatusCode($status);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data, JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * Return a redirect response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        $response = app('response');
        $response->setStatusCode($status);
        $response->setHeader('Location', $url);

        return $response;
    }

    /**
     * Get validation errors and flash them to session
     */
    protected function withErrors(array $errors): self
    {
        $session = app('session');
        $session->flash('errors', $errors);

        return $this;
    }

    /**
     * Flash input data to session
     */
    protected function withInput(array $input): self
    {
        $session = app('session');
        $session->flash('old', $input);

        return $this;
    }

    /**
     * Flash a message to session
     */
    protected function with(string $key, string $message): self
    {
        $session = app('session');
        $session->flash($key, $message);

        return $this;
    }

    /**
     * Get the current authenticated user
     */
    protected function user(): ?array
    {
        $session = app('session');

        if (!$session->has('user_id')) {
            return null;
        }

        try {
            $db = app('database');
            $stmt = $db->prepare("
                SELECT id, name, email, role, phone, address, status, created_at, last_login 
                FROM users 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$session->get('user_id')]);

            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole(string $role): bool
    {
        $session = app('session');
        return $session->get('user_role') === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    protected function hasAnyRole(array $roles): bool
    {
        $session = app('session');
        $userRole = $session->get('user_role');

        return in_array($userRole, $roles);
    }

    /**
     * Abort with error response
     */
    protected function abort(int $status = 404, ?string $message = null): Response
    {
        $response = app('response');
        $response->setStatusCode($status);

        $statusMessages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error'
        ];

        $defaultMessage = $statusMessages[$status] ?? 'Error';
        $message = $message ?? $defaultMessage;

        // For AJAX requests, return JSON
        $request = app('request');
        if ($request->expectsJson()) {
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'error' => $message,
                'status' => $status
            ]));
        } else {
            // For regular requests, render error view
            try {
                return $this->view("errors/{$status}", [
                    'title' => $message,
                    'message' => $message,
                    'status' => $status
                ]);
            } catch (\Exception $e) {
                // Fallback to simple HTML
                $response->setHeader('Content-Type', 'text/html');
                $response->setContent("
                    <!DOCTYPE html>
                    <html>
                    <head><title>{$message}</title></head>
                    <body>
                        <h1>{$status} - {$message}</h1>
                        <p>Sorry, an error occurred.</p>
                        <a href='/'>Go Home</a>
                    </body>
                    </html>
                ");
            }
        }

        return $response;
    }
    /**
     * Return a success JSON response
     */
    protected function success(string $message, array $data = [], int $status = 200): Response
    {
        return $this->json(array_merge(['success' => true, 'message' => $message], $data), $status);
    }

    /**
     * Return an error JSON response
     */
    protected function error(string $message, int $status = 400, array $data = []): Response
    {
        return $this->json(array_merge(['success' => false, 'error' => $message], $data), $status);
    }
}