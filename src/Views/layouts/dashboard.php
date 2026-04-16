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
<div class="dashboard-layout">
    <div class="sidebar-backdrop"></div>
    <!-- Sidebar Navigation -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-container">
            <div>
                <img src="/assets/logo-text-black.png" class="desktop-logo" />
                <img src="/assets/logo-icon.png" class="mobile-logo" />
            </div>
            <nav class="nav-menu-container">
                <ul class="nav-menu">
                    <?php
                    foreach ($navigation as $item):
                        $isActive = NavigationConfig::isActiveUrl($item['url'], $currentUrl) ? 'active' : '';
                        ?>
                        <li class="nav-item <?= $isActive ?>">
                            <a href="<?= htmlspecialchars($item['url']) ?>" class="nav-link"
                                title="<?= htmlspecialchars($item['description'] ?? '') ?>">
                                <i class="fa-solid fa-<?= htmlspecialchars($item['icon']) ?>"></i>
                                <span><?= htmlspecialchars($item['title']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <!-- Sidebar footer: logout form (POST) to properly clear session -->
            <div class="sidebar-footer">
                <form id="logout-form" action="/logout" method="post" style="display:inline">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button type="submit" class="nav-link logout-link" title="Logout"
                        style="background:none;border:0;padding:0;cursor:pointer">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="content-header" style="display: none;">
            <div class="content-header__title">
                <i class="fa-solid fa-square-caret-left"></i>
                <!-- <h1><?= $pageTitle ?></h1> -->
            </div>
        </div>

        <div class="content-body">
            <?= $content ?>
        </div>
    </main>
</div>