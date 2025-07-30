<?php

namespace Pages;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Index Page (/) - Like Next.js pages/index.js
 */
class IndexPage
{
    public function render(Request $request): Response
    {
        $props = [
            'title' => 'Home - Custom PHP Framework',
            'user' => [
                'name' => 'Developer',
                'role' => 'Full Stack'
            ],
            'stats' => [
                'visitors' => rand(1000, 5000),
                'pages' => 12,
                'uptime' => '99.9%'
            ]
        ];

        return $this->component('index', $props);
    }

    private function component(string $template, array $props): Response
    {
        // Extract props for the template
        extract($props);

        ob_start();
        include __DIR__ . "/../Views/pages/{$template}.php";
        $content = ob_get_clean();

        // Wrap in layout (like Next.js _app.js)
        ob_start();
        include __DIR__ . "/../Views/layouts/app.php";
        $html = ob_get_clean();

        $response = new Response();
        $response->setContent($html);
        $response->setHeader('Content-Type', 'text/html');

        return $response;
    }
}
