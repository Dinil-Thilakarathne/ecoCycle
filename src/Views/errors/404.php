<?php
/**
 * Basic 404 error view
 * Expects optional $error variable (message) provided by controller.
 */
$message = $error ?? 'The page you are looking for does not exist.';
?>

<?php
// Link the 404 stylesheet (public path)
$headContent = '<link rel="stylesheet" href="/public/css/404.css">';
?>

<section class="page-404" aria-labelledby="page-404-title">
    <div class="page-404__card">
        <h1 id="page-404-title" class="page-404__headline">404 — Page not found</h1>
        <p class="page-404__message"><?php echo htmlspecialchars($message); ?></p>

        <div class="page-404__actions">
            <a href="/" class="btn btn-outline">Go to home</a>
        </div>
    </div>
</section>

