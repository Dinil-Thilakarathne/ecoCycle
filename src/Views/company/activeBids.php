<?php
// Load minimum bids from config
$config = include __DIR__ . "/../../../config/data.php";
$minimumBids = $config['minimum_bids'];

// Bidding History (dummy data)
$biddingHistory = [
    ['id' => 'BID003', 'type' => 'Plastic', 'quantity' => '2,500 kg', 'amount' => 'Rs.1,250', 'status' => 'Active', 'date' => '2025-07-15'],
    ['id' => 'BID002', 'type' => 'Paper', 'quantity' => '1,800 kg', 'amount' => 'Rs.900', 'status' => 'Won', 'date' => '2025-07-12'],
    ['id' => 'BID001', 'type' => 'Organic', 'quantity' => '1,200 kg', 'amount' => 'Rs.600', 'status' => 'Rejected', 'date' => '2025-07-10'],
];

// Available waste lots → dynamically load current bid from config
$wasteLots = [
    ["type" => "Plastic", "status" => "Available", "quantity" => "2,500 kg", "current_bid" => "Rs." . $minimumBids['plastic'] . "/kg"],
    ["type" => "Paper", "status" => "Available", "quantity" => "1,800 kg", "current_bid" => "Rs." . $minimumBids['paper'] . "/kg"],
    ["type" => "Organic", "status" => "Available", "quantity" => "1,200 kg", "current_bid" => "Rs." . $minimumBids['organic'] . "/kg"],
];

// --- Handle form submission ---
$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $wasteType = strtolower(trim($_POST['waste_type'] ?? ''));
    $bidAmount = (int)($_POST['bid_amount'] ?? 0);
    $wasteAmount = (int)($_POST['waste_amount'] ?? 0);

    // Validation
    if (!isset($minimumBids[$wasteType])) {
        $errors[] = "Invalid waste type selected.";
    } elseif ($bidAmount < $minimumBids[$wasteType]) {
        $errors[] = ucfirst($wasteType) . " requires a minimum bid of Rs." . $minimumBids[$wasteType] . "/kg.";
    }

    if ($wasteAmount < 10 || $wasteAmount > 10000) {
        $errors[] = "Waste amount must be between 10kg and 10,000kg.";
    }

    if (empty($errors)) {
        // ✅ Normally, save bid to database here
        echo "<p style='color:green; font-weight:bold;'>Bid placed successfully for $wasteType at Rs.$bidAmount/kg for $wasteAmount kg.</p>";
    }
}
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
                <h2 class="page-header__title">Place your bid here!</h2>
                <p class="page-header__description">Submit bids for available waste lots</p>
        </div>
    </header>

    <div class="top-section">
      <!-- New Bid Form -->
      <form class="bid-form" method="post" action="">
        <h2 style="font-size: 20px; font-weight: bold;">New Bid Submission</h2>

        <!-- Show validation errors -->
        <?php if (!empty($errors)): ?>
            <div class="error-box" style="color:red; margin-bottom:10px;">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <label>Waste Type</label>
        <select name="waste_type" id="waste_type" required>
          <option value="">Select waste type…</option>
          <?php foreach ($minimumBids as $type => $min): ?>
            <option value="<?= htmlspecialchars($type) ?>"><?= ucfirst($type) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Bid for 1kg of waste</label>
        <input type="number" id="bid_amount" name="bid_amount" step="10" placeholder="Enter bid amount" required>

        <label>Waste Amount (kg)</label>
        <input type="number" name="waste_amount" step="1" required placeholder="Enter waste amount" min="10" max="10000">

        <button class="btn btn-primary outline" style="width: 100%; margin-top: 15px;" type="submit">Place Bid</button>
      </form>
    

        <div class="available-waste">
        <h2 style="font-size: 20px; font-weight: bold;">Available Waste Lots</h2>

        <?php foreach ($wasteLots as $lot): ?>
            <div class="waste-lots">
                <div class="lot-header">
                    <span class="waste-type"><?= htmlspecialchars($lot['type']) ?></span>
                    <span class="tag <?= strtolower($lot['status']) ?>">
                        <?= htmlspecialchars($lot['status']) ?>
                    </span>
                </div>
                <div class="lot-details">
                    <p><strong>Quantity:</strong> <?= htmlspecialchars($lot['quantity']) ?></p>
                    <p><strong>Current Bid:</strong> <?= htmlspecialchars($lot['current_bid']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Bidding History -->
    <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">View Bidding History</h3>
            </div>    
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bid ID</th>
                            <th>Waste Type</th>
                            <th>Quantity</th>
                            <th>Bid Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($biddingHistory as $bid): ?>
                            <tr>
                                <td><?= htmlspecialchars($bid['id']) ?></td>
                                <td><?= htmlspecialchars($bid['type']) ?></td>
                                <td><?= htmlspecialchars($bid['quantity']) ?></td>
                                <td><?= htmlspecialchars($bid['amount']) ?></td>
                                <td><span class="tag <?= strtolower($bid['status']) ?>"><?= htmlspecialchars($bid['status']) ?></span></td>
                                <td><?= htmlspecialchars($bid['date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
    </div>
</main>

<script>
// Pass PHP minimum bids to JS for dynamic validation
const minBids = <?= json_encode($minimumBids) ?>;

document.getElementById('waste_type').addEventListener('change', function() {
    let selected = this.value;
    let bidInput = document.getElementById('bid_amount');

    if (selected && minBids[selected]) {
        bidInput.min = minBids[selected];
        bidInput.placeholder = "Minimum: " + minBids[selected];
    } else {
        bidInput.min = 500;
        bidInput.placeholder = "Enter bid amount";
    }
});
</script>
