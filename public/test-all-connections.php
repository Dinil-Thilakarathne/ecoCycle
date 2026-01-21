<?php
/**
 * Quick Database Connection Tester
 * Tests different database configurations
 */

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🔍 DATABASE CONNECTION DIAGNOSTICS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Load .env
$env_file = __DIR__ . '/../.env';
$config = [];
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $config[trim($key)] = trim($value);
        }
    }
}

echo "📋 Current .env Configuration:\n";
echo "  DB_CONNECTION: " . ($config['DB_CONNECTION'] ?? 'NOT SET') . "\n";
echo "  DB_HOST: " . ($config['DB_HOST'] ?? 'NOT SET') . "\n";
echo "  DB_PORT: " . ($config['DB_PORT'] ?? 'NOT SET') . "\n";
echo "  DB_DATABASE: " . ($config['DB_DATABASE'] ?? 'NOT SET') . "\n";
echo "  DB_USERNAME: " . ($config['DB_USERNAME'] ?? 'NOT SET') . "\n";
echo "\n";

// Test configurations
$tests = [
    'MySQL Local' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'eco_cycle',
        'username' => 'root',
        'password' => '',
    ],
    'MySQL with Password' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'eco_cycle',
        'username' => 'root',
        'password' => 'root',
    ],
    'PostgreSQL Local' => [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'eco_cycle',
        'username' => 'postgres',
        'password' => 'root',
    ],
    'PostgreSQL Docker' => [
        'driver' => 'pgsql',
        'host' => 'localhost',
        'port' => '5432',
        'database' => 'eco_cycle',
        'username' => 'postgres',
        'password' => 'root',
    ],
];

echo "🧪 Testing Different Database Configurations:\n\n";

foreach ($tests as $name => $config) {
    echo "Testing: {$name}\n";
    echo "  Driver: {$config['driver']}\n";
    echo "  Host: {$config['host']}:{$config['port']}\n";
    echo "  Database: {$config['database']}\n";
    echo "  Username: {$config['username']}\n";
    
    try {
        if ($config['driver'] === 'pgsql') {
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        } else {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        }
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        
        // Test query
        $stmt = $pdo->query("SELECT 1");
        echo "  ✅ CONNECTION SUCCESSFUL!\n\n";
        
        // Show which config to use
        echo "  📝 USE THIS IN .env:\n";
        echo "     DB_CONNECTION=" . $config['driver'] . "\n";
        echo "     DB_HOST=" . $config['host'] . "\n";
        echo "     DB_PORT=" . $config['port'] . "\n";
        echo "     DB_USERNAME=" . $config['username'] . "\n";
        echo "     DB_PASSWORD=" . $config['password'] . "\n\n";
        
    } catch (PDOException $e) {
        echo "  ❌ Failed: " . substr($e->getMessage(), 0, 80) . "\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
