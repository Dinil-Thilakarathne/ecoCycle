<?php
// Centralized dummy data usage
$dummy = require base_path('config/dummy.php');
$wasteCategories = getWasteCategories();
$payments = $dummy['payments'];

// Compute revenue (completed company payments) and payouts (completed customer payouts)
$totalRevenue = 0; // Rs
$customerPayouts = 0; // Rs
foreach ($payments as $p) {
    if ($p['type'] === 'payment' && $p['status'] === 'completed') {
        $totalRevenue += $p['amount'];
    } elseif ($p['type'] === 'payout' && $p['status'] === 'completed') {
        $customerPayouts += $p['amount'];
    }
}
$netProfit = $totalRevenue - $customerPayouts;

$totalWasteCollected = array_sum(array_column($wasteCategories, 'volume'));
$avgCollectionPerDay = $totalWasteCollected > 0 ? round($totalWasteCollected / 30) : 0;
?>

<div>
    <!-- Page Header (component) -->
    <page-header title="Analytics &amp; Reports" description="View system analytics and generate reports">
        <div data-header-action style="display: flex; gap: var(--space-2);">
            <button class="btn btn-outline" onclick="exportReport('CSV')">
                <i class="fa-solid fa-download"></i>
                Export CSV
            </button>
            <button class="btn btn-outline" onclick="exportReport('PDF')">
                <i class="fa-solid fa-download"></i>
                Export PDF
            </button>
        </div>
    </page-header>

    <!-- Main Statistics Grid (using feature-card component) -->
    <?php
    $statCards = [
        [
            'title' => 'Total Waste Collected',
            'value' => number_format($totalWasteCollected) . ' kg',
            'icon' => 'fa-solid fa-box',
            'change' => '+12%',
            'period' => 'from last month',
            'negative' => false,
        ],
        [
            'title' => 'Total Revenue',
            'value' => 'Rs ' . number_format($totalRevenue, 2),
            'icon' => 'fa-solid fa-arrow-trend-up',
            'change' => '+8%',
            'period' => 'from last month',
            'negative' => false,
        ],
        [
            'title' => 'Avg. Collection/Day',
            'value' => number_format($avgCollectionPerDay) . ' kg',
            'icon' => 'fa-solid fa-chart-column',
            'change' => '',
            'period' => 'Daily average',
            'negative' => false,
        ],
    ];
    ?>
    <div class="stats-grid">
        <?php foreach ($statCards as $card): ?>
            <feature-card unwrap title="<?= htmlspecialchars($card['title']) ?>"
                value="<?= htmlspecialchars($card['value']) ?>" icon="<?= htmlspecialchars($card['icon']) ?>"
                period="<?= htmlspecialchars($card['period']) ?>" <?php if (strlen(trim($card['change']))): ?>change="<?= htmlspecialchars($card['change']) ?>" <?php endif; ?>     <?php if ($card['negative']): ?>change-negative<?php endif; ?>></feature-card>
        <?php endforeach; ?>
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
                    <!-- Chart: Waste Volume by Category -->
                    <div class="pc-card" style="padding:0;">
                        <canvas id="wasteVolumeChart" style="width:100%; max-height:360px;"></canvas>
                    </div>

                    <!-- Fallback textual list (kept for accessibility) -->
                    <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                        <?php foreach ($wasteCategories as $item): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: var(--space-3);">
                                    <div
                                        style="width: 12px; height: 12px; border-radius: 50%; background-color: <?= htmlspecialchars(material_color(lcfirst($item['category']))) ?>;">
                                    </div>
                                    <span class="font-medium"><?= htmlspecialchars($item['category']) ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: var(--space-2);">
                                    <span style="font-size: var(--text-sm); color: var(--neutral-600);">
                                        <?= number_format($item['volume']) ?> kg
                                    </span>
                                    <div class="tag secondary"><?= htmlspecialchars($item['percentage']) ?>%</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
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
                    <!-- Chart: Waste Volume by Category -->
                    <div class="pc-card" style="padding:0;">
                        <canvas id="wasteVolumeChart" style="width:100%; max-height:360px;"></canvas>
                    </div>

                    <!-- Fallback textual list (kept for accessibility) -->
                    <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                        <?php foreach ($wasteCategories as $item): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: var(--space-3);">
                                    <div
                                        style="width: 12px; height: 12px; border-radius: 50%; background-color: <?= htmlspecialchars(material_color(lcfirst($item['category']))) ?>;">
                                    </div>
                                    <span class="font-medium"><?= htmlspecialchars($item['category']) ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: var(--space-2);">
                                    <span style="font-size: var(--text-sm); color: var(--neutral-600);">
                                        <?= number_format($item['volume']) ?> kg
                                    </span>
                                    <div class="tag secondary"><?= htmlspecialchars($item['percentage']) ?>%</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Area-wise Collection Stats removed per request -->
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
                        Rs <?= number_format($totalRevenue, 2) ?>
                    </p>
                    <p style="font-size: var(--text-sm); color: var(--neutral-600);">Total Revenue</p>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: var(--text-2xl); font-weight: var(--font-weight-bold); color: #dc2626;">
                        Rs <?= number_format($customerPayouts, 2) ?>
                    </p>
                    <p style="font-size: var(--text-sm); color: var(--neutral-600);">Customer Payouts</p>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: var(--text-2xl); font-weight: var(--font-weight-bold); color: #2563eb;">
                        Rs <?= number_format($netProfit, 2) ?>
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

<!-- Waste Volume Chart script -->
<script>
    // Prepare data from PHP
    const wasteLabels = <?php echo json_encode(array_map(fn($i) => $i['category'], $wasteCategories)); ?>;
    const wasteData = <?php echo json_encode(array_map(fn($i) => $i['volume'], $wasteCategories)); ?>;
    const wasteColors = <?php echo json_encode(array_map(fn($i) => material_color(lcfirst($i['category'])), $wasteCategories)); ?>;

    // Render Chart.js bar chart
    (function renderWasteVolumeChart() {
        const el = document.getElementById('wasteVolumeChart');
        if (!el) return;
        const ctx = el.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: wasteLabels,
                datasets: [{
                    label: 'Waste Volume (kg)',
                    data: wasteData,
                    backgroundColor: wasteColors,
                    borderRadius: 6,
                    barThickness: 'flex'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Kilograms (kg)' }
                    },
                    x: {
                        title: { display: true, text: 'Material Type' }
                    }
                }
            }
        });
    })();
</script>