<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Home Controller
 * 
 * Sample controller demonstrating framework usage
 */
class HomeController extends BaseController
{
    /**
     * Display the home page
     */
    public function index(Request $request): Response
    {
        return $this->json([
            'message' => 'Welcome to the Custom PHP Framework!',
            'timestamp' => date('Y-m-d H:i:s'),
            'framework' => 'Custom PHP Framework v1.0.0'
        ]);
    }

    /**
     * Display framework information
     */
    public function about(Request $request): Response
    {
        return $this->json([
            'framework' => 'Custom PHP Framework',
            'version' => '1.0.0',
            'features' => [
                'MVC Architecture',
                'Dependency Injection Container',
                'Middleware Support',
                'Request/Response Handling',
                'Session Management',
                'CSRF Protection',
                'Routing with Parameters',
                'Database Abstraction',
                'Validation System',
                'Event System'
            ],
            'php_version' => phpversion()
        ]);
    }
}
