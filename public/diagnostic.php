<?php
/**
 * Route System Diagnostic Page
 * 
 * This page helps validate and debug the new route system
 */

use EcoCycle\Core\Navigation\NavigationConfig;
use EcoCycle\Core\Navigation\RouteConfig;

// Get all routes and validate them
$routes = RouteConfig::getAllDashboardRoutes();
$missing = RouteConfig::validateRoutes();
$stubs = RouteConfig::generateMissingMethodStubs();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route System Diagnostic - EcoCycle</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #1ce36a;
            margin-top: 0;
        }

        .status {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }

        .status.success {
            background: #d1fae5;
            color: #047857;
        }

        .status.warning {
            background: #fef3c7;
            color: #b45309;
        }

        .status.error {
            background: #fee2e2;
            color: #dc2626;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }

        th {
            background: #f9f9f9;
            font-weight: 600;
        }

        .method {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .url {
            font-family: monospace;
            background: #ecfdf5;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .missing {
            background: #fef2f2;
        }

        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            border-left: 4px solid #1ce36a;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <h1>🚦 Route System Diagnostic</h1>
            <p>This page validates the new centralized routing system that automatically generates routes from
                NavigationConfig.</p>

            <?php if (empty($missing)): ?>
                <div class="status success">✅ All routes are valid and have corresponding controller methods</div>
            <?php else: ?>
                <div class="status warning">⚠️ <?= count($missing) ?> routes are missing controller methods</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📋 All Dashboard Routes (<?= count($routes) ?> total)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>URL</th>
                        <th>Controller</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                        <?php
                        $isMissing = false;
                        foreach ($missing as $miss) {
                            if ($miss['url'] === $route['url']) {
                                $isMissing = true;
                                break;
                            }
                        }
                        ?>
                        <tr class="<?= $isMissing ? 'missing' : '' ?>">
                            <td><strong><?= htmlspecialchars($route['title']) ?></strong><br>
                                <small><?= htmlspecialchars($route['description']) ?></small>
                            </td>
                            <td><span class="url"><?= htmlspecialchars($route['url']) ?></span></td>
                            <td><?= htmlspecialchars($route['controller']) ?></td>
                            <td><span class="method"><?= htmlspecialchars($route['action']) ?></span></td>
                            <td>
                                <?= $isMissing ? '<span style="color: #dc2626;">❌ Missing</span>' : '<span style="color: #047857;">✅ Valid</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($missing)): ?>
            <div class="card">
                <h2>🔧 Missing Controller Methods</h2>
                <p>The following methods need to be added to their respective controllers:</p>

                <?php foreach ($stubs as $controller => $methodStubs): ?>
                    <h3><?= htmlspecialchars($controller) ?></h3>
                    <pre><code><?= htmlspecialchars(implode('', $methodStubs)) ?></code></pre>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>🎯 Navigation Configuration Summary</h2>
            <?php $userTypes = NavigationConfig::getAvailableUserTypes(); ?>
            <p>Navigation is configured for <?= count($userTypes) ?> user types:
                <strong><?= implode(', ', $userTypes) ?></strong></p>

            <?php foreach ($userTypes as $userType): ?>
                <?php $nav = NavigationConfig::getNavigation($userType); ?>
                <h3><?= ucfirst($userType) ?> Dashboard (<?= count($nav) ?> items)</h3>
                <ul>
                    <?php foreach ($nav as $item): ?>
                        <li>
                            <strong><?= htmlspecialchars($item['title']) ?></strong>
                            → <span class="url"><?= htmlspecialchars($item['url']) ?></span>
                            <br><small><?= htmlspecialchars($item['description']) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h2>🔗 Quick Links</h2>
            <ul>
                <li><a href="/test">System Test API</a> - JSON overview of the system</li>
                <li><a href="/routes/list">Routes List API</a> - JSON list of all routes</li>
                <li><a href="/routes/validate">Routes Validation API</a> - JSON validation results</li>
                <li><a href="/dashboards">Dashboard Navigation</a> - Main navigation page</li>
            </ul>
        </div>
    </div>
</body>

</html>