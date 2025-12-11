<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Routes - EcoCycle</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; background: #f5f5f5; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .stats { margin-bottom: 20px; color: #666; font-size: 0.9em; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: 600; color: #444; position: sticky; top: 0; }
        tr:hover { background-color: #f8f9fa; }
        .method { font-weight: bold; padding: 4px 8px; border-radius: 4px; font-size: 12px; display: inline-block; min-width: 50px; text-align: center; }
        .method.GET { background-color: #e3f2fd; color: #1565c0; }
        .method.POST { background-color: #e8f5e9; color: #2e7d32; }
        .method.PUT { background-color: #fff3e0; color: #ef6c00; }
        .method.DELETE { background-color: #ffebee; color: #c62828; }
        .method.PATCH { background-color: #f3e5f5; color: #7b1fa2; }
        code { background: #f1f2f3; padding: 2px 5px; border-radius: 3px; font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace; color: #d63384; }
        .action { font-family: monospace; color: #333; }
        .middleware { font-size: 0.85em; color: #666; }
        .middleware-badge { background: #eee; padding: 2px 6px; border-radius: 10px; margin-right: 4px; display: inline-block; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registered Routes</h1>
        <div class="stats">
            Total Routes: <strong><?= count($routes) ?></strong> | Generated: <?= date('Y-m-d H:i:s') ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="80">Method</th>
                    <th width="30%">Path</th>
                    <th>Action</th>
                    <th>Middleware</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $route): ?>
                    <tr>
                        <td>
                            <span class="method <?= $route['method'] ?>">
                                <?= $route['method'] ?>
                            </span>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($route['path']) ?></code>
                        </td>
                        <td class="action">
                            <?php
                            if (is_callable($route['action'])) {
                                echo '<i>Closure</i>';
                            } else {
                                echo htmlspecialchars($route['action']);
                            }
                            ?>
                        </td>
                        <td class="middleware">
                            <?php if (!empty($route['middleware'])): ?>
                                <?php foreach ($route['middleware'] as $mw): ?>
                                    <span class="middleware-badge"><?= htmlspecialchars(basename(str_replace('\\', '/', $mw))) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
