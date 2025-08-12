<?php
// System statistics data (in a real application, this would come from your database/models)
$stats = [
    ['title' => 'Total Customers', 'value' => '2,847', 'icon' => 'fa-solid fa-users', 'change' => '+12%'],
    ['title' => 'Recycling Companies', 'value' => '156', 'icon' => 'fa-solid fa-building', 'change' => '+3%'],
    ['title' => 'Active Collectors', 'value' => '89', 'icon' => 'fa-solid fa-truck', 'change' => '+5%'],
    ['title' => 'Active Pickups', 'value' => '234', 'icon' => 'fa-solid fa-box', 'change' => '+18%'],
    ['title' => 'Pending Bids', 'value' => '67', 'icon' => 'fa-solid fa-gavel', 'change' => '+8%'],
    ['title' => 'Monthly Revenue', 'value' => '$45,230', 'icon' => 'fa-solid fa-chart-line', 'change' => '+15%'],
];

$recentActivity = [
    ['action' => 'New pickup scheduled', 'detail' => 'John Doe', 'time' => '2 minutes ago'],
    ['action' => 'Bid won by GreenTech Co.', 'detail' => 'Lot #1234', 'time' => '5 minutes ago'],
    ['action' => 'Collector assigned', 'detail' => 'Pickup #5678', 'time' => '10 minutes ago'],
    ['action' => 'Payment processed', 'detail' => '$125.50', 'time' => '15 minutes ago'],
    ['action' => 'New company registered', 'detail' => 'EcoRecycle Ltd.', 'time' => '1 hour ago'],
];
?>

<div>
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">
                        <?= htmlspecialchars($stat['title']) ?>
                    </h3>
                    <div class="feature-card__icon">
                        <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                    </div>
                </div>
                <p class="feature-card__body">
                    <?= htmlspecialchars($stat['value']) ?>
                </p>
                <div class="feature-card__footer">
                    <div class="tag success">
                        <?= htmlspecialchars($stat['change']) ?>
                    </div>
                    <span>
                        from last month
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Activity & System Health Cards -->
    <div class="cards-grid">
        <!-- Recent Activity Card -->
        <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">Recent Activity</h3>
                <p class="activity-card__description">Latest system activities and updates</p>
            </div>
            <div class="activity-card__content">
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-item__content">
                            <p class="activity-item__title"><?= htmlspecialchars($activity['action']) ?></p>
                            <p class="activity-item__subtitle"><?= htmlspecialchars($activity['detail']) ?></p>
                        </div>
                        <p class="activity-item__time"><?= htmlspecialchars($activity['time']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- System Health Card -->
        <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">System Health</h3>
                <p class="activity-card__description">Current system status and performance</p>
            </div>
            <div class="activity-card__content">
                <div class="status-item">
                    <span class="status-item__label">Server Status</span>
                    <div class="tag online">Online</div>
                </div>
                <div class="status-item">
                    <span class="status-item__label">Database</span>
                    <div class="tag healthy">Healthy</div>
                </div>
                <div class="status-item">
                    <span class="status-item__label">Payment Gateway</span>
                    <div class="tag connected">Connected</div>
                </div>
                <div class="status-item">
                    <span class="status-item__label">Notification Service</span>
                    <div class="tag warning">Warning</div>
                </div>
            </div>
        </div>
    </div>
</div>