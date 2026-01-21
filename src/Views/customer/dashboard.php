<?php

use function htmlspecialchars as e;

$initialPickupRequests = $pickupRequests ?? $recentPickups ?? [];
$pickupRequests = is_array($initialPickupRequests) ? array_values($initialPickupRequests) : [];
// Remove cancelled requests from the dashboard so they are never displayed here
$pickupRequests = array_values(array_filter($pickupRequests, static function ($r) {
    $status = strtolower((string) ($r['status'] ?? ''));
    return $status !== 'cancelled';
}));

$pendingCount = 0;
$scheduledCount = 0;
$completedCount = 0;
$totalCount = 0;

foreach ($pickupRequests as $request) {
    $status = strtolower((string) ($request['status'] ?? ''));
    if ($status === 'pending') {
        $pendingCount++;
    }
    if (in_array($status, ['assigned', 'confirmed'], true)) {
        $scheduledCount++;
    }
    if ($status === 'completed') {
        $completedCount++;
    }
    $totalCount++;
}

// Get only the top 5 most recent pickups for the dashboard widget
$recentPickupsWidget = array_slice(
    array_values(array_filter($pickupRequests, static function ($r) {
        $status = strtolower((string) ($r['status'] ?? ''));
        return $status !== 'cancelled';
    })),
    0,
    5
);

if (!function_exists('customer_pickup_status_class')) {
    function customer_pickup_status_class(string $status): string
    {
        $normalized = strtolower($status);
        switch ($normalized) {
            case 'pending':
                return 'pending';
            case 'assigned':
            case 'confirmed':
                return 'assigned';
            case 'completed':
                return 'completed';
            case 'cancelled':
                return 'warning';
            default:
                return 'secondary';
        }
    }
}

if (!function_exists('customer_pickup_format_datetime')) {
    function customer_pickup_format_datetime(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        return date('M d, Y', $timestamp);
    }
}

$customerStats = [
    [
        'title' => 'Total Pickups',
        'value' => $totalCount,
        'icon' => 'fa-solid fa-truck',
        'subtitle' => 'All time',
    ],
    [
        'title' => 'Total Income',
        'value' => 'Rs 0.00',
        'icon' => 'fa-solid fa-wallet',
        'subtitle' => 'Earnings',
    ],
    [
        'title' => 'Total Weight',
        'value' => '0 kg',
        'icon' => 'fa-solid fa-weight',
        'subtitle' => 'Waste collected',
    ],
];
?>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-page" style="background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); min-height: 100vh; padding: 2rem; display: flex; flex-direction: column;">
        
        <!-- Welcome Section with CTA -->
        <div class="welcome-section" style="margin-bottom: 2.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem; flex-wrap: wrap;">
                <div>
                    <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1rem;">
                        <?php
                        $profileData = $userProfile ?? [];
                        $firstName = $profileData['firstName'] ?? ($user['name'] ?? 'Customer');
                        $firstName = $firstName !== '' ? $firstName : ($user['name'] ?? 'Customer');
                        $imagePath = $profileData['profileImage'] ?? null;
                        $profilePic = $imagePath ? asset($imagePath) : asset('assets/logo-icon.png');
                        ?>
                        <img src="<?= htmlspecialchars($profilePic) ?>" class="avatar" style="width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(28, 227, 106, 0.15);">
                        <div>
                            <h1 style="margin: 0; color: #111827; font-size: 1.75rem; font-weight: 700;">Welcome, <?= htmlspecialchars($firstName) ?>!</h1>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.95rem;">Your waste collection dashboard</p>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="navigateTo('/customer/pickup')" style="height: fit-content; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; background: #1ce36a; color: white; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(28, 227, 106, 0.25);">
                    <i class="fa-solid fa-plus" style="margin-right: 0.5rem;"></i> New Request
                </button>
            </div>
        </div>

        <!-- Stats Feature Cards (Using old style) -->
        <div class="stats-grid" style="margin-bottom: 2.5rem;">
            <?php foreach ($customerStats as $stat): ?>
                <div class="feature-card">
                    <div class="feature-card__header">
                        <h3 class="feature-card__title">
                            <?= e($stat['title']) ?>
                        </h3>
                        <div class="feature-card__icon">
                            <i class="<?= e($stat['icon']) ?>"></i>
                        </div>
                    </div>
                    <p class="feature-card__body">
                        <?= e((string) $stat['value']) ?>
                    </p>
                    <div class="feature-card__footer">
                        <span class="tag success"><?= e($stat['subtitle']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Main Content Grid -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; flex: 1; margin-bottom: 0; align-items: start;">
            
            <!-- Left Column: Chart & Recent Activity -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                
                <!-- Activity Doughnut Chart -->
                <div style="background: white; border-radius: 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; padding: 2rem;">
                    <div style="margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 700;">Request Status Overview</h2>
                        <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Distribution of your pickup requests</p>
                    </div>
                    <div style="height: 280px; position: relative; display: flex; justify-content: center; align-items: center;">
                        <canvas id="statusChart" style="max-height: 280px;"></canvas>
                    </div>
                    <div style="margin-top: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1ce36a;"><?= $pendingCount ?></div>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Pending</p>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;"><?= $scheduledCount ?></div>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Scheduled</p>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;"><?= $completedCount ?></div>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Completed</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div style="background: white; border-radius: 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden;">
                    <div style="padding: 2rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 700;">Recent Activity</h2>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Last 5 pickup requests</p>
                        </div>
                        <a href="/customer/pickup" style="color: #1ce36a; text-decoration: none; font-weight: 600; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; transition: color 0.3s;">
                            View All <i class="fa-solid fa-arrow-right" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>

                    <div style="max-height: 350px; overflow-y: auto;">
                        <?php if (empty($recentPickupsWidget)): ?>
                            <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📦</div>
                                <p style="margin: 0; font-weight: 500;">No pickup requests yet</p>
                                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">Create your first pickup request to get started</p>
                            </div>
                        <?php else: ?>
                            <div style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($recentPickupsWidget as $request): 
                                    $status = strtolower((string) ($request['status'] ?? 'pending'));
                                    $statusColors = [
                                        'pending' => ['#1ce36a', '#f0fdf4'],
                                        'assigned' => ['#3b82f6', '#eff6ff'],
                                        'confirmed' => ['#10b981', '#f0fdf4'],
                                        'completed' => ['#059669', '#d1fae5'],
                                        'cancelled' => ['#ef4444', '#fee2e2'],
                                    ];
                                    $colors = $statusColors[$status] ?? ['#6b7280', '#f9fafb'];
                                    ?>
                                    <div style="padding: 1.25rem 2rem; border-bottom: 1px solid #f3f4f6; transition: background 0.3s;" onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background='transparent'">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                                            <div style="flex: 1;">
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                                    <span style="font-weight: 700; color: #111827; font-size: 0.95rem;">Request #<?= e((string) $request['id']) ?></span>
                                                    <span style="background: <?= e($colors[1]) ?>; color: <?= e($colors[0]) ?>; padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;"><?= e(ucfirst($status)) ?></span>
                                                </div>
                                                <p style="margin: 0.5rem 0 0 0; color: #4b5563; font-size: 0.875rem;">
                                                    <i class="fa-solid fa-map-marker-alt" style="color: #1ce36a; margin-right: 0.5rem;"></i>
                                                    <?= e((string) ($request['address'] ?? 'Address not provided')) ?>
                                                </p>
                                                <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.75rem;">
                                                    <i class="fa-solid fa-calendar" style="margin-right: 0.5rem;"></i>
                                                    <?php 
                                                    $createdAt = strtotime($request['createdAt'] ?? 'now');
                                                    echo date('M d, Y', $createdAt);
                                                    ?>
                                                </p>
                                            </div>
                                            <a href="/customer/pickup?edit=<?= e((string) $request['id']) ?>" style="padding: 0.5rem 1rem; background: #f3f4f6; color: #1ce36a; text-decoration: none; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; white-space: nowrap; transition: all 0.3s;" onmouseenter="this.style.background='#e5e7eb'" onmouseleave="this.style.background='#f3f4f6'">View Details</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Quick Stats & Actions -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <!-- Performance Card -->
                <div style="background: linear-gradient(135deg, #1ce36a 0%, #08682d 100%); border-radius: 1.25rem; padding: 2rem; color: white; box-shadow: 0 8px 16px rgba(28, 227, 106, 0.25);">
                    <h3 style="margin: 0; font-size: 1rem; opacity: 0.9; font-weight: 600; margin-bottom: 0.5rem;">Completion Rate</h3>
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        <?= $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0 ?>%
                    </div>
                    <p style="margin: 0; font-size: 0.875rem; opacity: 0.95;">of your requests completed</p>
                    <div style="margin-top: 1rem; background: rgba(255,255,255,0.2); border-radius: 0.5rem; height: 6px; overflow: hidden;">
                        <div style="background: white; height: 100%; width: <?= $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0 ?>%; transition: width 0.5s ease;"></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="background: white; border-radius: 1.25rem; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <h3 style="margin: 0 0 1rem 0; color: #111827; font-size: 1rem; font-weight: 700;">Quick Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/customer/pickup" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #f9fafb; color: #111827; text-decoration: none; border-radius: 0.75rem; font-weight: 500; font-size: 0.875rem; transition: all 0.3s; border: 1px solid #e5e7eb;" onmouseenter="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'" onmouseleave="this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'">
                            <i class="fa-solid fa-plus" style="color: #1ce36a;"></i> New Pickup Request
                        </a>
                        <a href="/customer/analytics" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #f9fafb; color: #111827; text-decoration: none; border-radius: 0.75rem; font-weight: 500; font-size: 0.875rem; transition: all 0.3s; border: 1px solid #e5e7eb;" onmouseenter="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'" onmouseleave="this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'">
                            <i class="fa-solid fa-chart-line" style="color: #3b82f6;"></i> View Analytics
                        </a>
                        <a href="/customer/profile" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #f9fafb; color: #111827; text-decoration: none; border-radius: 0.75rem; font-weight: 500; font-size: 0.875rem; transition: all 0.3s; border: 1px solid #e5e7eb;" onmouseenter="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'" onmouseleave="this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'">
                            <i class="fa-solid fa-user" style="color: #f59e0b;"></i> Edit Profile
                        </a>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div style="background: white; border-radius: 1.25rem; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <h3 style="margin: 0 0 1rem 0; color: #111827; font-size: 1rem; font-weight: 700;">Summary</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                            <span style="color: #4b5563; font-size: 0.875rem;">Total Requests</span>
                            <span style="font-weight: 700; color: #111827; font-size: 1.125rem;"><?= $totalCount ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                            <span style="color: #4b5563; font-size: 0.875rem;">Success Rate</span>
                            <span style="font-weight: 700; color: #1ce36a; font-size: 1.125rem;"><?= $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0 ?>%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #4b5563; font-size: 0.875rem;">Awaiting Action</span>
                            <span style="font-weight: 700; color: #f59e0b; font-size: 1.125rem;"><?= $pendingCount ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    function navigateTo(url) {
        window.location.href = url;
    }

    // Initialize Status Doughnut Chart
    document.addEventListener('DOMContentLoaded', function() {
        const chartCanvas = document.getElementById('statusChart');
        if (chartCanvas) {
            new Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Scheduled', 'Completed'],
                    datasets: [
                        {
                            data: [<?= $pendingCount ?>, <?= $scheduledCount ?>, <?= $completedCount ?>],
                            backgroundColor: [
                                '#1ce36a',
                                '#3b82f6',
                                '#10b981'
                            ],
                            borderColor: [
                                '#08682d',
                                '#1e40af',
                                '#047857'
                            ],
                            borderWidth: 2,
                            hoverOffset: 8,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: { size: 12, weight: '600' },
                                color: '#4b5563',
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            padding: 12,
                            titleFont: { size: 12, weight: '600' },
                            bodyFont: { size: 12 },
                            borderColor: '#1ce36a',
                            borderWidth: 1,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>