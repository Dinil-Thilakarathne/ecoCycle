<?php

// Sample data (replace with database queries in real implementation)
$availableWaste = [
  ['title' => 'Plastic', 'value' => '2,500 kg', 'icon' => 'fa-solid fa-bottle-water'],
  ['title' => 'Paper', 'value' => '1,800 kg', 'icon' => 'fa-solid fa-paper-plane'],
  ['title' => 'Metal', 'value' => '3,200 kg', 'icon' => 'fa-solid fa-box'],
  ['title' => 'Glass', 'value' => '1,200 kg', 'icon' => 'fa-solid fa-wine-bottle'],
  ['title' => 'Organic', 'value' => '1500 kg', 'icon' => 'fa-solid fa-leaf'],

];

$totalWaste = 0;

$bids = [
  ['title' => 'Plastic', 'amount' => '2,500 kg', 'bid' => 'Rs.1,250', 'status' => 'Active'],
  ['title' => 'Paper', 'amount' => '1,800 kg', 'bid' => 'Rs.900', 'status' => 'Active'],
  ['title' => 'Metal', 'amount' => '3,200 kg', 'bid' => 'Rs.1,600', 'status' => 'Pending'],
  ['title' => 'Glass', 'amount' => '1,200 kg', 'bid' => 'Rs.600', 'status' => 'Closed']
];

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
    <?php foreach ($availableWaste as $waste) {
      $numericValue = (int) filter_var($waste['value'], FILTER_SANITIZE_NUMBER_INT);
      $totalWaste += $numericValue;
    }
    ?>

    <div class="c-dashboard-card">
      <h3>Available Waste Amount</h3>
      <p class="value">
        <?= number_format($totalWaste) ?> kg
      </p>
      <ul class="stats-grid">
        <?php foreach ($availableWaste as $type): ?>
          <feature-card unwrap title="<?= htmlspecialchars($type['title']) ?>"
            value="<?= htmlspecialchars($type['value']) ?>" icon="<?= htmlspecialchars($type['icon']) ?>"></feature-card>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Highest Bids -->
    <div class="c-dashboard-grid">
      <div class="c-dashboard-card">
        <h3>Current Highest Bid for Each Waste Type</h3>
        <ul class="stats-grid">
          <?php
          foreach ($bids as $type => $data):
            $safeType = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
            $safeBid = htmlspecialchars($data['bid'], ENT_QUOTES, 'UTF-8');
            $safeAmount = htmlspecialchars($data['amount'], ENT_QUOTES, 'UTF-8');
            $safeStatus = htmlspecialchars($data['status'], ENT_QUOTES, 'UTF-8');
            ?>
            <bid-item unwrap type="<?= $safeType ?>" bid="<?= $safeBid ?>" amount="<?= $safeAmount ?>"
              status="<?= $safeStatus ?>"></bid-item>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </section>
</main>