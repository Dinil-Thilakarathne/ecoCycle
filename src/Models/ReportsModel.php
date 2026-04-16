<?php

namespace Models;

class ReportsModel extends BaseModel
{
    /**
     * Get high-level stats for the admin dashboard
     */
    public function getDashboardStats(): array
    {
        // Total Users
        $totalUsers = $this->db->fetchColumn("SELECT COUNT(*) FROM users");

        // Active Bids (Rounds that are active)
        $activeBids = $this->db->fetchColumn("SELECT COUNT(*) FROM bidding_rounds WHERE status = 'active'");

        // Monthly Revenue (sum of completed 'payment' types in current month)
        // Adjust logic if 'payment' means inflow vs 'payout' means outflow.
        // Assuming 'payment' type is revenue from companies.
        $currentMonthStart = date('Y-m-01 00:00:00');
        $currentMonthEnd = date('Y-m-t 23:59:59');

        $monthlyRevenue = $this->db->fetchColumn(
            "SELECT SUM(amount) FROM payments 
             WHERE type = 'payment' 
             AND status = 'completed' 
             AND date >= ? AND date <= ?",
            [$currentMonthStart, $currentMonthEnd]
        ) ?: 0;

        return [
            'total_users' => (int) $totalUsers,
            'active_bids' => (int) $activeBids,
            'monthly_revenue' => (float) $monthlyRevenue
        ];
    }

    /**
     * Get waste collection stats (e.g. for a pie chart or list)
     */
    public function getWasteCollectionReport(): array
    {
        // Group by status
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count 
             FROM pickup_requests 
             GROUP BY status"
        );

        $stats = [
            'total_collections' => 0,
            'pending_collections' => 0,
            'completed_collections' => 0,
            'details' => []
        ];

        foreach ($rows as $row) {
            $count = (int) $row['count'];
            $status = $row['status'];

            $stats['total_collections'] += $count;

            if (in_array($status, ['pending', 'assigned', 'collected'])) {
                $stats['pending_collections'] += $count;
            }

            if ($status === 'completed') {
                $stats['completed_collections'] += $count;
            }

            $stats['details'][$status] = $count;
        }

        return $stats;
    }

    /**
     * Get bidding analytics
     */
    public function getBiddingAnalytics(): array
    {
        // Total rounds
        $totalRounds = $this->db->fetchColumn("SELECT COUNT(*) FROM bidding_rounds");

        // Successful (awarded/completed)
        $successful = $this->db->fetchColumn("SELECT COUNT(*) FROM bidding_rounds WHERE status IN ('awarded', 'completed')");

        // Failed (cancelled)
        $failed = $this->db->fetchColumn("SELECT COUNT(*) FROM bidding_rounds WHERE status = 'cancelled'");

        return [
            'total_bids' => (int) $totalRounds,
            'successful_bids' => (int) $successful,
            'failed_bids' => (int) $failed
        ];
    }

    /**
     * Get revenue report
     */
    public function getRevenueReport(): array
    {
        // Monthly Revenue
        $currentMonthStart = date('Y-m-01 00:00:00');
        $monthly = $this->db->fetchColumn(
            "SELECT SUM(amount) FROM payments WHERE type = 'payment' AND status = 'completed' AND date >= ?",
            [$currentMonthStart]
        ) ?: 0;

        // Yearly Revenue
        $currentYearStart = date('Y-01-01 00:00:00');
        $yearly = $this->db->fetchColumn(
            "SELECT SUM(amount) FROM payments WHERE type = 'payment' AND status = 'completed' AND date >= ?",
            [$currentYearStart]
        ) ?: 0;

        return [
            'monthly_revenue' => (float) $monthly,
            'yearly_revenue' => (float) $yearly
        ];
    }

    /**
     * Export report logic (Simulation/Placeholder for now as simulated file download)
     */
    public function exportReport(string $reportType, string $format): string
    {
        // logic to generate CSV/PDF content could go here
        // For now, returning a dummy URL or file path
        return "/exports/{$reportType}_" . date('Ymd_His') . ".{$format}";
    }

    /**
     * Get waste volume aggregated by category
     */
    public function getWasteVolumeByCategory(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT wc.name, SUM(br.quantity) AS total_quantity
             FROM waste_categories wc
             LEFT JOIN bidding_rounds br ON br.waste_category_id = wc.id
             GROUP BY wc.id, wc.name
             ORDER BY wc.name'
        );

        return array_map(function ($row) {
            return [
                'category' => $row['name'] ?? 'Unknown',
                'volume' => isset($row['total_quantity']) ? (float) $row['total_quantity'] : 0.0
            ];
        }, $rows ?: []);
    }

    /**
     * Get daily revenue and payouts for the last N days
     */
    public function getDailyFinancials(int $days = 30): array
    {
        $days = max(1, $days);
        $rows = $this->db->fetchAll(
            "SELECT CAST(\"date\" AS DATE) AS day, type, SUM(amount) AS total
             FROM payments
             WHERE \"date\" >= CURRENT_DATE - INTERVAL '{$days} days'
               AND status = 'completed'
             GROUP BY CAST(\"date\" AS DATE), type"
        );

        return $rows ?: [];
    }

    /**
     * Get daily pickup counts for the last N days
     */
    public function getPickupTrendsByDay(int $days = 30): array
    {
        $days = max(1, $days);
        $rows = $this->db->fetchAll(
            "SELECT CAST(created_at AS DATE) AS day, COUNT(*) AS total
             FROM pickup_requests
             WHERE created_at >= CURRENT_DATE - INTERVAL '{$days} days'
             GROUP BY CAST(created_at AS DATE)
             ORDER BY day ASC"
        );

        return array_map(function ($row) {
            return [
                'day' => $row['day'] ?? '',
                'total' => isset($row['total']) ? (int) $row['total'] : 0,
            ];
        }, $rows ?: []);
    }

    /**
     * Get top collectors ranked by completed pickup count
     */
    public function getTopCollectors(int $limit = 5): array
    {
        $limit = max(1, $limit);
        $rows = $this->db->fetchAll(
            "SELECT col.id, col.name,
                    COUNT(pr.id)        AS total_pickups,
                    SUM(pr.weight)      AS total_weight
             FROM pickup_requests pr
             JOIN users col ON col.id = pr.collector_id
             WHERE pr.status = 'completed'
             GROUP BY col.id, col.name
             ORDER BY total_pickups DESC
             LIMIT {$limit}"
        );

        return array_map(function ($row) {
            return [
                'id' => $row['id'] ?? null,
                'name' => $row['name'] ?? 'Unknown',
                'totalPickups' => isset($row['total_pickups']) ? (int) $row['total_pickups'] : 0,
                'totalWeight' => isset($row['total_weight']) ? (float) $row['total_weight'] : 0.0,
            ];
        }, $rows ?: []);
    }

    /**
     * Get pickup counts grouped by status
     */
    public function getPickupStatusBreakdown(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS count
             FROM pickup_requests
             GROUP BY status
             ORDER BY count DESC"
        );

        return array_map(function ($row) {
            return [
                'status' => $row['status'] ?? 'unknown',
                'count' => isset($row['count']) ? (int) $row['count'] : 0,
            ];
        }, $rows ?: []);
    }
}
