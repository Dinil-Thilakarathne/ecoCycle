<?php

namespace Pages;

use Core\Http\Request;
use Core\Http\Response;

/**
 * About Page (/about) - Like Next.js pages/about.js
 */
class AboutPage
{
    public function render(Request $request): Response
    {
        $props = [
            'title' => 'About - Custom PHP Framework',
            'framework' => [
                'name' => 'Custom PHP Framework',
                'version' => '1.0.0',
                'author' => 'Developer',
                'description' => 'A Laravel-inspired PHP framework built from scratch'
            ],
            'features' => [
                'MVC Architecture',
                'Dependency Injection',
                'Middleware System',
                'Component-based Views',
                'Next.js-style Routing',
                'Hot Reloading Ready'
            ]
        ];

        return $this->component('about', $props);
    }

    private function component(string $template, array $props): Response
    {
        extract($props);

        ob_start();
        include __DIR__ . "/../Views/pages/{$template}.php";
        $content = ob_get_clean();

        ob_start();
        include __DIR__ . "/../Views/layouts/app.php";
        $html = ob_get_clean();

        $response = new Response();
        $response->setContent($html);
        $response->setHeader('Content-Type', 'text/html');

        return $response;
    }
}
