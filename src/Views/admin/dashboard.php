<?php
$stats = $stats ?? [];
$recentActivity = $recentActivity ?? [];
?>

<div>
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <?php if (empty($stats)): ?>
            <div class="empty-state">No statistics available yet.</div>
        <?php else: ?>
            <?php foreach ($stats as $stat): ?>
                <feature-card unwrap title="<?= htmlspecialchars($stat['title']) ?>"
                    value="<?= htmlspecialchars($stat['value']) ?>" icon="<?= htmlspecialchars($stat['icon']) ?>"
                    change="<?= htmlspecialchars($stat['change']) ?>" <?php if (strpos($stat['change'], '-') === 0): ?>change-negative<?php endif; ?>></feature-card>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Activity & System Health Cards -->
    <div class="cards-grid">
        <activity-card title="Recent Activity" description="Latest system activities and updates">
            <?php if (empty($recentActivity)): ?>
                <p style="color: var(--neutral-500); font-size: var(--text-sm);">No recent activity recorded.</p>
            <?php else: ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <activity-item action="<?= htmlspecialchars($activity['action']) ?>"
                        detail="<?= htmlspecialchars($activity['detail']) ?>"
                        time="<?= htmlspecialchars($activity['time']) ?>"></activity-item>
                <?php endforeach; ?>
            <?php endif; ?>
        </activity-card>

        <activity-card title="System Health" description="Current system status and performance">
            <status-item label="Server Status" state="Online" state-class="online"></status-item>
            <status-item label="Database" state="Healthy" state-class="healthy"></status-item>
            <status-item label="Payment Gateway" state="Connected" state-class="connected"></status-item>
            <status-item label="Notification Service" state="Warning" state-class="warning"></status-item>
        </activity-card>
    </div>
</div>