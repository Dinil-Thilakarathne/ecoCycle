<?php

namespace Core\Http;

/**
 * HTTP Response Class
 * 
 * Represents an HTTP response with methods to set headers, status codes, and content.
 * Similar to Laravel's Response class functionality.
 * 
 * @package Core\Http
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Response
{
    /**
     * Response content
     * 
     * @var string
     */
    protected string $content;

    /**
     * HTTP status code
     * 
     * @var int
     */
    protected int $statusCode;

    /**
     * Response headers
     * 
     * @var array
     */
    protected array $headers;

    /**
     * Create new Response instance
     * 
     * @param string $content
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Set response content
     * 
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get response content
     * 
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set HTTP status code
     * 
     * @param int $statusCode
     * @return self
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set response header
     * 
     * @param string $name
     * @param string $value
     * @return self
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get response header
     * 
     * @param string $name
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[$name] ?? $default;
    }

    /**
     * Get all headers
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Send response to client
     * 
     * @return void
     */
    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Send content
        echo $this->content;
    }

    /**
     * Create JSON response
     * 
     * @param array|object $data
     * @param int $statusCode
     * @param array $headers
     * @return static
     */
    public static function json($data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);

        return new static($content, $statusCode, $headers);
    }

    /**
     * Create redirect response
     * 
     * @param string $url
     * @param int $statusCode
     * @return static
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        $headers = ['Location' => $url];
        return new static('', $statusCode, $headers);
    }

    /**
     * Create view response
     * 
     * @param string $view
     * @param array $data
     * @param int $statusCode
     * @return static
     */
    public static function view(string $view, array $data = [], int $statusCode = 200): self
    {
        // This would integrate with a view engine
        $content = "<!-- View: {$view} -->";
        return new static($content, $statusCode);
    }

    /**
     * Create error response
     * 
     * @param string $message
     * @param int $statusCode
     * @return static
     */
    public static function error(string $message, int $statusCode = 500): self
    {
        return new static($message, $statusCode);
    }

    /**
     * Create success response
     * 
     * @param string $message
     * @param array $data
     * @return static
     */
    public static function success(string $message, array $data = []): self
    {
        return static::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Create error JSON response
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return static
     */
    public static function errorJson(string $message, int $statusCode = 400, array $errors = []): self
    {
        return static::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Create CSV response
     * 
     * @param string $filename
     * @param array $headers The column headers for the CSV
     * @param array $data The multi-dimensional array of row data
     * @param int $statusCode
     * @return static
     */
    public static function csv(string $filename, array $columns, array $data, int $statusCode = 200): self
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        $output = fopen('php://temp', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if (!empty($columns)) {
            fputcsv($output, $columns);
        }

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return new static($content, $statusCode, $headers);
    }
}
