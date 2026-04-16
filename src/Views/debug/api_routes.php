<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Route Explorer - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --get: #3b82f6;
            --post: #10b981;
            --put: #f59e0b;
            --delete: #ef4444;
            --patch: #8b5cf6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.5;
            padding: 2rem;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
        }

        h1 {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            background: linear-gradient(to right, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .stats {
            display: flex;
            gap: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .stat-item strong {
            color: var(--text-main);
            font-weight: 600;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: rgba(15, 23, 42, 0.5);
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(51, 65, 85, 0.3);
        }

        .method-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .method-GET { background: rgba(59, 130, 246, 0.1); color: var(--get); border: 1px solid rgba(59, 130, 246, 0.2); }
        .method-POST { background: rgba(16, 185, 129, 0.1); color: var(--post); border: 1px solid rgba(16, 185, 129, 0.2); }
        .method-PUT { background: rgba(245, 158, 11, 0.1); color: var(--put); border: 1px solid rgba(245, 158, 11, 0.2); }
        .method-DELETE { background: rgba(239, 68, 68, 0.1); color: var(--delete); border: 1px solid rgba(239, 68, 68, 0.2); }
        .method-PATCH { background: rgba(139, 92, 246, 0.1); color: var(--patch); border: 1px solid rgba(139, 92, 246, 0.2); }

        .path-cell {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            color: #cbd5e1;
        }

        .path-highlight {
            color: var(--primary);
        }

        .description-cell {
            max-width: 300px;
        }

        .description-text {
            font-size: 0.875rem;
            color: var(--text-main);
            font-weight: 500;
        }

        .description-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .auth-required {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .auth-public {
            background: rgba(16, 185, 129, 0.1);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .role-pill {
            display: inline-block;
            background: #334155;
            color: #e2e8f0;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .role-all {
            background: #475569;
            font-style: italic;
        }

        .action-cell {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .search-container {
            margin-bottom: 2rem;
            position: relative;
        }

        #routeSearch {
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            color: var(--text-main);
            font-size: 1rem;
            outline: none;
            transition: all 0.2s;
        }

        #routeSearch:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        @media (max-width: 1024px) {
            .hide-tablet { display: none; }
        }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .hide-mobile { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>API Route Explorer</h1>
                <p class="subtitle">EcoCycle Backend Endpoint Registry</p>
            </div>
            <div class="stats">
                <div class="stat-item">Total APIs: <strong><?= count($routes) ?></strong></div>
                <div class="stat-item">Version: <strong>1.0.0</strong></div>
            </div>
        </header>

        <div class="search-container">
            <input type="text" id="routeSearch" placeholder="Search by path, description or action..." onkeyup="filterRoutes()">
        </div>

        <div class="card">
            <table id="routesTable">
                <thead>
                    <tr>
                        <th width="100">Method</th>
                        <th>Endpoint Path</th>
                        <th>Details</th>
                        <th width="200">Authentication</th>
                        <th class="hide-tablet">Action Handler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                        <tr class="route-row">
                            <td>
                                <span class="method-badge method-<?= $route['method'] ?>">
                                    <?= $route['method'] ?>
                                </span>
                            </td>
                            <td class="path-cell">
                                <?php 
                                    $path = htmlspecialchars($route['path']);
                                    echo str_replace('/api', '<span class="path-highlight">/api</span>', $path);
                                ?>
                            </td>
                            <td class="description-cell">
                                <div class="description-text"><?= htmlspecialchars($route['description']) ?></div>
                                <div class="description-sub hide-mobile"><?= htmlspecialchars($route['method']) ?> request to <?= htmlspecialchars($route['path']) ?></div>
                            </td>
                            <td>
                                <div style="margin-bottom: 0.5rem;">
                                    <?php if ($route['requires_auth']): ?>
                                        <span class="auth-badge auth-required">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                            Auth Required
                                        </span>
                                    <?php else: ?>
                                        <span class="auth-badge auth-public">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            Public
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="roles-container">
                                    <?php foreach ($route['roles_allowed'] as $role): ?>
                                        <span class="role-pill <?= ($role === 'All Authenticated Users' || $role === 'Public') ? 'role-all' : '' ?>">
                                            <?= htmlspecialchars($role) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="action-cell hide-tablet">
                                <?= htmlspecialchars($route['action']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterRoutes() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("routeSearch");
            filter = input.value.toUpperCase();
            table = document.getElementById("routesTable");
            tr = table.getElementsByClassName("route-row");

            for (i = 0; i < tr.length; i++) {
                var visible = false;
                // Search in Path, Description, and Action columns
                var columns = [1, 2, 4]; 
                
                for (var j = 0; j < columns.length; j++) {
                    td = tr[i].getElementsByTagName("td")[columns[j]];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            visible = true;
                            break;
                        }
                    }
                }
                
                if (visible) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
