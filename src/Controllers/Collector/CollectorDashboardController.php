<?php

namespace Controllers\Collector;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Models\PickupRequest;
use Models\User;
use Models\Vehicle;
use Models\IncomeWaste;
use Models\CollectorFeedback;
use Models\CollectorRating;


/**
 * Collector Dashboard Controller
 * 
 * Handles collector-specific dashboard functionality
 */
class CollectorDashboardController extends DashboardController
{
    private ?array $collectorRecord = null;

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

    // Placeholder methods for data retrieval
    private function getTodayPickups(): int
    {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            return 0;
        }

        try {
            $pickupRequest = new PickupRequest();
            $allPickups = $pickupRequest->listForCollector($collectorId);
            
            // Count only today's pickups (assigned, in progress, or completed)
            $today = date('Y-m-d');
            $count = 0;
            foreach ($allPickups as $pickup) {
                $createdDate = isset($pickup['created_at']) ? substr($pickup['created_at'], 0, 10) : '';
                $scheduledDate = isset($pickup['scheduled_at']) ? substr($pickup['scheduled_at'], 0, 10) : '';
                
                if (($createdDate === $today || $scheduledDate === $today) && 
                    in_array($pickup['status'] ?? '', ['assigned', 'in_progress', 'completed'])) {
                    $count++;
                }
            }
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getCompletedPickupsToday(): int
    {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            return 0;
        }

        try {
            $pickupRequest = new PickupRequest();
            $completedPickups = $pickupRequest->listForCollector($collectorId, 'completed');
            
            // Count only today's completed pickups
            $today = date('Y-m-d');
            $count = 0;
            foreach ($completedPickups as $pickup) {
                $updatedDate = isset($pickup['updated_at']) ? substr($pickup['updated_at'], 0, 10) : '';
                if ($updatedDate === $today) {
                    $count++;
                }
            }
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getPendingPickups(): array
    {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            return [];
        }

        try {
            $pickupRequest = new PickupRequest();
            // Get assigned and in-progress pickups (not completed)
            $assigned = $pickupRequest->listForCollector($collectorId, 'assigned');
            $inProgress = $pickupRequest->listForCollector($collectorId, 'in_progress');
            
            return array_merge($assigned, $inProgress);
        } catch (\Throwable $e) {
            return [];
        }
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
            return ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00'];  // TODO: need to fix this
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
        $record = $this->loadCollectorRecord();
        if (!$record) {
            return $this->getCollectorFallbackProfile();
        }

        $metadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];

        $firstName = trim((string) ($metadata['firstName'] ?? ''));
        $lastName = trim((string) ($metadata['lastName'] ?? ''));
        if ($firstName === '' && $lastName === '') {
            [$firstName, $lastName] = $this->splitName((string) ($record['name'] ?? ''));
        }

        $displayName = trim((string) ($record['name'] ?? ''));
        if ($displayName === '' && ($firstName !== '' || $lastName !== '')) {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        $postalCode = trim((string) ($metadata['postalCode'] ?? ($metadata['postal_code'] ?? '')));
        $nic = trim((string) ($metadata['nic'] ?? ($metadata['NIC'] ?? '')));
        $description = trim((string) ($metadata['description'] ?? ($metadata['bio'] ?? '')));

        $bank = [
            'bankName' => $record['bank_name'] ?? '',
            'branch' => $record['bank_branch'] ?? '',
            'holderName' => $record['bank_account_name'] ?? '',
            'accountNumber' => $record['bank_account_number'] ?? '',
        ];

        $bankRaw = $metadata['bank'] ?? $metadata['bank_details'] ?? $metadata['bankDetails'] ?? [];
        if (is_string($bankRaw)) {
            $decoded = json_decode($bankRaw, true);
            $bankRaw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($bankRaw)) {
            $bankRaw = [];
        }

        if ($bank['bankName'] === '') {
            $bank['bankName'] = $bankRaw['bankName'] ?? ($bankRaw['bank_name'] ?? ($bankRaw['bank'] ?? ''));
        }
        if ($bank['branch'] === '') {
            $bank['branch'] = $bankRaw['branch'] ?? '';
        }
        if ($bank['holderName'] === '') {
            $bank['holderName'] = $bankRaw['holderName'] ?? ($bankRaw['account_name'] ?? ($bankRaw['accountName'] ?? ''));
        }
        if ($bank['accountNumber'] === '') {
            $bank['accountNumber'] = $bankRaw['accountNumber'] ?? ($bankRaw['account_number'] ?? '');
        }

        $profileImagePath = $record['profileImagePath'] ?? ($record['profile_image_path'] ?? null);
        $profilePic = $metadata['profile_pic'] ?? ($metadata['profileImage'] ?? $profileImagePath);

        $profile = [
            'id' => $record['id'] ?? null,
            'name' => $displayName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $record['email'] ?? '',
            'phone' => $record['phone'] ?? '',
            'address' => $record['address'] ?? ($metadata['address'] ?? ''),
            'postalCode' => $postalCode,
            'nic' => $nic,
            'description' => $description,
            'bank' => $bank,
            'bankAccount' => $bank['accountNumber'],
            'profile_pic' => $profilePic,
            'profileImage' => $profileImagePath,
            'profileImagePath' => $profileImagePath,
            'metadata' => $metadata,
        ];

        $hasCoreDetails = trim((string) ($profile['name'] ?? '')) !== ''
            || trim((string) ($profile['email'] ?? '')) !== ''
            || trim((string) ($profile['phone'] ?? '')) !== ''
            || trim((string) ($profile['address'] ?? '')) !== '';

        if (!$hasCoreDetails) {
            return $this->getCollectorFallbackProfile();
        }

        return $profile;
    }
    private function getVehicleInfo(): array
    {
        $record = $this->loadCollectorRecord();
        if (!$record) {
            return [];
        }

        $vehicleId = (int) ($record['vehicleId'] ?? $record['vehicle_id'] ?? 0);
        if ($vehicleId > 0) {
            try {
                $vehicleModel = new Vehicle();
                $vehicle = $vehicleModel->find($vehicleId);
                if (!empty($vehicle)) {
                    return $vehicle;
                }
            } catch (\Throwable $e) {
                error_log('Collector vehicle load failed: ' . $e->getMessage());
            }
        }

        $metadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];
        $vehicleMeta = $metadata['vehicle'] ?? [];
        if (is_string($vehicleMeta)) {
            $decoded = json_decode($vehicleMeta, true);
            $vehicleMeta = is_array($decoded) ? $decoded : [];
        }

        return is_array($vehicleMeta) ? $vehicleMeta : [];
    }
    private function getCertifications(): array
    {
        $record = $this->loadCollectorRecord();
        if (!$record) {
            return [];
        }

        $metadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];
        $certifications = $metadata['certifications'] ?? [];
        if (is_string($certifications)) {
            $decoded = json_decode($certifications, true);
            $certifications = is_array($decoded) ? $decoded : [];
        }

        return is_array($certifications) ? $certifications : [];
    }

    private function loadCollectorRecord(): ?array
    {
        if ($this->collectorRecord !== null) {
            return $this->collectorRecord;
        }

        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            $this->collectorRecord = null;
            return null;
        }

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
        $email = $this->user['email'] ?? 'collector@example.com';
        [$first, $last] = $this->splitName($name);

        return [
            'name' => $name,
            'firstName' => $first,
            'lastName' => $last,
            'email' => $email,
            'phone' => $this->user['phone'] ?? '+94 71 000 0000',
            'address' => $this->user['address'] ?? '42 Green Route, Eco City',
            'postalCode' => '',
            'nic' => '',
            'description' => '',
            'profile_pic' => null,
            'profileImage' => null,
            'profileImagePath' => null,
            'bank' => [
                'bankName' => 'National Bank',
                'branch' => 'Colombo Main',
                'holderName' => $name,
                'accountNumber' => '1234567890',
            ],
            'bankAccount' => '1234567890',
            'metadata' => [],
        ];
    }

    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName, 2);
        if (!$parts) {
            return ['', ''];
        }

        $first = $parts[0];
        $last = $parts[1] ?? '';

        return [$first, $last];
    }

    public function saveWeight(\Core\Http\Request $request)
{
    header('Content-Type: text/html; charset=utf-8');

    try {
        $pickupId = $request->route('id');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            throw new \Exception('Invalid input');
        }

        $weight = isset($data['weight']) ? floatval($data['weight']) : 0;
        if (empty($pickupId) || $weight <= 0) {
            http_response_code(400);
            echo "<div class='alert error'>Invalid pickup ID or weight</div>";
            exit;
        }

        // Save weight & calculate amount
        $incomeWaste = new IncomeWaste();
        $amount = $incomeWaste->saveWeightAndCalculateSingle((string)$pickupId, $weight);

        // Update pickup status
        $pickupRequest = new PickupRequest();
        $pickupRequest->updateStatus((int)$pickupId, 'in progress');

        // ✅ HTML RESPONSE with calculated amount
       echo "
            <div class='weight-result success'>
                <p><strong>Measured Weight:</strong> {$weight} kg</p>
                <p><strong>Total Amount:</strong> Rs. " . number_format($amount, 2) . "</p>
                <span class='status-tag inprogress'>In Progress</span>
            </div>
        ";
        exit;

    } catch (\Throwable $e) {
        http_response_code(500);
        $errorMsg = $e->getMessage() ?: 'Failed to save weight';
        error_log('Weight save error: ' . $errorMsg);
        echo "<div class='alert error'>{$errorMsg}</div>";
        exit;
    }
}

public function updateStatus(\Core\Http\Request $request)
{
    header('Content-Type: text/html; charset=utf-8');

    try {
        $pickupId = $request->route('id');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            throw new \Exception('Invalid input');
        }

        $status = trim($data['status'] ?? '');
        if (empty($pickupId) || $status === '') {
            http_response_code(400);
            echo "<div class='alert error'>Invalid pickup ID or status</div>";
            exit;
        }

        $pickupRequest = new PickupRequest();
        $pickupRequest->updateStatus((int)$pickupId, $status);

        $safeStatus = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
        $class = strtolower(str_replace(' ', '', $safeStatus));

        // ✅ HTML RESPONSE
        echo "
            <span class='status-tag {$class}'>
                " . ucfirst($safeStatus) . "
            </span>
        ";
        exit;

    } catch (\Throwable $e) {
        http_response_code(500);
        echo "<div class='alert error'>Failed to update status</div>";
        exit;
    }
}

public function getMetrics(Request $request)
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        // Fix: Use a more reliable way to detect the collector ID
        $collectorId = (int) $request->query('collector_id');
        
        // Fallback to logged in user if query param is missing
        if ($collectorId <= 0 && isset($this->user['id'])) {
            $collectorId = (int) $this->user['id'];
        }

        if ($collectorId <= 0) {
            throw new \Exception('Collector ID is required');
        }

        $model = new CollectorFeedback();

        echo json_encode([
            'success' => true,
            'data' => [
                'feedbackMetrics' => [
                    'averageRating' => $model->getAverageRating($collectorId),
                    'totalFeedback' => $model->getCollectorFeedbackCount($collectorId),
                    'pendingReview' => 0, 
                    'lowRatings'    => count($model->getLowRatings($collectorId, 2))
                ]
            ]
        ]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(400); // 400 is better for 'Invalid Input' than 500
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
    /**
     * GET /api/collector/feedback
     * Returns recent feedback records
     */
    /**
     * POST /api/collector/feedback
     * Add new feedback
     */

//     **
//  * FIXED: Standardized collectorId detection
//  */
public function getFeedback(\Core\Http\Request $request)
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $collectorId = (int) ($request->query('collector_id') ?? $this->user['id'] ?? 0);
        $limit = (int) $request->query('limit', 50);

        if ($collectorId <= 0) {
            throw new \Exception('Invalid collector ID');
        }

        $model = new CollectorFeedback();
        $feedback = $model->getCollectorFeedback($collectorId, $limit);

        echo json_encode([
            'success' => true,
            'data' => $feedback // Your Model already aliases 'description' to 'feedback'
        ]);
        exit;

    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
    public function addFeedback(Request $request)
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $data = $request->json();

            $collectorId = (int) ($data['collector_id'] ?? 0);
            $customerId  = (int) ($data['customer_id'] ?? 0);
            $rating      = (int) ($data['rating'] ?? 0);
            $feedback    = trim($data['feedback'] ?? '');

            if ($collectorId <= 0 || $rating < 1 || $rating > 5 || $feedback === '') {
                throw new \Exception('Invalid input');
            }

            $model = new CollectorFeedback();
            $model->create([
                'collector_id' => $collectorId,
                'customer_id'  => $customerId ?: null,
                'rating'       => $rating,
                'feedback'     => $feedback
            ]);

            echo json_encode(['success' => true]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

public function getWasteCollection(\Core\Http\Request $request)
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 1. Check Query String first (matches your JS ?collector_id=1)
        // 2. Fallback to Session user id
        $collectorId = (int) ($request->query('collector_id') ?? $this->user['id'] ?? 0);

        if ($collectorId <= 0) {
            throw new \Exception('Invalid collector ID - Please log in or provide an ID');
        }

        $incomeWaste = new \Models\IncomeWaste();
        $records = $incomeWaste->getWasteCollectionForCollector($collectorId);

        // Clean the data to ensure JS can parse numbers correctly
        $formattedRecords = array_map(function($r) {
            return [
                'customer_id'   => $r['customer_id'] ?? 'N/A',
                'customer_name' => $r['customer_name'] ?? 'Unknown',
                'category'      => $r['category'] ?? 'General',
                'weight'        => (float)($r['weight'] ?? 0),
                'amount'        => (float)($r['amount'] ?? 0),
                'pickup_id'     => $r['pickup_id']
            ];
        }, $records);

        echo json_encode([
            'success' => true,
            'data' => $formattedRecords
        ]);
        exit;

    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
}



    