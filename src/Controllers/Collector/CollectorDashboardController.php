<?php

namespace Controllers\Collector;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Models\PickupRequest;
use Models\User;
use Models\Vehicle;
use Models\IncomeWaste;

class CollectorDashboardController extends DashboardController
{
    private ?array $collectorRecord = null;

    protected function setUserContext(): void
    {
        $this->userType = 'collector';
        $this->viewPrefix = 'collector';
        // $this->ensureRole('collector'); // optional role enforcement
    }

    // ------------------------
    // DASHBOARD
    // ------------------------
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
        return $this->renderDashboard('setting', ['pageTitle' => 'Collection Setting']);
    }

    public function profile(): \Core\Http\Response
    {
        $session = session();

        $data = [
            'pageTitle' => 'Collector Profile',
            'collectorProfile' => $this->getCollectorProfile(),
            'vehicleInfo' => $this->getVehicleInfo(),
            'certifications' => $this->getCertifications(),
            'statusMessage' => $session->getFlash('status'),
            'validationErrors' => $session->getFlash('errors', []),
            'oldInput' => $session->getFlash('old', []),
        ];

        return $this->renderDashboard('profile', $data);
    }

    protected function getNavigationItems(): array
    {
        return NavigationConfig::getNavigation($this->userType);
    }

    // ------------------------
    // PICKUP WORKFLOW
    // ------------------------

    public function startPickup(): \Core\Http\Response
    {
        $request = request();
        $pickupId = (int)$request->route('id');
        $collectorId = (int)($this->user['id'] ?? 0);

        $pickupModel = new PickupRequest();
        $incomeModel = new IncomeWaste();

        $pickup = $pickupModel->findForCollector($pickupId, $collectorId);
        if (!$pickup) return response()->json(['message' => 'Pickup not found'], 404);
        if ($pickup['status'] !== 'assigned') return response()->json(['message' => 'Pickup cannot be started'], 400);

        $pickupModel->updateById($pickupId, ['status' => 'in_progress']);

        $calculated = $incomeModel->calculateAmountsForDisplay($pickupId);

        return response()->json([
            'message' => 'Pickup started',
            'status' => 'in_progress',
            'wastes' => $calculated['wastes'],
            'totalWeight' => array_sum(array_column($calculated['wastes'], 'quantity')),
            'totalPrice' => $calculated['totalPrice']
        ]);
    }

    /**
     * Preview weight-based calculations WITHOUT saving
     */
    public function previewPickupWeight(): \Core\Http\Response
    {
        $request = request();
        $pickupId = (int) $request->route('id');
        $weight   = (float) $request->input('weight');

        if ($weight <= 0) return response()->json(['message' => 'Weight must be greater than 0'], 400);

        $collectorId = (int)($this->user['id'] ?? 0);
        $pickupModel = new PickupRequest();
        $incomeModel = new IncomeWaste();

        $pickup = $pickupModel->findForCollector($pickupId, $collectorId);
        if (!$pickup) return response()->json(['message' => 'Pickup not found'], 404);

        try {
            $calculated = $incomeModel->calculateAmountsForDisplay($pickupId, $weight);
        } catch (\Throwable $e) {
            error_log('Failed to calculate pickup amounts: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to calculate pickup amounts'], 500);
        }

        return response()->json([
            'message' => 'Preview calculated successfully',
            'weight' => $weight,
            'totalPrice' => $calculated['totalPrice'],
            'wastes' => $calculated['wastes']
        ]);
    }

    /**
     * Complete pickup and save weight/price
     */
    public function completePickup(): \Core\Http\Response
    {
        $request = request();
        $pickupId = (int)$request->route('id');
        $weight   = (float)$request->input('weight');

        if ($weight <= 0) return response()->json(['message' => 'Weight must be positive'], 400);

        $collectorId = (int)($this->user['id'] ?? 0);
        $pickupModel = new PickupRequest();
        $incomeModel = new IncomeWaste();

        $pickup = $pickupModel->findForCollector($pickupId, $collectorId);
        if (!$pickup || $pickup['status'] !== 'in_progress') return response()->json(['message' => 'Pickup not in progress'], 400);

        try {
            // Save final weight & price
            $result = $incomeModel->saveWeightAndPrice($pickupId, $weight);
            // Mark pickup as completed
            $incomeModel->completePickup($pickupId);
        } catch (\Throwable $e) {
            error_log('Complete pickup failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to complete pickup'], 500);
        }

        $updatedPickup = $pickupModel->find($pickupId);

        return response()->json([
            'message' => 'Pickup completed successfully',
            'weight' => $weight,
            'totalPrice' => $result['totalPrice'],
            'breakdown' => $result['breakdown'],
            'data' => $updatedPickup
        ]);
    }

    // ------------------------
    // OTHER PLACEHOLDER METHODS
    // ------------------------
    private function getTodayPickups(): array { return []; }
    private function getCompletedPickupsToday(): int { return 5; }
    private function getPendingPickups(): array { return []; }
    private function getTodayEarnings(): float { return 125.50; }
    private function getOptimizedRoute(): array { return []; }
    private function getAssignedPickups(string $timeSlotFilter = 'all', string $statusFilter = 'all'): array
    {
        $collectorId = (int)($this->user['id'] ?? 0);
        if ($collectorId <= 0) return [];

        $timeSlot = $timeSlotFilter !== 'all' ? $timeSlotFilter : null;
        $status = $statusFilter !== 'all' ? $this->mapStatusForQuery($statusFilter) : null;

        try {
            $pickupRequest = new PickupRequest();
            $records = $pickupRequest->listForCollector($collectorId, $status, $timeSlot);
            if (!empty($records)) return $records;
        } catch (\Throwable $e) {
            error_log('Collector tasks load failed: ' . $e->getMessage());
        }

        return [];
    }
    private function getAvailablePickups(): array { return []; }
    private function getPickupFilters(): array
    {
        return [
            'timeSlots' => $this->getTimeSlots(),
            'statuses' => ['all','pending','assigned','in progress','completed'],
        ];
    }
    private function getTimeSlots(): array { return ['09:00-11:00','11:00-13:00','14:00-16:00','16:00-18:00']; }
    private function normalizeTimeSlot(string $input): string { return trim($input) ?: 'all'; }
    private function normalizeStatus(string $input): string
    {
        $candidate = strtolower(trim($input));
        $allowed = ['pending','assigned','in progress','completed'];
        return in_array($candidate,$allowed,true) ? $candidate : 'all';
    }
    private function mapStatusForQuery(string $status): string { return $status === 'in progress' ? 'in_progress' : $status; }
    private function getRouteHistory(): array { return []; }
    private function getRouteStats(): array { return []; }
    private function getDailyEarnings(): array { return []; }
    private function getMonthlyEarnings(): float { return 2500.00; }
    private function getPaymentHistory(): array { return []; }
    private function getPendingPayments(): array { return []; }
    private function getCollectionStats(): array { return []; }
    private function getWeightReports(): array { return []; }
    private function getMaterialBreakdown(): array { return []; }

    // ------------------------
    // PROFILE / VEHICLE / CERTIFICATIONS
    // ------------------------
    private function getCollectorProfile(): array
    {
        $record = $this->loadCollectorRecord();
        if (!$record) return $this->getCollectorFallbackProfile();

        $metadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];
        [$firstName, $lastName] = [$metadata['firstName'] ?? '', $metadata['lastName'] ?? ''];
        if ($firstName === '' && $lastName === '') [$firstName, $lastName] = $this->splitName($record['name'] ?? '');

        $displayName = trim($record['name'] ?? '');
        if ($displayName === '' && ($firstName !== '' || $lastName !== '')) $displayName = trim($firstName . ' ' . $lastName);

        $profileImagePath = $record['profileImagePath'] ?? ($record['profile_image_path'] ?? null);

        return [
            'id' => $record['id'] ?? null,
            'name' => $displayName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $record['email'] ?? '',
            'phone' => $record['phone'] ?? '',
            'address' => $record['address'] ?? ($metadata['address'] ?? ''),
            'postalCode' => $metadata['postalCode'] ?? ($metadata['postal_code'] ?? ''),
            'nic' => $metadata['nic'] ?? '',
            'description' => $metadata['description'] ?? '',
            'profile_pic' => $metadata['profile_pic'] ?? null,
            'profileImage' => $profileImagePath,
            'profileImagePath' => $profileImagePath,
            'bank' => $record['bank_name'] ?? [],
            'metadata' => $metadata,
        ];
    }
    private function getVehicleInfo(): array { return []; }
    private function getCertifications(): array { return []; }
    private function loadCollectorRecord(): ?array { return $this->collectorRecord ?? null; }
    private function getCollectorFallbackProfile(): array { return []; }
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
