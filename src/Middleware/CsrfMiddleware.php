<?php

namespace Middleware;

use Core\Http\Request;
use Core\Http\Response;

class CsrfMiddleware
{
    protected array $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next): Response
    {
        if (!in_array($request->getMethod(), $this->protectedMethods)) {
            // safe methods
            $response = $next($request);
            if (!$response instanceof Response) {
                throw new \RuntimeException('Next middleware or controller must return Core\Http\Response');
            }
            return $response;
        }

        $session = app('session');

        $requestToken = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
        $sessionToken = $session->token();

        if (!$requestToken || !hash_equals($sessionToken, $requestToken)) {
            if ($request->expectsJson() || $request->isAjax()) {
                return Response::errorJson('CSRF token mismatch', 419);
            }
            $session->flash('error', 'CSRF token mismatch. Please try again.');
            return Response::redirect($request->header('Referer', '/'));
        }

        // ✅ Call $next and return its response
        $response = $next($request);

        if (!$response instanceof Response) {
            throw new \RuntimeException('Next middleware or controller must return Core\Http\Response');
        }

        return $response;
    }
}
