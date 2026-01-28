<?php

namespace Core\Http;

/**
 * HTTP Request Class
 * 
 * Represents an HTTP request with methods to access request data.
 * Similar to Laravel's Request class functionality.
 * 
 * @package Core\Http
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Request
{
    /**
     * Request URI
     * 
     * @var string
     */
    protected string $uri;

    /**
     * Request method
     * 
     * @var string
     */
    protected string $method;

    /**
     * Request headers
     * 
     * @var array
     */
    protected array $headers;

    /**
     * Request parameters
     * 
     * @var array
     */
    protected array $parameters;

    /**
     * Query parameters
     * 
     * @var array
     */
    protected array $query;

    /**
     * Request body data
     * 
     * @var array
     */
    protected array $body;

    /**
     * Uploaded files
     * 
     * @var array
     */
    protected array $files;

    /**
     * Route parameters extracted from the matched route
     *
     * @var array
     */
    protected array $routeParameters = [];

    /**
     * Create new Request instance
     * 
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @param array $query
     * @param array $body
     * @param array $files
     */
    public function __construct(
        string $uri,
        string $method,
        array $headers = [],
        array $query = [],
        array $body = [],
        array $files = []
    ) {
        $this->uri = $uri;
        $this->method = strtoupper($method);
        // Normalize header keys to lowercase for case-insensitive lookups
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower((string) $k)] = $v;
        }
        $this->headers = $normalized;
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->parameters = array_merge($query, $body);
    }

    /**
     * Set parameters resolved from the route path
     *
     * @param array $parameters
     * @return void
     */
    public function setRouteParameters(array $parameters): void
    {
        $this->routeParameters = $parameters;
        if (!empty($parameters)) {
            $this->parameters = array_merge($this->parameters, $parameters);
        }
    }

    /**
     * Get a specific route parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function route(string $key, $default = null)
    {
        return $this->routeParameters[$key] ?? $default;
    }

    /**
     * Get all route parameters
     *
     * @return array
     */
    public function routeParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * Create Request from PHP globals
     * 
     * @return static
     */
    public static function createFromGlobals(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Get headers with fallback for CLI mode
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        } else {
            // Fallback for CLI mode - extract headers from $_SERVER
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $headers[$header] = $value;
                }
            }
        }

        $query = $_GET;
        $body = $_POST;

        // Parse JSON body if Content-Type is application/json
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $headers['Content-Type'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $json = json_decode($input, true);
            if (is_array($json)) {
                $body = array_merge($body, $json);
            }
        }
        $files = $_FILES ?? [];

        return new static($uri, $method, $headers, $query, $body, $files);
    }

    /**
     * Get request URI
     * 
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get request path (alias for getUri)
     * 
     * @return string
     */
    public function getPath(): string
    {
        return $this->uri;
    }

    /**
     * Get request method
     * 
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Check if request method matches
     * 
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Get request parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Get all request parameters
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Get query parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get body parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get request header
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        $lookup = strtolower($key);
        return $this->headers[$lookup] ?? $default;
    }

    /**
     * Get all headers
     * 
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get uploaded file
     * 
     * @param string $key
     * @return array|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if request has file
     * 
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Check if request has parameter
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->parameters[$key]);
    }

    /**
     * Get request JSON data
     * 
     * @return array|null
     */
    public function json(): ?array
    {
        $contentType = $this->header('Content-Type', '');

        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            return json_decode($input, true);
        }

        return null;
    }

    /**
     * Merge additional data into the request body parameters.
     */
    public function mergeBody(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $this->body = array_merge($this->body, $data);
        $this->parameters = array_merge($this->parameters, $data);
    }

    /**
     * Check if request expects JSON response
     * 
     * @return bool
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return strpos($accept, 'application/json') !== false;
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ??
            $_SERVER['HTTP_X_REAL_IP'] ??
            $_SERVER['REMOTE_ADDR'] ??
            'unknown';
    }

    /**
     * Get user agent
     * 
     * @return string
     */
    public function userAgent(): string
    {
        return $this->header('User-Agent', '');
    }

    /**
     * Check if request method is POST
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Check if request method is GET
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Check if request method is PUT
     */
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    /**
     * Check if request method is DELETE
     */
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    /**
     * Check if request method is PATCH
     */
    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }
}
