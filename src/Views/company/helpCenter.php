<?php
// Simulated FAQ data
$faq = [
    ["topic" => "Manage Profile", "question" => "How do I update my company profile?", "answer" => "Go to edit Profile in your profile to update details and save changes."],
    ["topic" => "Manage Profile", "question" => "How do I update my bank details?", "answer" => "Go to edit Profile in your profile to update bank details and save changes."],
    ["topic" => "Manage Profile", "question" => "How do I update my company logo?", "answer" => "Go to edit Profile in your profile to update your company logo and save changes."],
    ["topic" => "Manage Profile", "question" => "How do I change my password?", "answer" => "Go to edit Profile in your profile to update your password, give new password and confirm it and save changes."],
    ["topic" => "Submit Bids", "question" => "How do I place a bid on a waste lot?", "answer" => "Go to Active Bids, select a lot, enter your bid amount, and click submit. You can't place a bid on a lot that is not active or has already ended. 
                                                                                                Also if you have already placed a bid on a lot, you can update your bid amount until the bidding period ends but it should be higher than your previous bid."],
    ["topic" => "Submit Bids", "question" => "What happens when I submit a bid?", "answer" => "Your can review bid status in bidding history and you will receive notifications about its status."],
    ["topic" => "Submit Bids", "question" => "What happens after I win a bid?", "answer" => "You will get confirmation and payment instructions in purchases."],
    ["topic" => "Payment", "question" => "How can I track my payment status?", "answer" => "Track your payment under Purchases → Purchase Summary."],
    ["topic" => "Notifications & Alerts", "question" => "How do I manage notifications?", "answer" => "Go to Notification Settings to adjust your preferences."]
];

// Handle filters
$searchTerm = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$selectedTopic = isset($_GET['topic']) ? $_GET['topic'] : '';

// Filter results only if topic is selected
$filteredFaq = [];
if ($selectedTopic) {
    $filteredFaq = array_filter($faq, function ($item) use ($searchTerm, $selectedTopic) {
        $matchesTopic = $item['topic'] === $selectedTopic;
        $matchesSearch = $searchTerm ? (strpos(strtolower($item['question']), $searchTerm) !== false || strpos(strtolower($item['answer']), $searchTerm) !== false) : true;
        return $matchesTopic && $matchesSearch;
    });
}

// Help categories
$helpCategories = [
    ["icon" => "fa-solid fa-user", "title" => "Manage Profile", "desc" => "Update your company information and settings", "articles" => 4],
    ["icon" => "fa-solid fa-clipboard-list", "title" => "Submit Bids", "desc" => "Learn how to place and manage bids", "articles" => 3],
    ["icon" => "fa-solid fa-credit-card", "title" => "Payment", "desc" => "Payment methods and billing information", "articles" => 1],
    ["icon" => "fa-solid fa-bell", "title" => "Notifications & Alerts", "desc" => "Manage your notification preferences", "articles" => 1],
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
                <i class="<?= $cat['icon'] ?>"></i>
                <h3 style="font-size: 15px; font-weight: bold;"><?= $cat['title'] ?></h3>
                <p><?= $cat['desc'] ?></p>
                <span class="article-count"><?= $cat['articles'] ?> articles</span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- FAQ Section -->
        <?php if ($selectedTopic): ?>
            <h2 style="font-size: 20px; font-weight: bold;"><?= htmlspecialchars($selectedTopic) ?> FAQs</h2>
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
        <?php endif; ?>

        <!-- Contact Section -->
        <div class="contact-support">
            <h2 style="font-size: 20px; font-weight: bold;">Still need help...?</h2>
            <p style="font-size: 17px;"><i>If you can’t find help, contact our support team:</i></p><br>
            <ul>
                <li><b>Email:</b> <a href="mailto:support@ecocycle.com">support@ecocycle.com</a></li>
                <li><b>Phone:</b> 011 2345 678 /  011 2345 114 (Everyday 8am - 10pm)</li>
            </ul>
        </div>
</main>