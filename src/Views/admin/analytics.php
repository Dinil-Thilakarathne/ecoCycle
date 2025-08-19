<?php
// Analytics data (in a real application, this would come from your database/models)
$wasteCategories = [
    ['category' => 'Plastic', 'volume' => 2500, 'percentage' => 35],
    ['category' => 'Paper', 'volume' => 1800, 'percentage' => 25],
    ['category' => 'Glass', 'volume' => 1200, 'percentage' => 17],
    ['category' => 'Metal', 'volume' => 900, 'percentage' => 13],
    ['category' => 'Cardboard', 'volume' => 700, 'percentage' => 10],
];

$areaStats = [
    ['area' => 'Downtown', 'collections' => 145, 'revenue' => 2850],
    ['area' => 'Suburbs', 'collections' => 89, 'revenue' => 1780],
    ['area' => 'Industrial', 'collections' => 67, 'revenue' => 3400],
    ['area' => 'Residential', 'collections' => 234, 'revenue' => 4680],
];

// Calculate totals
$totalWasteCollected = array_sum(array_column($wasteCategories, 'volume'));
$totalRevenue = array_sum(array_column($areaStats, 'revenue'));
$activeAreas = count($areaStats);
$avgCollectionPerDay = round($totalWasteCollected / 30); // Assuming 30 days

// Financial summary
$customerPayouts = 3240;
$netProfit = $totalRevenue - $customerPayouts;

// Color mapping for waste categories
$categoryColors = [
    'Plastic' => '#3b82f6',    // Blue
    'Paper' => '#10b981',      // Emerald
    'Glass' => '#8b5cf6',      // Violet
    'Metal' => '#f59e0b',      // Amber
    'Cardboard' => '#ef4444',  // Red
];
?>

<div>
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Analytics & Reports</h2>
            <p class="page-header__description">View system analytics and generate reports</p>
        </div>
        <div style="display: flex; gap: var(--space-2);">
            <button class="btn btn-outline" onclick="exportReport('CSV')">
                <i class="fa-solid fa-download"></i>
                Export CSV
            </button>
            <button class="btn btn-outline" onclick="exportReport('PDF')">
                <i class="fa-solid fa-download"></i>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Main Statistics Grid -->
    <div class="stats-grid">
        <!-- Total Waste Collected -->
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Total Waste Collected</h3>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-box" style="color: var(--neutral-600);"></i>
                </div>
            </div>
            <p class="feature-card__body">
                <?= number_format($totalWasteCollected) ?> kg
            </p>
            <div class="feature-card__footer">
                <span class="tag success" style="font-size: var(--text-xs);">+12%</span>
                <span style="font-size: var(--text-xs); color: var(--neutral-600); margin-left: var(--space-2);">
                    from last month
                </span>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Total Revenue</h3>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-arrow-trend-up" style="color: var(--neutral-600);"></i>
                </div>
            </div>
            <p class="feature-card__body">
                $<?= number_format($totalRevenue) ?>
            </p>
            <div class="feature-card__footer">
                <span class="tag success" style="font-size: var(--text-xs);">+8%</span>
                <span style="font-size: var(--text-xs); color: var(--neutral-600); margin-left: var(--space-2);">
                    from last month
                </span>
            </div>
        </div>

        <!-- Active Areas -->
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Active Areas</h3>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-location-dot" style="color: var(--neutral-600);"></i>
                </div>
            </div>
            <p class="feature-card__body">
                <?= $activeAreas ?>
            </p>
            <div class="feature-card__footer">
                <span style="font-size: var(--text-xs); color: var(--neutral-600);">
                    Collection zones
                </span>
            </div>
        </div>

        <!-- Avg Collection Per Day -->
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Avg. Collection/Day</h3>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-chart-column" style="color: var(--neutral-600);"></i>
                </div>
            </div>
            <p class="feature-card__body">
                <?= number_format($avgCollectionPerDay) ?> kg
            </p>
            <div class="feature-card__footer">
                <span style="font-size: var(--text-xs); color: var(--neutral-600);">
                    Daily average
                </span>
            </div>
        </div>
    </div>

    <!-- Secondary Stats Grid -->
    <div
        style="margin-top: var(--space-8); display: grid; gap: var(--space-6); grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">
        <!-- Waste Volume by Category -->
        <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-box" style="margin-right: var(--space-2);"></i>
                    Waste Volume by Category
                </h3>
                <p class="activity-card__description">Breakdown of collected waste by material type</p>
            </div>
            <div class="activity-card__content">
                <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <?php foreach ($wasteCategories as $item): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: var(--space-3);">
                                <div
                                    style="width: 12px; height: 12px; border-radius: 50%; background-color: <?= $categoryColors[$item['category']] ?>;">
                                </div>
                                <span class="font-medium"><?= htmlspecialchars($item['category']) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: var(--space-2);">
                                <span style="font-size: var(--text-sm); color: var(--neutral-600);">
                                    <?= number_format($item['volume']) ?> kg
                                </span>
                                <div class="tag secondary"><?= $item['percentage'] ?>%</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Area-wise Collection Stats -->
        <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-location-dot" style="margin-right: var(--space-2);"></i>
                    Area-wise Collection Stats
                </h3>
                <p class="activity-card__description">Collection performance by geographic area</p>
            </div>
            <div class="activity-card__content">
                <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <?php foreach ($areaStats as $area): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($area['area']) ?></p>
                                <p style="font-size: var(--text-sm); color: var(--neutral-600);">
                                    <?= number_format($area['collections']) ?> collections
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <p class="font-medium">$<?= number_format($area['revenue']) ?></p>
                                <p style="font-size: var(--text-sm); color: var(--neutral-600);">Revenue</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="activity-card" style="margin-top: var(--space-8);">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-chart-column" style="margin-right: var(--space-2);"></i>
                Financial Summary
            </h3>
            <p class="activity-card__description">Monthly financial performance overview</p>
        </div>
        <div class="activity-card__content">
            <div
                style="display: grid; gap: var(--space-4); grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div style="text-align: center;">
                    <p style="font-size: var(--text-2xl); font-weight: var(--font-weight-bold); color: #16a34a;">
                        $<?= number_format($totalRevenue) ?>
                    </p>
                    <p style="font-size: var(--text-sm); color: var(--neutral-600);">Total Revenue</p>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: var(--text-2xl); font-weight: var(--font-weight-bold); color: #dc2626;">
                        $<?= number_format($customerPayouts) ?>
                    </p>
                    <p style="font-size: var(--text-sm); color: var(--neutral-600);">Customer Payouts</p>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: var(--text-2xl); font-weight: var(--font-weight-bold); color: #2563eb;">
                        $<?= number_format($netProfit) ?>
                    </p>
                    <p style="font-size: var(--text-sm); color: var(--neutral-600);">Net Profit</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function exportReport(format) {
        // Placeholder for export functionality
        console.log('Exporting report in ' + format + ' format');
        alert('Export functionality would be implemented here for ' + format + ' format');
    }
</script>