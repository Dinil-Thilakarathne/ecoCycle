<?php
// Simulated FAQ data
$faq = [
    ["topic" => "Manage Profile", "question" => "How do I update my company profile?", "answer" => "Go to Manage Profile in your settings to update details."],
    ["topic" => "Submit Bids", "question" => "How do I place a bid on a waste lot?", "answer" => "Go to Active Bids, select a lot, enter your bid amount, and click submit."],
    ["topic" => "Submit Bids", "question" => "What happens after I win a bid?", "answer" => "You will get confirmation and payment instructions in your dashboard."],
    ["topic" => "Payment", "question" => "How can I track my payment status?", "answer" => "Track your payment under Purchases → Purchase Summary."],
    ["topic" => "Notifications & Alerts", "question" => "How do I manage notifications?", "answer" => "Go to Notification Settings to adjust your preferences."]
];

// Handle filters
$searchTerm = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$selectedTopic = isset($_GET['topic']) ? $_GET['topic'] : '';

// Filter results
$filteredFaq = array_filter($faq, function ($item) use ($searchTerm, $selectedTopic) {
    $matchesTopic = $selectedTopic ? $item['topic'] === $selectedTopic : true;
    $matchesSearch = $searchTerm ? (strpos(strtolower($item['question']), $searchTerm) !== false || strpos(strtolower($item['answer']), $searchTerm) !== false) : true;
    return $matchesTopic && $matchesSearch;
});

// Help categories
$helpCategories = [
    ["icon" => "👤", "title" => "Manage Profile", "desc" => "Update your company information and settings", "articles" => 1],
    ["icon" => "📄", "title" => "Submit Bids", "desc" => "Learn how to place and manage bids", "articles" => 2],
    ["icon" => "💳", "title" => "Payment", "desc" => "Payment methods and billing information", "articles" => 1],
    ["icon" => "🔔", "title" => "Notifications & Alerts", "desc" => "Manage your notification preferences", "articles" => 3],
];
?>

<main class="content">
        <header class="page-header">
            <div class="page-header__content">
                    <h2 class="page-header__title">Help Center</h2>
                    <p class="page-header__description">Hi, How can we help you?</p>
            </div>
        </header>

        <!-- Search Form -->
        <form method="GET">
            <input type="hidden" name="topic" value="<?= htmlspecialchars($selectedTopic) ?>">
            <input type="text" name="search" placeholder="Search for help..." value="<?= htmlspecialchars($searchTerm) ?>" class="search-bar">
        </form>

        <!-- Categories -->
        <div class="categories">
            <?php foreach($helpCategories as $cat): ?>
            <a href="?topic=<?= urlencode($cat['title']) ?>" class="category">
                <div class="icon"><?= $cat['icon'] ?></div>
                <h3><?= $cat['title'] ?></h3>
                <p><?= $cat['desc'] ?></p>
                <span class="article-count"><?= $cat['articles'] ?> articles</span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- FAQ Section -->
        <h2 style="font-size: 20px; font-weight: bold;">📢 Frequently Asked Questions</h2>
        <div class="faq">
            <?php if (empty($filteredFaq)): ?>
                <p>No results found.</p>
            <?php else: ?>
                <?php foreach($filteredFaq as $item): ?>
                <details>
                    <summary><?= htmlspecialchars($item['question']) ?></summary>
                    <p><?= htmlspecialchars($item['answer']) ?></p>
                </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
</main>