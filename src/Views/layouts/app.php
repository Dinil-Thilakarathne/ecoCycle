<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Page Title -->
    <title><?= $title ?? 'EcoCycle - Waste Management System' ?></title>

    <!-- Meta Description for SEO -->
    <meta name="description"
        content="<?= $description ?? 'EcoCycle - Sustainable waste management and recycling solutions' ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <!-- CSS Files - Load in order -->
    <!--  Main styles -->
    <link rel="stylesheet" href="/css/main.css">

    <!-- Dashboard CSS if this is a dashboard page -->
    <?php if (isset($userType)): ?>
        <link rel="stylesheet" href="/css/dashboard.css">
        <!-- Per-role dashboard styles -->
        <?php if (isset($userType) && $userType === 'collector'): ?>
            <link rel="stylesheet" href="/css/Collector.css">
        <?php elseif (isset($userType) && $userType === 'company'): ?>
            <link rel="stylesheet" href="/css/company.css">
        <?php elseif (isset($userType) && $userType === 'customer'): ?>
            <link rel="stylesheet" href="/css/customer.css">
        <?php elseif (isset($userType) && $userType === 'admin'): ?>
            <link rel="stylesheet" href="/css/admin.css">
            <link rel="stylesheet" href="/css/components/bid-history.css">
        <?php endif; ?>
    <?php endif; ?>

    <!-- Additional head content -->
    <?= $headContent ?? '' ?>

    <?php // Avoid calling methods on url() object; instead check the error status when available
    if (!isset($status) || (int) $status !== 404): ?>
        <script src="https://kit.fontawesome.com/10d4f02353.js" crossorigin="anonymous"></script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body
    class="<?= $bodyClass ?? '' ?><?php if (isset($userType)): ?> dashboard-layout <?= $userType ?>-dashboard<?php endif; ?>">

    <!-- Main Content -->
    <?= $content ?? '' ?>

    <!-- JavaScript Files -->
    <!-- Core JS -->
    <script type="module" src="/js/components/modal-manager.js"></script>
    <script type="module" src="/js/components/core.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/toast.js"></script>

    <!-- Dashboard JS if this is a dashboard page -->
    <?php if (isset($userType)): ?>
        <script src="/js/dashboard.js"></script>
    <?php endif; ?>

    <!-- Live Reload for Development (only loads on localhost/development)
         Controlled by HOT_RELOADER_ENABLED environment variable (true/false).
    -->
    <?php
    // Read boolean toggle from environment; default to true for local dev
    $envVal = $_ENV['HOT_RELOADER_ENABLED'] ?? $_SERVER['HOT_RELOADER_ENABLED'] ?? getenv('HOT_RELOADER_ENABLED') ?? null;
    $hotReloadEnabled = $envVal === null ? true : filter_var($envVal, FILTER_VALIDATE_BOOLEAN);

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($hotReloadEnabled && ($host === 'localhost' || $host === '127.0.0.1')): ?>
        <?php
        $hotReloaderPath = __DIR__ . '/../../../utils/HotReloader.php';
        if (file_exists($hotReloaderPath)) {
            require_once $hotReloaderPath;
            new HotReloader\HotReloader('/phrWatcher.php');
        } else {
            echo '<script src="/js/simple-live-reload.js"></script>';
        }
        ?>
    <?php endif; ?>
</body>

</html>