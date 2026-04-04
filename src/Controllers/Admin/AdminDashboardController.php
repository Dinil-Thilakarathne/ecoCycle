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
        $monthlyRevenue = $paymentModel->sumCompletedPaymentsForMonth((int) date('Y'), (int) date('m'));

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
        $wasteCategories = (new \Models\WasteCategory())->listAll();

        $data = [
            'pageTitle' => 'Admin Dashboard',
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'wasteCategories' => $wasteCategories,
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
        $timeSlots = ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00']; // TODO: need to get the value from the db 

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

        return $this->renderDashboard('users', $data + [
            'vehicles' => (new Vehicle())->listAll(),
        ]);
    }

    /**
     * Vehicle management page
     */
    public function vehicles(): Response
    {
        $vehicles = (new Vehicle())->listAll();
        $collectors = (new User())->listByType('collector');

        // Fetch today's availability statuses
        $statusModel = new \Models\CollectorDailyStatus();
        $todayStatuses = $statusModel->getAllTodayStatuses();

        // Create a map of collector ID to availability status
        $availabilityMap = [];
        foreach ($todayStatuses as $status) {
            $availabilityMap[$status['collectorId']] = $status;
        }

        $data = [
            'pageTitle' => 'Vehicle Management',
            'vehicles' => $vehicles,
            'collectors' => $collectors,
            'availabilityStatuses' => $todayStatuses,
            'availabilityMap' => $availabilityMap,
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

    public function bidding(): Response
    {
        $request = app('request');
        $searchQuery = $request->query('q', '');

        $biddingModel = new BiddingRound();

        // 1. Fetch Active Rounds (Always show all active)
        $activeRounds = $biddingModel->activeLots();

        // 2. Fetch History Rounds (Filtered / Paginated)
        $historyResult = $biddingModel->searchHistory(['search' => $searchQuery], 50);

        // 3. Stats
        $stats = $biddingModel->stats();

        $db = new Database();
        $categoryRows = $db->fetchAll('SELECT name FROM waste_categories ORDER BY name');
        $wasteCategories = array_map(static fn($row) => $row['name'] ?? '', $categoryRows ?: []);

        // Fetch minimum bids from database instead of hardcoded config
        $categoryPrices = $db->fetchAll('SELECT name, price_per_unit FROM waste_categories WHERE price_per_unit IS NOT NULL');
        $minimumBids = [];
        foreach ($categoryPrices as $cat) {
            $minimumBids[strtolower($cat['name'])] = (float) $cat['price_per_unit'];
        }

        $data = [
            'pageTitle' => 'Bidding Management',
            'activeRounds' => $activeRounds,
            'historyRounds' => $historyResult,
            'searchQuery' => $searchQuery,
            'biddingRounds' => $activeRounds, // Backwards compat if view uses this variable name for active
            'bidStats' => $stats,
            'wasteCategories' => $wasteCategories,
            'minimumBids' => $minimumBids,
        ];

        return $this->renderDashboard('biddingManagement', $data);
    }

    /**
     * Waste Categories & Pricing page
     */
    public function wasteCategories(): Response
    {
        $db = new Database();
        // Fetch categories with the new price_per_unit column
        // Note: Make sure the DB migration has been applied!
        try {
            $categories = $db->fetchAll("SELECT * FROM waste_categories ORDER BY name ASC");
        } catch (\Throwable $e) {
            // Fallback for when migration hasn't run yet
            $categories = $db->fetchAll("SELECT id, name, unit, color FROM waste_categories ORDER BY name ASC");
            foreach ($categories as &$cat) {
                $cat['price_per_unit'] = 0.00;
            }
        }

        $data = [
            'pageTitle' => 'Waste Pricing',
            'categories' => $categories,
        ];

        return $this->renderDashboard('waste_categories', $data);
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
        $request = app('request');
        $paymentModel = new Payment();
        $pickupModel = new PickupRequest();
        $reportsModel = new \Models\ReportsModel();

        // ── Financial Summary ───────────────────────────────────────────────
        $summary = $paymentModel->getSummary();
        $totalRevenue = $summary['total_payments'] ?? 0.0;
        $customerPayouts = $summary['total_payouts'] ?? 0.0;
        $netProfit = $totalRevenue - $customerPayouts;

        // ── Waste Volume by Category ────────────────────────────────────────
        $wasteData = $reportsModel->getWasteVolumeByCategory();
        $totalWaste = array_sum(array_column($wasteData, 'volume'));

        $wasteCategories = [];
        foreach ($wasteData as $item) {
            $wasteCategories[] = [
                'category' => $item['category'],
                'volume' => $item['volume'],
                'percentage' => $totalWaste > 0
                    ? round(($item['volume'] / $totalWaste) * 100, 1)
                    : 0,
            ];
        }

        $avgCollectionPerDay = $totalWaste > 0 ? (int) round($totalWaste / 30) : 0;

        // ── Pickup Counts ───────────────────────────────────────────────────
        $totalPickups = $pickupModel->countByStatuses(['pending', 'assigned', 'confirmed', 'in_progress', 'completed', 'cancelled']);
        $completedPickups = $pickupModel->countByStatuses(['completed']);

        // ── Revenue & Payouts – 30-day chart ───────────────────────────────
        $chartDays = [];
        for ($i = 29; $i >= 0; $i--) {
            $chartDays[] = date('Y-m-d', strtotime("-{$i} days"));
        }
        $revenueMap = array_fill_keys($chartDays, 0.0);
        $payoutsMap = array_fill_keys($chartDays, 0.0);

        try {
            $chartRows = $reportsModel->getDailyFinancials(30);
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
            // Charts will simply be empty on error
        }

        // ── Pickup Trends – 30-day chart ────────────────────────────────────
        $pickupTrendMap = array_fill_keys($chartDays, 0);
        try {
            foreach ($reportsModel->getPickupTrendsByDay(30) as $row) {
                $d = $row['day'] ?? null;
                if ($d && isset($pickupTrendMap[$d])) {
                    $pickupTrendMap[$d] = $row['total'];
                }
            }
        } catch (\Throwable $e) {
            // leave as zeros
        }

        // ── Pickup Status Breakdown ─────────────────────────────────────────
        $pickupStatusBreakdown = [];
        try {
            $pickupStatusBreakdown = $reportsModel->getPickupStatusBreakdown();
        } catch (\Throwable $e) {
            // leave empty
        }

        // ── Top Collectors ──────────────────────────────────────────────────
        $topCollectors = [];
        try {
            $topCollectors = $reportsModel->getTopCollectors(5);
        } catch (\Throwable $e) {
            // leave empty
        }

        $data = [
            'pageTitle' => 'Analytics',
            'analyticsSummary' => [
                'totalWasteCollected' => $totalWaste,
                'avgCollectionPerDay' => $avgCollectionPerDay,
                'totalRevenue' => $totalRevenue,
                'customerPayouts' => $customerPayouts,
                'netProfit' => $netProfit,
                'totalPickups' => $totalPickups,
                'completedPickups' => $completedPickups,
            ],
            'wasteCategories' => $wasteCategories,
            'pickupStatusBreakdown' => $pickupStatusBreakdown,
            'topCollectors' => $topCollectors,
            'chartData' => [
                'labels' => $chartDays,
                'shortLabels' => array_map(static fn($d) => date('d', strtotime($d)), $chartDays),
                'revenueSeries' => array_values($revenueMap),
                'payoutSeries' => array_values($payoutsMap),
                'pickupSeries' => array_values($pickupTrendMap),
            ],
        ];

        // Handle CSV Export
        if ($request->query('export') === '1' && $request->query('format') === 'csv') {
            $csvData = [];
            
            // Build Summary Section
            $csvData[] = ['Summary Metrics', 'Value'];
            $csvData[] = ['Total Waste Collected (kg)', $totalWaste];
            $csvData[] = ['Average Collection/Day (kg)', $avgCollectionPerDay];
            $csvData[] = ['Total Revenue (Rs)', number_format($totalRevenue, 2, '.', '')];
            $csvData[] = ['Customer Payouts (Rs)', number_format($customerPayouts, 2, '.', '')];
            $csvData[] = ['Net Profit (Rs)', number_format($netProfit, 2, '.', '')];
            $csvData[] = ['Total Pickups', $totalPickups];
            $csvData[] = ['Completed Pickups', $completedPickups];
            $csvData[] = [];
            
            // Build Waste Category Section
            $csvData[] = ['Waste Category Breakdown', 'Volume (kg)', 'Percentage'];
            foreach ($wasteCategories as $wc) {
                $csvData[] = [$wc['category'], $wc['volume'], $wc['percentage'] . '%'];
            }
            $csvData[] = [];
            
            // Build Pickup Status Section
            $csvData[] = ['Pickup Status Breakdown', 'Count'];
            foreach ($pickupStatusBreakdown as $ps) {
                $csvData[] = [ucfirst(str_replace('_', ' ', $ps['status'])), $ps['count']];
            }
            
            $filename = 'admin_analytics_' . date('Ymd_His') . '.csv';
            return \Core\Http\Response::csv($filename, [], $csvData);
        }

        return $this->renderDashboard('analytics', $data);
    }

    /**
     * Notifications page
     */
    public function notifications(): Response
    {
        $request = app('request');
        $page = (int) $request->query('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'type' => $request->query('type'),
            'status' => $request->query('status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => $request->query('q')
        ];

        // Remove empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        $notificationModel = new Notification();

        // Get paginated results with filters
        $result = $notificationModel->search($filters, $limit, $offset);

        // Also get recent notifications for the top card (unfiltered, small limit)
        $recent = $notificationModel->recent(5);
        $alerts = $notificationModel->systemAlerts();

        $data = [
            'pageTitle' => 'Notifications',
            'recentNotifications' => $recent,
            'allNotifications' => $result['notifications'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'last_page' => $result['last_page']
            ],
            'filters' => $filters,
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
