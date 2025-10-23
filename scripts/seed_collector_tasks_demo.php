<?php

/**
 * Script: scripts/seed_collector_tasks_demo.php
 *
 * Populates the database with demo data for the collector dashboard tasks
 * screen. Reuses the SQL seed to keep logic in one place.
 */

const BASE_PATH = __DIR__ . '/..';
const SEED_PATH = BASE_PATH . '/database/seeds/seed_collector_tasks_demo.sql';

require BASE_PATH . '/vendor/autoload.php';

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

// Minimal bootstrap to mirror public/index.php behaviour
loadEnvironment(BASE_PATH . '/.env');
require_once BASE_PATH . '/src/helpers.php';

function loadEnvironment(string $envPath): void
{
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, "\"'");
    }
}

function runSqlSeed(\Core\Database $db, string $path): void
{
    if (!is_readable($path)) {
        throw new RuntimeException('Seed file not readable: ' . $path);
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Failed to read seed file: ' . $path);
    }

    $trimmed = trim($sql);
    if ($trimmed === '') {
        throw new RuntimeException('Seed file is empty: ' . $path);
    }

    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

    $pdo = $db->pdo();

    foreach ($statements as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $pdo->exec($statement);
    }
}

try {
    $db = new \Core\Database();
    runSqlSeed($db, SEED_PATH);
    echo "Collector task demo seed applied successfully." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
