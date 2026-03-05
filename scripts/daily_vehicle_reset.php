#!/usr/bin/env php
<?php

/**
 * Daily Vehicle Availability Reset Script
 * 
 * This script resets all collector availability statuses to "available" for a new day.
 * Should be run daily via cron job (recommended: 6:00 AM)
 * 
 * Cron configuration:
 * 0 6 * * * /usr/bin/php /path/to/ecoCycle/scripts/daily_vehicle_reset.php >> /var/log/ecocycle_reset.log 2>&1
 */

// Bootstrap the application
require_once __DIR__ . '/../bootstrap/app.php';

use Models\CollectorDailyStatus;

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting daily vehicle availability reset...\n";

    $statusModel = new CollectorDailyStatus();
    $success = $statusModel->resetDailyStatuses();

    if ($success) {
        echo "[" . date('Y-m-d H:i:s') . "] ✓ Daily availability reset completed successfully\n";
        exit(0);
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ✗ Daily availability reset failed\n";
        exit(1);
    }
} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ✗ Error during reset: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
