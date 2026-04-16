<?php

/**
 * Setup Script for Collector Analytics
 * 
 * This script:
 * 1. Runs the migration to add weight and amount columns
 * 2. Seeds sample collector ratings data
 * 3. Seeds sample waste collection data with weight and amount
 * 
 * Usage: php scripts/setup_collector_analytics.php
 */

const BASE_PATH = __DIR__ . '/..';

require BASE_PATH . '/vendor/autoload.php';

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

// Load environment
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

loadEnvironment(BASE_PATH . '/.env');
require_once BASE_PATH . '/src/helpers.php';

try {
    $db = new \Core\Database();
    $pdo = $db->pdo();
    
    echo "=================================================\n";
    echo "Collector Analytics Setup\n";
    echo "=================================================\n\n";
    
    // Step 1: Run migration to add columns
    echo "[1/3] Running migration to add weight and amount columns...\n";
    
    $migrationFile = BASE_PATH . '/database/mysql/add_missing_columns.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || str_starts_with($statement, '--')) {
            continue;
        }
        try {
            $pdo->exec($statement);
        } catch (\PDOException $e) {
            // Ignore errors for columns that already exist
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✓ Migration completed successfully\n\n";
    
    // Step 2: Get or create demo collector
    echo "[2/3] Setting up demo collector...\n";
    
    $collector = $pdo->query("SELECT id FROM users WHERE type = 'collector' LIMIT 1")->fetch();
    if (!$collector) {
        throw new Exception("No collector found. Please run seed_collector_tasks_demo.php first.");
    }
    $collectorId = $collector['id'];
    echo "✓ Using collector ID: $collectorId\n\n";
    
    // Step 3: Seed collector ratings
    echo "[3/3] Seeding sample data...\n";
    
    // Get some customers
    $customers = $pdo->query("SELECT id, name FROM users WHERE type = 'customer' LIMIT 3")->fetchAll();
    
    if (count($customers) > 0) {
        echo "  → Adding collector ratings...\n";
        
        foreach ($customers as $i => $customer) {
            $rating = 5 - $i; // 5, 4, 3
            $descriptions = [
                'Excellent service! Very professional and punctual.',
                'Good collector, arrived on time and handled waste carefully.',
                'Decent service but could be more friendly.'
            ];
            
            $checkRating = $pdo->prepare("SELECT COUNT(*) FROM collector_ratings WHERE collector_id = ? AND customer_id = ?");
            $checkRating->execute([$collectorId, $customer['id']]);
            
            if ($checkRating->fetchColumn() == 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO collector_ratings 
                    (customer_id, collector_id, collector_name, rating, description, rating_date, created_at) 
                    VALUES (?, ?, (SELECT name FROM users WHERE id = ?), ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), NOW())
                ");
                
                $stmt->execute([
                    $customer['id'],
                    $collectorId,
                    $collectorId,
                    $rating,
                    $descriptions[$i] ?? 'Good service',
                    $i 
                ]);
                
                echo "    ✓ Added rating from {$customer['name']}: $rating stars\n";
            }
        }
    }
    
    // Update existing pickup_request_wastes with weight and amount
    echo "  → Updating waste collection records with weight and amount...\n";
    
    $updateSql = "
        UPDATE pickup_request_wastes prw
        JOIN waste_categories wc ON wc.id = prw.waste_category_id
        SET 
            prw.weight = COALESCE(prw.weight, prw.quantity),
            prw.amount = COALESCE(prw.amount, prw.quantity * COALESCE(wc.price_per_unit, 10.00))
        WHERE prw.weight IS NULL OR prw.amount IS NULL
    ";
    
    $affected = $pdo->exec($updateSql);
    echo "    ✓ Updated $affected waste collection records\n";
    
    echo "\n=================================================\n";
    echo "✓ Setup completed successfully!\n";
    echo "=================================================\n\n";
    echo "Next steps:\n";
    echo "1. Refresh the collector analytics page\n";
    echo "2. Check the browser console for any API errors\n";
    echo "3. Verify data is showing in both tables\n\n";
    
} catch (Throwable $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
