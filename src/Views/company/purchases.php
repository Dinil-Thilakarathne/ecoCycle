<?php
// Simulating database data
$acceptedPurchases = [
    [
        "type" => "Plastic",
        "id" => "PUR008",
        "amount" => "500 kg",
        "price" => "Rs.60,000",
        "pickup_date" => "2025-08-20",
        "status" => "Confirmed"
    ],
    [
        "type" => "Paper",
        "id" => "PUR007",
        "amount" => "1,800 kg",
        "price" => "Rs.20,000",
        "pickup_date" => "2025-08-18",
        "status" => "In-Transit"
    ],
    [
        "type" => "Metal",
        "id" => "PUR006",
        "amount" => "1,200 kg",
        "price" => "Rs.15,000",
        "pickup_date" => "2025-08-12",
        "status" => "Delivered"
    ]
];

$purchaseSummary = [
    "total" => "Rs.85,000",
    "active_orders" => 2,
    "completed" => 6
];

$purchaseHistory = [
    ["id" => "PUR005", "type" => "Glass", "amount" => "1,200 kg", "price" => "Rs.20,000", "delivery_status" => "Completed", "date" => "2025-08-10"],
    ["id" => "PUR004", "type" => "Paper", "amount" => "3,000 kg", "price" => "Rs.18,500", "delivery_status" => "Completed", "date" => "2025-08-05"],
    ["id" => "PUR003", "type" => "Matal", "amount" => "1,000 kg", "price" => "Rs.15,000", "delivery_status" => "Completed", "date" => "2025-07-25"],
    ["id" => "PUR002", "type" => "Organic", "amount" => "2,000 kg", "price" => "Rs.1,5000", "delivery_status" => "Completed", "date" => "2025-07-15"],
    ["id" => "PUR001", "type" => "Glass", "amount" => "800 kg", "price" => "Rs.14,000", "delivery_status" => "Completed", "date" => "2025-07-10"]
];
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
                <?php foreach($acceptedPurchases as $purchase): ?>
                <div class="purchase-box">
                    <h3><?= $purchase['type'] ?></h3>
                    <p>ID: <?= $purchase['id'] ?></p>
                    <p>Amount: <strong><?= $purchase['amount'] ?></strong></p>
                    <p>Price: <span class="price"><?= $purchase['price'] ?></span></p>
                    <p>Pickup Date: <?= $purchase['pickup_date'] ?></p>
                    <span class="status <?= strtolower(str_replace(' ', '-', $purchase['status'])) ?>"><?= strtoupper($purchase['status']) ?> </span>
                    <?php if($purchase['status'] == "Confirmed"): ?>
                        <button class="pay-btn">Make Payment</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Purchase Summary -->
            <div class="c-purchase-card">
                <h2 style="font-size: 20px; font-weight: bold;">Purchase Summary</h2>
                <div class="total"><?= $purchaseSummary['total'] ?></div>
                <h2 style="font-size: 20px; font-weight: bold;">Total purchases</h2>
                <div class="summary-box">
                    <div class="box blue"><?= $purchaseSummary['active_orders'] ?> <span>Active Orders</span></div>
                    <div class="box purple"><?= $purchaseSummary['completed'] ?> <span>Completed</span></div>
                </div>
            </div>
        </div>

        <!-- Purchase History -->
        <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title" >Purchase History</h3>
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
                    <?php foreach($purchaseHistory as $history): ?>
                    <tr>
                        <td><?= $history['id'] ?></td>
                        <td><?= $history['type'] ?></td>
                        <td><?= $history['amount'] ?></td>
                        <td class="price"><?= $history['price'] ?></td>
                        <td><span class="status completed"><?= $history['delivery_status'] ?></span></td>
                        <td><?= $history['date'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
</main>