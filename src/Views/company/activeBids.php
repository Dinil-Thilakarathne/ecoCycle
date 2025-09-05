<?php
$biddingHistory = [
                            ['id' => 'BID001', 'type' => 'Plastic', 'quantity' => '2,500 kg', 'location' => 'District A',
                            'amount' => 'Rs.1,250', 'status' => 'Active', 'date' => '2025-07-15'],
                            ['id' => 'BID002', 'type' => 'Paper', 'quantity' => '1,800 kg', 'location' => 'District B',
                                'amount' => 'Rs.900', 'status' => 'Won', 'date' => '2025-07-12'],
                            ['id' => 'BID001', 'type' => 'Organic', 'quantity' => '1,200 kg', 'location' => 'District C',
                                'amount' => 'Rs.600', 'status' => 'Rejected', 'date' => '2025-07-10'],

                        ];

$wasteLots = [
    ["type" => "Plastic", "status" => "Available", "quantity" => "2,500 kg", "current_bid" => "Rs.125/kg"],
    ["type" => "Paper", "status" => "Available", "quantity" => "1,800 kg", "current_bid" => "Rs.50/kg"],
    ["type" => "Organic", "status" => "Available", "quantity" => "1,200 kg", "current_bid" => "Rs.100/kg"]
];

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
        <label>Waste Type</label>
        <select name="waste_type" required>
          <option value="">Select waste type…</option>
          <option value="Plastic">Plastic</option>
          <option value="Paper">Paper</option>
          <option value="metal">Metal</option>
          <option value="glass">Glass</option>
          <option value="Organic">Organic</option>
        </select>

        <label>Bid for 1kg of waste</label>
        <input type="number" name="bid_amount" step="10" required placeholder="Enter bid amount" min="500" max="5000" required>

        <label>Waste Amount (kg)</label>
        <input type="number" name="waste_amount" step="1" required placeholder="Enter waste amount" min="10" max="10000" required>

        <button type="submit">Place Bid</button>
      </form>
    

        <div class="available-waste">
        <h2 style="font-size: 20px; font-weight: bold;">Available Waste Lots</h2>

        <?php foreach ($wasteLots as $lot): ?>
            <div class="waste-lots">
                <div class="lot-header">
                    <span class="waste-type"><?= htmlspecialchars($lot['type']) ?></span>
                    <span class="status <?= strtolower($lot['status']) ?>">
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
                            <th>Location</th>
                            <th>Bid Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        
                        foreach ($biddingHistory as $bid) {
                            echo "<tr>
                                <td>{$bid['id']}</td>
                                <td>{$bid['type']}</td>
                                <td>{$bid['quantity']}</td>
                                <td>{$bid['location']}</td>
                                <td>{$bid['amount']}</td>
                                <td><span class='status {$bid['status']}'>{$bid['status']}</span></td>
                                <td>{$bid['date']}</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
        
    </div>
</main>