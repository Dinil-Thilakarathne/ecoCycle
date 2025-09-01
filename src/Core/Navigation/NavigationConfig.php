<?php

namespace EcoCycle\Core\Navigation;

/**
 * Navigation Configuration Manager
 * 
 * Centralized navigation configuration for all user roles
 * This eliminates duplication and makes navigation management easier
 */
class NavigationConfig
{
    /**
     * Get navigation items for a specific user role
     * 
     * @param string $userType The user role (admin, customer, collector, company)
     * @return array Navigation items array
     */
    public static function getNavigation(string $userType): array
    {
        $navigationConfig = self::getAllNavigationConfig();

        return $navigationConfig[$userType] ?? [];
    }

    /**
     * Get all navigation configurations
     * 
     * @return array Complete navigation configuration for all roles
     */
    private static function getAllNavigationConfig(): array
    {
        return [
            'admin' => [
                ['title' => 'Overview', 'url' => '/admin', 'icon' => 'chart-column', 'description' => 'System overview and statistics'],
                ['title' => 'Pickup Requests', 'url' => '/admin/pickup-requests', 'icon' => 'truck', 'description' => 'Manage pickup requests'],
                ['title' => 'Bidding', 'url' => '/admin/bidding', 'icon' => 'gavel', 'description' => 'Manage waste lot bidding'],
                ['title' => 'User Management', 'url' => '/admin/users', 'icon' => 'users', 'description' => 'Manage system users'],
                ['title' => 'Vehicles', 'url' => '/admin/vehicles', 'icon' => 'car', 'description' => 'Vehicle management'],
                ['title' => 'Payments', 'url' => '/admin/payments', 'icon' => 'credit-card', 'description' => 'Payment management'],
                ['title' => 'Analytics', 'url' => '/admin/analytics', 'icon' => 'chart-line', 'description' => 'System analytics'],
                ['title' => 'Notifications', 'url' => '/admin/notifications', 'icon' => 'bell', 'description' => 'System notifications'],
            ],
            'customer' => [
                ['title' => 'Dashboard', 'url' => '/customer', 'icon' => 'home', 'description' => 'Your dashboard overview'],
                ['title' => 'Pickup Request', 'url' => '/customer/pickup', 'icon' => 'calendar', 'description' => 'Schedule waste pickup'],
                ['title' => 'Payment', 'url' => '/customer/payment', 'icon' => 'history', 'description' => 'do payment'],
                ['title' => 'notification', 'url' => '/customer/notification', 'icon' => 'gift', 'description' => 'See notifications'],
                ['title' => 'report', 'url' => '/customer/analytics', 'icon' => 'book', 'description' => 'see progress'],
                ['title' => 'Profile', 'url' => '/customer/profile', 'icon' => 'user', 'description' => 'Your profile settings'],
            ],
            'collector' => [
                ['title' => 'Dashboard', 'url' => '/collector', 'icon' => 'tachometer-alt', 'description' => 'Collector dashboard'],
                ['title' => 'Daily Tasks', 'url' => '/collector/tasks', 'icon' => 'truck', 'description' => 'Manage pickups'],
                ['title' => 'Pickup History', 'url' => '/collector/history', 'icon' => 'history', 'description' => 'View History'],
                ['title' => 'Earnings', 'url' => '/collector/earnings', 'icon' => 'dollar-sign', 'description' => 'Track earnings'],
                ['title' => 'Reports', 'url' => '/collector/analytics', 'icon' => 'chart-bar', 'description' => 'Performance reports'],
                ['title' => 'Profile', 'url' => '/collector/profile', 'icon' => 'user', 'description' => 'Your profile settings'],
            ],
            'company' => [
                // Matches: src/Views/company/dashboard.php -> route: /company (index)
                ['title' => 'Dashboard', 'url' => '/company', 'icon' => 'chart-column', 'description' => 'Company dashboard overview'],
                // Matches: src/Views/company/activeBids.php -> route: /company/activeBids
                ['title' => 'Active Bids', 'url' => '/company/activeBids', 'icon' => 'gavel', 'description' => 'View and manage active bids'],
                // Matches: src/Views/company/purchases.php -> route: /company/purchases
                ['title' => 'Purchases', 'url' => '/company/purchases', 'icon' => 'cart-shopping', 'description' => 'Your purchases and orders'],
                // Matches: src/Views/company/reports.php -> route: /company/reports
                ['title' => 'Reports', 'url' => '/company/reports', 'icon' => 'chart-line', 'description' => 'Analytics and reports'],
                // Matches: src/Views/company/helpCenter.php -> route: /company/helpCenter
                ['title' => 'Help & Support', 'url' => '/company/help-center', 'icon' => 'life-ring', 'description' => 'Help center and support'],
                // Matches: src/Views/company/profile.php -> route: /company/profile
                ['title' => 'Profile', 'url' => '/company/profile', 'icon' => 'user', 'description' => 'Company profile & settings'],
                ['title' => 'Notification', 'url' => '/company/notification', 'icon' => 'bell', 'description' => 'User profile & Details'],
            ],
        ];
    }

    /**
     * Get navigation item by URL for a specific user type
     * 
     * @param string $userType The user role
     * @param string $url The URL to find
     * @return array|null Navigation item or null if not found
     */
    public static function getNavigationItemByUrl(string $userType, string $url): ?array
    {
        $navigation = self::getNavigation($userType);

        foreach ($navigation as $item) {
            if ($item['url'] === $url) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Check if URL is active for current request
     * 
     * @param string $url The navigation URL
     * @param string $currentUrl The current request URL
     * @return bool Whether the URL is active
     */
    public static function isActiveUrl(string $url, string $currentUrl): bool
    {
        // Exact match
        if ($url === $currentUrl) {
            return true;
        }

        // For dashboard root URLs, only match exactly to avoid highlighting multiple items
        $dashboardRoots = ['/admin', '/customer', '/collector', '/company'];
        if (in_array($url, $dashboardRoots)) {
            return $url === $currentUrl;
        }

        // For other URLs, check if current URL starts with navigation URL
        return str_starts_with($currentUrl, $url);
    }

    /**
     * Get breadcrumb navigation for current page
     * 
     * @param string $userType The user role
     * @param string $currentUrl The current URL
     * @return array Breadcrumb items
     */
    public static function getBreadcrumbs(string $userType, string $currentUrl): array
    {
        $navigation = self::getNavigation($userType);
        $breadcrumbs = [];

        // Add home/dashboard as first breadcrumb
        $dashboardUrls = [
            'admin' => '/admin',
            'customer' => '/customer',
            'collector' => '/collector',
            'company' => '/company'
        ];

        if (isset($dashboardUrls[$userType])) {
            $breadcrumbs[] = [
                'title' => 'Dashboard',
                'url' => $dashboardUrls[$userType],
                'active' => $currentUrl === $dashboardUrls[$userType]
            ];
        }

        // Add current page if it's not the dashboard
        $currentItem = self::getNavigationItemByUrl($userType, $currentUrl);
        if ($currentItem && $currentUrl !== ($dashboardUrls[$userType] ?? '')) {
            $breadcrumbs[] = [
                'title' => $currentItem['title'],
                'url' => $currentItem['url'],
                'active' => true
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Get all available user types
     * 
     * @return array List of available user types
     */
    public static function getAvailableUserTypes(): array
    {
        return array_keys(self::getAllNavigationConfig());
    }
}