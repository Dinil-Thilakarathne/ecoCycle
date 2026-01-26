<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Database;
use Core\Http\Request;
use Core\Http\Response;
use Models\CollectorFeedback;
use Models\PickupRequest;

class AnalyticsController extends BaseController
{
    public function dashboard()
    {
        $model = new \Models\ReportsModel();
        $stats = $model->getDashboardStats();

        return response()->json(array_merge(
            ['role' => 'admin'], // Keep role if needed by frontend
            $stats
        ));
    }
}
