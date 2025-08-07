<!-- Sidebar Navigation -->
<nav class="dashboard-sidebar">
    <div>
        <img src="/assets/logo-text-black.png" />
    </div>
    <div class="nav-menu-container">
        <h3 class="nav-menu__header">Dashboards</h3>
        <ul class="nav-menu">
            <?php
            $navigation = [];
            switch ($userType) {
                case 'admin':
                    $navigation = [
                        ['title' => 'Overview', 'url' => '/admin', 'icon' => 'chart-column'],
                        ['title' => 'Pickup Requests', 'url' => '/admin/pickup-requests', 'icon' => 'truck'],
                        ['title' => 'Bidding', 'url' => '/admin/bidding', 'icon' => 'auction'],
                        ['title' => 'User Management', 'url' => '/admin/users', 'icon' => 'users'],
                        ['title' => 'Vehicles', 'url' => '/admin/vehicles', 'icon' => 'car'],
                        ['title' => 'Payments', 'url' => '/admin/payments', 'icon' => 'credit-card'],
                        ['title' => 'Analytics', 'url' => '/admin/analytics', 'icon' => 'chart'],
                        ['title' => 'Notifications', 'url' => '/admin/notifications', 'icon' => 'bell'],
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
                        <i class="fa-solid fa-<?= $item['icon'] ?>"></i>
                        <span><?= $item['title'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<main class="dashboard-content">
    <div class="content-header">
        <div class="content-header__title">
            <i class="fa-solid fa-square-caret-left"></i>
            <h1><?= $pageTitle ?></h1>
        </div>
        <div class="content-header__icons">
            <i class="fa-solid fa-bell"></i>
            <i class="fa-solid fa-gear"></i>
        </div>
    </div>

    <div class="content-body">
        <?= $content ?>
    </div>
</main>