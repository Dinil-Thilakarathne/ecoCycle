<?php

namespace Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Throttle Middleware
 * 
 * Provides rate limiting functionality to prevent abuse.
 * 
 * @package Middleware
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class ThrottleMiddleware
{
    /**
     * Maximum number of requests
     * 
     * @var int
     */
    protected int $maxAttempts;

    /**
     * Time window in minutes
     * 
     * @var int
     */
    protected int $decayMinutes;

    /**
     * Create new ThrottleMiddleware instance
     * 
     * @param int $maxAttempts Maximum requests allowed
     * @param int $decayMinutes Time window in minutes
     */
    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Handle the incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->tooManyAttempts($key)) {
            return $this->buildRateLimitResponse();
        }

        $this->incrementAttempts($key);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key);
    }

    /**
     * Resolve request signature for rate limiting
     * 
     * @param Request $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $ip = $request->ip();
        $route = $request->getPath();

        return 'throttle:' . sha1($ip . '|' . $route);
    }

    /**
     * Check if too many attempts have been made
     * 
     * @param string $key
     * @return bool
     */
    protected function tooManyAttempts(string $key): bool
    {
        $session = app('session');
        $attempts = $session->get($key, 0);

        return $attempts >= $this->maxAttempts;
    }

    /**
     * Increment attempts for the key
     * 
     * @param string $key
     * @return void
     */
    protected function incrementAttempts(string $key): void
    {
        $session = app('session');
        $attempts = $session->get($key, 0);

        // Set expiry for the key (in minutes)
        $session->put($key, $attempts + 1);

        // Note: In a real implementation, you'd want to use a proper cache
        // that supports TTL (Time To Live) like Redis or Memcached
    }

    /**
     * Build rate limit exceeded response
     * 
     * @return Response
     */
    protected function buildRateLimitResponse(): Response
    {
        $retryAfter = $this->decayMinutes * 60; // Convert to seconds

        return Response::errorJson('Too Many Requests', 429)
            ->setHeader('Retry-After', (string) $retryAfter)
            ->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->setHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * Add rate limit headers to response
     * 
     * @param Response $response
     * @param string $key
     * @return Response
     */
    protected function addRateLimitHeaders(Response $response, string $key): Response
    {
        $session = app('session');
        $attempts = $session->get($key, 0);
        $remaining = max(0, $this->maxAttempts - $attempts);

        return $response
            ->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->setHeader('X-RateLimit-Remaining', (string) $remaining);
    }
}
