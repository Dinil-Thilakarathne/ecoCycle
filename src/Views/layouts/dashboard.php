<?php
use EcoCycle\Core\Navigation\NavigationConfig;

// Prepare navigation and current URL
$navigation = NavigationConfig::getNavigation($userType);
$currentUrl = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Determine role-aware notifications URL
$notificationsUrl = null;
foreach ($navigation as $navItem) {
    if (isset($navItem['title']) && strcasecmp($navItem['title'], 'Notifications') === 0) {
        $notificationsUrl = $navItem['url'];
        break;
    }
    if (isset($navItem['icon']) && $navItem['icon'] === 'bell') {
        $notificationsUrl = $navItem['url'];
        break;
    }
}
// Fallback to conventional path if not present in NavigationConfig
if (!$notificationsUrl) {
    $notificationsUrl = '/' . $userType . '/notifications';
}
?>
<!-- Sidebar Navigation -->
<aside class="dashboard-sidebar">
    <div>
        <img src="/assets/logo-text-black.png" />
    </div>
    <nav class="nav-menu-container">
        <h3 class="nav-menu__header">Dashboards</h3>
        <ul class="nav-menu">
            <?php foreach ($navigation as $item): ?>
                <?php $isActive = NavigationConfig::isActiveUrl($item['url'], $currentUrl); ?>
                <li class="nav-item <?= $isActive ? 'active' : '' ?>">
                    <nav-link href="<?= htmlspecialchars($item['url']) ?>"
                        title="<?= htmlspecialchars($item['description'] ?? '') ?>" <?= $isActive ? 'active' : '' ?>>
                        <i class="fa-solid fa-<?= htmlspecialchars($item['icon']) ?>"></i>
                        <?= htmlspecialchars($item['title']) ?>
                    </nav-link>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

<!-- Main Content -->
<main class="dashboard-content">
    <div class="content-header">
        <div class="content-header__title">
            <i class="fa-solid fa-square-caret-left"></i>
            <h1><?= $pageTitle ?></h1>
        </div>
        <div class="content-header__icons">
            <a href="<?= htmlspecialchars($notificationsUrl) ?>" class="header-icon-link">
                <i class="fa-solid fa-bell"></i>
            </a>
            <i class="fa-solid fa-gear"></i>
        </div>
    </div>

    <div class="content-body">
        <?= $content ?>
    </div>
</main>