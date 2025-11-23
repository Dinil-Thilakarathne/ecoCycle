<?php

namespace Controllers\Api;

use Core\Http\Response;
use Models\AnalyticsModel;

class AnalyticsController
{
    // GET /api/analytics/dashboard
    public function dashboard()
    {
        $model = new AnalyticsModel();
        $data = $model->getDashboardAnalytics();

        return Response::json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
