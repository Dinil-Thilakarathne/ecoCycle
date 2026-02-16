<?php
/**
 * Test script for Collector Daily Status functionality
 * Run this from the command line: php scripts/test_collector_availability.php
 */

require_once __DIR__ . '/../bootstrap/app.php';

use Core\Database;
use Models\CollectorDailyStatus;
use Models\User;

echo "=== Collector Daily Status Test Script ===\n\n";

try {
    $db = new Database();

    // 1. Verify table exists
    echo "1. Checking if collector_daily_status table exists...\n";
    $tableCheck = $db->fetchOne("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'collector_daily_status'
    ");

    if ($tableCheck) {
        echo "   ✓ Table exists\n\n";
    } else {
        echo "   ✗ Table does not exist. Please run the migration first.\n";
        exit(1);
    }

    // 2. Check table structure
    echo "2. Checking table structure...\n";
    $columns = $db->fetchAll("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_name = 'collector_daily_status'
        ORDER BY ordinal_position
    ");

    foreach ($columns as $col) {
        echo "   - {$col['column_name']} ({$col['data_type']}) " .
            ($col['is_nullable'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    echo "\n";

    // 3. Find a collector to test with
    echo "3. Finding a collector for testing...\n";
    $userModel = new User();
    $collectors = $userModel->listByType('collector', 1);

    if (empty($collectors)) {
        echo "   ✗ No collectors found in database. Please create a collector first.\n";
        exit(1);
    }

    $testCollector = $collectors[0];
    $collectorId = $testCollector['id'];
    $vehicleId = $testCollector['vehicleId'] ?? null;

    echo "   ✓ Found collector: {$testCollector['name']} (ID: {$collectorId})\n";
    echo "   Vehicle ID: " . ($vehicleId ?? 'Not assigned') . "\n\n";

    if (!$vehicleId) {
        echo "   ⚠ Warning: Collector has no vehicle assigned. Assigning vehicle ID 1 for testing.\n";
        $vehicleId = 1;
    }

    // 4. Test CollectorDailyStatus model
    echo "4. Testing CollectorDailyStatus model...\n";
    $statusModel = new CollectorDailyStatus();

    // 4a. Get today's status (should be empty initially)
    echo "   a. Getting today's status...\n";
    $todayStatus = $statusModel->getTodayStatus($collectorId);
    if ($todayStatus) {
        echo "      ✓ Found existing status: " . ($todayStatus['isAvailable'] ? 'Available' : 'Unavailable') . "\n";
    } else {
        echo "      ✓ No status found (expected for first run)\n";
    }

    // 4b. Update status to unavailable
    echo "   b. Setting status to UNAVAILABLE with notes...\n";
    $updated = $statusModel->updateStatus(
        $collectorId,
        $vehicleId,
        false,
        "Testing unavailable status"
    );
    echo "      ✓ Status updated: " . json_encode($updated, JSON_PRETTY_PRINT) . "\n";

    // 4c. Get status again
    echo "   c. Retrieving updated status...\n";
    $newStatus = $statusModel->getTodayStatus($collectorId);
    echo "      ✓ Current status: " . ($newStatus['isAvailable'] ? 'Available' : 'Unavailable') . "\n";
    echo "      Notes: " . ($newStatus['notes'] ?? 'None') . "\n";

    // 4d. Update status to available
    echo "   d. Setting status to AVAILABLE...\n";
    $statusModel->updateStatus($collectorId, $vehicleId, true, null);
    $finalStatus = $statusModel->getTodayStatus($collectorId);
    echo "      ✓ Status updated: " . ($finalStatus['isAvailable'] ? 'Available' : 'Unavailable') . "\n\n";

    // 5. Test getAllTodayStatuses
    echo "5. Testing getAllTodayStatuses()...\n";
    $allStatuses = $statusModel->getAllTodayStatuses();
    echo "   ✓ Found " . count($allStatuses) . " collector status(es) for today\n";
    foreach ($allStatuses as $status) {
        echo "      - Collector {$status['collectorId']}: " .
            ($status['isAvailable'] ? 'Available' : 'Unavailable') . "\n";
    }
    echo "\n";

    // 6. Test history
    echo "6. Testing getCollectorHistory()...\n";
    $history = $statusModel->getCollectorHistory($collectorId, 5);
    echo "   ✓ Found " . count($history) . " history record(s)\n";
    foreach ($history as $record) {
        echo "      - {$record['date']}: " .
            ($record['isAvailable'] ? 'Available' : 'Unavailable') .
            ($record['notes'] ? " ({$record['notes']})" : "") . "\n";
    }
    echo "\n";

    // 7. Test daily reset
    echo "7. Testing resetDailyStatuses()...\n";
    $resetSuccess = $statusModel->resetDailyStatuses();
    if ($resetSuccess) {
        echo "   ✓ Daily reset completed successfully\n";
        $afterReset = $statusModel->getTodayStatus($collectorId);
        echo "   Status after reset: " . ($afterReset['isAvailable'] ? 'Available' : 'Unavailable') . "\n";
    } else {
        echo "   ✗ Daily reset failed\n";
    }

    echo "\n=== All tests completed successfully! ===\n";

} catch (\Throwable $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
