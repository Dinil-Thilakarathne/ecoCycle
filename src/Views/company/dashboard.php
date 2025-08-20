<?php

// Sample data (replace with database queries in real implementation)
$availableWaste = [
  'Plastic' => 2500,
  'Paper' => 1800,
  'Metal' => 3200,
  'Glass' => 1200,
  'polythene' => 2500,
  'e-waste' => 1800
];


$notifications = [];

?>


<!-- Main Content -->
<main class="content">
  <header class="page-header">
    <div class="page-header__content">
            <h2 class="page-header__title">Welcome back!</h2>
            <p class="page-header__description">Here is your latest update on your Dashboard</p>
    </div>
  </header>

  <section class="companyDashboard">
    <!-- Available Waste Amount -->
    <div class="c-dashboard-card">
      <h3>Available Waste Amount</h3>
      <p class="value">
        <?php echo array_sum($availableWaste); ?> kg
      </p>
      <ul class="waste-list">
        <?php foreach ($availableWaste as $type => $kg): ?>
          <li><?php echo $type; ?>: <?php echo $kg; ?> kg</li>
        <?php endforeach; ?>
      </ul>
    </div>


    <!-- Highest Bids -->
    <div class="c-dashboard-grid">
      <div class="c-dashboard-card">
        <h3>Current Highest Bid for Each Waste Type</h3>
        <ul class="bids">
          <?php
          $bids = [
            'Plastic' => ['amount' => '2,500 kg', 'bid' => 'Rs.1,250', 'status' => 'Active'],
            'Paper' => ['amount' => '1,800 kg', 'bid' => 'Rs.900', 'status' => 'Active'],
            'Metal' => ['amount' => '3,200 kg', 'bid' => 'Rs.1,600', 'status' => 'Pending'],
            'Glass' => ['amount' => '1,200 kg', 'bid' => 'Rs.600', 'status' => 'Closed']
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
      <div class="c-dashboard-card">
        <h3>Profile & Notifications</h3>
        <div class="profile-box">
          <p><strong>Company Name</strong></p>
          <a href="http://localhost:8000/company/profile">View Profile</a>
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
    </div>
  </section>
</main>