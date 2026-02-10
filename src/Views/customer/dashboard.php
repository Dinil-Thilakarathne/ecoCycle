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

consoleLog($userProfile);

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

?>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-page">
        
        <!-- Welcome + CTA -->
        <div class="page-header" style="margin-bottom: 2rem;">
            <div class="page-header__content">
                <?php
                $profileData = $userProfile ?? [];
                $firstName = $profileData['firstName'] ?? ($user['name'] ?? 'Customer');
                $firstName = $firstName !== '' ? $firstName : ($user['name'] ?? 'Customer');
                $imagePath = $userProfile['profileImage'] ?? null;
                $profilePic = $imagePath ? asset($imagePath) : asset('assets/logo-icon.png');
                ?>
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <!-- <img src="<?= e($profilePic) ?>" alt="" class="customer-dashboard-avatar"> -->
                    <img src="<?= '/' . ltrim($imagePath, '/') ?>" alt="Profile picture" class="customer-dashboard-avatar">
                    <div>
                        <h1 class="page-header__title" style="margin: 0;">Welcome, <?= e($firstName) ?>!</h1>
                        <p class="page-header__description" style="margin: 0.25rem 0 0 0;">Your waste collection dashboard</p>
                    </div>
                    </div>
                </div>
            </div>

        <!-- Stats Feature Cards (Using old style) -->
        <div class="stats-grid" style="margin-bottom: 2.5rem;">
            <div class="feature-card" data-stat="pickups">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Total Pickups</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-truck"></i></div>
                </div>
                <p class="feature-card__body" style="margin: 0; font-size: 2rem; font-weight: 700; color: #111827;">0</p>
                <div class="feature-card__footer"><span class="tag success">All time</span></div>
            </div>
            <div class="feature-card" data-stat="income">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Total Income</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-wallet"></i></div>
                </div>
                <p class="feature-card__body" style="margin: 0; font-size: 2rem; font-weight: 700; color: #111827;">Rs 0.00</p>
                <div class="feature-card__footer"><span class="tag success">Earnings</span></div>
            </div>
            <div class="feature-card" data-stat="weight">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Total Weight</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-weight"></i></div>
                </div>
                <p class="feature-card__body" style="margin: 0; font-size: 2rem; font-weight: 700; color: #111827;">0 kg</p>
                <div class="feature-card__footer"><span class="tag success">Waste collected</span></div>
            </div>
        </div>

        <!-- Main Content Grid: Chart + Price per unit -->
        <div class="customer-dashboard-grid" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; margin-top: 2rem;">
            
            <!-- Left: Request Status Chart -->
            <div class="customer-dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 1.5rem; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e5e7eb;">
                <div class="customer-dashboard-card__header" style="margin-bottom: 1.5rem;">
                    <h2 class="section-title" style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: #111827; font-weight: 700;">Request Status Overview</h2>
                    <p class="section-subtitle" style="margin: 0; color: #6b7280; font-size: 0.875rem;">Distribution of your pickup requests</p>
                </div>
                <div class="customer-dashboard-chart-wrap" style="height: 320px; margin-bottom: 2rem; display:flex; align-items:center; gap:1.25rem;">
                        <div style="flex:1; height:220px; position: relative;">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div id="statusChartLegend" style="width:180px; display:flex; flex-direction:column; gap:12px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:14px; height:14px; background:#f59e0b; border-radius:3px;"></div>
                                <div style="flex:1;">Pending</div>
                                <div id="legend-pending"><?= (int) $pendingCount ?></div>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:14px; height:14px; background:#06b6d4; border-radius:3px;"></div>
                                <div style="flex:1;">Scheduled</div>
                                <div id="legend-scheduled"><?= (int) $scheduledCount ?></div>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:14px; height:14px; background:#10b981; border-radius:3px;"></div>
                                <div style="flex:1;">Completed</div>
                                <div id="legend-completed"><?= (int) $completedCount ?></div>
                            </div>
                        </div>
                </div>
            </div>

            <!-- Right: Price per unit -->
            <div class="customer-dashboard-card customer-price-unit" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 1.5rem; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e5e7eb;">
                <div class="customer-dashboard-card__header" style="margin-bottom: 1.5rem;">
                    <h2 class="section-title" style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: #111827; font-weight: 700;">
                        <i class="fa-solid fa-leaf" style="margin-right: 0.75rem; color: #1ce36a;"></i>
                        Price Per Unit
                    </h2>
                    <p class="section-subtitle" style="margin: 0; color: #6b7280; font-size: 0.875rem;">Current rates per kg — earn based on these</p>
                </div>
                <div id="material-prices-container" class="customer-price-unit__list" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- Dummy price data -->
                    <div class="customer-price-unit__item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.75rem; background: rgba(245, 158, 11, 0.08); border-radius: 0.75rem; border-left: 4px solid #f59e0b;">
                        <span style="color: #4b5563; font-weight: 600;">Plastic</span>
                        <span style="color: #111827; font-weight: 700; font-size: 1.05rem;">Rs 15.00 <span style="font-size: 0.75rem; color: #6b7280;">/ kg</span></span>
                    </div>
                    <div class="customer-price-unit__item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.75rem; background: rgba(16, 185, 129, 0.08); border-radius: 0.75rem; border-left: 4px solid #10b981;">
                        <span style="color: #4b5563; font-weight: 600;">Paper</span>
                        <span style="color: #111827; font-weight: 700; font-size: 1.05rem;">Rs 8.50 <span style="font-size: 0.75rem; color: #6b7280;">/ kg</span></span>
                    </div>
                    <div class="customer-price-unit__item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.75rem; background: rgba(59, 130, 246, 0.08); border-radius: 0.75rem; border-left: 4px solid #3b82f6;">
                        <span style="color: #4b5563; font-weight: 600;">Glass</span>
                        <span style="color: #111827; font-weight: 700; font-size: 1.05rem;">Rs 12.00 <span style="font-size: 0.75rem; color: #6b7280;">/ kg</span></span>
                    </div>
                    <div class="customer-price-unit__item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.75rem; background: rgba(139, 92, 246, 0.08); border-radius: 0.75rem; border-left: 4px solid #8b5cf6;">
                        <span style="color: #4b5563; font-weight: 600;">Metal</span>
                        <span style="color: #111827; font-weight: 700; font-size: 1.05rem;">Rs 25.00 <span style="font-size: 0.75rem; color: #6b7280;">/ kg</span></span>
                    </div>
                    <div class="customer-price-unit__item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.75rem; background: rgba(249, 115, 22, 0.08); border-radius: 0.75rem; border-left: 4px solid #f97316;">
                        <span style="color: #4b5563; font-weight: 600;">Organic</span>
                        <span style="color: #111827; font-weight: 700; font-size: 1.05rem;">Rs 5.00 <span style="font-size: 0.75rem; color: #6b7280;">/ kg</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// --- Dashboard data & chart logic ---
let chartInstance = null;

function navigateTo(url) { window.location.href = url; }

function loadDashboardData() {
    fetch('/api/customer/dashboard/stats', { credentials: 'include' })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            if (data && data.data) {
                updateFeatureCards(data.data);
                updateChart(data.data);
            }
        })
        .catch(() => console.error('Error loading dashboard stats'));
}

function updateFeatureCards(stats) {
    const setText = (selector, text) => {
        const el = document.querySelector(selector);
        if (el) el.querySelector('.feature-card__body').textContent = text;
    };
    setText('[data-stat="pickups"]', stats.totalPickups || 0);
    setText('[data-stat="income"]', 'Rs ' + ((stats.totalIncome || 0).toFixed(2)));
    setText('[data-stat="weight"]', (stats.totalWeight || 0) + ' kg');
}

function updateChart(stats) {
    const canvas = document.getElementById('statusChart');
    if (!canvas) return;

    const pending = stats.pendingCount || 0;
    const scheduled = stats.scheduledCount || 0;
    const completed = stats.completedCount || 0;

    if (!chartInstance) {
        chartInstance = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Scheduled', 'Completed'],
                datasets: [{ data: [pending, scheduled, completed], backgroundColor: ['#f59e0b', '#06b6d4', '#10b981'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    } else {
        chartInstance.data.datasets[0].data = [pending, scheduled, completed];
        chartInstance.update();
    }

    // update custom legend counts
    const setLegend = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setLegend('legend-pending', pending);
    setLegend('legend-scheduled', scheduled);
    setLegend('legend-completed', completed);
}

document.addEventListener('DOMContentLoaded', loadDashboardData);
</script>

<script>
// --- Price panel: fetch live material prices with fallback ---
(function() {
    const endpoint = '/api/collector/material-prices';
    const container = document.getElementById('material-prices-container');
    if (!container) return;

    const fallback = [
        { name: 'Plastic', price_per_unit: 15.00 },
        { name: 'Paper', price_per_unit: 8.50 },
        { name: 'Glass', price_per_unit: 12.00 },
        { name: 'Metal', price_per_unit: 25.00 },
        { name: 'Organic', price_per_unit: 5.00 }
    ];

    const formatPrice = v => (v === null || v === undefined) ? 'Rs 0.00' : ('Rs ' + (parseFloat(v) || 0).toFixed(2));
    const getColor = name => ({ plastic: '#f59e0b', glass: '#3b82f6', metal: '#8b5cf6', paper: '#10b981', organic: '#f97316' }[(name||'').toLowerCase()] || '#64748b');
    const escapeHtml = s => { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };

    function render(list) {
        if (!Array.isArray(list) || list.length === 0) {
            container.innerHTML = '<div class="customer-price-unit__empty"><i class="fa-solid fa-info-circle"></i><p>No material prices available right now.</p></div>';
            return;
        }
        container.innerHTML = list.map(m => {
            const name = m.name || 'Material';
            const price = (m.price_per_unit != null) ? m.price_per_unit : (m.price != null ? m.price : 0);
            const color = getColor(name);
            return `<div class="customer-price-unit__row" style="display:flex; justify-content:space-between; align-items:center; padding:0.9rem 0.75rem; border-radius:0.6rem; background:rgba(15,23,42,0.02);">
                <span style="display:flex;align-items:center;gap:0.6rem;"><span style="width:10px;height:10px;border-radius:50%;background:${color}"></span>${escapeHtml(name)}</span>
                <span style="font-weight:700;">${formatPrice(price)} <small style="font-weight:400; color:#6b7280;">/ kg</small></span>
            </div>`;
        }).join('');
    }

    function fetchPrices() {
        fetch(endpoint, { credentials: 'include', headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(json => {
                if (json && (json.status === 'success' || json.success) && Array.isArray(json.data)) return render(json.data);
                if (Array.isArray(json)) return render(json);
                render(fallback);
            })
            .catch(() => render(fallback));
    }

    // render fallback immediately, then try live fetch
    render(fallback);
    fetchPrices();
    setInterval(fetchPrices, 15000);
})();
</script>