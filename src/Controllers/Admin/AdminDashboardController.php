<?php

namespace Controllers\Admin;

use Controllers\DashboardController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Admin Dashboard Controller
 * 
 * Handles admin-specific dashboard functionality
 */
class AdminDashboardController extends DashboardController
{
    protected function setUserContext(): void
    {
        $this->userType = 'admin';
        $this->viewPrefix = 'admin';
        // Comment out role enforcement for development
        // $this->ensureRole('admin');
    }

    /**
     * Admin dashboard home
     */
    public function index(): Response
    {
        $data = [
            'pageTitle' => 'Admin Dashboard',
        ];

        return $this->renderDashboard('dashboard', $data);
    }

    /**
     * User management page
     */
    public function users(): Response
    {
        $data = [
            'pageTitle' => 'User Management',
        ];

        return $this->renderDashboard('users', $data);
    }

    /**
     * System settings page
     */
    public function settings(): Response
    {
        $data = [
            'pageTitle' => 'System Settings',
        ];

        return $this->renderDashboard('settings', $data);
    }

    /**
     * Reports and analytics
     */
    public function reports(): Response
    {
        $data = [
            'pageTitle' => 'Reports & Analytics',
        ];

        return $this->renderDashboard('reports', $data);
    }

    /**
     * Content management
     */
    public function content(): Response
    {
        $data = [
            'pageTitle' => 'Content Management',
        ];

        return $this->renderDashboard('content', $data);
    }

    protected function getNavigationItems(): array
    {
        return [
            ['title' => 'Dashboard', 'url' => '/admin', 'icon' => 'dashboard'],
            ['title' => 'User Management', 'url' => '/admin/users', 'icon' => 'users'],
            ['title' => 'Reports', 'url' => '/admin/reports', 'icon' => 'analytics'],
            ['title' => 'Content', 'url' => '/admin/content', 'icon' => 'content'],
            ['title' => 'Settings', 'url' => '/admin/settings', 'icon' => 'settings'],
        ];
    }
}
