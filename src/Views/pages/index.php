<?php
include __DIR__ . '/../components/LandingHeader.php';
$bodyClass = "landing-page";
// Feature benefits data definition (kept near top for easy maintenance)
$benefits = [
    [
        'icon' => 'fa-solid fa-dollar-sign',
        'title' => 'Earn extra income',
        'description' => 'Turn your recyclable waste into a steady income stream with competitive market rates and transparent pricing.'
    ],
    [
        'icon' => 'fa-solid fa-leaf',
        'title' => 'Environmental impact',
        'description' => 'Contribute to a sustainable future by ensuring proper recycling of materials and reducing landfill waste.'
    ],
    [
        'icon' => 'fa-solid fa-clock',
        'title' => 'Save time',
        'description' => 'Convenient scheduling and pickup services that fit your busy lifestyle with flexible time slots.'
    ],
    [
        'icon' => 'fa-solid fa-shield-halved',
        'title' => 'Secure & reliable',
        'description' => 'Trusted platform with secure payments, verified recycling partners, and full transaction transparency.'
    ],
];

// How it works steps data
$howItWorksSteps = [
    [
        'step' => '01',
        'title' => 'Schedule pickup',
        'description' => 'Choose your waste categories and preferred time slot through our intuitive platform.',
        'image' => '/assets'
    ],
    [
        'step' => '02',
        'title' => 'We collect',
        'description' => 'Our trained collectors arrive at your scheduled time to collect your recyclable materials.',
        'image' => ''
    ],
    [
        'step' => '03',
        'title' => 'Companies bid',
        'description' => 'Recycling companies bid on your materials in real-time competitive auctions.',
        'image' => ''
    ],
    [
        'step' => '04',
        'title' => 'Get paid',
        'description' => 'Receive your earnings directly to your account after successful sales.',
        'image' => ''
    ],
];

// Testimonials / Reviews data
$testimonials = [
    [
        'name' => 'Sarah Johnson',
        'role' => 'Homeowner, Seattle',
        'content' => "I've earned over $300 this year just from my recyclable waste. The pickup service is incredibly reliable and the app makes everything so easy!",
        'rating' => 5,
        'avatar' => ''
    ],
    [
        'name' => 'David Chen',
        'role' => 'GreenTech Recycling',
        'content' => 'The bidding system is transparent and efficient. We\'ve secured high-quality materials at fair prices consistently. Great platform for sourcing materials.',
        'rating' => 5,
        'avatar' => ''
    ],
    [
        'name' => 'Mike Wilson',
        'role' => 'Collector, Portland',
        'content' => 'The route optimization and scheduling system makes my job much more efficient. I can handle 40% more pickups per day now!',
        'rating' => 5,
        'avatar' => ''
    ],
];
?>

<main>
    <!-- hero section -->
    <section class="hero-section main-section container">
        <div>
            <div class="hero-content">
                <div>
                    <div class="tag success">Join 10,000+ eco-conscious users</div>
                    <h1 class="hero-title">
                        <span>Turn Waste Into</span>
                        <span class="gradient-text">Wealth</span>
                    </h1>
                    <p class="hero-description">The smartest way to recycle. Schedule pickups, earn money, and help save
                        the planet.</p>
                </div>
                <div>
                    <button class="btn btn-gradient btn-icon"><span>Join Today </span><i
                            class="fa-solid fa-arrow-right"></i></button>
                </div>
                <div class="hero-content__bottom">
                    <div class="hero-content__bottom-card">
                        <h3>10K+</h3>
                        <p>Active users</p>
                    </div>
                    <div class="hero-content__bottom-card">
                        <h3>500+</h3>
                        <p>Tons recycled</p>
                    </div>
                    <div class="hero-content__bottom-card">
                        <h3>$50K+</h3>
                        <p>Paid to users</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section main-section container">
        <div>
            <h2 class="section-header">
                <span>Recycling made</span>
                <span class="gradient-text">simple</span>
            </h2>
            <p class="section-description">Our platform connects households with recycling companies through smart
                technology and sustainable practices.</p>
        </div>
    </section>

    <section class="feature-section main-section container">
        <div>
            <h2 class="section-header">
                <span>Why choose</span>
                <span class="gradient-text">EcoCycle</span>
            </h2>
        </div>
        <div class="feature-section__content">
            <div class="feature-benefits">
                <?php foreach ($benefits as $benefit): ?>
                    <div class="feature-benefit">
                        <div class="feature-benefit__icon">
                            <i class="<?php echo htmlspecialchars($benefit['icon']); ?>"></i>
                        </div>
                        <div class="feature-benefit__body">
                            <h3 class="feature-benefit__title"><?php echo htmlspecialchars($benefit['title']); ?></h3>
                            <p class="feature-benefit__description">
                                <?php echo htmlspecialchars($benefit['description']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="how-it-works-section main-section container">
        <div>
            <h2 class="section-header">
                <span>How it</span>
                <span class="gradient-text">works</span>
            </h2>
            <p class="section-description">Get started in just four simple steps</p>
        </div>
        <div class="how-it-works__content">
            <div class="how-it-works__grid">
                <?php foreach ($howItWorksSteps as $step): ?>
                    <div class="how-step">
                        <div class="how-step__media">
                            <img src="<?php echo htmlspecialchars($step['image']); ?>"
                                alt="<?php echo htmlspecialchars($step['title']); ?>" class="how-step__image" />
                            <div class="how-step__badge">
                                <span><?php echo htmlspecialchars($step['step']); ?></span>
                            </div>
                        </div>
                        <div class="how-step__body">
                            <h3 class="how-step__title"><?php echo htmlspecialchars($step['title']); ?></h3>
                            <p class="how-step__description"><?php echo htmlspecialchars($step['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="reviews-section main-section container">
        <div>
            <h2 class="section-header">
                <span>Loved by</span>
                <span class="gradient-text">thousands</span>
            </h2>
        </div>
        <div class="reviews-section__content">
            <div class="reviews-grid">
                <?php foreach ($testimonials as $t): ?>
                    <div class="review-card">
                        <div class="review-card__stars" aria-label="Rating: <?php echo (int) $t['rating']; ?> out of 5">
                            <?php for ($i = 0; $i < $t['rating']; $i++): ?>
                                <i class="fa-solid fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="review-card__content">&ldquo;<?php echo htmlspecialchars($t['content']); ?>&rdquo;</p>
                        <div class="review-card__user">
                            <div class="review-card__avatar-wrapper">
                                <img class="review-card__avatar" src="<?php echo htmlspecialchars($t['avatar']); ?>"
                                    alt="<?php echo htmlspecialchars($t['name']); ?>" />
                            </div>
                            <div class="review-card__meta">
                                <p class="review-card__name"><?php echo htmlspecialchars($t['name']); ?></p>
                                <p class="review-card__role"><?php echo htmlspecialchars($t['role']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php
include __DIR__ . '/../components/Footer.php';
