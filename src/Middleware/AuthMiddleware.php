<?php
namespace Middleware;

use Core\Http\Request;
use Core\Http\Response;

class AuthMiddleware
{
    /**
     * Handle the incoming request
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $session = app('session');

        if (!$session->isAuthenticated()) {
            // If the request expects JSON, return JSON error
            if ($request->expectsJson() || $request->isAjax()) {
                return Response::errorJson('Unauthenticated', 401);
            }

            // Otherwise redirect to login page
            return Response::redirect('/login');
        }

        // IMPORTANT: call $next and return its response
        $response = $next($request);

        // Make sure it is a Response object
        if (!$response instanceof Response) {
            throw new \RuntimeException(
                'Next middleware or controller must return an instance of Core\Http\Response'
            );
        }

        return $response;
    }
}
