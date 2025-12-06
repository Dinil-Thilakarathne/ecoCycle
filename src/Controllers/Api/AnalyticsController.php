<?php

namespace Controllers\Api;

use Controllers\BaseController;

class AnalyticsController extends BaseController
{
    public function dashboard()
    {
        return response()->json([
            'role' => 'admin',
            'total_users' => 120,
            'active_bids' => 34,
            'monthly_revenue' => 56000
        ]);
    }
}
