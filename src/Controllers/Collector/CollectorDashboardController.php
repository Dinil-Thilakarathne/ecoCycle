<?php

namespace Controllers\Collector;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Models\PickupRequest;

/**
 * Collector Dashboard Controller
 * 
 * Handles collector-specific dashboard functionality
 */
class CollectorDashboardController extends DashboardController
{
    protected function setUserContext(): void
    {
        $this->userType = 'collector';
        $this->viewPrefix = 'collector';
        // Comment out role enforcement for development
        // $this->ensureRole('collector');
    }

    /**
     * Collector dashboard home
     */
    public function index(): \Core\Http\Response
    {
        $data = [
            'pageTitle' => 'Collector Dashboard',
            'todayPickups' => $this->getTodayPickups(),
            'completedPickups' => $this->getCompletedPickupsToday(),
            'pendingPickups' => $this->getPendingPickups(),
            'earnings' => $this->getTodayEarnings(),
            'route' => $this->getOptimizedRoute()
        ];

        return $this->renderDashboard('dashboard', $data);
    }

    /**
     * Pickup assignments
     */
    public function tasks(): \Core\Http\Response
    {
        $request = request();
        $selectedTimeSlot = $this->normalizeTimeSlot((string) $request->query('time_slot', 'all'));
        $selectedStatus = $this->normalizeStatus((string) $request->query('status', 'all'));

        $data = [
            'pageTitle' => 'Pickup Assignments',
            'assignedPickups' => $this->getAssignedPickups($selectedTimeSlot, $selectedStatus),
            'availablePickups' => $this->getAvailablePickups(),
            'pickupFilters' => $this->getPickupFilters(),
            'timeSlots' => $this->getTimeSlots(),
            'selectedTimeSlot' => $selectedTimeSlot,
            'selectedStatus' => $selectedStatus,
        ];
        return $this->renderDashboard('tasks', $data);
    }

    /**
     * Earnings and payments
     */
    /* public function earnings(): Response
     {
         $data = [
             'pageTitle' => 'Earnings & Payments',
             'dailyEarnings' => $this->getDailyEarnings(),
             'monthlyEarnings' => $this->getMonthlyEarnings(),
             'paymentHistory' => $this->getPaymentHistory(),
             'pendingPayments' => $this->getPendingPayments()
         ];

         return $this->renderDashboard('earnings', $data);
     }*/

    /**
     * Collection reporting
     */
    public function analytics(): \Core\Http\Response
    {
        $data = [
            'pageTitle' => 'Collection Analytics',
            'collectionStats' => $this->getCollectionStats(),
            'weightReports' => $this->getWeightReports(),
            'materialBreakdown' => $this->getMaterialBreakdown()
        ];

        return $this->renderDashboard('analytics', $data);
    }

    public function notification(): \Core\Http\Response
    {
        $data = [
            'pageTitle' => 'Notifications',
            'collectionStats' => $this->getCollectionStats(),
            'weightReports' => $this->getWeightReports(),
            'materialBreakdown' => $this->getMaterialBreakdown()
        ];

        return $this->renderDashboard('notification', $data);
    }





    public function setting(): \Core\Http\Response
    {
        $data = [
            'pageTitle' => 'Collection Setting',
        ];

        return $this->renderDashboard('setting', $data);
    }


    /**
     * Profile and vehicle info
     */
    public function profile(): \Core\Http\Response
    {
        $data = [
            'pageTitle' => 'Collector Profile',
            'collectorProfile' => $this->getCollectorProfile(),
            'vehicleInfo' => $this->getVehicleInfo(),
            'certifications' => $this->getCertifications()
        ];

        return $this->renderDashboard('profile', $data);
    }

    protected function getNavigationItems(): array
    {
        return NavigationConfig::getNavigation($this->userType);
    }

    // Placeholder methods for data retrieval
    private function getTodayPickups(): array
    {
        return [];
    }
    private function getCompletedPickupsToday(): int
    {
        return 5;
    }
    private function getPendingPickups(): array
    {
        return [];
    }
    private function getTodayEarnings(): float
    {
        return 125.50;
    }
    private function getOptimizedRoute(): array
    {
        return [];
    }
    private function getAssignedPickups(string $timeSlotFilter = 'all', string $statusFilter = 'all'): array
    {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            return [];
        }

        $timeSlot = $timeSlotFilter !== 'all' ? $timeSlotFilter : null;
        $status = $statusFilter !== 'all' ? $this->mapStatusForQuery($statusFilter) : null;

        try {
            $pickupRequest = new PickupRequest();
            $records = $pickupRequest->listForCollector($collectorId, $status, $timeSlot);
            if (!empty($records)) {
                return $records;
            }
        } catch (\Throwable $e) {
            error_log('Collector tasks load failed: ' . $e->getMessage());
        }

        return [];
    }
    private function getAvailablePickups(): array
    {
        return [];
    }
    private function getPickupFilters(): array
    {
        return [
            'timeSlots' => $this->getTimeSlots(),
            'statuses' => ['all', 'pending', 'assigned', 'in progress', 'completed'],
        ];
    }

    private function getTimeSlots(): array
    {
        try {
            $pickupRequest = new PickupRequest();
            return $pickupRequest->listTimeSlots();
        } catch (\Throwable $e) {
            error_log('Collector time slot load failed: ' . $e->getMessage());
        }

        $dummy = dummy_data('time_slots');
        return is_array($dummy) ? $dummy : [];
    }

    private function normalizeTimeSlot(string $input): string
    {
        $candidate = trim($input);
        if ($candidate === '') {
            return 'all';
        }
        return $candidate;
    }

    private function normalizeStatus(string $input): string
    {
        $candidate = strtolower(trim($input));
        if ($candidate === '' || $candidate === 'all') {
            return 'all';
        }

        $allowed = ['pending', 'assigned', 'in progress', 'completed'];
        return in_array($candidate, $allowed, true) ? $candidate : 'all';
    }

    private function mapStatusForQuery(string $status): string
    {
        if ($status === 'in progress') {
            return 'in_progress';
        }

        return $status;
    }
    private function getRouteHistory(): array
    {
        return [];
    }
    private function getRouteStats(): array
    {
        return [];
    }
    private function getDailyEarnings(): array
    {
        return [];
    }
    private function getMonthlyEarnings(): float
    {
        return 2500.00;
    }
    private function getPaymentHistory(): array
    {
        return [];
    }
    private function getPendingPayments(): array
    {
        return [];
    }
    private function getCollectionStats(): array
    {
        return [];
    }
    private function getWeightReports(): array
    {
        return [];
    }
    private function getMaterialBreakdown(): array
    {
        return [];
    }
    private function getCollectorProfile(): array
    {
        return [];
    }
    private function getVehicleInfo(): array
    {
        return [];
    }
    private function getCertifications(): array
    {
        return [];
    }
}
