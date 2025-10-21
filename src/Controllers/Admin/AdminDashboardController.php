<?php

namespace Controllers\Admin;

use Controllers\DashboardController;
use EcoCycle\Core\Navigation\NavigationConfig;
use Core\Config;
use Core\Database;
use Core\Http\Response;
use Models\BiddingRound;
use Models\Notification;
use Models\Payment;
use Models\PickupRequest;
use Models\User;
use Models\Vehicle;

/**
 * Admin Dashboard Controller
 * 
 * Handles admin-specific dashboard functionality
 */
class AdminDashboardController extends DashboardController
{
    protected function setUserContext(): void
    {
        $this->userType = 'admin';
        $this->viewPrefix = 'admin';
        // Comment out role enforcement for development
        // $this->ensureRole('admin');
    }

    /**
     * Admin dashboard home
     */
    public function index(): Response
    {
        $userModel = new User();
        $pickupModel = new PickupRequest();
        $paymentModel = new Payment();
        $biddingModel = new BiddingRound();

        $customerCount = $userModel->countByType('customer');
        $companyCount = $userModel->countByType('company');
        $activeCollectors = $userModel->countByType('collector', 'active');
        $activePickups = $pickupModel->countByStatuses(['pending', 'assigned']);
        $bidStats = $biddingModel->stats();
        $monthlyRevenue = max(10000.00, $paymentModel->sumCompletedPaymentsForMonth((int) date('Y'), (int) date('m')));

        $stats = [
            [
                'title' => 'Total Customers',
                'value' => number_format($customerCount),
                'icon' => 'fa-solid fa-users',
                'change' => ''
            ],
            [
                'title' => 'Recycling Companies',
                'value' => number_format($companyCount),
                'icon' => 'fa-solid fa-building',
                'change' => ''
            ],
            [
                'title' => 'Active Collectors',
                'value' => number_format($activeCollectors),
                'icon' => 'fa-solid fa-truck',
                'change' => ''
            ],
            [
                'title' => 'Active Pickups',
                'value' => number_format($activePickups),
                'icon' => 'fa-solid fa-box',
                'change' => ''
            ],
            [
                'title' => 'Active Bids',
                'value' => number_format($bidStats['active'] ?? 0),
                'icon' => 'fa-solid fa-gavel',
                'change' => ''
            ],
            [
                'title' => 'Monthly Revenue',
                'value' => 'Rs ' . number_format($monthlyRevenue, 2),
                'icon' => 'fa-solid fa-chart-line',
                'change' => ''
            ],
        ];

        $recentActivity = $this->buildRecentActivity($pickupModel, $paymentModel, $biddingModel);

        $data = [
            'pageTitle' => 'Admin Dashboard',
            'stats' => $stats,
            'recentActivity' => $recentActivity,
        ];

        return $this->renderDashboard('dashboard', $data);
    }
    /**
     * Pickup request page
     */
    public function pickupRequest(): Response
    {
        $request = app('request');
        $selectedTimeSlot = $request->query('time_slot', 'all');

        $pickupModel = new PickupRequest();
        $allRequests = $pickupModel->listAll();
        $timeSlots = $pickupModel->listTimeSlots();

        $collectors = (new User())->listByType('collector', 200);

        $filtered = ($selectedTimeSlot === 'all')
            ? $allRequests
            : array_values(array_filter($allRequests, fn($row) => ($row['timeSlot'] ?? null) === $selectedTimeSlot));

        $data = [
            'pageTitle' => 'Pickup Requests',
            'pickupRequests' => $allRequests,
            'filteredPickupRequests' => $filtered,
            'timeSlots' => $timeSlots,
            'selectedTimeSlot' => $selectedTimeSlot,
            'collectors' => $collectors,
        ];

        return $this->renderDashboard('pickupRequest', $data);
    }

    /**
     * User management page
     */
    public function users(): Response
    {
        // Load users from database and provide to view instead of dummy config-based data
        $userModel = new User();
        $customers = $userModel->listByType('customer', 200);
        $companies = $userModel->listByType('company', 200);
        $collectors = $userModel->listByType('collector', 200);

        $data = [
            'pageTitle' => 'User Management',
            'customers' => $customers,
            'companies' => $companies,
            'collectors' => $collectors,
        ];

        return $this->renderDashboard('users', $data);
    }

    /**
     * Vehicle management page
     */
    public function vehicles(): Response
    {
        $vehicles = (new Vehicle())->listAll();

        $data = [
            'pageTitle' => 'Vehicle Management',
            'vehicles' => $vehicles,
        ];

        return $this->renderDashboard('vehicles', $data);
    }

    /**
     * Payment management page
     */
    public function payments(): Response
    {
        $paymentModel = new Payment();
        $payments = $paymentModel->listRecent(100);
        $summary = $paymentModel->getSummary();

        $data = [
            'pageTitle' => 'Payment Management',
            'payments' => $payments,
            'paymentSummary' => $summary,
        ];

        return $this->renderDashboard('payments', $data);
    }

    /**
     * System settings page
     */
    public function settings(): Response
    {
        $data = [
            'pageTitle' => 'System Settings',
        ];

        return $this->renderDashboard('settings', $data);
    }

    /**
     * Bidding management page
     */
    public function bidding(): Response
    {
        $biddingModel = new BiddingRound();
        $rounds = $biddingModel->listAll();
        $stats = $biddingModel->stats();

        $db = new Database();
        $categoryRows = $db->fetchAll('SELECT name FROM waste_categories ORDER BY name');
        $wasteCategories = array_map(static fn($row) => $row['name'] ?? '', $categoryRows ?: []);

        $minimumBids = Config::get('data.minimum_bids', []);
        $minimumBids = is_array($minimumBids) ? array_change_key_case($minimumBids, CASE_LOWER) : [];

        $data = [
            'pageTitle' => 'Bidding Management',
            'biddingRounds' => $rounds,
            'bidStats' => $stats,
            'wasteCategories' => $wasteCategories,
            'minimumBids' => $minimumBids,
        ];

        return $this->renderDashboard('biddingManagement', $data);
    }

    /**
     * Reports and analytics
     */
    public function reports(): Response
    {
        $data = [
            'pageTitle' => 'Reports & Analytics',
        ];

        return $this->renderDashboard('reports', $data);
    }

    /**
     * Content management
     */
    public function content(): Response
    {
        $data = [
            'pageTitle' => 'Content Management',
        ];

        return $this->renderDashboard('content', $data);
    }

    /**
     * Analytics page
     */
    public function analytics(): Response
    {
        $paymentModel = new Payment();
        $summary = $paymentModel->getSummary();

        $totalRevenue = $summary['total_payments'] ?? 0.0;
        $customerPayouts = $summary['total_payouts'] ?? 0.0;
        $netProfit = $totalRevenue - $customerPayouts;

        $db = new Database();
        try {
            $wasteRows = $db->fetchAll(
                'SELECT wc.name, SUM(br.quantity) AS total_quantity
                 FROM waste_categories wc
                 LEFT JOIN bidding_rounds br ON br.waste_category_id = wc.id
                 GROUP BY wc.id, wc.name
                 ORDER BY wc.name'
            );
        } catch (\Throwable $e) {
            $wasteRows = [];
        }

        $wasteCategories = [];
        $totalWaste = 0.0;
        foreach ($wasteRows as $row) {
            $quantity = isset($row['total_quantity']) ? (float) $row['total_quantity'] : 0.0;
            $totalWaste += $quantity;
            $wasteCategories[] = [
                'category' => $row['name'] ?? '',
                'volume' => $quantity,
            ];
        }

        if ($totalWaste > 0) {
            foreach ($wasteCategories as &$item) {
                $item['percentage'] = $totalWaste > 0 ? round(($item['volume'] / $totalWaste) * 100, 1) : 0;
            }
            unset($item);
        } else {
            $fallback = Config::get('data.wasteCategories', []);
            if (is_array($fallback) && !empty($fallback)) {
                $wasteCategories = array_map(static function ($item) {
                    return [
                        'category' => $item['category'] ?? '',
                        'volume' => isset($item['volume']) ? (float) $item['volume'] : 0,
                        'percentage' => isset($item['percentage']) ? (float) $item['percentage'] : 0,
                    ];
                }, $fallback);
                $totalWaste = array_sum(array_column($wasteCategories, 'volume'));
            }
        }

        $avgCollectionPerDay = $totalWaste > 0 ? (int) round($totalWaste / 30) : 0;

        $chartDays = [];
        for ($i = 29; $i >= 0; $i--) {
            $chartDays[] = date('Y-m-d', strtotime("-{$i} days"));
        }

        $revenueMap = array_fill_keys($chartDays, 0.0);
        $payoutsMap = array_fill_keys($chartDays, 0.0);

        try {
            $chartRows = $db->fetchAll(
                "SELECT DATE(`date`) AS day, `type`, SUM(amount) AS total
                 FROM payments
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY day, type"
            );
            foreach ($chartRows as $row) {
                $day = $row['day'] ?? null;
                if (!$day || !isset($revenueMap[$day])) {
                    continue;
                }
                $total = isset($row['total']) ? (float) $row['total'] : 0.0;
                if (($row['type'] ?? '') === 'payment') {
                    $revenueMap[$day] = $total;
                } elseif (($row['type'] ?? '') === 'payout') {
                    $payoutsMap[$day] = $total;
                }
            }
        } catch (\Throwable $e) {
            // Leave maps at zero if query fails
        }

        $data = [
            'pageTitle' => 'Analytics',
            'analyticsSummary' => [
                'totalWasteCollected' => $totalWaste,
                'avgCollectionPerDay' => $avgCollectionPerDay,
                'totalRevenue' => $totalRevenue,
                'customerPayouts' => $customerPayouts,
                'netProfit' => $netProfit,
            ],
            'wasteCategories' => $wasteCategories,
            'chartData' => [
                'labels' => $chartDays,
                'shortLabels' => array_map(static fn($d) => date('d', strtotime($d)), $chartDays),
                'revenueSeries' => array_values($revenueMap),
                'payoutSeries' => array_values($payoutsMap),
            ],
        ];

        return $this->renderDashboard('analytics', $data);
    }

    /**
     * Notifications page
     */
    public function notifications(): Response
    {
        $notificationModel = new Notification();
        $recent = $notificationModel->recent();
        $alerts = $notificationModel->systemAlerts();

        $data = [
            'pageTitle' => 'Notifications',
            'recentNotifications' => $recent,
            'systemAlerts' => $alerts,
        ];

        return $this->renderDashboard('notifications', $data);
    }

    private function buildRecentActivity(PickupRequest $pickupModel, Payment $paymentModel, BiddingRound $biddingModel): array
    {
        $records = [];

        foreach ($pickupModel->recent(4) as $pickup) {
            $records[] = [
                'action' => 'Pickup ' . ucfirst($pickup['status'] ?? 'updated'),
                'detail' => $pickup['customer_name'] ?? ($pickup['id'] ?? 'Pickup'),
                'timestamp' => $pickup['created_at'] ?? null,
            ];
        }

        foreach ($paymentModel->listRecent(4) as $payment) {
            $records[] = [
                'action' => ($payment['type'] ?? '') === 'payout' ? 'Payout processed' : 'Payment received',
                'detail' => $payment['recipient'] ?: ($payment['id'] ?? 'Payment'),
                'timestamp' => $payment['date'] ?? null,
            ];
        }

        foreach ($biddingModel->recent(4) as $round) {
            $records[] = [
                'action' => 'Bidding round ' . ($round['status'] ?? 'updated'),
                'detail' => ($round['waste_category_name'] ?? '') . ' ' . ($round['lot_id'] ?? $round['id'] ?? ''),
                'timestamp' => $round['end_time'] ?? null,
            ];
        }

        usort($records, static function (array $a, array $b): int {
            $tsA = isset($a['timestamp']) ? strtotime((string) $a['timestamp']) : 0;
            $tsB = isset($b['timestamp']) ? strtotime((string) $b['timestamp']) : 0;
            return $tsB <=> $tsA;
        });

        $records = array_slice($records, 0, 6);

        return array_map(function (array $record): array {
            return [
                'action' => trim($record['action']),
                'detail' => trim($record['detail']),
                'time' => $this->formatActivityTime($record['timestamp'] ?? null),
            ];
        }, $records);
    }

    private function formatActivityTime(?string $timestamp): string
    {
        if (!$timestamp) {
            return '—';
        }

        $time = strtotime($timestamp);
        if (!$time) {
            return '—';
        }

        $diff = time() - $time;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $minutes = max(1, (int) floor($diff / 60));
            return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
        }
        if ($diff < 86400) {
            $hours = max(1, (int) floor($diff / 3600));
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        return date('M j, H:i', $time);
    }

    protected function getNavigationItems(): array
    {
        return NavigationConfig::getNavigation($this->userType);
    }
}
