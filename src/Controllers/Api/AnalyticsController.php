<?php

namespace Controllers\Api;

use Controllers\BaseController;

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
