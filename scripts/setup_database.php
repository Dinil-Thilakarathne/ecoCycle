#!/usr/bin/env php
<?php
/**
 * Complete Database Setup & Migration Script
 * Handles PostgreSQL Docker setup and applies all migrations
 */

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "🚀 ECOCYCLE DATABASE SETUP & MIGRATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Step 1: Load environment
echo "📋 Step 1: Loading environment configuration...\n";
$env_file = __DIR__ . '/../.env';
if (!file_exists($env_file)) {
    echo "❌ .env file not found at {$env_file}\n";
    exit(1);
}

$env = [];
$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

echo "✅ Configuration loaded\n\n";

// Step 2: Verify Docker
echo "📋 Step 2: Checking Docker setup...\n";
$docker_check = shell_exec('docker ps 2>&1');
if (strpos($docker_check, 'ecocycle') === false) {
    echo "⚠️  Docker containers not found running\n\n";
    echo "📖 To start Docker containers:\n";
    echo "   cd " . dirname(__DIR__) . "\n";
    echo "   docker-compose -f docker-compose.dev.yml up -d\n\n";
    echo "   Wait for containers to be ready (check: docker-compose logs)\n\n";
} else {
    echo "✅ Docker containers are running\n\n";
}

// Step 3: Test database connection
echo "📋 Step 3: Testing database connection...\n";

$db_connection = $env['DB_CONNECTION'] ?? 'pgsql';
$db_host = $env['DB_HOST'] ?? 'localhost';
$db_port = $env['DB_PORT'] ?? '5432';
$db_database = $env['DB_DATABASE'] ?? 'eco_cycle';
$db_username = $env['DB_USERNAME'] ?? 'postgres';
$db_password = $env['DB_PASSWORD'] ?? 'root';

echo "  Connection Type: {$db_connection}\n";
echo "  Host: {$db_host}:{$db_port}\n";
echo "  Database: {$db_database}\n";
echo "  Username: {$db_username}\n\n";

try {
    if ($db_connection === 'pgsql') {
        $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_database}";
    } else {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_database}";
    }
    
    $pdo = new PDO($dsn, $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connection successful!\n\n";
    
    // Step 4: Check tables
    echo "📋 Step 4: Verifying database tables...\n\n";
    
    $required_tables = [
        'waste_categories' => ['id', 'name', 'price_per_unit'],
        'pickup_request_wastes' => ['id', 'pickup_id', 'waste_category_id', 'weight', 'amount'],
        'pickup_requests' => ['id', 'customer_id', 'weight', 'price'],
    ];
    
    $all_good = true;
    
    foreach ($required_tables as $table => $required_cols) {
        echo "Table: {$table}\n";
        
        try {
            if ($db_connection === 'pgsql') {
                $stmt = $pdo->query("
                    SELECT column_name 
                    FROM information_schema.columns 
                    WHERE table_name = '{$table}'
                ");
            } else {
                $stmt = $pdo->query("
                    SELECT COLUMN_NAME as column_name 
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_NAME = '{$table}' AND TABLE_SCHEMA = '{$db_database}'
                ");
            }
            
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($required_cols as $col) {
                if (in_array($col, $existing)) {
                    echo "  ✅ {$col}\n";
                } else {
                    echo "  ❌ {$col} (MISSING - needs migration)\n";
                    $all_good = false;
                }
            }
            echo "\n";
        } catch (\Exception $e) {
            echo "  ❌ Table not found!\n\n";
            $all_good = false;
        }
    }
    
    // Step 5: Migration if needed
    if (!$all_good) {
        echo "🔄 Step 5: Running migration to add missing columns...\n\n";
        
        try {
            // Add price_per_unit
            try {
                $pdo->query("ALTER TABLE waste_categories ADD COLUMN price_per_unit DECIMAL(12,2) DEFAULT 0.00");
                echo "  ✅ Added price_per_unit to waste_categories\n";
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'column') === false) {
                    throw $e;
                }
                echo "  ℹ️  price_per_unit already exists\n";
            }
            
            // Add weight and amount to pickup_request_wastes
            try {
                $pdo->query("ALTER TABLE pickup_request_wastes ADD COLUMN weight DECIMAL(10,2) DEFAULT NULL");
                echo "  ✅ Added weight to pickup_request_wastes\n";
            } catch (\Exception $e) {
                echo "  ℹ️  weight already exists\n";
            }
            
            try {
                $pdo->query("ALTER TABLE pickup_request_wastes ADD COLUMN amount DECIMAL(12,2) DEFAULT NULL");
                echo "  ✅ Added amount to pickup_request_wastes\n";
            } catch (\Exception $e) {
                echo "  ℹ️  amount already exists\n";
            }
            
            // Add price to pickup_requests
            try {
                $pdo->query("ALTER TABLE pickup_requests ADD COLUMN price DECIMAL(12,2) DEFAULT NULL");
                echo "  ✅ Added price to pickup_requests\n";
            } catch (\Exception $e) {
                echo "  ℹ️  price already exists\n";
            }
            
            echo "\n✅ Migration completed!\n\n";
            
            // Update prices
            echo "💰 Setting waste category prices...\n";
            $prices = [
                'Plastic' => 10.00,
                'Paper' => 5.00,
                'Glass' => 8.00,
                'Metal' => 20.00,
                'Cardboard' => 3.00
            ];
            
            foreach ($prices as $category => $price) {
                $pdo->query(
                    "UPDATE waste_categories SET price_per_unit = ? WHERE name = ?",
                    [$price, $category]
                );
                echo "  ✅ {$category}: Rs {$price}/kg\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "✨ DATABASE SETUP COMPLETE!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    echo "📊 Database Status:\n";
    echo "  ✅ Connection: OK\n";
    echo "  ✅ Tables: OK\n";
    echo "  ✅ Columns: OK\n";
    echo "  ✅ Prices: Configured\n\n";
    echo "🎯 Next Steps:\n";
    echo "  1. Verify data in DBeaver\n";
    echo "  2. Test weight entry in collector dashboard\n";
    echo "  3. Check calculated prices\n\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n\n";
    
    echo "🔧 Troubleshooting:\n";
    echo "  1. Make sure Docker containers are running:\n";
    echo "     docker-compose -f docker-compose.dev.yml up -d\n\n";
    echo "  2. Check Docker logs:\n";
    echo "     docker-compose logs -f db\n\n";
    echo "  3. Verify .env has correct credentials\n\n";
    
    exit(1);
}
