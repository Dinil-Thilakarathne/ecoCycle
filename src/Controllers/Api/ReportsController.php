<?php

namespace Controllers\Api;

use Controllers\BaseController;

class ReportsController extends BaseController
{
    public function wasteCollection()
    {
        return response()->json([
            'total_collections' => 450,
            'pending_collections' => 20,
            'completed_collections' => 430
        ]);
    }

    public function bidding()
    {
        return response()->json([
            'total_bids' => 98,
            'successful_bids' => 65,
            'failed_bids' => 33
        ]);
    }

    public function revenue()
    {
        return response()->json([
            'monthly_revenue' => 78000,
            'yearly_revenue' => 850000
        ]);
    }

    public function export(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated'
        ]);
    }
}
