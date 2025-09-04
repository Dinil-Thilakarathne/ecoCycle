<?php

namespace Controllers\Customer;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Customer Dashboard Controller
 * 
 * Handles customer-specific dashboard functionality
 */
class CustomerDashboardController extends DashboardController
{
    protected function setUserContext(): void
    {
        $this->userType = 'customer';
        $this->viewPrefix = 'customer';
        // Comment out role enforcement for development
        // $this->ensureRole('customer');
    }

    /**
     * Customer dashboard home
     */
    public function index(): Response
    {
        $data = [
            'pageTitle' => 'My Dashboard',
            'rewardPoints' => $this->getRewardPoints(),
            'recentPickups' => $this->getRecentPickups(),
            'upcomingPickups' => $this->getUpcomingPickups(),
            'recyclingStats' => $this->getRecyclingStats()
        ];

        return $this->renderDashboard('dashboard', $data);
    }

    /**
     * Schedule pickup page
     */
    public function pickup(): Response
    {
        $data = [
            'pageTitle' => 'Pickup Request',
        ];

        return $this->renderDashboard('pickup', $data);
    }

    /**
     * Pickup history
     */
    public function payment(): Response
    {
        $data = [
            'pageTitle' => 'Payment',
        ];

        return $this->renderDashboard('payment', $data);
    }

    /**
     * Rewards and points
     */
    public function notification(): Response
    {
        $data = [
            'pageTitle' => 'Notifications',
            'currentPoints' => $this->getRewardPoints(),
            'rewardHistory' => $this->getRewardHistory(),
            'availableRewards' => $this->getAvailableRewards()
        ];

        return $this->renderDashboard('notification', $data);
    }

    /**
     * Profile management
     */
    public function profile(): Response
    {
        $data = [
            'pageTitle' => 'My Profile',
            'userProfile' => $this->getUserProfile(),
            'addressBook' => $this->getAddressBook()
        ];

        return $this->renderDashboard('profile', $data);
    }

    /**
     * Education center
     */
    public function analytics(): Response
    {
        $data = [
            'pageTitle' => 'Analytics',
            'articles' => $this->getEducationalArticles(),
            'tips' => $this->getRecyclingTips(),
            'videos' => $this->getEducationalVideos()
        ];

        return $this->renderDashboard('analytics', $data);
    }

    protected function getNavigationItems(): array
    {
        return NavigationConfig::getNavigation($this->userType);
    }

    // Placeholder methods for data retrieval
    private function getRewardPoints(): int
    {
        return 250;
    }
    private function getRecentPickups(): array
    {
        return [];
    }
    private function getUpcomingPickups(): array
    {
        return [];
    }
    private function getRecyclingStats(): array
    {
        return [];
    }
    private function getAvailableSlots(): array
    {
        return [];
    }
    private function getUserAddress(): array
    {
        return [];
    }
    private function getPickupHistory(): array
    {
        return [];
    }
    private function getTotalWeightRecycled(): float
    {
        return 45.5;
    }
    private function getRewardHistory(): array
    {
        return [];
    }
    private function getAvailableRewards(): array
    {
        return [];
    }
    private function getUserProfile(): array
    {
        return [];
    }
    private function getAddressBook(): array
    {
        return [];
    }
    private function getEducationalArticles(): array
    {
        return [];
    }
    private function getRecyclingTips(): array
    {
        return [];
    }
    private function getEducationalVideos(): array
    {
        return [];
    }
}
