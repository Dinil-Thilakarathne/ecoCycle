<?php
// Simulating database data
$acceptedPurchases = [
    [
        "type" => "Plastic Bottles",
        "id" => "PUR001",
        "amount" => "2,500 kg",
        "price" => "Rs.1,250",
        "pickup_date" => "2024-01-20",
        "status" => "Confirmed"
    ],
    [
        "type" => "Paper & Cardboard",
        "id" => "PUR002",
        "amount" => "1,800 kg",
        "price" => "Rs.900",
        "pickup_date" => "2024-01-18",
        "status" => "In-Transit"
    ],
    [
        "type" => "Metal Cans",
        "id" => "PUR003",
        "amount" => "1,200 kg",
        "price" => "Rs.600",
        "pickup_date" => "2024-01-15",
        "status" => "Delivered"
    ]
];

$purchaseSummary = [
    "total" => "$3,250",
    "active_orders" => 8,
    "completed" => 15,
    "recent" => [
        ["msg" => "Payment processed", "detail" => "Metal Cans - Rs.600"],
        ["msg" => "Pickup scheduled", "detail" => "Plastic Bottles - Jan 20"]
    ]
];

$purchaseHistory = [
    ["id" => "PUR004", "type" => "Glass Bottles", "amount" => "800 kg", "price" => "Rs.400", "delivery_status" => "Completed", "date" => "2024-01-10"],
    ["id" => "PUR005", "type" => "Mixed Recyclables", "amount" => "3,000 kg", "price" => "Rs.1,500", "delivery_status" => "Completed", "date" => "2024-01-05"]
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
                <h2>Accepted Purchases</h2>
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
                <h3>Purchase Summary</h3>
                <div class="total"><?= $purchaseSummary['total'] ?></div>
                <p>Total Purchases This Month</p>
                <div class="summary-box">
                    <div class="box blue"><?= $purchaseSummary['active_orders'] ?> <span>Active Orders</span></div>
                    <div class="box purple"><?= $purchaseSummary['completed'] ?> <span>Completed</span></div>
                </div>
                <div>
                <h3>Recent Activity</h3>
                <ul class = "activity-list">
                    <?php foreach($purchaseSummary['recent'] as $act): ?>
                        <li><?= $act['msg'] ?> - <?= $act['detail'] ?></li>
                    <?php endforeach; ?>
                </ul>
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