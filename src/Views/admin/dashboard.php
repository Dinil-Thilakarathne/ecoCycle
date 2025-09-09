<?php
// System statistics data (in a real application, this would come from your database/models)
$stats = [
    ['title' => 'Total Customers', 'value' => '2,847', 'icon' => 'fa-solid fa-users', 'change' => '+12%'],
    ['title' => 'Recycling Companies', 'value' => '156', 'icon' => 'fa-solid fa-building', 'change' => '+3%'],
    ['title' => 'Active Collectors', 'value' => '89', 'icon' => 'fa-solid fa-truck', 'change' => '+5%'],
    ['title' => 'Active Pickups', 'value' => '234', 'icon' => 'fa-solid fa-box', 'change' => '+18%'],
    ['title' => 'Pending Bids', 'value' => '67', 'icon' => 'fa-solid fa-gavel', 'change' => '+8%'],
    ['title' => 'Monthly Revenue', 'value' => 'Rs 45,230', 'icon' => 'fa-solid fa-chart-line', 'change' => '+15%'],
];

$recentActivity = [
    ['action' => 'New pickup scheduled', 'detail' => 'John Doe', 'time' => '2 minutes ago'],
    ['action' => 'Bid won by GreenTech Co.', 'detail' => 'Lot #1234', 'time' => '5 minutes ago'],
    ['action' => 'Collector assigned', 'detail' => 'Pickup #5678', 'time' => '10 minutes ago'],
    ['action' => 'Payment processed', 'detail' => 'Rs 125.50', 'time' => '15 minutes ago'],
    ['action' => 'New company registered', 'detail' => 'EcoRecycle Ltd.', 'time' => '1 hour ago'],
];
?>

<div>
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
            <feature-card unwrap title="<?= htmlspecialchars($stat['title']) ?>"
                value="<?= htmlspecialchars($stat['value']) ?>" icon="<?= htmlspecialchars($stat['icon']) ?>"
                change="<?= htmlspecialchars($stat['change']) ?>" period="from last month" <?php if (strpos($stat['change'], '-') === 0): ?>change-negative<?php endif; ?>></feature-card>
        <?php endforeach; ?>
    </div>

    <!-- Activity & System Health Cards -->
    <div class="cards-grid">
        <activity-card title="Recent Activity" description="Latest system activities and updates">
            <?php foreach ($recentActivity as $activity): ?>
                <activity-item action="<?= htmlspecialchars($activity['action']) ?>"
                    detail="<?= htmlspecialchars($activity['detail']) ?>"
                    time="<?= htmlspecialchars($activity['time']) ?>"></activity-item>
            <?php endforeach; ?>
        </activity-card>

        <activity-card title="System Health" description="Current system status and performance">
            <status-item label="Server Status" state="Online" state-class="online"></status-item>
            <status-item label="Database" state="Healthy" state-class="healthy"></status-item>
            <status-item label="Payment Gateway" state="Connected" state-class="connected"></status-item>
            <status-item label="Notification Service" state="Warning" state-class="warning"></status-item>
        </activity-card>
    </div>
</div>