<?php
$HeaderNav = [
    'Features' => '#features',
    'How It Works' => '#how-it-works',
    'Reviews' => '#reviews',
];
?>

<header class="top-nav">
    <div class="min-container top-nav__content">
        <div>
            <img src="/assets/logo-text-black.png" alt="EcoCycle Logo">
        </div>
        <nav>
            <ul>
                <?php foreach ($HeaderNav as $name => $link): ?>
                    <li><a href="<?php echo $link; ?>"><?php echo $name; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="login-button">
            <a href="/login" class="btn btn-focus">Get Started</a>
        </div>
    </div>
</header>