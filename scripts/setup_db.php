<?php

require __DIR__ . '/../vendor/autoload.php';

use Core\Database;

// This script applies database/create_tables.sql to the configured database.
// It's idempotent (uses CREATE TABLE IF NOT EXISTS in the SQL file).
// Run on first boot or from your deployment process.

$path = __DIR__ . '/../database/mysql/create_tables.sql';
if (!is_file($path)) {
    echo "ERROR: schema file not found: $path\n";
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    echo "ERROR: failed to read schema file\n";
    exit(1);
}

// Optional opt-out via env var
$skip = getenv('SKIP_DB_SETUP');
if ($skip && in_array(strtolower($skip), ['1', 'true', 'yes'], true)) {
    echo "SKIP_DB_SETUP set, skipping DB setup.\n";
    exit(0);
}

try {
    $db = new Database();
    $pdo = $db->pdo();

    // Split SQL into statements. This is a simple splitter and assumes no complex
    // procedures that contain semicolons inside string literals. Our schema is plain SQL.
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

    echo "Applying schema (" . count($statements) . " statements) ...\n";

    foreach ($statements as $i => $stmt) {
        if ($stmt === '')
            continue;
        try {
            $pdo->exec($stmt);
            echo sprintf("[%03d] OK\n", $i + 1);
        } catch (PDOException $e) {
            // Print error and continue — CREATE TABLE IF NOT EXISTS will skip existing;
            // foreign key checks toggling may produce harmless errors depending on order.
            echo sprintf("[%03d] ERR: %s\n", $i + 1, $e->getMessage());
        }
    }

    echo "Schema applied.\n";
    exit(0);
} catch (Throwable $e) {
    echo "DB setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
