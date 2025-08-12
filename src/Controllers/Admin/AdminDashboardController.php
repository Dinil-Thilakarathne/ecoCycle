<?php

namespace Controllers\Admin;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
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
     * Pickup request page
     */
    public function pickupRequest(): Response
    {
        $data = [
            'pageTitle' => 'Pickup Requests',
        ];
        return $this->renderDashboard('pickupRequest', $data);
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
     * Vehicle management page
     */
    public function vehicles(): Response
    {
        $data = [
            'pageTitle' => 'Vehicle Management',
        ];

        return $this->renderDashboard('vehicles', $data);
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
     * Bidding management page
     */
    public function bidding(): Response
    {
        $data = [
            'pageTitle' => 'Bidding Management',
        ];

        return $this->renderDashboard('biddingManagement', $data);
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
        return NavigationConfig::getNavigation($this->userType);
    }
}
