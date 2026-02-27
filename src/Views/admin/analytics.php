<?php
/** @var array $analyticsSummary */
/** @var array $wasteCategories */
/** @var array $chartData */
/** @var array $pickupStatusBreakdown */
/** @var array $topCollectors */

// Extract summary data for easier access
$totalWasteCollected = $analyticsSummary['totalWasteCollected'] ?? 0;
$avgCollectionPerDay = $analyticsSummary['avgCollectionPerDay'] ?? 0;
$totalRevenue = $analyticsSummary['totalRevenue'] ?? 0;
$customerPayouts = $analyticsSummary['customerPayouts'] ?? 0;
$netProfit = $analyticsSummary['netProfit'] ?? 0;
$totalPickups = $analyticsSummary['totalPickups'] ?? 0;
$completedPickups = $analyticsSummary['completedPickups'] ?? 0;

// Prepare JSON data for charts from controller data
$chartShortLabelsJson = json_encode($chartData['shortLabels'] ?? []);
$revenueJson = json_encode($chartData['revenueSeries'] ?? []);
$payoutsJson = json_encode($chartData['payoutSeries'] ?? []);
$pickupSeriesJson = json_encode($chartData['pickupSeries'] ?? []);

// Pickup status chart data
$statusLabels = json_encode(array_map(fn($r) => ucfirst($r['status']), $pickupStatusBreakdown ?? []));
$statusCounts = json_encode(array_map(fn($r) => $r['count'], $pickupStatusBreakdown ?? []));
$statusColors = json_encode(['#16a34a', '#2563eb', '#f59e0b', '#dc2626', '#8b5cf6', '#6b7280']);
?>

<div>
    <!-- Page Header (component) -->
    <page-header title="Analytics &amp; Reports" description="View system analytics and generate reports">
        <div data-header-action style="display: flex; gap: var(--space-2);">
            <a class="btn btn-outline" href="?format=csv&export=1">
                <i class="fa-solid fa-download"></i>
                Export CSV
            </a>
        </div>
    </page-header>

    <!-- Main Statistics Grid -->
    <?php
    $statCards = [
        [
            'title' => 'Total Waste Collected',
            'value' => number_format($totalWasteCollected) . ' kg',
            'icon' => 'fa-solid fa-box',
            'change' => '',
            'period' => 'All completed pickups',
            'negative' => false,
        ],
        [
            'title' => 'Total Revenue',
            'value' => 'Rs ' . number_format($totalRevenue, 2),
            'icon' => 'fa-solid fa-arrow-trend-up',
            'change' => '',
            'period' => 'From completed payments',
            'negative' => false,
        ],
        [
            'title' => 'Total Pickups',
            'value' => number_format($totalPickups),
            'icon' => 'fa-solid fa-truck',
            'change' => '',
            'period' => 'All time',
            'negative' => false,
        ]
    ];
    ?>
    <div class="stats-grid">
        <?php foreach ($statCards as $card): ?>
            <feature-card unwrap title="<?= htmlspecialchars($card['title']) ?>"
                value="<?= htmlspecialchars($card['value']) ?>" icon="<?= htmlspecialchars($card['icon']) ?>"
                period="<?= htmlspecialchars($card['period']) ?>" <?php if (strlen(trim($card['change']))): ?>change="<?= htmlspecialchars($card['change']) ?>" <?php endif; ?>     <?php if ($card['negative']): ?>change-negative<?php endif; ?>></feature-card>
        <?php endforeach; ?>
    </div>

    <!-- Charts Row 1: Revenue & Payouts + Waste Volume -->
    <div
        style="margin-top: var(--space-8); display: grid; gap: var(--space-6); grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">

        <!-- Revenue & Payouts Chart -->
        <div class="activity-card pc-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-chart-line" style="margin-right: var(--space-2);"></i>
                    Revenue &amp; Payouts (30 days)
                </h3>
                <p class="activity-card__description">Daily totals for incoming payments and outgoing payouts</p>
            </div>
            <div class="activity-card__content">
                <div class="pc-card" style="padding:0;">
                    <canvas id="revenueChart" style="width:100%; max-height:380px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Waste Volume by Category -->
        <div class="activity-card pc-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-box" style="margin-right: var(--space-2);"></i>
                    Waste Volume by Category
                </h3>
                <p class="activity-card__description">Breakdown of collected waste by material type</p>
            </div>
            <div class="activity-card__content">
                <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <div class="pc-card" style="padding:0;">
                        <canvas id="wasteVolumeChart" style="width:100%; max-height:360px;"></canvas>
                    </div>
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
    </div>

    <!-- Charts Row 2: Pickup Trends + Status Breakdown -->
    <div
        style="margin-top: var(--space-6); display: grid; gap: var(--space-6); grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">

        <!-- Pickup Trends -->
        <div class="activity-card pc-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-truck" style="margin-right: var(--space-2);"></i>
                    Pickup Trends (30 days)
                </h3>
                <p class="activity-card__description">Number of pickup requests created each day</p>
            </div>
            <div class="activity-card__content">
                <div class="pc-card" style="padding:0;">
                    <canvas id="pickupTrendsChart" style="width:100%; max-height:300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Pickup Status Breakdown -->
        <div class="activity-card pc-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-circle-half-stroke" style="margin-right: var(--space-2);"></i>
                    Pickup Status Breakdown
                </h3>
                <p class="activity-card__description">Current distribution of pickup request statuses</p>
            </div>
            <div class="activity-card__content">
                <div style="display: flex; align-items: center; gap: var(--space-6); flex-wrap: wrap;">
                    <div class="pc-card" style="padding:0; flex: 0 0 220px;">
                        <canvas id="pickupStatusChart" style="width:220px; height:220px; max-height:220px;"></canvas>
                    </div>
                    <?php if (!empty($pickupStatusBreakdown)): ?>
                        <div style="display: flex; flex-direction: column; gap: var(--space-3); flex: 1;">
                            <?php
                            $colourPalette = ['#16a34a', '#2563eb', '#f59e0b', '#dc2626', '#8b5cf6', '#6b7280'];
                            foreach ($pickupStatusBreakdown as $idx => $row):
                                $colour = $colourPalette[$idx % count($colourPalette)];
                                ?>
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                                        <div style="width:10px; height:10px; border-radius:50%; background:<?= $colour ?>;">
                                        </div>
                                        <span><?= htmlspecialchars(ucfirst($row['status'])) ?></span>
                                    </div>
                                    <div class="tag secondary"><?= number_format($row['count']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--neutral-500); font-size: var(--text-sm);">No pickup data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Collectors Table -->
    <div class="activity-card" style="margin-top: var(--space-6);">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-medal" style="margin-right: var(--space-2);"></i>
                Top Collectors
            </h3>
            <p class="activity-card__description">Ranked by number of completed pickups</p>
        </div>
        <div class="activity-card__content">
            <?php if (!empty($topCollectors)): ?>
                <table style="width:100%; border-collapse: collapse; font-size: var(--text-sm);">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--neutral-200); text-align: left;">
                            <th
                                style="padding: var(--space-2) var(--space-4); color: var(--neutral-500); font-weight: 600;">
                                #</th>
                            <th
                                style="padding: var(--space-2) var(--space-4); color: var(--neutral-500); font-weight: 600;">
                                Collector</th>
                            <th
                                style="padding: var(--space-2) var(--space-4); color: var(--neutral-500); font-weight: 600; text-align:right;">
                                Completed Pickups</th>
                            <th
                                style="padding: var(--space-2) var(--space-4); color: var(--neutral-500); font-weight: 600; text-align:right;">
                                Total Weight (kg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCollectors as $rank => $collector): ?>
                            <tr style="border-bottom: 1px solid var(--neutral-100);">
                                <td
                                    style="padding: var(--space-3) var(--space-4); color: var(--neutral-400); font-weight: 700;">
                                    <?= $rank + 1 ?>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4); font-weight: 500;">
                                    <?= htmlspecialchars($collector['name']) ?>
                                </td>
                                <td
                                    style="padding: var(--space-3) var(--space-4); text-align:right; font-weight: 600; color: var(--green-700);">
                                    <?= number_format($collector['totalPickups']) ?>
                                </td>
                                <td
                                    style="padding: var(--space-3) var(--space-4); text-align:right; color: var(--neutral-600);">
                                    <?= number_format($collector['totalWeight'], 1) ?> kg
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p
                    style="color: var(--neutral-500); font-size: var(--text-sm); text-align: center; padding: var(--space-8);">
                    No completed pickups yet.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Financial Summary -->
    <?php
    $financialStats = [
        ['value' => 'Rs ' . number_format($totalRevenue, 2), 'period' => 'Total Revenue'],
        ['value' => 'Rs ' . number_format($customerPayouts, 2), 'period' => 'Customer Payouts'],
        ['value' => 'Rs ' . number_format($netProfit, 2), 'period' => 'Net Profit'],
    ];
    ?>
    <div class="activity-card" style="margin-top: var(--space-8);">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-chart-column" style="margin-right: var(--space-2);"></i>
                Financial Summary
            </h3>
            <p class="activity-card__description">All-time financial performance overview</p>
        </div>
        <div class="activity-card__content">
            <div class="financial-grid"
                style="display: grid; gap: var(--space-4); grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <?php foreach ($financialStats as $stat): ?>
                    <feature-card unwrap value="<?= htmlspecialchars($stat['value']) ?>"
                        period="<?= htmlspecialchars($stat['period']) ?>">
                    </feature-card>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHARTS ===== -->

<!-- Waste Volume Chart -->
<script>
    const wasteLabels = <?php echo json_encode(array_map(fn($i) => $i['category'], $wasteCategories)); ?>;
    const wasteData = <?php echo json_encode(array_map(fn($i) => $i['volume'], $wasteCategories)); ?>;
    const wasteColors = <?php echo json_encode(array_map(fn($i) => material_color(lcfirst($i['category'])), $wasteCategories)); ?>;

    (function renderWasteVolumeChart() {
        const el = document.getElementById('wasteVolumeChart');
        if (!el) return;
        new Chart(el.getContext('2d'), {
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
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Kilograms (kg)' } },
                    x: { title: { display: true, text: 'Material Type' } }
                }
            }
        });
    })();
</script>

<!-- Revenue & Payouts Chart -->
<script>
    const revenueLabels = <?= $chartShortLabelsJson ?>;
    const revenueSeries = <?= $revenueJson ?>;
    const payoutsSeries = <?= $payoutsJson ?>;

    (function renderRevenueChart() {
        const el = document.getElementById('revenueChart');
        if (!el) return;
        new Chart(el.getContext('2d'), {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [
                    {
                        label: 'Revenue (Rs)',
                        data: revenueSeries,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,0.08)',
                        tension: 0.3, fill: true, pointRadius: 2,
                    },
                    {
                        label: 'Payouts (Rs)',
                        data: payoutsSeries,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.08)',
                        tension: 0.3, fill: true, pointRadius: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Amount (Rs)' } },
                    x: { ticks: { maxRotation: 0, minRotation: 0 }, title: { display: true, text: 'Date' } }
                }
            }
        });
    })();
</script>

<!-- Pickup Trends Chart -->
<script>
    const pickupLabels = <?= $chartShortLabelsJson ?>;
    const pickupSeries = <?= $pickupSeriesJson ?>;

    (function renderPickupTrendsChart() {
        const el = document.getElementById('pickupTrendsChart');
        if (!el) return;
        new Chart(el.getContext('2d'), {
            type: 'line',
            data: {
                labels: pickupLabels,
                datasets: [{
                    label: 'Pickups',
                    data: pickupSeries,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.08)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Pickups' } },
                    x: { ticks: { maxRotation: 0, minRotation: 0 }, title: { display: true, text: 'Date' } }
                }
            }
        });
    })();
</script>

<!-- Pickup Status Doughnut Chart -->
<script>
    const statusLabels = <?= $statusLabels ?>;
    const statusCounts = <?= $statusCounts ?>;
    const statusColors = <?= $statusColors ?>;

    (function renderPickupStatusChart() {
        const el = document.getElementById('pickupStatusChart');
        if (!el) return;
        new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: statusColors.slice(0, statusLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: false,
                cutout: '65%',
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } }
            }
        });
    })();
</script>