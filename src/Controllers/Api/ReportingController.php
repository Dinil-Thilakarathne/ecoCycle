<?php

namespace Controllers\Api;

use Core\Http\Response;
use Core\Http\Request;
use Models\ReportsModel;

class ReportingController
{
    // GET /api/reports/waste-collection
    public function wasteCollection()
    {
        $model = new ReportsModel();
        $data = $model->getWasteCollectionReport();

        return Response::json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // GET /api/reports/bidding
    public function bidding()
    {
        $model = new ReportsModel();
        $data = $model->getBiddingAnalytics();

        return Response::json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // GET /api/reports/revenue
    public function revenue()
    {
        $model = new ReportsModel();
        $data = $model->getRevenueReport();

        return Response::json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // POST /api/reports/export
    public function export()
    {
        $model = new ReportsModel();

        // In a real framework, use Request object. 
        // Assuming standard $_POST or Request helper usage
        $type = $_POST['type'] ?? 'csv';
        $report = $_POST['report'] ?? 'waste-collection';

        $fileUrl = $model->exportReport($report, $type);

        return Response::json([
            'status' => 'success',
            'message' => 'Report exported successfully',
            'file' => $fileUrl
        ]);
    }
}
