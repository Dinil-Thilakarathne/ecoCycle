<?php

namespace Controllers\Customer;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Core\Http\Response;
use Models\User;
use Models\PickupRequest;
use Models\WasteCategory;
use Models\Payment;
use Models\CollectorRating;

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
        $pickupData = $this->getCustomerPickupData();

        $data = [
            'pageTitle' => 'My Dashboard',
            'rewardPoints' => $this->getRewardPoints(),
            'recentPickups' => array_slice($pickupData['pickupRequests'], 0, 5),
            'upcomingPickups' => $this->getUpcomingPickups(),
            'recyclingStats' => $this->getRecyclingStats(),
            'userProfile' => $pickupData['userProfile'] ?? []
        ];

        $data = array_merge($data, $pickupData);

        return $this->renderDashboard('dashboard', $data);
    }

    /**
     * Schedule pickup page
     */
    public function pickup(): Response
    {
        $pickupData = $this->getCustomerPickupData();

        $data = [
            'pageTitle' => 'Pickup Request',
        ] + $pickupData;

        return $this->renderDashboard('pickup', $data);
    }

    /**
     * Pickup history
     */
    public function payment(): Response
    {
        $user   = auth();
        $userId = (int) ($user['id'] ?? 0);

        $paymentModel = new Payment();
        $transactions = $paymentModel->listCustomerPayments($userId, 50);

        $data = [
            'pageTitle' => 'Payments & Payouts',
            'payments'  => $transactions,
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
        $session = session();

        $statusMessage = $session->getFlash('status');
        $errors = $session->getFlash('errors', []);
        $oldInput = $session->getFlash('old', []);

        $data = [
            'pageTitle' => 'My Profile',
            'userProfile' => $this->getUserProfile(),
            'addressBook' => $this->getAddressBook(),
            'statusMessage' => $statusMessage,
            'validationErrors' => $errors,
            'oldInput' => $oldInput,
        ];

        return $this->renderDashboard('profile', $data);
    }

    /**
     * Education center
     */
    public function analytics(): Response
    {
        $analyticsData = $this->getCustomerAnalyticsData();

        $data = [
            'pageTitle' => 'Analytics',
            'articles' => $this->getEducationalArticles(),
            'tips' => $this->getRecyclingTips(),
            'videos' => $this->getEducationalVideos(),
            'analyticsData' => $analyticsData,
        ];

        return $this->renderDashboard('analytics', $data);
    }

    private function getCustomerAnalyticsData(): array
    {
        $customerId = (int) ($this->user['id'] ?? 0);
        if ($customerId <= 0) {
            return [
                'totalWeight' => 0.0,
                'totalIncomeThisMonth' => 0.0,
                'monthlyWasteData' => [],
                'monthlyIncomeData' => [],
            ];
        }

        $db = app('db');
        $isPgsql = method_exists($db, 'isPgsql') && $db->isPgsql();
        $scheduledDateExpr = 'pr.scheduled_at';

        $totalWeight = 0.0;
        $totalIncomeThisMonth = 0.0;
        $monthlyWasteData = [];
        $monthlyIncomeData = [];

        try {
            $weightRow = $db->fetchOne(
                    "SELECT COALESCE(SUM(COALESCE(prw.weight, 0)), 0) AS total_weight
                 FROM pickup_requests pr
                 INNER JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
                 WHERE pr.customer_id = ? AND pr.status = 'completed'",
                [$customerId]
            );
            $totalWeight = (float) ($weightRow['total_weight'] ?? 0);
        } catch (\Throwable $e) {
            $totalWeight = 0.0;
        }

        try {
            $monthStart = date('Y-m-01 00:00:00');
            $nextMonthStart = date('Y-m-01 00:00:00', strtotime('+1 month', strtotime($monthStart)));

            $incomeRow = $db->fetchOne(
                "SELECT COALESCE(SUM(COALESCE(pr.price, 0)), 0) AS total_income
                 FROM pickup_requests pr
                 WHERE pr.customer_id = ?
                   AND pr.status IN ('confirmed', 'completed')
                   AND COALESCE(pr.price, 0) > 0
                                     AND {$scheduledDateExpr} >= ?
                                     AND {$scheduledDateExpr} < ?",
                [$customerId, $monthStart, $nextMonthStart]
            );

            $totalIncomeThisMonth = (float) ($incomeRow['total_income'] ?? 0);
        } catch (\Throwable $e) {
            $totalIncomeThisMonth = 0.0;
        }

        try {
            $categoryRows = $db->fetchAll(
                "SELECT name
                 FROM waste_categories
                 ORDER BY name ASC"
            );

            $categoryNames = [];
            foreach ($categoryRows as $categoryRow) {
                $name = trim((string) ($categoryRow['name'] ?? ''));
                if ($name !== '') {
                    $categoryNames[] = $name;
                }
            }

            $wasteRows = $db->fetchAll(
                    "SELECT pr.scheduled_at,
                            wc.name AS category_name,
                            COALESCE(prw.weight, 0) AS waste_weight
                 FROM pickup_requests pr
                 INNER JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
                 INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
                 WHERE pr.customer_id = ?
                                     AND pr.status = 'completed'
                                     AND pr.scheduled_at IS NOT NULL",
                [$customerId]
            );

            $monthMap = [];
            foreach ($wasteRows as $row) {
                $dateValue = (string) ($row['scheduled_at'] ?? '');
                $timestamp = strtotime($dateValue);
                if ($timestamp === false) {
                    continue;
                }

                $monthKey = date('Y-m', $timestamp);
                $monthLabel = date('M Y', strtotime($monthKey . '-01'));
                $categoryName = trim((string) ($row['category_name'] ?? ''));
                $weightValue = (float) ($row['waste_weight'] ?? 0);

                if (!isset($monthMap[$monthKey])) {
                    $monthMap[$monthKey] = [
                        'month' => $monthKey,
                        'label' => $monthLabel,
                    ];

                    foreach ($categoryNames as $name) {
                        $monthMap[$monthKey][$name] = 0.0;
                    }
                }

                if ($categoryName !== '') {
                    if (!array_key_exists($categoryName, $monthMap[$monthKey])) {
                        $monthMap[$monthKey][$categoryName] = 0.0;
                    }
                    $monthMap[$monthKey][$categoryName] += $weightValue;
                }
            }

            if (empty($monthMap)) {
                $currentMonth = date('Y-m');
                $monthMap[$currentMonth] = [
                    'month' => $currentMonth,
                    'label' => date('M Y', strtotime($currentMonth . '-01')),
                ];
                foreach ($categoryNames as $name) {
                    $monthMap[$currentMonth][$name] = 0.0;
                }
            }

            ksort($monthMap);
            $monthlyWasteData = array_values($monthMap);
        } catch (\Throwable $e) {
            $monthlyWasteData = [];
        }

        try {
            if ($isPgsql) {
                $incomeRows = $db->fetchAll(
                                                                                "SELECT TO_CHAR({$scheduledDateExpr}, 'YYYY-MM') AS month_key,
                            COALESCE(SUM(COALESCE(pr.price, 0)), 0) AS total_income
                     FROM pickup_requests pr
                     WHERE pr.customer_id = ?
                       AND pr.status IN ('confirmed', 'completed')
                       AND COALESCE(pr.price, 0) > 0
                                             AND {$scheduledDateExpr} IS NOT NULL
                     GROUP BY month_key
                     ORDER BY month_key ASC",
                    [$customerId]
                );
            } else {
                $incomeRows = $db->fetchAll(
                                                                                "SELECT DATE_FORMAT({$scheduledDateExpr}, '%Y-%m') AS month_key,
                            COALESCE(SUM(COALESCE(pr.price, 0)), 0) AS total_income
                     FROM pickup_requests pr
                     WHERE pr.customer_id = ?
                       AND pr.status IN ('confirmed', 'completed')
                       AND COALESCE(pr.price, 0) > 0
                                             AND {$scheduledDateExpr} IS NOT NULL
                     GROUP BY month_key
                     ORDER BY month_key ASC",
                    [$customerId]
                );
            }

            foreach ($incomeRows as $row) {
                $monthKey = (string) ($row['month_key'] ?? '');
                if ($monthKey === '') {
                    continue;
                }

                $monthlyIncomeData[$monthKey] = (float) ($row['total_income'] ?? 0);
            }
        } catch (\Throwable $e) {
            $monthlyIncomeData = [];
        }

        return [
            'totalWeight' => round($totalWeight, 2),
            'totalIncomeThisMonth' => round($totalIncomeThisMonth, 2),
            'monthlyWasteData' => $monthlyWasteData,
            'monthlyIncomeData' => $monthlyIncomeData,
        ];
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
    private function getCustomerPickupData(): array
    {
        $pickupModel = new PickupRequest();
        $wasteCategoryModel = new WasteCategory();
        $collectorRatingModel = new CollectorRating();
        $customerId = (int) ($this->user['id'] ?? 0);

        try {
            $timeSlots = ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00']; // TODO: need to get the value from the db 
        } catch (\Throwable $e) {
            $timeSlots = [];
        }

        if (empty($timeSlots)) {
            $timeSlots = ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00'];
        }

        try {
            $pickupRequests = $pickupModel->listForCustomer($customerId);
        } catch (\Throwable $e) {
            $pickupRequests = [];
        }

        try {
            $ratedPickupIds = $collectorRatingModel->getRatedPickupRequestIds($customerId);
        } catch (\Throwable $e) {
            $ratedPickupIds = [];
        }

        if (!empty($pickupRequests)) {
            $ratedLookup = array_fill_keys($ratedPickupIds, true);
            $pickupRequests = array_map(static function (array $request) use ($ratedLookup): array {
                $id = (string) ($request['id'] ?? '');
                $request['hasRating'] = isset($ratedLookup[$id]);
                return $request;
            }, $pickupRequests);
        }

        try {
            $wasteCategories = $wasteCategoryModel->listAll();
        } catch (\Throwable $e) {
            $wasteCategories = [];
        }

        if (empty($wasteCategories)) {
            $wasteCategories = [
                ['id' => 1, 'name' => 'Plastic'],
                ['id' => 2, 'name' => 'Glass'],
                ['id' => 3, 'name' => 'Paper'],
                ['id' => 4, 'name' => 'Metal'],
                ['id' => 6, 'name' => 'Organic']
            ];
        }

        return [
            'timeSlots' => $timeSlots,
            'pickupRequests' => array_values($pickupRequests),
            'wasteCategories' => $wasteCategories,
            'userProfile' => $this->getUserProfile(),
        ];
    }
    private function getUserProfile(): array
    {
        $userId = (int) ($this->user['id'] ?? 0);
        if ($userId <= 0) {
            return [];
        }

        $userModel = new User();

        try {
            $user = $userModel->findById($userId);
        } catch (\Throwable $e) {
            return [];
        }

        if (!$user) {
            return [];
        }

        $metadata = is_array($user['metadata'] ?? null) ? $user['metadata'] : [];

        $firstName = $metadata['firstName'] ?? '';
        $lastName = $metadata['lastName'] ?? '';

        if ($firstName === '' && $lastName === '') {
            [$firstName, $lastName] = $this->splitName((string) ($user['name'] ?? ''));
        }

        $displayName = trim((string) ($user['name'] ?? ''));
        if ($displayName === '' && ($firstName !== '' || $lastName !== '')) {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        $nic = $metadata['nic'] ?? ($metadata['NIC'] ?? '');
        $description = $metadata['description'] ?? ($metadata['bio'] ?? '');

        $bank = [
            'bankName' => $user['bank_name'] ?? '',
            'branch' => $user['bank_branch'] ?? '',
            'holderName' => $user['bank_account_name'] ?? '',
            'accountNumber' => $user['bank_account_number'] ?? '',
        ];

        $bankRaw = $metadata['bank'] ?? $metadata['bankDetails'] ?? [];
        if (is_string($bankRaw)) {
            $decoded = json_decode($bankRaw, true);
            $bankRaw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($bankRaw)) {
            $bankRaw = [];
        }

        if ($bank['bankName'] === '') {
            $bank['bankName'] = $bankRaw['bankName'] ?? ($bankRaw['bank'] ?? '');
        }
        if ($bank['branch'] === '') {
            $bank['branch'] = $bankRaw['branch'] ?? '';
        }
        if ($bank['holderName'] === '') {
            $bank['holderName'] = $bankRaw['holderName'] ?? ($bankRaw['accountName'] ?? '');
        }
        if ($bank['accountNumber'] === '') {
            $bank['accountNumber'] = $bankRaw['accountNumber'] ?? ($bankRaw['account_number'] ?? '');
        }

        $profileImagePath = $user['profile_image_path'] ?? null;
        if (!$profileImagePath && isset($metadata['profileImage'])) {
            $profileImagePath = $metadata['profileImage'];
        }

        $profilePic = $metadata['profile_pic'] ?? $profileImagePath;

        return [
            'id' => $user['id'] ?? null,
            'name' => $displayName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? ($metadata['address'] ?? ''),
            'postalCode' => $metadata['postalCode'] ?? '',
            'nic' => $nic,
            'description' => $description,
            'bank' => $bank,
            'bankAccount' => $bank['accountNumber'],
            'profile_pic' => $profilePic,
            'profileImage' => $profileImagePath,
            'metadata' => $metadata,
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
