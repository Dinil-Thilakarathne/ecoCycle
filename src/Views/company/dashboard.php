<?php
$availableWaste = $availableWaste ?? [];
$highestBids = $highestBids ?? [];
$totalWaste = array_reduce($availableWaste, function (float $carry, array $item): float {
  $quantity = (float) ($item['quantity'] ?? 0);
  if ($quantity > 0) {
    return $carry + $quantity;
  }

  $value = (string) ($item['value'] ?? '');
  $numeric = preg_replace('/[^0-9.]/', '', $value);
  return $carry + (float) $numeric;
}, 0.0);
?>

<!-- Main Content -->
<main class="content">
  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title">Welcome back, EcoWaste!</h2>
      <p class="page-header__description">Here is your latest update on your Dashboard</p>
    </div>
  </header>

  <section class="companyDashboard">
    <!-- Available Waste Amount -->


    <div class="c-dashboard-card">
      <h3>Available Waste Amount</h3>
      <p class="value">
        <?= number_format($totalWaste) ?> kg
      </p>
      <ul class="stats-grid">
        <?php foreach ($availableWaste as $type): ?>
          <feature-card unwrap title="<?= htmlspecialchars($type['title'] ?? 'Unknown') ?>"
            value="<?= htmlspecialchars($type['value'] ?? ($type['quantity'] ?? 0) . ' ' . ($type['unit'] ?? 'kg')) ?>"
            icon="<?= htmlspecialchars($type['icon'] ?? 'fa-solid fa-recycle') ?>"></feature-card>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Highest Bids -->
    <div class="c-dashboard-grid">
      <div class="c-dashboard-card">
        <h3>Current Highest Bid for Each Waste Type</h3>
        <ul class="stats-grid">
          <?php foreach ($highestBids as $data):
            $safeType = htmlspecialchars($data['title'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
            $safeBid = htmlspecialchars($data['bid'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $safeAmount = htmlspecialchars($data['amount'] ?? '0 kg', ENT_QUOTES, 'UTF-8');
            $safeStatus = htmlspecialchars($data['status'] ?? 'Active', ENT_QUOTES, 'UTF-8');
            ?>
            <bid-item unwrap type="<?= $safeType ?>" bid="<?= $safeBid ?>" amount="<?= $safeAmount ?>"
              status="<?= $safeStatus ?>"></bid-item>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="c-dashboard-card">
        <h3>Send Feedback</h3>
        <div class="feedback">
          <input type="text" placeholder="Send Feedback">
          <button class="btn btn-primary" style="flex: 1;" type="submit">Send</button>
        </div>
      </div>
    </div>
  </section>
</main>