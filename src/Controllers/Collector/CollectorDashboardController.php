<?php

namespace Controllers\Collector;

use Controllers\DashboardController;
use Core\Database;
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
     * Embeddable route preview for pickup navigation
     */
    public function routePreview(): \Core\Http\Response
    {
        $request = request();

        $data = [
            'pageTitle' => 'Route Preview',
            'originLat' => (string) $request->query('origin_lat', ''),
            'originLng' => (string) $request->query('origin_lng', ''),
            'destinationLat' => (string) $request->query('destination_lat', ''),
            'destinationLng' => (string) $request->query('destination_lng', ''),
            'destinationLabel' => (string) $request->query('destination_label', 'Pickup destination'),
        ];

        $content = $this->renderView('route_preview', $data);

        return response($content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);
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
        $request = request();
        $collectorId = (int) ($this->user['id'] ?? 0);

        if ((string) $request->query('export', '0') === '1' && $collectorId > 0) {
            $format = strtolower((string) $request->query('format', ''));
            $fromDate = (string) $request->query('from_date', '');
            $toDate = (string) $request->query('to_date', '');
            if ($format === 'waste') {
                return $this->exportWasteCollectionReport($collectorId, $fromDate, $toDate);
            }
            if ($format === 'salary') {
                return $this->exportSalaryTransactionReport($collectorId, $fromDate, $toDate);
            }
        }

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
            'materialBreakdown' => $this->getMaterialBreakdown(),
            'notifications' => $this->getNotifications(),
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
                fn(array $pickup) => $this->isPickupForToday($pickup)
                && in_array(strtolower((string) ($pickup['statusRaw'] ?? $pickup['status'] ?? '')), ['assigned', 'in_progress', 'completed'], true)
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
            return count(array_filter(
                $completedPickups,
                fn(array $pickup) => $this->isPickupForToday($pickup)
            ));
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
            $allPickups = $pickupRequest->listForCollector($collectorId);

            return array_values(array_filter(
                $allPickups,
                fn(array $pickup): bool => $this->isPickupForToday($pickup)
                && in_array(strtolower((string) ($pickup['statusRaw'] ?? $pickup['status'] ?? '')), ['pending', 'assigned', 'in_progress'], true)
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
                return array_values(array_filter(
                    $records,
                    fn(array $pickup): bool => $this->isPickupForToday($pickup)
                ));
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

        $scheduledAt = (string) ($pickup['scheduledAt'] ?? $pickup['scheduled_at'] ?? '');
        if ($scheduledAt === '') {
            return false;
        }

        return substr($scheduledAt, 0, 10) === $today;
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

    private function getNotifications(): array
    {
        $collectorId = (int) ($this->user['id'] ?? 0);
        if ($collectorId <= 0) {
            return [];
        }

        try {
            $record = $this->loadCollectorRecord();
            $createdAt = (string) ($record['created_at'] ?? ($this->user['created_at'] ?? ''));
            $notificationModel = new Notification();

            return $notificationModel->forUser($collectorId, 'collector', $createdAt, 100);
        } catch (\Throwable $e) {
            error_log('Collector notifications load failed: ' . $e->getMessage());
            return [];
        }
    }


    private function exportWasteCollectionReport(int $collectorId, ?string $fromDate = null, ?string $toDate = null): \Core\Http\Response
    {
        $db = new Database();
        [$periodStart, $periodEnd, $periodLabel, $periodKey] = $this->resolveReportDateRange($fromDate, $toDate);
        $rows = $db->fetchAll(
            "SELECT
                pr.customer_id,
                COALESCE(c.name, 'Unknown Customer') AS customer_name,
                COALESCE(pr.address, 'Not provided') AS address,
                COALESCE(wc.name, 'General') AS material_name,
                COALESCE(prw.weight, 0) AS material_weight,
                pr.created_at
             FROM pickup_requests pr
             LEFT JOIN users c ON c.id = pr.customer_id
             LEFT JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
             LEFT JOIN waste_categories wc ON wc.id = prw.waste_category_id
             WHERE pr.collector_id = ?
                             AND pr.status = 'completed'
                         ORDER BY COALESCE(pr.updated_at, pr.created_at) DESC, pr.customer_id ASC, material_name ASC",
            [$collectorId]
        ) ?: [];

        $rows = array_values(array_filter($rows, static function (array $row) use ($periodStart, $periodEnd): bool {
            $timestamp = strtotime((string) ($row['created_at'] ?? ''));
            if (!$timestamp) {
                return false;
            }

            $start = strtotime($periodStart);
            $end = strtotime($periodEnd);
            return $timestamp >= $start && $timestamp <= $end;
        }));

        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                'customer_id' => (string) ($row['customer_id'] ?? '-'),
                'customer_name' => (string) ($row['customer_name'] ?? 'Unknown Customer'),
                'address' => (string) ($row['address'] ?? 'Not provided'),
                'material_collected' => (string) ($row['material_name'] ?? 'General'),
                'weight' => (float) ($row['material_weight'] ?? 0),
            ];
        }

        $html = $this->generateWasteCollectionReportHtml($tableRows, $periodLabel);

        return \Core\Http\Response::pdf(
            'waste_collection_' . $periodKey . '_' . date('Ymd_His') . '.pdf',
            $html
        );
    }

    private function exportSalaryTransactionReport(int $collectorId, ?string $fromDate = null, ?string $toDate = null): \Core\Http\Response
    {
        $db = new Database();
        [$periodStart, $periodEnd, $periodLabel, $periodKey] = $this->resolveReportDateRange($fromDate, $toDate);
        $completedAtExpr = 'COALESCE(pr.updated_at, pr.created_at)';

        $queryWithWeight = "SELECT
                pr.id AS pickup_id,
                {$completedAtExpr} AS collected_at,
                COALESCE(wc.name, 'General') AS material_name,
                                COALESCE(prw.weight, 0) AS collected_weight,
                                COALESCE(wc.price_per_unit, 0) AS unit_amount,
                                COALESCE(prw.weight, 0) * COALESCE(wc.price_per_unit, 0) AS line_amount
            FROM pickup_requests pr
            INNER JOIN pickup_request_wastes prw ON prw.pickup_id = pr.id
            INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
            WHERE pr.collector_id = ?
              AND pr.status = 'completed'
            ORDER BY {$completedAtExpr} DESC, material_name ASC";

        $rows = $db->fetchAll($queryWithWeight, [$collectorId]) ?: [];

        $rows = array_values(array_filter($rows, static function (array $row) use ($periodStart, $periodEnd): bool {
            $timestamp = strtotime((string) ($row['collected_at'] ?? ''));
            if (!$timestamp) {
                return false;
            }

            $start = strtotime($periodStart);
            $end = strtotime($periodEnd);
            return $timestamp >= $start && $timestamp <= $end;
        }));

        $grouped = [];
        $sectionTotals = [];

        foreach ($rows as $row) {
            $collectedAt = (string) ($row['collected_at'] ?? '');
            $timestamp = strtotime($collectedAt);
            if (!$timestamp) {
                continue;
            }

            [$bucketKey, $bucketLabel] = $this->resolveSalaryReportBucket($timestamp, $periodKey);
            $materialName = (string) ($row['material_name'] ?? 'General');
            $materialKey = strtolower(trim($materialName));
            $weight = (float) ($row['collected_weight'] ?? 0.0);
            $unitAmount = (float) ($row['unit_amount'] ?? 0.0);
            $lineAmount = (float) ($row['line_amount'] ?? ($weight * $unitAmount));
            $pickupId = (string) ($row['pickup_id'] ?? '');

            if (!isset($grouped[$bucketKey])) {
                $grouped[$bucketKey] = [
                    'label' => $bucketLabel,
                    'materials' => [],
                    'total' => 0.0,
                    'pickupIds' => [],
                ];
                $sectionTotals[$bucketKey] = 0.0;
            }

            if (!isset($grouped[$bucketKey]['materials'][$materialKey])) {
                $grouped[$bucketKey]['materials'][$materialKey] = [
                    'material' => $materialName,
                    'weight' => 0.0,
                    'unitAmount' => $unitAmount,
                    'amount' => 0.0,
                ];
            }

            $grouped[$bucketKey]['materials'][$materialKey]['weight'] += $weight;
            $grouped[$bucketKey]['materials'][$materialKey]['amount'] += $lineAmount;
            $grouped[$bucketKey]['materials'][$materialKey]['unitAmount'] = $unitAmount;
            $grouped[$bucketKey]['total'] += $lineAmount;
            $sectionTotals[$bucketKey] += $lineAmount;

            if ($pickupId !== '') {
                $grouped[$bucketKey]['pickupIds'][$pickupId] = true;
            }
        }

        foreach ($grouped as $monthKey => $monthData) {
            $materials = array_values($monthData['materials']);
            usort($materials, static function (array $a, array $b): int {
                return strcasecmp((string) ($a['material'] ?? ''), (string) ($b['material'] ?? ''));
            });

            $grouped[$monthKey]['materials'] = $materials;
            $grouped[$monthKey]['pickupCount'] = count($monthData['pickupIds']);
            unset($grouped[$monthKey]['pickupIds']);
        }

        krsort($grouped);

        $html = $this->generateSalaryTransactionReportHtml($grouped, $sectionTotals, $periodLabel, $periodKey);

        return \Core\Http\Response::pdf(
            'salary_transactions_' . $periodKey . '_' . date('Ymd_His') . '.pdf',
            $html
        );
    }

    private function resolveSalaryReportBucket(int $timestamp, string $periodKey): array
    {
        switch ($periodKey) {
            case 'custom':
                return [
                    date('Y-m-d', $timestamp),
                    date('Y-m-d', $timestamp),
                ];
            case 'daily':
                return [
                    date('Y-m-d', $timestamp),
                    date('Y-m-d', $timestamp),
                ];
            case 'weekly':
                $start = (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone(date_default_timezone_get()))->modify('monday this week')->setTime(0, 0, 0);
                $end = $start->modify('+6 days')->setTime(23, 59, 59);
                return [
                    $start->format('o-\WW'),
                    'Week of ' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d'),
                ];
            case 'yearly':
                return [
                    date('Y-m', $timestamp),
                    date('F Y', $timestamp),
                ];
            case 'monthly':
            default:
                return [
                    date('Y-m', $timestamp),
                    date('F Y', $timestamp),
                ];
        }
    }

    private function salaryReportSectionTotalLabel(string $periodKey): string
    {
        if ($periodKey === 'yearly') {
            return 'Year Total';
        }
        if ($periodKey === 'custom') {
            return 'Date Total';
        }
        return 'Monthly Total';
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2);
    }

    private function resolveReportPeriodWindow(string $period): array
    {
        $period = strtolower(trim($period));
        $now = new \DateTimeImmutable('now');

        switch ($period) {
            case 'daily':
                $start = $now->setTime(0, 0, 0);
                $end = $now->setTime(23, 59, 59);
                $label = 'Daily (' . $start->format('Y-m-d') . ')';
                $key = 'daily';
                break;
            case 'weekly':
                $start = $now->modify('monday this week')->setTime(0, 0, 0);
                $end = $start->modify('+6 days')->setTime(23, 59, 59);
                $label = 'Weekly (' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d') . ')';
                $key = 'weekly';
                break;
            case 'yearly':
                $start = (new \DateTimeImmutable($now->format('Y-01-01 00:00:00')))->setTime(0, 0, 0);
                $end = $start->modify('last day of december this year')->setTime(23, 59, 59);
                $label = 'Yearly (' . $start->format('Y') . ')';
                $key = 'yearly';
                break;
            case 'monthly':
            default:
                $start = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);
                $end = $start->modify('last day of this month')->setTime(23, 59, 59);
                $label = 'Monthly (' . $start->format('F Y') . ')';
                $key = 'monthly';
                break;
        }

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            $label,
            $key,
        ];
    }

    private function resolveReportDateRange(?string $fromDate, ?string $toDate): array
    {
        $now = new \DateTimeImmutable('now');
        $defaultStart = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);
        $defaultEnd = $now->setTime(23, 59, 59);

        $start = $this->createDateFromInput($fromDate, $defaultStart);
        $end = $this->createDateFromInput($toDate, $defaultEnd);

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $label = 'From ' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d');
        $key = 'custom';

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            $label,
            $key,
        ];
    }

    private function createDateFromInput(?string $value, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date instanceof \DateTimeImmutable) {
            return $date->setTime((int) $fallback->format('H'), (int) $fallback->format('i'), (int) $fallback->format('s'));
        }

        return $fallback;
    }

    private function generateWasteCollectionReportHtml(array $tableRows, string $periodLabel): string
    {
        $date = date('Y-m-d H:i:s');
        $html = "<html><head>" . $this->collectorReportStyle() . "</head><body>"
            . "<h1>Waste Collection Report</h1><p class=\"report-meta\">Generated on: {$date}</p><p class=\"report-meta\">Period: {$periodLabel}</p>";

        if (empty($tableRows)) {
            $html .= '<div class="report-empty">No waste collection data available for this period.</div>';
        } else {
            $html .= '<table><thead><tr>'
                . '<th>Customer ID</th>'
                . '<th>Customer Name</th>'
                . '<th>Address</th>'
                . '<th>Material</th>'
                . '<th>Weight (kg)</th>'
                . '</tr></thead><tbody>';

            foreach ($tableRows as $row) {
                $html .= '<tr>'
                    . '<td>' . htmlspecialchars((string) ($row['customer_id'] ?? '-')) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($row['customer_name'] ?? 'Unknown Customer')) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($row['address'] ?? 'Not provided')) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($row['material_collected'] ?? 'General')) . '</td>'
                    . '<td>' . number_format((float) ($row['weight'] ?? 0), 2) . '</td>'
                    . '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '<p class="report-footer">This is an automatically generated report. Please ensure accuracy of data.</p></body></html>';

        return $html;
    }

    private function generateSalaryTransactionReportHtml(array $grouped, array $sectionTotals, string $periodLabel, string $periodKey): string
    {
        $date = date('Y-m-d H:i:s');
        $overallTotal = array_sum($sectionTotals);
        $sectionTotalLabel = $this->salaryReportSectionTotalLabel($periodKey);

        $html = "<html><head>" . $this->collectorReportStyle() . "</head><body>"
            . "<h1>Salary Report</h1><p class=\"report-meta\">Generated on: {$date}</p><p class=\"report-meta\">Period: {$periodLabel}</p><p class=\"report-meta\"><strong>Overall Summary:</strong> Rs. {$this->formatAmount($overallTotal)}</p>";

        if (empty($grouped)) {
            $html .= '<div class="report-empty">No completed material collections found for this period.</div>';
        } else {
            foreach ($grouped as $bucket => $data) {
                $bucketLabel = htmlspecialchars((string) ($data['label'] ?? 'Unknown Period'));
                $bucketTotal = number_format((float) ($data['total'] ?? 0), 2);
                $pickupCount = (int) ($data['pickupCount'] ?? 0);

                $html .= '<div class="report-section">'
                    . '<h3>' . $bucketLabel . ' (' . $pickupCount . ' completed pickups)</h3>'
                    . '<p class="report-submeta"><strong>' . htmlspecialchars($sectionTotalLabel) . ':</strong> Rs. ' . $bucketTotal . '</p>'
                    . '<table><thead><tr>'
                    . '<th>Material</th>'
                    . '<th>Total Weight</th>'
                    . '<th>Unit Amount (Rs)</th>'
                    . '<th>' . htmlspecialchars($sectionTotalLabel) . ' (Rs)</th>'
                    . '</tr></thead><tbody>';

                foreach (($data['materials'] ?? []) as $materialRow) {
                    $html .= '<tr>'
                        . '<td><strong>' . htmlspecialchars((string) ($materialRow['material'] ?? 'General')) . '</strong></td>'
                        . '<td>' . number_format((float) ($materialRow['weight'] ?? 0), 2) . ' kg</td>'
                        . '<td>' . number_format((float) ($materialRow['unitAmount'] ?? 0), 2) . '</td>'
                        . '<td><strong>' . number_format((float) ($materialRow['amount'] ?? 0), 2) . '</strong></td>'
                        . '</tr>';
                }

                $html .= '<tr class="monthly-total-row"><td colspan="3"><strong>' . htmlspecialchars($sectionTotalLabel) . '</strong></td><td><strong>' . $bucketTotal . '</strong></td></tr>';
                $html .= '</tbody></table></div>';
            }
        }

        $html .= '<p class="report-footer">This is an automatically generated report based on completed pickup material records.</p></body></html>';

        return $html;
    }

    private function collectorReportStyle(): string
    {
        return '<style>
                @page { margin: 28px 24px; }
                body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; font-size: 12px; line-height: 1.45; }
                h1 { color: #15803d; border-bottom: 2px solid #16a34a; padding-bottom: 10px; margin: 0 0 12px 0; font-size: 22px; }
                h3 { margin: 24px 0 10px; color: #374151; font-size: 15px; }
                .report-meta { margin: 0 0 6px; color: #4b5563; }
                .report-submeta { margin: 0 0 10px; color: #374151; }
                .report-footer { margin-top: 16px; color: #6b7280; font-style: italic; }
                .report-empty { padding: 14px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px; }
                .report-section { margin-bottom: 18px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; vertical-align: top; }
                th { background-color: #f3f4f6; font-weight: 700; }
                tr:nth-child(even) td { background-color: #f9fafb; }
                .monthly-total-row td { background-color: #ecfdf5; font-weight: 700; }
            </style>';
    }

    private function htmlReportResponse(string $filename, string $html): \Core\Http\Response
    {
        return new \Core\Http\Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function buildWasteReportLines(array $tableRows, string $periodLabel): array
    {
        $lines = [
            'Waste Collection Report',
            'Generated on: ' . date('Y-m-d H:i:s'),
            'Period: ' . $periodLabel,
            str_repeat('=', 95),
            str_pad('Customer ID', 12)
            . str_pad('Customer Name', 24)
            . str_pad('Address', 28)
            . str_pad('Material', 19)
            . 'Weight',
            str_repeat('-', 95),
        ];

        if (empty($tableRows)) {
            $lines[] = 'No waste collection data available.';
            return $lines;
        }

        foreach ($tableRows as $row) {
            $lines[] = str_pad(substr((string) ($row['customer_id'] ?? '-'), 0, 11), 12)
                . str_pad(substr((string) ($row['customer_name'] ?? 'Unknown Customer'), 0, 23), 24)
                . str_pad(substr((string) ($row['address'] ?? 'Not provided'), 0, 27), 28)
                . str_pad(substr((string) ($row['material_collected'] ?? 'General'), 0, 18), 19)
                . number_format((float) ($row['weight'] ?? 0), 2) . ' kg';
        }

        return $lines;
    }

    private function buildSalaryReportPdfLines(array $grouped, array $monthlyTotals, string $periodLabel, string $periodKey): array
    {
        $lines = [
            'Salary Transaction Report',
            'Generated on: ' . date('Y-m-d H:i:s'),
            'Period: ' . $periodLabel,
            'Overall Total: Rs. ' . number_format((float) array_sum($monthlyTotals), 2),
            str_repeat('=', 95),
        ];

        if (empty($grouped)) {
            $lines[] = 'No salary transactions found.';
            return $lines;
        }

        foreach ($grouped as $monthData) {
            $monthLabel = (string) ($monthData['label'] ?? 'Unknown Month');
            $monthTotal = number_format((float) ($monthData['total'] ?? 0), 2);
            $count = (int) ($monthData['pickupCount'] ?? 0);

            $lines[] = '';
            $lines[] = sprintf('%s (%d completed pickups) - Total Rs. %s', $monthLabel, $count, $monthTotal);
            $lines[] = str_pad('Material', 28)
                . str_pad('Weight', 16)
                . str_pad('Unit', 16)
                . 'Amount';
            $lines[] = str_repeat('-', 95);

            foreach (($monthData['materials'] ?? []) as $materialRow) {
                $material = substr((string) ($materialRow['material'] ?? 'General'), 0, 27);
                $weight = number_format((float) ($materialRow['weight'] ?? 0), 2) . ' kg';
                $unitAmount = 'Rs. ' . number_format((float) ($materialRow['unitAmount'] ?? 0), 2);
                $amount = 'Rs. ' . number_format((float) ($materialRow['amount'] ?? 0), 2);

                $lines[] = str_pad($material, 28)
                    . str_pad($weight, 16)
                    . str_pad($unitAmount, 16)
                    . $amount;
            }

            $lines[] = str_repeat('-', 95);
            $lines[] = 'Date Total: Rs. ' . $monthTotal;
        }

        return $lines;
    }

    private function pdfResponse(string $filename, array $lines): \Core\Http\Response
    {
        $pdfContent = $this->buildPlainPdf($lines);

        return new \Core\Http\Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function buildPlainPdf(array $lines): string
    {
        $maxLinesPerPage = 48;
        $pages = array_chunk($lines, $maxLinesPerPage);
        if (empty($pages)) {
            $pages = [['Empty report']];
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        $fontObjectId = 3;
        $objects[$fontObjectId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $nextId = 4;
        foreach ($pages as $pageLines) {
            $pageObjId = $nextId++;
            $contentObjId = $nextId++;

            $stream = "BT\n/F1 10 Tf\n";
            $y = 800;
            foreach ($pageLines as $line) {
                $safe = str_replace(['\\\\', '(', ')'], ['\\\\\\\\', '\\(', '\\)'], (string) $line);
                $stream .= sprintf("1 0 0 1 40 %d Tm (%s) Tj\n", $y, $safe);
                $y -= 16;
            }
            $stream .= "ET\n";

            $objects[$contentObjId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageObjId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents " . $contentObjId . " 0 R >>";
            $kids[] = $pageObjId . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Count ' . count($kids) . ' /Kids [' . implode(' ', $kids) . '] >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $objectContent) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $objectContent . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= '0 ' . (max(array_keys($objects)) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= max(array_keys($objects)); $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size ' . (max(array_keys($objects)) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
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
        if (!is_string($profileImagePath) || trim($profileImagePath) === '') {
            $profileImagePath = null;
        } else {
            $profileImagePath = trim($profileImagePath);
        }

        $profilePic = $profileImagePath;
        if ($profilePic === null) {
            foreach (['profile_pic', 'profileImage'] as $metadataKey) {
                $candidate = $metadata[$metadataKey] ?? null;
                if (is_string($candidate) && trim($candidate) !== '') {
                    $profilePic = trim($candidate);
                    break;
                }
            }
        }

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
            
            // Validate weight limit (max 100kg)
            if ($weight > 100) {
                http_response_code(400);
                echo "<div class='alert error'>Weight cannot exceed 100 kg. You entered: {$weight} kg</div>";
                exit;
            }
            
            // Validate decimal places (max 2)
            if (strpos($weight, '.') !== false) {
                $parts = explode('.', $weight);
                if (strlen($parts[1]) > 2) {
                    http_response_code(400);
                    echo "<div class='alert error'>Weight can only have up to 2 decimal places</div>";
                    exit;
                }
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

            // Validate weights if present
            if ($weights) {
                foreach ($weights as $weightEntry) {
                    $weight = floatval($weightEntry['weight'] ?? 0);
                    
                    // Check weight limit (max 100kg)
                    if ($weight > 100) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => "Weight cannot exceed 100 kg. Entered: {$weight} kg"
                        ]);
                        exit;
                    }
                    
                    // Check if weight is positive
                    if ($weight <= 0) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'All weights must be greater than 0'
                        ]);
                        exit;
                    }
                }
            }

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

    public function getLowRatingsCount(int $collectorId, int $maxRating = 2): int
    {
        $model = new CollectorFeedback();
        return count($model->getLowRatings($collectorId, $maxRating));
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