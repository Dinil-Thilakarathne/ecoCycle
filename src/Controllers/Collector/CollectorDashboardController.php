<?php

namespace Controllers\Collector;

use Controllers\DashboardController;
use Core\Http\Request;
use EcoCycle\Core\Navigation\NavigationConfig;
use Models\PickupRequest;
use Models\User;
use Models\Vehicle;
use Models\IncomeWaste;
use Models\CollectorFeedback;
use Models\CollectorRating; 

use Models\Notification;

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
            'collectorProfile' => $this->getCollectorProfile(),
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
    public function earnings(): \Core\Http\Response
    {
        $data = [
            'pageTitle' => 'My Earnings',
        ];

        return $this->renderDashboard('earnings', $data);
    }

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
            return count(array_filter(
                $allPickups,
                fn(array $pickup) => in_array($pickup['status'] ?? '', ['assigned', 'in_progress', 'completed'], true)
            ));
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
            return count($completedPickups);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getPendingPickups(): array
    {
        try {
            $pickupRequest = new PickupRequest();
            $allPickups = $pickupRequest->listAll();

            return array_values(array_filter(
                $allPickups,
                static fn (array $pickup): bool => strtolower((string) ($pickup['status'] ?? '')) === 'pending'
            ));
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
                return array_values($records);
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

    private function isPickupForToday(array $pickup): bool
    {
        $today = date('Y-m-d');

        $scheduledDate = isset($pickup['scheduled_at']) ? substr((string) $pickup['scheduled_at'], 0, 10) : '';
        if ($scheduledDate !== '') {
            return $scheduledDate === $today;
        }

        $createdDate = isset($pickup['created_at']) ? substr((string) $pickup['created_at'], 0, 10) : '';
        if ($createdDate !== '') {
            return $createdDate === $today;
        }

        return false;
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
            $amount = $incomeWaste->saveWeightAndCalculateSingle((string) $pickupId, $weight);

            // Update pickup status
            $pickupRequest = new PickupRequest();
            $pickupRequest->updateStatus((string) $pickupId, 'in progress');

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
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pickupId = $request->route('id');
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                throw new \Exception('Invalid input');
            }

            $status = trim($data['status'] ?? '');
            if (empty($pickupId) || $status === '') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid pickup ID or status'
                ]);
                exit;
            }

            // Get collector ID from session
            $collectorId = (int) ($this->user['id'] ?? 0);
            if ($collectorId <= 0) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized: Collector not found'
                ]);
                exit;
            }

            $pickupRequest = new PickupRequest();

            // Extract weights array if status is completed
            $weights = isset($data['weights']) && is_array($data['weights']) ? $data['weights'] : null;

            // Log the request for debugging
            error_log("Updating pickup {$pickupId} for collector {$collectorId} to status {$status}");
            if ($weights) {
                error_log("Weights data: " . json_encode($weights));
            }

            // Use updateStatusForCollector to handle weights and price calculation
            try {
                $result = $pickupRequest->updateStatusForCollector(
                    (string) $pickupId,
                    $collectorId,
                    $status,
                    $weights
                );

                if (!$result) {
                    error_log("updateStatusForCollector returned false for pickup {$pickupId}");
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update pickup status. Please check if the pickup is assigned to you and try again.'
                    ]);
                    exit;
                }
            } catch (\Throwable $updateError) {
                error_log("Error in updateStatusForCollector: " . $updateError->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $updateError->getMessage()
                ]);
                exit;
            }

            // 📝 Create payment records if status is completed
            if ($status === 'completed' && $weights) {
                // Fetch the updated pickup to get total price
                $completedPickup = $pickupRequest->find((string) $pickupId);
                $totalPayoutAmount = (float) ($completedPickup['price'] ?? 0);
                $customerId = (int) ($completedPickup['customerId'] ?? 0);

                if ($totalPayoutAmount > 0 && $customerId > 0) {
                    try {
                        $paymentService = new \Services\Payment\PaymentService();

                        // 1. Create Customer Payout Payment
                        $paymentService->createManualPayment([
                            'type' => 'payout',
                            'recipientId' => $customerId,
                            'amount' => $totalPayoutAmount,
                            'status' => 'pending',
                            'notes' => "Payout for Pickup #{$pickupId}",
                            'txnId' => "PO-{$pickupId}-" . time()
                        ]);

                        // 2. Create Collector Commission Payment
                        // Commission: Rs. 100 base + 10% of customer payout
                        $baseCommission = 100.00;
                        $percentageCommission = $totalPayoutAmount * 0.10; // 10%
                        $totalCommission = round($baseCommission + $percentageCommission, 2);

                        $paymentService->createManualPayment([
                            'type' => 'payout',
                            'recipientId' => $collectorId,
                            'amount' => $totalCommission,
                            'status' => 'pending',
                            'notes' => "Commission for Pickup #{$pickupId} (Base: Rs.{$baseCommission} + 10% of Rs.{$totalPayoutAmount})",
                            'txnId' => "COM-{$pickupId}-" . time()
                        ]);

                        error_log("✅ Payments created: Customer Rs.{$totalPayoutAmount}, Collector Rs.{$totalCommission}");
                    } catch (\Throwable $paymentError) {
                        error_log("❌ Failed to create payment records: " . $paymentError->getMessage());
                        // Don't fail the entire request if payment creation fails
                        // Let the status update succeed, but log the error
                    }
                }
            }

            // Fetch updated pickup data to return to frontend
            $updatedPickup = $pickupRequest->find((string) $pickupId);

            if (!$updatedPickup) {
                // Status was updated but we couldn't fetch the record
                echo json_encode([
                    'success' => true,
                    'message' => 'Status updated successfully',
                    'data' => [
                        'id' => $pickupId,
                        'status' => $status,
                        'statusRaw' => $status
                    ]
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $updatedPickup
            ]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('Update status error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ]);
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
                        'lowRatings' => count($model->getLowRatings($collectorId, 2))
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

    public function getFeedback(Request $request)
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
                'data' => $feedback
            ]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function addFeedback(Request $request)
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $data = $request->json();

            $customerId = (int) ($data['customer_id'] ?? 0);
            $pickupRequestId = trim((string) ($data['pickup_request_id'] ?? $data['pickupRequestId'] ?? ''));
            $rating = (int) ($data['rating'] ?? 0);
            $description = trim($data['description'] ?? '');

            if ($customerId <= 0 || $pickupRequestId === '' || $rating < 1 || $rating > 5 || $description === '') {
                throw new \Exception('Invalid input');
            }

            $pickupModel = new PickupRequest();
            $pickup = $pickupModel->find($pickupRequestId);

            if (!$pickup) {
                throw new \Exception('Pickup request not found');
            }

            $pickupCustomerId = (int) ($pickup['customerId'] ?? $pickup['customer_id'] ?? 0);
            $collectorId = (int) ($pickup['collectorId'] ?? $pickup['collector_id'] ?? 0);

            if ($pickupCustomerId !== $customerId) {
                throw new \Exception('Pickup request does not match customer_id');
            }

            if ($collectorId <= 0) {
                throw new \Exception('collector_id is missing in pickup request');
            }

            $model = new CollectorFeedback();
            $model->create([
                'collector_id' => $collectorId,
                'customer_id' => $customerId ?: null,
                'pickup_request_id' => $pickupRequestId,
                'rating' => $rating,
                'description' => $description
            ]);

            echo json_encode(['success' => true]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }


    public function getWasteCollection(Request $request)
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
            // $records = $incomeWaste->getWasteCollectionForCollector($collectorId);
            $limit = (int) ($request->query('limit') ?? 50);
            $records = $incomeWaste->getWasteCollectionForCollector($collectorId, $limit);


            // Clean the data to ensure JS can parse numbers correctly
            $formattedRecords = array_map(function ($r) {
                return [
                    'customer_id' => $r['customer_id'] ?? 'N/A',
                    'customer_name' => $r['customer_name'] ?? 'Unknown',
                    'location' => $r['location'] ?? 'Not provided',
                    'category' => $r['category'] ?? 'General',
                    'weight' => (float) ($r['weight'] ?? 0),
                    'amount' => (float) ($r['amount'] ?? 0),
                    'pickup_id' => $r['pickup_id']
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

public function notifications(Request $request)
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            throw new \Exception('Collector not authenticated');
        }

        $limit = (int) $request->query('limit', 100);
        $createdAfter = (string) $request->query('created_after', '1970-01-01 00:00:00');

        $notificationModel = new Notification();
        $notifications = $notificationModel->forUser($collectorId, 'collector', $createdAfter, $limit);

        echo json_encode([
            'status' => 'success',
            'data' => $notifications,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch notifications',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

public function markNotificationRead(Request $request)
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $collectorId = (int) ($this->user['id'] ?? 0);
        $notificationId = (string) $request->route('id', '');

        if ($collectorId <= 0) {
            throw new \Exception('Collector not authenticated');
        }

        if ($notificationId === '') {
            throw new \Exception('Notification ID is required');
        }

        $notificationModel = new Notification();
        $result = $notificationModel->markAsRead($notificationId, $collectorId);

        if (!$result) {
            throw new \Exception('Notification update failed');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

public function markAllNotificationsRead(Request $request)
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            throw new \Exception('Collector not authenticated');
        }

        $notificationModel = new Notification();
        $notificationModel->markAllAsRead($collectorId);

        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

public function getLowRatingsCount(int $collectorId, int $maxRating = 2): int
{
    $sql = "
        SELECT COUNT(*) AS count
        FROM {$this->table}
        WHERE collector_id = ? AND rating <= ?
    ";

        $row = $this->db->fetchOne($sql, [$collectorId, $maxRating]);
        return (int) ($row['count'] ?? 0);
    }



    // public function notifications(): \Core\Http\Response
    // {
    //     $userId = (int) ($this->user['id'] ?? 0);
    //     $role = $this->user['role'] ?? 'collector'; // adjust if needed

    //     $notificationModel = new Notification();

    //     // Fetch latest 100 notifications for this user
    //     $notifications = $notificationModel->forUser(
    //         $userId,
    //         $role,
    //         date('Y-m-d 00:00:00'),
    //         100
    //     );

    //     $data = [
    //         'pageTitle' => 'Notifications',
    //         'notifications' => $notifications, // Pass to the view
    //         'authUser' => $this->user
    //     ];

    //     return $this->renderDashboard('notification', $data);
    // }
}