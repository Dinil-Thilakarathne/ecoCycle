<?php
// Footer component

$footerNav = [
    'Platform' => ['For Households', 'For Companies', 'For Collectors', 'Pricing'],
    'Resources' => ['Help Center', 'Recycling Guide', 'API Documentation', 'Community Forum', 'Blog'],
];

$contactInfo = [
    ['icon' => 'fa-solid fa-phone', 'label' => '+1 (555) 123-4567'],
    ['icon' => 'fa-solid fa-envelope', 'label' => 'hello@ecowaste.com'],
    ['icon' => 'fa-solid fa-location-dot', 'label' => 'San Francisco, CA'],
];

$badges = [
    ['icon' => 'fa-solid fa-award', 'text' => 'ISO Certified'],
    ['icon' => 'fa-solid fa-globe', 'text' => 'Carbon Neutral'],
];
?>

<footer class="site-footer">
    <div class="site-footer__inner container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <div class="brand-logo">
                    <div class="brand-logo__icon">
                        <i class="fa-solid fa-recycle"></i>
                    </div>
                    <span class="brand-logo__text">EcoWaste</span>
                </div>
                <p class="site-footer__tagline">Transforming waste management through technology and sustainable
                    practices. Join us in building a cleaner, more sustainable future.</p>
                <div class="site-footer__badges">
                    <?php foreach ($badges as $badge): ?>
                        <span class="badge badge-outline"><i class="<?php echo htmlspecialchars($badge['icon']); ?>"></i>
                            <?php echo htmlspecialchars($badge['text']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($footerNav as $section => $links): ?>
                <div class="site-footer__col">
                    <h3 class="site-footer__heading"><?php echo htmlspecialchars($section); ?></h3>
                    <ul class="site-footer__list">
                        <?php foreach ($links as $item): ?>
                            <li><a href="#" class="site-footer__link"><?php echo htmlspecialchars($item); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <div class="site-footer__col">
                <h3 class="site-footer__heading">Contact</h3>
                <ul class="site-footer__contact">
                    <?php foreach ($contactInfo as $c): ?>
                        <li class="contact-item"><i
                                class="<?php echo htmlspecialchars($c['icon']); ?>"></i><span><?php echo htmlspecialchars($c['label']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p class="site-footer__copyright">&copy; <?php echo date('Y'); ?> EcoWaste. All rights reserved.</p>
            <div class="site-footer__powered">
                <span>Powered by sustainable technology</span>
                <i class="fa-solid fa-bolt-lightning"></i>
            </div>
        </div>
    </div>
</footer>