<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Base Dashboard Controller
 * 
 * Provides common functionality for all dashboard types
 */
abstract class DashboardController extends BaseController
{
    protected string $userType;
    protected array $user;
    protected string $viewPrefix;

    public function __construct()
    {
        // Disable authentication for development
        // $this->ensureAuthenticated();
        $this->setUserContext();
        $this->setDemoUser();
    }

    /**
     * Set demo user data for development
     */
    protected function setDemoUser(): void
    {
        // Set demo user data based on user type
        $demoUsers = [
            'admin' => [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@ecocycle.com',
                'role' => 'admin'
            ],
            'customer' => [
                'id' => 2,
                'name' => 'John Customer',
                'email' => 'customer@ecocycle.com',
                'role' => 'customer'
            ],
            'collector' => [
                'id' => 3,
                'name' => 'Jane Collector',
                'email' => 'collector@ecocycle.com',
                'role' => 'collector'
            ],
            'company' => [
                'id' => 4,
                'name' => 'ABC Company',
                'email' => 'company@ecocycle.com',
                'role' => 'company'
            ]
        ];

        $this->user = $demoUsers[$this->userType] ?? $demoUsers['customer'];
    }

    /**
     * Ensure user is authenticated
     */
    protected function ensureAuthenticated(): void
    {
        $this->user = auth();

        if (!$this->user) {
            redirect('/login')->send();
            exit;
        }
    }

    /**
     * Set user context for the dashboard
     */
    abstract protected function setUserContext(): void;

    /**
     * Check if user has required role
     */
    protected function hasRole(string $role): bool
    {
        return $this->user['role'] === $role;
    }

    /**
     * Ensure user has required role (disabled for development)
     */
    protected function ensureRole(string $role): void
    {
        // Disabled for development
        // if (!$this->hasRole($role)) {
        //     abort(403, 'Access denied');
        // }
    }

    /**
     * Render dashboard view
     */
    protected function renderDashboard(string $view, array $data = []): Response
    {
        $data = array_merge($data, [
            'user' => $this->user,
            'userType' => $this->userType,
            'pageTitle' => $data['pageTitle'] ?? ucfirst($this->userType) . ' Dashboard'
        ]);

        // Create dashboard content by including dashboard layout with specific view content
        ob_start();
        $content = $this->renderView($view, $data);
        $userType = $this->userType;
        $pageTitle = $data['pageTitle'];
        include __DIR__ . '/../Views/layouts/dashboard.php';
        $dashboardContent = ob_get_clean();

        // Use a dummy view approach to work with the existing view system
        return $this->renderWithLayout($dashboardContent, [
            'title' => $data['pageTitle'] . ' - EcoCycle',
            'userType' => $this->userType,
            'user' => $this->user
        ]);
    }

    /**
     * Render content with app layout
     */
    protected function renderWithLayout(string $content, array $data = []): Response
    {
        $response = app('response');

        // Extract data for the layout
        extract($data);

        // Start output buffering for layout
        ob_start();

        // Include the app layout file directly
        $layoutPath = __DIR__ . "/../Views/layouts/app.php";
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            throw new \Exception("App layout not found at {$layoutPath}");
        }

        // Get the final rendered output
        $finalOutput = ob_get_clean();

        // Set the response
        $response->setContent($finalOutput);
        $response->setHeader('Content-Type', 'text/html');

        return $response;
    }

    /**
     * Render specific view for user type
     */
    protected function renderView(string $view, array $data = []): string
    {
        $viewPath = $this->viewPrefix . '.' . $view;

        ob_start();
        extract($data);

        $filePath = base_path("src/Views/{$this->viewPrefix}/{$view}.php");

        if (file_exists($filePath)) {
            include $filePath;
        } else {
            throw new \Exception("View '{$viewPath}' not found");
        }

        return ob_get_clean();
    }

    /**
     * Get navigation items for user type
     */
    abstract protected function getNavigationItems(): array;
}
