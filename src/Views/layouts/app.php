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

    <!-- Additional head content -->
    <?= $headContent ?? '' ?>
</head>

<body class="<?= $bodyClass ?? '' ?>">

    <!-- Main Content -->
    <main class="main-content" role="main">
        <!-- Page Content -->
        <?= $content ?? '' ?>
    </main>


    <!-- JavaScript Files -->
    <!-- Core JS -->
    <script src="/js/app.js"></script>
</body>

</html>