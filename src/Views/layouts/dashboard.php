<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - EcoCycle</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
</head>

<body class="dashboard-layout <?= $userType ?>-dashboard">

    <!-- Sidebar Navigation -->
    <nav class="dashboard-sidebar">
        <ul class="nav-menu">
            <?php
            $navigation = [];
            switch ($userType) {
                case 'admin':
                    $navigation = [
                        ['title' => 'Dashboard', 'url' => '/admin', 'icon' => 'dashboard'],
                        ['title' => 'User Management', 'url' => '/admin/users', 'icon' => 'users'],
                        ['title' => 'Reports', 'url' => '/admin/reports', 'icon' => 'analytics'],
                        ['title' => 'Content', 'url' => '/admin/content', 'icon' => 'content'],
                        ['title' => 'Settings', 'url' => '/admin/settings', 'icon' => 'settings'],
                    ];
                    break;
                case 'customer':
                    $navigation = [
                        ['title' => 'Dashboard', 'url' => '/customer', 'icon' => 'home'],
                        ['title' => 'Schedule Pickup', 'url' => '/customer/schedule', 'icon' => 'calendar'],
                        ['title' => 'Pickup History', 'url' => '/customer/history', 'icon' => 'history'],
                        ['title' => 'My Rewards', 'url' => '/customer/rewards', 'icon' => 'gift'],
                        ['title' => 'Education', 'url' => '/customer/education', 'icon' => 'book'],
                        ['title' => 'Profile', 'url' => '/customer/profile', 'icon' => 'user'],
                    ];
                    break;
                case 'collector':
                    $navigation = [
                        ['title' => 'Dashboard', 'url' => '/collector', 'icon' => 'dashboard'],
                        ['title' => 'Pickups', 'url' => '/collector/pickups', 'icon' => 'truck'],
                        ['title' => 'Routes', 'url' => '/collector/routes', 'icon' => 'map'],
                        ['title' => 'Earnings', 'url' => '/collector/earnings', 'icon' => 'money'],
                        ['title' => 'Reports', 'url' => '/collector/reports', 'icon' => 'chart'],
                        ['title' => 'Profile', 'url' => '/collector/profile', 'icon' => 'user'],
                    ];
                    break;
                case 'company':
                    $navigation = [
                        ['title' => 'Dashboard', 'url' => '/company', 'icon' => 'dashboard'],
                        ['title' => 'Waste Management', 'url' => '/company/waste', 'icon' => 'recycle'],
                        ['title' => 'Schedule Collection', 'url' => '/company/schedule', 'icon' => 'calendar'],
                        ['title' => 'Analytics', 'url' => '/company/analytics', 'icon' => 'chart'],
                        ['title' => 'Billing', 'url' => '/company/billing', 'icon' => 'invoice'],
                        ['title' => 'Sustainability', 'url' => '/company/sustainability', 'icon' => 'leaf'],
                        ['title' => 'Profile', 'url' => '/company/profile', 'icon' => 'building'],
                    ];
                    break;
            }

            foreach ($navigation as $item):
                $isActive = $_SERVER['REQUEST_URI'] === $item['url'] ? 'active' : '';
                ?>
                <li class="nav-item <?= $isActive ?>">
                    <a href="<?= $item['url'] ?>" class="nav-link">
                        <i class="icon icon-<?= $item['icon'] ?>"></i>
                        <span><?= $item['title'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="content-header">
            <h1><?= $pageTitle ?></h1>
        </div>

        <div class="content-body">
            <?= $content ?>
        </div>
    </main>

    <script src="<?= asset('js/dashboard.js') ?>"></script>
</body>

</html>