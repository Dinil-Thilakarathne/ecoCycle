<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
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
