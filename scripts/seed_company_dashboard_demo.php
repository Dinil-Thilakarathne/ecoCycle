<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Core\Database;

// Seeds the company dashboard demo data defined in database/seeds/seed_company_dashboard_demo.sql.
// Mirrors scripts/seed_db.php but targets the demo dataset so you can preview UI widgets quickly.

$seedFile = __DIR__ . '/../database/mysql/seeds/seed_company_dashboard_demo.sql';
if (!is_file($seedFile)) {
    fwrite(STDERR, "Seed file not found: {$seedFile}\n");
    exit(1);
}

$sql = file_get_contents($seedFile);
if ($sql === false) {
    fwrite(STDERR, "Failed to read seed file.\n");
    exit(1);
}

try {
    $database = new Database();
    $pdo = $database->pdo();

    // Split on semicolon + newline. Works for our handcrafted seed script.
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

    echo 'Applying company dashboard demo seed (' . count($statements) . " statements)...\n";

    $ok = 0;
    $errors = 0;

    foreach ($statements as $index => $statement) {
        if ($statement === '') {
            continue;
        }

        try {
            $pdo->exec($statement);
            echo sprintf('[%03d] OK\n', $index + 1);
            $ok++;
        } catch (\PDOException $e) {
            echo sprintf('[%03d] ERR: %s\n', $index + 1, $e->getMessage());
            $errors++;
        }
    }

    echo "Seed finished. OK={$ok}, ERR={$errors}\n";

    $tables = [
        'users',
        'waste_categories',
        'bidding_rounds',
        'bids',
        'payments',
        'notifications'
    ];

    foreach ($tables as $table) {
        try {
            $row = $database->fetch("SELECT COUNT(*) AS cnt FROM {$table}");
            $count = $row['cnt'] ?? 'N/A';
        } catch (\Throwable $e) {
            $count = 'ERR';
        }

        echo str_pad($table, 24) . ': ' . $count . "\n";
    }

    exit($errors === 0 ? 0 : 2);
} catch (\Throwable $e) {
    fwrite(STDERR, 'DB seeding failed: ' . $e->getMessage() . "\n");
    exit(1);
}
