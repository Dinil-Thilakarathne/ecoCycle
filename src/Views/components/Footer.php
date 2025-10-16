<?php
// Footer component (refactored for Ecocycle)

// Platform links (relative)
$footerNav = [
    'Platform' => [
        ['label' => 'For Households', 'href' => '/customer'],
        ['label' => 'For Companies', 'href' => '/company'],
        ['label' => 'For Collectors', 'href' => '/collector'],
        ['label' => 'Pricing', 'href' => '/pricing'],
    ],
];

// Contact info formatted for Sri Lanka
$contactInfo = [
    ['icon' => 'fa-solid fa-phone', 'label' => '+94 77 123 4567'],
    ['icon' => 'fa-solid fa-envelope', 'label' => 'hello@ecocycle.com'],
    ['icon' => 'fa-solid fa-location-dot', 'label' => 'Colombo, Sri Lanka'],
];

?>

<footer class="site-footer">
    <div class="site-footer__inner container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <div class="brand-logo">
                    <img src="/assets/logo-text-white.png" alt="Ecocycle Logo" />
                 </div>
                <p class="site-footer__tagline">Transforming waste management through technology and sustainable
                    practices.</p>
            </div>

            <?php foreach ($footerNav as $section => $links): ?>
                <div class="site-footer__col">
                    <h3 class="site-footer__heading"><?php echo htmlspecialchars($section); ?></h3>
                    <ul class="site-footer__list">
                        <?php foreach ($links as $item): ?>
                            <li><a href="<?php echo htmlspecialchars($item['href']); ?>"
                                    class="site-footer__link"><?php echo htmlspecialchars($item['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <div class="site-footer__col">
                <h3 class="site-footer__heading">Contact</h3>
                <ul class="site-footer__contact">
                    <?php foreach ($contactInfo as $c): ?>
                        <li class="contact-item"><i class="<?php echo htmlspecialchars($c['icon']); ?>"></i>
                            <span><?php echo htmlspecialchars($c['label']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p class="site-footer__copyright">&copy; <?php echo date('Y'); ?> Ecocycle. All rights reserved.</p>
            <div class="site-footer__powered">
                <span>Powered by sustainable technology</span>
                <i class="fa-solid fa-bolt-lightning"></i>
            </div>
        </div>
    </div>
</footer>