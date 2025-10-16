<?php

namespace Core;

/**
 * Page Router - Next.js style routing for pages
 */
class PageRouter
{
    /**
     * Auto-register page routes based on file structure
     */
    public static function registerPageRoutes(Router $router): void
    {
        // Index page (/)
        $router->get('/', function ($request) {
            $page = new \Pages\IndexPage();
            return $page->render($request);
        });

        // About page (/about)
        $router->get('/about', function ($request) {
            $page = new \Pages\AboutPage();
            return $page->render($request);
        });

        // Dynamic pages can be added here
        // e.g., /blog/[slug], /user/[id], etc.
    }

    /**
     * Create API routes (like Next.js api/ folder)
     */
    public static function registerApiRoutes(Router $router): void
    {
        $router->group(['prefix' => 'api'], function ($router) {

            // GET /api/stats
            $router->get('/stats', function ($request) {
                return response()->json([
                    'visitors' => rand(1000, 5000),
                    'pages' => 12,
                    'uptime' => '99.9%',
                    'timestamp' => date('c')
                ]);
            });

            // POST /api/contact
            $router->post('/contact', function ($request) {
                $data = $request->all();

                // Validate and process contact form
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for your message!',
                    'data' => $data
                ]);
            });

            // GET /api/user/[id]
            $router->get('/user/{id}', function ($request) {
                $id = $request->get('id');

                return response()->json([
                    'user' => [
                        'id' => $id,
                        'name' => 'User ' . $id,
                        'email' => 'user' . $id . '@example.com',
                        'role' => 'user'
                    ]
                ]);
            });
        });
    }
}
