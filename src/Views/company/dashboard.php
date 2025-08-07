  <!-- Main Content -->
  <main class="content">
    <header class="header">
      <h1>Welcome back!</h1>
      <p>Here is your latest update on your Dashboard.</p>
      <div class="icons">
        <span>Notification</span>
        <span>Settings</span>
      </div>
    </header>

    <section class="dashboard">
      <!-- Available Waste Amount -->
      <div class="card">
        <h3>Available Waste Amount</h3>
        <div class= waste-types>
        <p class="amount">
          <?php echo array_sum($availableWaste); ?> kg
        </p>
        <ul>
          <?php foreach ($availableWaste as $type => $kg): ?>
            <li><?php echo $type; ?>: <?php echo $kg; ?> kg</li>
          <?php endforeach; ?>
        </ul>
        </div>
      </div>

      <!-- Highest Bids -->
      <div class="card">
        <h3>Current Highest Bid for Each Waste Type</h3>
        <ul class="bids">
          <?php 
              $bids = [
                        'Plastic' => ['amount' => '2,500 kg', 'bid' => 'Rs.1,250', 'status' => 'active'],
                        'Paper' => ['amount' => '1,800 kg', 'bid' => 'Rs.900', 'status' => 'active'],
                        'Metal' => ['amount' => '3,200 kg', 'bid' => 'Rs.1,600', 'status' => 'pending'],
                        'Glass' => ['amount' => '1,200 kg', 'bid' => 'Rs.600', 'status' => 'closed']
                    ];
              foreach ($bids as $type => $data) {
              echo "
                        <div class='bid-item'>
                            <div class='bid-header'>
                                <span class='waste-type'>{$type}</span>
                                <span class='bid-amount'>{$data['bid']}</span>
                            </div>
                            <div class='bid-details'>
                                <span>{$data['amount']}</span>
                                <span class='status {$data['status']}'>{$data['status']}</span>
                            </div>
                        </div>";
              }
        
          ?>
        </ul>
      </div>

      <!-- Notifications -->
      <div class="card">
        <h3>Profile & Notifications</h3>
        <div class="profile-box">
          <p><strong>Company Name</strong></p>
          <a href="#">View Profile</a>
        </div>
        <ul class="notifications">
          <?php foreach ($notifications as $note => $time): ?>
            <li><?php echo $note; ?> <span class="time"><?php echo $time; ?></span></li>
          <?php endforeach; ?>
        </ul>
        <div class="feedback">
                        <input type="text" placeholder="Send Feedback">
                        <button>Send</button>
        </div>
      </div>
    </section>
  </main>