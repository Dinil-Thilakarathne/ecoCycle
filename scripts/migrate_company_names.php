<?php

/**
 * Migration Script: Fix Company Name Storage
 * 
 * This script will:
 * 1. Find all users of type 'company'.
 * 2. If 'companyName' exists in metadata, move it to the 'name' column.
 * 3. Move the old 'name' value (the owner) to 'metadata.contactPerson'.
 */

// Load basic environment if possible, or define constants
define('BASE_PATH', dirname(__DIR__));

// Load core classes manually since we don't have an autoloader here
require_once BASE_PATH . '/src/Core/Environment.php';
require_once BASE_PATH . '/src/helpers.php';

// Load environment variables
\Core\Environment::load(BASE_PATH);

// Database config
$config = require BASE_PATH . '/config/database.php';
$dbConfig = $config['connections'][$config['default']];

try {
    $host = $dbConfig['host'];
    $port = $dbConfig['port'];

    // If host is 'db' (Docker) but we are running on local Mac, try 127.0.0.1
    if ($host === 'db' && !file_exists('/.dockerenv')) {
        $host = '127.0.0.1';
    }

    $dsn = sprintf(
        "%s:host=%s;port=%s;dbname=%s",
        $dbConfig['driver'],
        $host,
        $port,
        $dbConfig['database']
    );

    // MySQL supports 'charset' in the DSN, but PostgreSQL does not
    if ($dbConfig['driver'] === 'mysql' && isset($dbConfig['charset'])) {
        $dsn .= ";charset=" . $dbConfig['charset'];
    }

    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Connected to database: " . $dbConfig['database'] . "\n";

    // Start transaction
    $pdo->beginTransaction();

    // 1. Fetch all companies
    $stmt = $pdo->query("SELECT id, name, metadata FROM users WHERE type = 'company'");
    $companies = $stmt->fetchAll();

    echo "Found " . count($companies) . " company accounts.\n";

    $updatedCount = 0;
    foreach ($companies as $company) {
        $meta = json_decode($company['metadata'] ?? '{}', true) ?: [];
        
        $oldOwnerName = $company['name'];
        $actualCompanyName = $meta['companyName'] ?? null;

        // Only migrate if we have a companyName in metadata
        if ($actualCompanyName && $actualCompanyName !== $oldOwnerName) {
            echo "Migrating Company ID {$company['id']}: '{$oldOwnerName}' -> '{$actualCompanyName}'\n";

            // Swap: name = companyName, contactPerson = old name
            $meta['contactPerson'] = $oldOwnerName;
            unset($meta['companyName']); // Remove old key

            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, metadata = ? WHERE id = ?");
            $updateStmt->execute([
                $actualCompanyName,
                json_encode($meta, JSON_UNESCAPED_UNICODE),
                $company['id']
            ]);
            $updatedCount++;
        } else {
            echo "Skipping Company ID {$company['id']} (Already migrated or no companyName in metadata)\n";
        }
    }

    $pdo->commit();
    echo "\nMigration completed successfully. Updated {$updatedCount} records.\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
