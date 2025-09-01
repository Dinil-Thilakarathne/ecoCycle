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
          <option value="Plastic Bottles">Plastic Bottles</option>
          <option value="Paper & Cardboard">Paper & Cardboard</option>
          <option value="metal">Metal</option>
          <option value="glass">Glass</option>
        </select>

        <label>Bid for 1kg of waste</label>
        <input type="number" name="bid_amount" step="100" required placeholder="Enter bid amount" min="1000">

        <label>Waste Amount (kg)</label>
        <input type="number" name="waste_amount" step="1" required placeholder="Enter waste amount" min="1">

        <button type="submit">Place Bid</button>
      </form>

      <div class ="available-waste">
        <div class="waste-lots">
          <h2 style="font-size: 20px; font-weight: bold;">Available Waste Lots</h2>
          <div class="lot-header">
              <span class="waste-type">Plastic Bottles</span>
              <span class="status available">Available</span>
          </div>
          <div class="lot-details">
              <p><strong>Location:</strong>District B</p>
              <p><strong>Quantity:</strong> 2,500 kg</p>
              <p><strong>Current Bid:</strong>Rs.125/kg</p>
          </div>
        </div>

        <div class="waste-lots">
              <div class="lot-header">
                  <span class="waste-type">Paper & Cardboard</span>
                  <span class="status available">Available</span>
              </div>
              <div class="lot-details">
                  <p><strong>Location:</strong> District A</p>
                  <p><strong>Quantity:</strong> 1,800 kg</p>
                  <p><strong>Current Bid:</strong> Rs.50/kg</p>
              </div>
        </div>
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
                        // Sample PHP data fro database
                        $biddingHistory = [
                            [
                                'id' => 'BID001',
                                'type' => 'Plastic Bottles',
                                'quantity' => '2,500 kg',
                                'location' => 'District A',
                                'amount' => 'Rs.1,250',
                                'status' => 'Active',
                                'date' => '2024-01-15'
                            ],
                            [
                                'id' => 'BID002',
                                'type' => 'Paper & Cardboard',
                                'quantity' => '1,800 kg',
                                'location' => 'District B',
                                'amount' => 'Rs.900',
                                'status' => 'Won',
                                'date' => '2024-01-12'
                            ]
                        ];
                        
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