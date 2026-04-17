<?php

namespace Controllers\Api;

use Core\Database;
use Core\Http\Request;
use Core\Http\Response;
use Controllers\BaseController;

class CollectorStatsController extends BaseController
{
    private Database $db;
    private \Models\Notification $notificationModel;
    private \Models\User $userModel;

    public function __construct()
    {
        $this->db = new Database();
        $this->notificationModel = new \Models\Notification();
        $this->userModel = new \Models\User();
    }

    /**
     * GET /api/collector/stats
     * Returns today's counts and total weight for pickups scheduled for the logged-in collector
     */
    public function stats(Request $request): Response
    {
        try {
            // Get the logged-in collector's ID from session
            $collectorId = session()->get('user_id');
            if (!$collectorId) {
                return $this->json(['status' => 'error', 'message' => 'Collector not authenticated'], 401);
            }

            $todayStart = (new \DateTimeImmutable('today'))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $todayEnd = (new \DateTimeImmutable('today'))->setTime(23, 59, 59)->format('Y-m-d H:i:s');

            // Today's scheduled pickups that are already in active/completed workflow states
            $tasksSql = "SELECT COUNT(*) AS count FROM pickup_requests 
                         WHERE collector_id = ? 
                         AND status IN ('assigned', 'in_progress', 'completed')
                         AND scheduled_at >= ?
                         AND scheduled_at <= ?";
            $tasks = $this->db->fetch($tasksSql, [$collectorId, $todayStart, $todayEnd]);

            // Today's completed pickups (by scheduled date)
            $completedSql = "SELECT COUNT(*) AS count FROM pickup_requests 
                             WHERE collector_id = ? 
                             AND status = 'completed'
                             AND scheduled_at >= ?
                             AND scheduled_at <= ?";
            $completed = $this->db->fetch($completedSql, [$collectorId, $todayStart, $todayEnd]);

            // Today's pending tasks (includes unassigned pending records plus active states)
            $pendingSql = "SELECT COUNT(*) AS count FROM pickup_requests 
                           WHERE collector_id = ? 
                           AND status IN ('pending', 'assigned', 'in_progress')
                           AND scheduled_at >= ?
                           AND scheduled_at <= ?";
            $pending = $this->db->fetch($pendingSql, [$collectorId, $todayStart, $todayEnd]);

            // Today's collected weight from completed pickups scheduled today
            $weightSql = "SELECT COALESCE(SUM(CAST(weight AS DECIMAL(12,2))), 0) AS total_weight 
                          FROM pickup_requests 
                          WHERE collector_id = ? 
                          AND status = 'completed'
                          AND scheduled_at >= ?
                          AND scheduled_at <= ?";
            $weight = $this->db->fetch($weightSql, [$collectorId, $todayStart, $todayEnd]);

            return $this->json([
                'status' => 'success',
                'data' => [
                    'todays_tasks' => (int) ($tasks['count'] ?? 0),
                    'completed' => (int) ($completed['count'] ?? 0),
                    'pending' => (int) ($pending['count'] ?? 0),
                    'total_weight' => (float) ($weight['total_weight'] ?? 0),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to fetch collector stats', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/collector/material-prices
     * Returns current price_per_unit for all waste categories
     */
    public function materialPrices(Request $request): Response
    {
        try {
            $prices = $this->db->fetchAll("SELECT id, name, price_per_unit FROM waste_categories WHERE price_per_unit IS NOT NULL ORDER BY name ASC");

            return $this->json([
                'status' => 'success',
                'data' => $prices ?: [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to fetch material prices', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/collector/material-collection
     * Returns this week's material collection summary grouped by waste category
     */
    public function materialCollection(Request $request): Response
    {
        try {
            $collectorId = session()->get('user_id');
            if (!$collectorId) {
                return $this->json(['status' => 'error', 'message' => 'Collector not authenticated'], 401);
            }

            $period = strtolower((string) $request->query('period', 'weekly'));
            $months = (int) $request->query('months', 6);
            $month = (string) $request->query('month', '');

            if ($period === 'monthly') {
                return $this->json($this->buildMonthlyMaterialCollectionResponse((int) $collectorId, $months));
            }

            if ($period === 'monthly-by-material') {
                return $this->json($this->buildMonthlyMaterialByCategoryResponse((int) $collectorId, $month));
            }

            if ($period === 'yearly-by-material') {
                $year = (string) $request->query('year', '');
                return $this->json($this->buildYearlyMaterialByCategoryResponse((int) $collectorId, $year));
            }

            // Current week window: Monday 00:00:00 to Sunday 23:59:59
            $now = new \DateTimeImmutable('now');
            $weekStartDate = $now->modify('monday this week')->setTime(0, 0, 0);
            $weekEndDate = $weekStartDate->modify('+6 days')->setTime(23, 59, 59);

            $weekStart = $weekStartDate->format('Y-m-d H:i:s');
            $weekEnd = $weekEndDate->format('Y-m-d H:i:s');

            $buildSql = static function (string $weightExpr): string {
                return "
                    SELECT 
                        wc.id,
                        wc.name,
                        wc.price_per_unit,
                        wc.color,
                        COALESCE(SUM({$weightExpr}), 0) AS total_weight,
                        COALESCE(SUM({$weightExpr} * COALESCE(wc.price_per_unit, 0)), 0) AS total_price
                    FROM waste_categories wc
                    LEFT JOIN pickup_request_wastes prw ON wc.id = prw.waste_category_id
                    LEFT JOIN pickup_requests pr ON prw.pickup_id = pr.id
                    WHERE pr.collector_id = ?
                        AND COALESCE(pr.updated_at, pr.created_at) >= ?
                        AND COALESCE(pr.updated_at, pr.created_at) <= ?
                        AND pr.status = 'completed'
                    GROUP BY wc.id, wc.name, wc.price_per_unit, wc.color
                    HAVING COALESCE(SUM({$weightExpr}), 0) > 0
                    ORDER BY total_weight DESC
                ";
            };

            $sqlWithWeight = $buildSql('COALESCE(prw.weight, prw.quantity, 0)');

            try {
                $materials = $this->db->fetchAll($sqlWithWeight, [$collectorId, $weekStart, $weekEnd]);
            } catch (\Throwable $queryError) {
                // Backward compatibility: some databases may not have pickup_request_wastes.weight yet.
                $sqlWithQuantity = $buildSql('COALESCE(prw.quantity, 0)');
                $materials = $this->db->fetchAll($sqlWithQuantity, [$collectorId, $weekStart, $weekEnd]);
            }

            $formattedMaterials = array_map(function($m) {
                return [
                    'id' => (int) $m['id'],
                    'name' => $m['name'],
                    'weight' => (float) $m['total_weight'],
                    'price' => (float) $m['total_price'],
                    'price_per_unit' => (float) ($m['price_per_unit'] ?? 0),
                    'color' => $m['color'] ?? $this->getColorForMaterial($m['name'])
                ];
            }, $materials ?: []);

            return $this->json([
                'status' => 'success',
                'data' => $formattedMaterials,
                'week_start' => $weekStartDate->format('Y-m-d'),
                'week_end' => $weekEndDate->format('Y-m-d'),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to fetch material collection', 'details' => $e->getMessage()], 500);
        }
    }

    private function buildMonthlyMaterialCollectionResponse(int $collectorId, int $months): array
    {
        $months = max(1, min(12, $months));

        $currentMonthStart = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);
        $startMonth = $currentMonthStart->modify('-' . ($months - 1) . ' months');
        $endMonth = $currentMonthStart->modify('last day of this month')->setTime(23, 59, 59);

        $monthBuckets = [];
        for ($i = 0; $i < $months; $i++) {
            $monthDate = $startMonth->modify('+' . $i . ' months');
            $monthKey = $monthDate->format('Y-m');
            $monthBuckets[$monthKey] = [
                'month_key' => $monthKey,
                'month_label' => $monthDate->format('M Y'),
                'weight' => 0.0,
                'salary' => 0.0,
            ];
        }

        $buildSql = static function (string $wasteTable, string $weightExpr): string {
            return "
                SELECT
                    COALESCE(pr.updated_at, pr.created_at) AS collected_at,
                    COALESCE(prw.weight, 0) AS collected_weight,
                    COALESCE(wc.price_per_unit, 0) AS unit_amount,
                    COALESCE(prw.weight, 0) * COALESCE(wc.price_per_unit, 0) AS line_amount
                FROM {$wasteTable} prw
                INNER JOIN pickup_requests pr ON prw.pickup_id = pr.id
                INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
                WHERE pr.collector_id = ?
                    AND pr.status = 'completed'
                    AND COALESCE(pr.updated_at, pr.created_at) >= ?
                    AND COALESCE(pr.updated_at, pr.created_at) <= ?
            ";
        };

        $sqlPrimary = $buildSql('pickup_request_wastes', 'prw.weight');
        $rows = $this->db->fetchAll($sqlPrimary, [$collectorId, $startMonth->format('Y-m-d H:i:s'), $endMonth->format('Y-m-d H:i:s')]);

        foreach ($rows ?: [] as $row) {
            $timestamp = strtotime((string) ($row['collected_at'] ?? ''));
            if (!$timestamp) {
                continue;
            }

            $monthKey = date('Y-m', $timestamp);
            if (!array_key_exists($monthKey, $monthBuckets)) {
                continue;
            }

            $weight = (float) ($row['collected_weight'] ?? 0);
            $unitAmount = (float) ($row['unit_amount'] ?? 0);
            $lineAmount = (float) ($row['line_amount'] ?? ($weight * $unitAmount));

            $monthBuckets[$monthKey]['weight'] += $weight;
            $monthBuckets[$monthKey]['salary'] += $lineAmount;
        }

        $formattedData = array_map(static function (array $bucket): array {
            return [
                'month_key' => $bucket['month_key'],
                'month_label' => $bucket['month_label'],
                'weight' => round((float) $bucket['weight'], 2),
                'salary' => round((float) $bucket['salary'], 2),
            ];
        }, array_values($monthBuckets));

        return [
            'status' => 'success',
            'period' => 'monthly',
            'months' => $months,
            'data' => $formattedData,
            'month_start' => $startMonth->format('Y-m-d'),
            'month_end' => $endMonth->format('Y-m-d'),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    private function buildMonthlyMaterialByCategoryResponse(int $collectorId, string $month): array
    {
        $isValidMonth = preg_match('/^\d{4}-\d{2}$/', $month) === 1;
        $monthKey = $isValidMonth ? $month : date('Y-m');

        $monthStart = (new \DateTimeImmutable($monthKey . '-01 00:00:00'))->setTime(0, 0, 0);
        $monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);

        $buildSql = static function (string $wasteTable): string {
            return "
                SELECT
                    wc.id,
                    wc.name,
                    wc.color,
                    wc.unit,
                    COALESCE(SUM(prw.weight), 0) AS total_weight
                FROM {$wasteTable} prw
                INNER JOIN pickup_requests pr ON prw.pickup_id = pr.id
                INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
                WHERE pr.collector_id = ?
                    AND pr.status = 'completed'
                    AND COALESCE(pr.updated_at, pr.created_at) >= ?
                    AND COALESCE(pr.updated_at, pr.created_at) <= ?
                GROUP BY wc.id, wc.name, wc.color, wc.unit
                HAVING COALESCE(SUM(prw.weight), 0) > 0
                ORDER BY total_weight DESC, wc.name ASC
            ";
        };

        $sqlPrimary = $buildSql('pickup_request_wastes');
        try {
            $materials = $this->db->fetchAll($sqlPrimary, [$collectorId, $monthStart->format('Y-m-d H:i:s'), $monthEnd->format('Y-m-d H:i:s')]);
        } catch (\Throwable $queryError) {
            $sqlFallback = $buildSql('pickup_request_wastes');
            $materials = $this->db->fetchAll($sqlFallback, [$collectorId, $monthStart->format('Y-m-d H:i:s'), $monthEnd->format('Y-m-d H:i:s')]);
        }

        $formattedData = array_map(function (array $material): array {
            return [
                'id' => (int) ($material['id'] ?? 0),
                'name' => (string) ($material['name'] ?? 'Unknown'),
                'weight' => round((float) ($material['total_weight'] ?? 0), 2),
                'unit' => (string) ($material['unit'] ?? 'kg'),
                'color' => (string) (($material['color'] ?? '') ?: $this->getColorForMaterial((string) ($material['name'] ?? ''))),
            ];
        }, $materials ?: []);

        return [
            'status' => 'success',
            'period' => 'monthly-by-material',
            'selected_month' => $monthKey,
            'selected_month_label' => $monthStart->format('M Y'),
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end' => $monthEnd->format('Y-m-d'),
            'data' => $formattedData,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    private function buildYearlyMaterialByCategoryResponse(int $collectorId, string $year): array
    {
        $isValidYear = preg_match('/^\d{4}$/', $year) === 1;
        $yearValue = $isValidYear ? (int) $year : (int) date('Y');

        $yearStart = (new \DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $yearValue)))->setTime(0, 0, 0);
        $yearEnd = $yearStart->modify('last day of december this year')->setTime(23, 59, 59);

        $sql = "
            SELECT
                wc.id,
                wc.name,
                wc.color,
                wc.unit,
                COALESCE(SUM(prw.weight), 0) AS total_weight
            FROM pickup_request_wastes prw
            INNER JOIN pickup_requests pr ON prw.pickup_id = pr.id
            INNER JOIN waste_categories wc ON wc.id = prw.waste_category_id
            WHERE pr.collector_id = ?
                AND pr.status = 'completed'
                AND COALESCE(pr.updated_at, pr.created_at) >= ?
                AND COALESCE(pr.updated_at, pr.created_at) <= ?
            GROUP BY wc.id, wc.name, wc.color, wc.unit
            HAVING COALESCE(SUM(prw.weight), 0) > 0
            ORDER BY total_weight DESC, wc.name ASC
        ";

        $materials = $this->db->fetchAll($sql, [
            $collectorId,
            $yearStart->format('Y-m-d H:i:s'),
            $yearEnd->format('Y-m-d H:i:s')
        ]);

        $formattedData = array_map(function (array $material): array {
            return [
                'id' => (int) ($material['id'] ?? 0),
                'name' => (string) ($material['name'] ?? 'Unknown'),
                'weight' => round((float) ($material['total_weight'] ?? 0), 2),
                'unit' => (string) ($material['unit'] ?? 'kg'),
                'color' => (string) (($material['color'] ?? '') ?: $this->getColorForMaterial((string) ($material['name'] ?? ''))),
            ];
        }, $materials ?: []);

        return [
            'status' => 'success',
            'period' => 'yearly-by-material',
            'selected_year' => (string) $yearValue,
            'year_start' => $yearStart->format('Y-m-d'),
            'year_end' => $yearEnd->format('Y-m-d'),
            'data' => $formattedData,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    private function getColorForMaterial(string $name): string
    {
        $colors = [
            'plastic' => '#0000ff',
            'glass' => '#ff0000',
            'metal' => '#ffa500',
            'paper' => '#008000',
            'cardboard' => '#fb923c',
            'organic' => '#8b5a2b',
        ];
        return $colors[strtolower($name)] ?? '#6b7280';
    }

    /**
     * GET /api/collector/notifications
     * Returns real-time notifications for the logged-in collector
     */
    public function notifications(Request $request): Response
    {
        try {
            $collectorId = session()->get('user_id');
            if (!$collectorId) {
                return $this->json(['status' => 'error', 'message' => 'Collector not authenticated'], 401);
            }

            // Fetch user profile to get created_at date
            $userProfile = $this->userModel->findById($collectorId);
            $createdAt = $userProfile['created_at'] ?? '2000-01-01 00:00:00';

            // Fetch notifications for this collector using the model
            $notifications = $this->notificationModel->forUser($collectorId, 'collector', $createdAt, 100);

            return $this->json([
                'status' => 'success',
                'data' => $notifications ?: [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to fetch notifications', 'details' => $e->getMessage()], 500);
        }
    }
}
