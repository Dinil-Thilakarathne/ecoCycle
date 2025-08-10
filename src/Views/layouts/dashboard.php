<?php
use EcoCycle\Core\Navigation\NavigationConfig;
?>
<!-- Sidebar Navigation -->
<nav class="dashboard-sidebar">
    <div>
        <img src="/assets/logo-text-black.png" />
    </div>
    <div class="nav-menu-container">
        <h3 class="nav-menu__header">Dashboards</h3>
        <ul class="nav-menu">
            <?php
            $navigation = NavigationConfig::getNavigation($userType);
            $currentUrl = $_SERVER['REQUEST_URI'];

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