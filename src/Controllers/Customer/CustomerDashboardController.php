<?php

namespace Controllers\Customer;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Core\Http\Response;
use Models\User;
use Models\PickupRequest;
use Models\WasteCategory;

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
    private function getCustomerPickupData(): array
    {
        $pickupModel = new PickupRequest();
        $wasteCategoryModel = new WasteCategory();
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
            $wasteCategories = $wasteCategoryModel->listAll();
        } catch (\Throwable $e) {
            $wasteCategories = [];
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
