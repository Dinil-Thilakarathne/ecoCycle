<?php

namespace Controllers\Collector;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Models\PickupRequest;
use Models\User;
use Models\Vehicle;
use Models\IncomeWaste;

/**
 * Collector Dashboard Controller
 * Handles collector-specific dashboard functionality
 */
class CollectorDashboardController extends DashboardController
{
    private ?array $collectorRecord = null;
    protected IncomeWaste $incomeWaste;

    protected function setUserContext(): void
    {
        $this->userType = 'collector';
        $this->viewPrefix = 'collector';

        // Initialize IncomeWaste model for weight & price handling
        $this->incomeWaste = new IncomeWaste();
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
     * Pickup assignments page
     */
    public function tasks(): \Core\Http\Response
    {
        $request = request();
        $selectedTimeSlot = $this->normalizeTimeSlot((string)$request->query('time_slot', 'all'));
        $selectedStatus = $this->normalizeStatus((string)$request->query('status', 'all'));

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
     * AJAX endpoint: Save measured weight & calculate each amount
     * Collector enters weight → server updates pickup_requests.weight,
     * updates pickup_request_wastes.weight, calculates individual amounts,
     * and returns breakdown for UI
     */
    public function saveWeight(int $pickupId)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $enteredWeight = isset($data['weight']) ? floatval($data['weight']) : 0;

        if ($pickupId <= 0 || $enteredWeight <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid pickup ID or weight'
            ]);
            exit;
        }

        try {
            $incomeWaste = new IncomeWaste();
            $pickupRequest = new PickupRequest();

            // 1️⃣ Save weight & calculate each individual amount
            $result = $incomeWaste->saveWeightAndCalculate((string)$pickupId, $enteredWeight);

            // 2️⃣ Update pickup_requests table weight only (price optional)
            $pickupRequest->updateWeightAndPrice((string)$pickupId, $enteredWeight, 0);

            // 3️⃣ Return JSON with individual amounts for frontend display
            echo json_encode([
                'success' => true,
                'data' => $result // contains ['breakdown' => [...], 'total' => ...]
            ]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage() ?: 'Failed to save weight'
            ]);
            exit;
        }
    }

    /**
     * AJAX endpoint: Mark pickup as completed
     */
    public function markCompleted(int $pickupId)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $weight = isset($data['weight']) ? floatval($data['weight']) : 0;
        $price = isset($data['price']) ? floatval($data['price']) : 0;

        if ($weight <= 0 || $price <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Weight or price missing']);
            exit;
        }

        $incomeWaste = new IncomeWaste();
        $pickupRequest = new PickupRequest();

        // Save weight & individual amounts
        $result = $incomeWaste->saveWeightAndCalculate((string)$pickupId, $weight);

        // Update pickup_requests weight and price, set status completed
        $pickupRequest->updateWeightAndPrice((string)$pickupId, $weight, $price, 'completed');

        echo json_encode([
            'message' => 'Task completed successfully',
            'data' => [
                'status' => 'completed',
                'weight' => $weight,
                'price' => $price,
                'breakdown' => $result['breakdown']
            ]
        ]);
    }

    /**
     * Profile and vehicle info
     */
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

    // ----------------- Data Retrieval Helpers -----------------
    private function getAssignedPickups(string $timeSlotFilter = 'all', string $statusFilter = 'all'): array
    {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) return [];

        $timeSlot = $timeSlotFilter !== 'all' ? $timeSlotFilter : null;
        $status = $statusFilter !== 'all' ? $this->mapStatusForQuery($statusFilter) : null;

        try {
            $pickupRequest = new PickupRequest();
            $records = $pickupRequest->listForCollector($collectorId, $status, $timeSlot);
            return $records ?: [];
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
            'statuses' => ['all', 'pending', 'assigned', 'in progress', 'completed'],
        ];
    }

    private function getTimeSlots(): array
    {
        return ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00'];
    }

    private function normalizeTimeSlot(string $input): string
    {
        return trim($input) === '' ? 'all' : trim($input);
    }

    private function normalizeStatus(string $input): string
    {
        $candidate = strtolower(trim($input));
        if ($candidate === '' || $candidate === 'all') return 'all';

        $allowed = ['pending', 'assigned', 'in progress', 'completed'];
        return in_array($candidate, $allowed, true) ? $candidate : 'all';
    }

    private function mapStatusForQuery(string $status): string
    {
        return $status === 'in progress' ? 'in_progress' : $status;
    }

    private function getCollectorProfile(): array
    {
        $record = $this->loadCollectorRecord();
        if (!$record) return $this->getCollectorFallbackProfile();

        $metadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];
        $firstName = trim((string)($metadata['firstName'] ?? ''));
        $lastName = trim((string)($metadata['lastName'] ?? ''));

        if ($firstName === '' && $lastName === '') {
            [$firstName, $lastName] = $this->splitName((string)($record['name'] ?? ''));
        }

        $displayName = trim((string)($record['name'] ?? '')) ?: trim($firstName . ' ' . $lastName);
        return [
            'id' => $record['id'] ?? null,
            'name' => $displayName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $record['email'] ?? '',
            'phone' => $record['phone'] ?? '',
            'address' => $record['address'] ?? ($metadata['address'] ?? ''),
            'bank' => [
                'bankName' => $record['bank_name'] ?? '',
                'branch' => $record['bank_branch'] ?? '',
                'holderName' => $record['bank_account_name'] ?? '',
                'accountNumber' => $record['bank_account_number'] ?? ''
            ],
            'profile_pic' => $metadata['profile_pic'] ?? null,
            'metadata' => $metadata,
        ];
    }

    private function loadCollectorRecord(): ?array
    {
        if ($this->collectorRecord !== null) return $this->collectorRecord;

        $collectorId = (int)($this->user['id'] ?? 0);
        if ($collectorId <= 0) return null;

        try {
            $userModel = new User();
            $record = $userModel->findById($collectorId);
        } catch (\Throwable $e) {
            error_log('Collector profile load failed: ' . $e->getMessage());
            $record = null;
        }

        $this->collectorRecord = is_array($record) ? $record : null;
        return $this->collectorRecord;
    }

    private function getCollectorFallbackProfile(): array
    {
        $name = $this->user['name'] ?? 'Demo Collector';
        [$first, $last] = $this->splitName($name);

        return [
            'name' => $name,
            'firstName' => $first,
            'lastName' => $last,
            'email' => $this->user['email'] ?? 'collector@example.com',
            'phone' => $this->user['phone'] ?? '+94 71 000 0000',
            'address' => $this->user['address'] ?? '42 Green Route, Eco City',
            'bank' => [
                'bankName' => 'National Bank',
                'branch' => 'Colombo Main',
                'holderName' => $name,
                'accountNumber' => '1234567890',
            ],
            'profile_pic' => null,
            'metadata' => [],
        ];
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [];
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    // Dummy placeholders for other dashboard stats
    private function getTodayPickups(): array { return []; }
    private function getCompletedPickupsToday(): int { return 5; }
    private function getPendingPickups(): array { return []; }
    private function getTodayEarnings(): float { return 125.50; }
    private function getOptimizedRoute(): array { return []; }
}
