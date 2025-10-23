<?php
$acceptedPurchases = $acceptedPurchases ?? [];
$purchaseSummary = $purchaseSummary ?? ['total' => format_rs(0), 'active_orders' => 0, 'completed' => 0];
$purchaseHistory = $purchaseHistory ?? [];
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Purchases</h2>
            <p class="page-header__description">Manage your accepted purchases and payment history</p>
        </div>
    </header>

    <div class="purchases-grid">
        <!-- Accepted Purchases -->
        <div class="c-purchase-card">
            <h2 style="font-size: 20px; font-weight: bold;">Accepted Purchases</h2>
            <?php foreach ($acceptedPurchases as $purchase): ?>
                <div class="purchase-box">
                    <h3 style="font-size: 18px; font-weight: bold;"><?= htmlspecialchars($purchase['type'] ?? 'Unknown') ?>
                    </h3>
                    <p>ID: <?= htmlspecialchars($purchase['id'] ?? 'N/A') ?></p>
                    <p>Amount: <strong><?= htmlspecialchars($purchase['amount'] ?? '0 kg') ?></strong></p>
                    <p>Price: <span class="price"><?= htmlspecialchars($purchase['price'] ?? format_rs(0)) ?></span></p>
                    <p>Pickup Date: <?= htmlspecialchars($purchase['pickup_date'] ?? 'TBD') ?></p>
                    <span class="tag <?= strtolower(str_replace(' ', '-', $purchase['status'] ?? 'pending')) ?>"
                        style="position: absolute; top: 15px; right: 20px;"><?= strtoupper($purchase['status'] ?? 'PENDING') ?>
                    </span>
                    <?php ?>
                    <button class="btn btn-primary outline" style="width: 100%; margin-top: 15px;" type="submit">Make
                        Payment</button>
                    <?php ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Purchase Summary -->
        <div class="c-purchase-card">
            <h2 style="font-size: 20px; font-weight: bold;">Purchase Summary</h2>
            <div class="total"><?= htmlspecialchars($purchaseSummary['total']) ?></div>
            <h2 style="font-size: 20px; font-weight: bold;">Total purchases</h2>
            <div class="summary-box">
                <div class="box blue"><?= (int) ($purchaseSummary['active_orders'] ?? 0) ?> <span>Active Orders</span>
                </div>
                <div class="box purple"><?= (int) ($purchaseSummary['completed'] ?? 0) ?> <span>Completed</span></div>
            </div>
        </div>
    </div>

    <!-- Purchase History -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">Purchase History</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Purchase ID</th>
                    <th>Waste Type</th>
                    <th>Amount</th>
                    <th>Price</th>
                    <th>Delivery Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchaseHistory as $history): ?>
                    <tr>
                        <td><?= htmlspecialchars($history['id'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($history['type'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($history['amount'] ?? '0 kg') ?></td>
                        <td class="price"><?= htmlspecialchars($history['price'] ?? format_rs(0)) ?></td>
                        <td><span
                                class="tag completed"><?= htmlspecialchars($history['delivery_status'] ?? 'Completed') ?></span>
                        </td>
                        <td><?= htmlspecialchars($history['date'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>