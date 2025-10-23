<?php
/**
 * Database Connection Test Page
 * Tests PostgreSQL connection and displays database info
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/helpers.php';

use Core\Database;

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PostgreSQL Connection Test - ecoCycle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
            text-align: center;
        }

        .status-box {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-box.success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }

        .status-box.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }

        .status-icon {
            font-size: 2em;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-card h3 {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card p {
            font-size: 1.3em;
            color: #333;
            font-weight: bold;
        }

        .table-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .table-list h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .table-list ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
        }

        .table-list li {
            background: white;
            padding: 10px 15px;
            border-radius: 5px;
            border-left: 3px solid #28a745;
            font-family: monospace;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: #764ba2;
        }

        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🐘 PostgreSQL Connection Test</h1>

        <?php
        try {
            // Test database connection
            $db = new Database();
            $pdo = $db->pdo();

            // Get PostgreSQL version
            $versionQuery = $pdo->query("SELECT version()");
            $version = $versionQuery->fetch(PDO::FETCH_ASSOC)['version'];

            // Get database name
            $dbNameQuery = $pdo->query("SELECT current_database()");
            $dbName = $dbNameQuery->fetch(PDO::FETCH_ASSOC)['current_database'];

            // Get connection info
            $config = require __DIR__ . '/../config/database.php';
            $conn = $config['connections'][$config['default']];

            // Count tables
            $tablesQuery = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public'");
            $tableCount = $tablesQuery->fetch(PDO::FETCH_ASSOC)['count'];

            // Get table list
            $tablesListQuery = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
            $tables = $tablesListQuery->fetchAll(PDO::FETCH_COLUMN);

            // Count users
            $usersQuery = $pdo->query("SELECT COUNT(*) as count FROM users");
            $userCount = $usersQuery->fetch(PDO::FETCH_ASSOC)['count'];

            // Count roles
            $rolesQuery = $pdo->query("SELECT COUNT(*) as count FROM roles");
            $roleCount = $rolesQuery->fetch(PDO::FETCH_ASSOC)['count'];

            echo '<div class="status-box success">';
            echo '<div class="status-icon">✅</div>';
            echo '<div>';
            echo '<strong style="font-size: 1.2em;">Connection Successful!</strong><br>';
            echo 'PostgreSQL is running and the database is accessible.';
            echo '</div>';
            echo '</div>';

            echo '<div class="info-grid">';

            echo '<div class="info-card">';
            echo '<h3>Database</h3>';
            echo '<p>' . htmlspecialchars($dbName) . '</p>';
            echo '</div>';

            echo '<div class="info-card">';
            echo '<h3>Host</h3>';
            echo '<p>' . htmlspecialchars($conn['host']) . ':' . htmlspecialchars($conn['port']) . '</p>';
            echo '</div>';

            echo '<div class="info-card">';
            echo '<h3>Driver</h3>';
            echo '<p>' . strtoupper(htmlspecialchars($conn['driver'])) . '</p>';
            echo '</div>';

            echo '<div class="info-card">';
            echo '<h3>Tables</h3>';
            echo '<p>' . $tableCount . '</p>';
            echo '</div>';

            echo '<div class="info-card">';
            echo '<h3>Users</h3>';
            echo '<p>' . $userCount . '</p>';
            echo '</div>';

            echo '<div class="info-card">';
            echo '<h3>Roles</h3>';
            echo '<p>' . $roleCount . '</p>';
            echo '</div>';

            echo '</div>';

            echo '<div class="table-list">';
            echo '<h3>📋 Database Tables</h3>';
            echo '<ul>';
            foreach ($tables as $table) {
                echo '<li>' . htmlspecialchars($table) . '</li>';
            }
            echo '</ul>';
            echo '</div>';

            // Show PostgreSQL version
            $versionShort = substr($version, 0, 60);
            echo '<div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 8px;">';
            echo '<strong>PostgreSQL Version:</strong><br>';
            echo '<code style="background: white; display: inline-block; margin-top: 5px; padding: 8px;">' . htmlspecialchars($versionShort) . '...</code>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="status-box error">';
            echo '<div class="status-icon">❌</div>';
            echo '<div>';
            echo '<strong style="font-size: 1.2em;">Connection Failed!</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            echo '</div>';

            echo '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">';
            echo '<strong>Troubleshooting Tips:</strong>';
            echo '<ul style="margin-top: 10px; margin-left: 20px;">';
            echo '<li>Check if Docker containers are running: <code>docker-compose ps</code></li>';
            echo '<li>Verify database credentials in <code>.env</code> file</li>';
            echo '<li>Check Docker logs: <code>docker-compose logs db</code></li>';
            echo '<li>Restart containers: <code>docker-compose restart</code></li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <div style="text-align: center;">
            <a href="/" class="back-link">← Back to Application</a>
        </div>
    </div>
</body>

</html>