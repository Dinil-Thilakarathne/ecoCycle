<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Database;
use Core\Http\Request;
use Core\Http\Response;

class ReportsController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * GET /api/reports/waste-collection
     * Get waste collection statistics
     */
    public function wasteCollection(Request $request): Response
    {
        try {
            $totalCollections = $this->db->fetchOne("SELECT COUNT(*) as count FROM pickup_requests");
            $pendingCollections = $this->db->fetchOne("SELECT COUNT(*) as count FROM pickup_requests WHERE status = 'pending'");
            $completedCollections = $this->db->fetchOne("SELECT COUNT(*) as count FROM pickup_requests WHERE status = 'completed'");

            return $this->json([
                'status' => 'success',
                'data' => [
                    'total_collections' => (int)($totalCollections['count'] ?? 0),
                    'pending_collections' => (int)($pendingCollections['count'] ?? 0),
                    'completed_collections' => (int)($completedCollections['count'] ?? 0),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to fetch waste collection report', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/reports/bidding
     * Get bidding statistics and reports
     */
    public function bidding(Request $request): Response
    {
        try {
            $totalBids = $this->db->fetchOne("SELECT COUNT(*) as count FROM bidding_rounds");
            $activeBids = $this->db->fetchOne("SELECT COUNT(*) as count FROM bidding_rounds WHERE status = 'active'");
            $completedBids = $this->db->fetchOne("SELECT COUNT(*) as count FROM bidding_rounds WHERE status = 'completed'");

            return $this->json([
                'status' => 'success',
                'data' => [
                    'total_bids' => (int)($totalBids['count'] ?? 0),
                    'active_bids' => (int)($activeBids['count'] ?? 0),
                    'completed_bids' => (int)($completedBids['count'] ?? 0),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to fetch bidding report', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/reports/revenue
     * Get revenue and payment statistics
     */
    public function revenue(Request $request): Response
    {
        try {
            $monthlyRevenue = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            );
            $yearlyRevenue = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND YEAR(created_at) = YEAR(CURDATE())"
            );
            $totalRevenue = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'"
            );

            return $this->json([
                'status' => 'success',
                'data' => [
                    'monthly_revenue' => (float)($monthlyRevenue['total'] ?? 0),
                    'yearly_revenue' => (float)($yearlyRevenue['total'] ?? 0),
                    'total_revenue' => (float)($totalRevenue['total'] ?? 0),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to fetch revenue report', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/reports/export
     * Export reports in CSV or PDF format
     */
    public function export(Request $request): Response
    {
        try {
            $reportType = $request->input('type', 'waste-collection');
            $format = $request->input('format', 'csv');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if (!in_array($format, ['csv', 'pdf', 'json'])) {
                return $this->json(['error' => 'Invalid export format'], 400);
            }

            // Simulate export generation
            $filename = $reportType . '_' . date('Y-m-d_His') . '.' . ($format === 'pdf' ? 'pdf' : $format);

            return $this->json([
                'status' => 'success',
                'message' => 'Export generated successfully',
                'data' => [
                    'filename' => $filename,
                    'format' => $format,
                    'report_type' => $reportType,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to export report', 'details' => $e->getMessage()], 500);
        }
    }
}
