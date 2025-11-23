<?php
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ReportsController;

Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);

Route::get('/reports/waste-collection', [ReportsController::class, 'wasteCollection']);
Route::get('/reports/bidding', [ReportsController::class, 'bidding']);
Route::get('/reports/revenue', [ReportsController::class, 'revenue']);

Route::post('/reports/export', [ReportsController::class, 'export']);
?>