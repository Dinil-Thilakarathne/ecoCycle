<?php

namespace Controllers\Api;

use Core\Http\Response;
use Models\ReportsModel;

class ReportsController
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

        // Parameters from POST
        $type = $_POST['type'] ?? 'csv'; // 'csv' or 'pdf'
        $report = $_POST['report'] ?? 'waste-collection';

        $file = $model->exportReport($report, $type);

        return Response::json([
            'status' => 'success',
            'message' => 'Report exported successfully',
            'file' => $file
        ]);
    }
}
