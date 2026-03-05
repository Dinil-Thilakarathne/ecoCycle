<?php
$stats = $stats ?? [];
$recentActivity = $recentActivity ?? [];
consoleLog($wasteCategories)
    ?>

<div>
    <div class="page-header">
        <div class="page-header__content" style="display: flex; align-items: center; gap: 1.25rem;">
            <img src="<?= !empty($user['profile_image_path']) ? htmlspecialchars($user['profile_image_path']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['name'] ?? 'Admin') . '&background=random' ?>"
                alt="Profile" class="customer-dashboard-avatar"
                style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-500);">
            <div>
                <h1 class="page-header__title">
                    Welcome,
                    <?= htmlspecialchars(explode(' ', $user['name'] ?? 'Admin')[0]) ?>!
                </h1>
                <p class="page-header__description">
                    Your system administration dashboard
                </p>
            </div>
        </div>
    </div>
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

        <activity-card title="Current Waste Prices" description="Latest market prices per unit">
            <?php if (empty($wasteCategories)): ?>
                <p style="color: var(--neutral-500); font-size: var(--text-sm);">No waste categories found.</p>
            <?php else: ?>
                <?php foreach ($wasteCategories as $category): ?>
                    <div style="display: flex; gap: .5rem; align-items: center;">
                        <span
                            style="
                        display:inline;width:12px;height:12px;border-radius:50%;background-color:<?= htmlspecialchars($category['color'] ?? '#ccc') ?>;"></span>
                        <status-item unwrap label="<?= htmlspecialchars($category['name']) ?>"
                            state="Rs <?= number_format($category['pricePerUnit'], 2) ?>"
                            state-class="text-primary"></status-item>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </activity-card>
    </div>
</div>