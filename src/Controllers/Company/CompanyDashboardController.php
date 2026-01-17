<?php

namespace Controllers\Company;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Core\Http\Response;
use Models\BiddingRound;
use Models\Bid;
use Models\Payment;
use Models\User;
use Models\Notification;

/**
 * Company Dashboard Controller
 * 
 * Handles company-specific dashboard functionality
 */
class CompanyDashboardController extends DashboardController
{
    protected int $companyId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->companyId = (int) ($this->user['id'] ?? 0);
    }

    protected function setUserContext(): void
    {
        $this->userType = 'company';
        $this->viewPrefix = 'company';
        // Comment out role enforcement for development
        // $this->ensureRole('company');
    }

    /**
     * Company dashboard home
     */
    public function index(): Response
    {
        $profile = $this->getCompanyProfile();

        $data = [
            'pageTitle' => 'Company Dashboard',
            'availableWaste' => $this->getWasteOverview(),
            'highestBids' => $this->getHighestBids(),
            'recentBidActivity' => $this->getBiddingHistory(5),
            'companyProfile' => $profile,
        ];

        return $this->renderDashboard('dashboard', $data);
    }

    /**
     * Active bids
     */
    public function activeBids(): Response
    {
        $data = [
            'pageTitle' => 'Active Bids',
            'minimumBids' => config('data.minimum_bids', []),
            'availableWasteLots' => $this->getAvailableWasteLots(),
            'biddingHistory' => $this->getBiddingHistory(),
        ];

        return $this->renderDashboard('activeBids', $data);
    }

    /**
     * Schedule collections
     */
    public function purchases(): Response
    {
        $data = [
            'pageTitle' => 'My Purchases',
            'acceptedPurchases' => $this->getAcceptedPurchases(),
            'purchaseSummary' => $this->getPurchaseSummary(),
            'purchaseHistory' => $this->getPurchaseHistory(),
        ];

        return $this->renderDashboard('purchases', $data);
    }

    /**
     * Analytics and reporting
     */
    public function reports(): Response
    {
        $reportData = $this->getReportData();

        $data = [
            'pageTitle' => 'Analytics & Reports',
            'reportData' => $reportData,
        ];

        return $this->renderDashboard('reports', $data);
    }

    /**
     * Billing and invoices
     */
    public function billing(): Response
    {
        $data = [
            'pageTitle' => 'Billing & Invoices',
            'currentInvoices' => $this->getCurrentInvoices(),
            'paymentHistory' => $this->getPaymentHistory(),
            'billingSettings' => $this->getBillingSettings(),
            'costBreakdown' => $this->getCostBreakdown()
        ];

        return $this->renderDashboard('billing', $data);
    }

    /**
     * Sustainability reports
     */
    public function helpCenter(): Response
    {
        $data = [
            'pageTitle' => 'Help & Support',
        ];

        return $this->renderDashboard('helpCenter', $data);
    }

    /**
     * Company profile and settings
     */
    public function profile(): Response
    {
        $profile = $this->getCompanyProfile();

        $data = [
            'pageTitle' => 'Company Profile',
            'companyProfile' => $profile,
            'bankDetails' => $profile['bank_details'] ?? [],
            'verification' => $profile['verification'] ?? [],
            'wasteTypes' => $profile['waste_types'] ?? [],
        ];

        return $this->renderDashboard('profile', $data);
    }

    /**
     * Notifications page
     */
    public function notification(): Response
    {
        $data = [
            'pageTitle' => 'Notifications',
            'notifications' => $this->getNotifications(),
        ];

        return $this->renderDashboard('notification', $data);
    }

    protected function getNavigationItems(): array
    {
        return NavigationConfig::getNavigation($this->userType);
    }

    private function getWasteOverview(): array
    {
        $rounds = new BiddingRound();
        $overview = $rounds->availableWasteOverview();
        if (empty($overview)) {
            return [];
        }

        $iconMap = [
            'plastic' => 'fa-solid fa-bottle-water',
            'paper' => 'fa-solid fa-paper-plane',
            'metal' => 'fa-solid fa-box',
            'glass' => 'fa-solid fa-wine-bottle',
            'organic' => 'fa-solid fa-leaf',
            'cardboard' => 'fa-solid fa-clipboard',
        ];

        return array_map(function (array $row) use ($iconMap): array {
            $category = $row['category'] ?? 'Unknown';
            $key = strtolower($category);
            $quantity = $row['quantity'] ?? 0.0;
            $unit = $row['unit'] ?? 'kg';

            return [
                'title' => $category,
                'value' => number_format($quantity) . ' ' . $unit,
                'quantity' => $quantity,
                'unit' => $unit,
                'icon' => $iconMap[$key] ?? 'fa-solid fa-recycle',
            ];
        }, $overview);
    }
    private function getCompanyProfile(): array
    {
        $userModel = new User();
        $profile = $userModel->findById($this->companyId);
        if (!$profile) {
            return [];
        }

        $metadata = is_array($profile['metadata'] ?? null) ? $profile['metadata'] : [];

        $wasteTypes = $metadata['waste_types'] ?? $metadata['wasteTypes'] ?? [];
        if (is_string($wasteTypes)) {
            $decoded = json_decode($wasteTypes, true);
            $wasteTypes = is_array($decoded) ? $decoded : [$wasteTypes];
        } elseif (!is_array($wasteTypes)) {
            $wasteTypes = [];
        }

        $verification = $metadata['verification'] ?? [];
        if (is_string($verification)) {
            $decoded = json_decode($verification, true);
            $verification = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($verification)) {
            $verification = [];
        }

        $bankDetails = [
            'name' => $profile['bank_name'] ?? '',
            'account_number' => $profile['bank_account_number'] ?? '',
            'user' => $profile['bank_account_name'] ?? '',
            'branch' => $profile['bank_branch'] ?? '',
        ];

        $bankRaw = $metadata['bank_details'] ?? [];
        if (is_string($bankRaw)) {
            $decoded = json_decode($bankRaw, true);
            $bankRaw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($bankRaw)) {
            $bankRaw = [];
        }
        if (empty($bankDetails['name'])) {
            $bankDetails['name'] = $bankRaw['name'] ?? ($bankRaw['bank'] ?? '');
        }
        if (empty($bankDetails['account_number'])) {
            $bankDetails['account_number'] = $bankRaw['account_number'] ?? ($bankRaw['accountNumber'] ?? '');
        }
        if (empty($bankDetails['user'])) {
            $bankDetails['user'] = $bankRaw['user'] ?? ($bankRaw['accountName'] ?? '');
        }
        if (empty($bankDetails['branch'])) {
            $bankDetails['branch'] = $bankRaw['branch'] ?? '';
        }

        return [
            'id' => $profile['id'],
            'name' => $profile['name'] ?? '',
            'type' => $metadata['companyType'] ?? 'Waste Management',
            'reg_number' => $metadata['registrationNumber'] ?? ($metadata['regNumber'] ?? ''),
            'description' => $metadata['description'] ?? '',
            'email' => $profile['email'] ?? '',
            'phone' => $profile['phone'] ?? '',
            'website' => $metadata['website'] ?? '',
            'address' => $profile['address'] ?? ($metadata['address'] ?? ''),
            'profile_picture' => $profile['profileImagePath'] ?? '/assets/avatar.png',
            'waste_types' => $wasteTypes,
            'verification' => $verification,
            'bank_details' => $bankDetails,
            'metadata' => $metadata,
        ];
    }
    private function getHighestBids(): array
    {
        $rounds = new BiddingRound();
        $records = $rounds->highestBidsByCategory();
        if (empty($records)) {
            return [];
        }

        return array_map(function (array $row): array {
            $quantity = $row['quantity'] ?? 0.0;
            $unit = $row['unit'] ?? 'kg';
            $perUnit = $quantity > 0 ? $row['currentHighestBid'] / $quantity : $row['currentHighestBid'];

            return [
                'title' => $row['category'] ?? 'Unknown',
                'amount' => number_format($quantity) . ' ' . $unit,
                'bid' => 'Rs.' . number_format($perUnit, 2) . '/' . $unit,
                'status' => ucfirst($row['status'] ?? 'active'),
            ];
        }, $records);
    }

    private function getAvailableWasteLots(): array
    {
        $rounds = new BiddingRound();
        return $rounds->activeLots();
    }

    private function getBiddingHistory(int $limit = 10): array
    {
        $bidModel = new Bid();
        return $bidModel->companyHistory($this->companyId, $limit);
    }

    private function getAcceptedPurchases(): array
    {
        $rounds = new BiddingRound();
        $active = $rounds->companyRounds($this->companyId, 'completed', 10);

        return array_map(function (array $row): array {
            return [
                'id' => $row['lotId'],
                'type' => $row['category'],
                'amount' => number_format($row['quantity']) . ' ' . $row['unit'],
                'price' => format_rs($row['currentHighestBid']),
                'pickup_date' => $row['endTime'] ? date('Y-m-d', strtotime($row['endTime'])) : 'TBD',
                
            ];
        }, $active);
    }

    private function getPurchaseHistory(): array
    {
        $rounds = new BiddingRound();
        $completed = $rounds->companyRounds($this->companyId, 'completed', 20);

        return array_map(function (array $row): array {
            return [
                'id' => $row['lotId'],
                'type' => $row['category'],
                'amount' => number_format($row['quantity']) . ' ' . $row['unit'],
                'price' => format_rs($row['currentHighestBid']),
                'delivery_status' => ucfirst($row['status']),
                'date' => $row['endTime'] ? date('Y-m-d', strtotime($row['endTime'])) : 'N/A',
            ];
        }, $completed);
    }

    private function getPurchaseSummary(): array
    {
        $payment = new Payment();
        $totals = $payment->companyTotals($this->companyId);

        return [
            'total' => format_rs($totals['totalAmount'] ?? 0),
            'active_orders' => $totals['activeOrders'] ?? 0,
            'completed' => $totals['completedOrders'] ?? 0,
        ];
    }

    private function getReportData(): array
    {
        $bidModel = new Bid();
        $totals = $bidModel->totals($this->companyId);
        $monthlyCounts = $bidModel->monthlyCounts($this->companyId, 6);
        $categorySeries = $bidModel->monthlyCategoryAmounts($this->companyId, 6);

        $months = [];
        $totalPerMonth = [];
        $wonPerMonth = [];

        foreach ($monthlyCounts as $row) {
            $label = $this->formatMonthLabel($row['period']);
            $months[] = $label;
            $totalPerMonth[] = $row['total'];
            $wonPerMonth[] = $row['won'];
        }

        if (empty($months)) {
            $months = [];
            $totalPerMonth = [];
            $wonPerMonth = [];
        }

        $series = [];
        foreach ($categorySeries as $category => $values) {
            $series[$category] = [];
            foreach ($monthlyCounts as $row) {
                $period = $row['period'];
                $series[$category][] = isset($values[$period]) ? (float) $values[$period] : 0.0;
            }
        }

        $totalBids = $totals['total'] ?? 0;
        $successful = $totals['won'] ?? 0;
        $rate = $totalBids > 0 ? round(($successful / $totalBids) * 100, 2) : 0;

        return [
            'totalBids' => $totalBids,
            'successfulBids' => $successful,
            'successRate' => $rate,
            'months' => $months,
            'totalBidsPerMonth' => $totalPerMonth,
            'wonBidsPerMonth' => $wonPerMonth,
            'categorySeries' => $series,
        ];
    }

    private function getNotifications(): array
    {
        $notifications = new Notification();
        return $notifications->forCompany($this->companyId, 25);
    }

    private function formatMonthLabel(string $period): string
    {
        $dt = \DateTime::createFromFormat('Y-m', $period);
        return $dt ? $dt->format('M') : $period;
    }

    private function getCurrentInvoices(): array
    {
        $payment = new Payment();
        $invoices = $payment->companyPayments($this->companyId, 20);

        return array_values(array_filter($invoices, function (array $invoice): bool {
            return ($invoice['status'] ?? '') !== 'completed';
        }));
    }

    private function getPaymentHistory(): array
    {
        $payment = new Payment();
        return $payment->companyPayments($this->companyId, 20);
    }

    private function getBillingSettings(): array
    {
        $profile = $this->getCompanyProfile();
        $metadata = $profile['metadata'] ?? [];

        return [
            'preferred_method' => $metadata['preferred_payment_method'] ?? 'bank_transfer',
            'invoice_email' => $profile['email'] ?? '',
            'billing_contact' => $profile['phone'] ?? '',
        ];
    }

    private function getCostBreakdown(): array
    {
        $payments = $this->getPaymentHistory();
        $totals = ['payment' => 0.0, 'payout' => 0.0];

        foreach ($payments as $payment) {
            $type = $payment['type'] ?? 'payment';
            $amount = $payment['amount'] ?? 0.0;
            if (!isset($totals[$type])) {
                $totals[$type] = 0.0;
            }
            $totals[$type] += $amount;
        }

        $formatted = [];
        foreach ($totals as $type => $amount) {
            $formatted[$type] = format_rs($amount);
        }

        return $formatted;
    }
}
