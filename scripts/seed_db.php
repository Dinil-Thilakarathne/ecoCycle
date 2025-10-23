<?php

require __DIR__ . '/../vendor/autoload.php';

use Core\Database;

// Seeder script: executes database/seeds/seed_dummy_data.sql using the app's DB config.
// - Idempotent where possible (INSERT IGNORE / ON DUPLICATE KEY used in SQL)
// - Respects SKIP_DB_SEED env var
// - Prints per-statement status and final row counts for key tables

$path = __DIR__ . '/../database/mysql/seeds/seed_dummy_data.sql';
if (!is_file($path)) {
    echo "ERROR: seed file not found: $path\n";
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    echo "ERROR: failed to read seed file\n";
    exit(1);
}

$skip = getenv('SKIP_DB_SEED');
if ($skip && in_array(strtolower($skip), ['1', 'true', 'yes'], true)) {
    echo "SKIP_DB_SEED set, skipping DB seeding.\n";
    exit(0);
}

try {
    $db = new Database();
    $pdo = $db->pdo();

    // Split statements on semicolon + newline like setup script does. Works for our seed file.
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

    echo "Applying seed (" . count($statements) . " statements) ...\n";

    $ok = 0;
    $err = 0;

    foreach ($statements as $i => $stmt) {
        if ($stmt === '')
            continue;
        try {
            $pdo->exec($stmt);
            echo sprintf("[%03d] OK\n", $i + 1);
            $ok++;
        } catch (\PDOException $e) {
            echo sprintf("[%03d] ERR: %s\n", $i + 1, $e->getMessage());
            $err++;
            // continue to next statement
        }
    }

    echo "Seed finished. OK={$ok}, ERR={$err}\n";

    // Print some helpful row counts to verify
    $checks = [
        'users',
        'roles',
        'vehicles',
        'waste_categories',
        'pickup_requests',
        'pickup_request_wastes',
        'payments',
        'bidding_rounds'
    ];

    foreach ($checks as $t) {
        try {
            $row = $db->fetch("SELECT COUNT(*) AS cnt FROM {$t}");
            $cnt = $row && isset($row['cnt']) ? $row['cnt'] : 'N/A';
        } catch (\Throwable $e) {
            $cnt = 'ERR';
        }
        echo sprintf("%s: %s\n", str_pad($t, 24), $cnt);
    }

    exit(0);

} catch (\Throwable $e) {
    echo "DB seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
