<?php
// analytics.php

// Feedback data will be fetched via JavaScript API call
$collectorFeedback = []; // Will be populated by JavaScript
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div>
    <!-- Page Header -->
    <page-header title="Collector Feedback & Reports" description="Monitor and review feedback from collectors"></page-header>

    <!-- Metrics Cards -->
    <div class="feature-cards">
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Average Ratings</div>
                <div class="feature-card__icon"><i class="fa-solid fa-star"></i></div>
            </div>
            <div class="feature-card__body" id="avgRatingValue">-</div>
        </div>
        <!-- <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Pending Reports</div>
                <div class="feature-card__icon"><i class="fa-solid fa-flag"></i></div>
            </div>
            <div class="feature-card__body" id="pendingReportsValue">-</div>
        </div> -->
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Total Feedbacks</div>
                <div class="feature-card__icon"><i class="fa-solid fa-comment"></i></div>
            </div>
            <div class="feature-card__body" id="totalFeedbackValue">-</div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Satisfaction Rate (%)</div>
                <div class="feature-card__icon"><i class="fa-solid fa-face-smile"></i></div>
            </div>
            <div class="feature-card__body" id="satisfactionRateValue">-</div>
        </div>
    </div>

    <!-- Monthly Collection Summary -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-chart-column" style="margin-right: 8px;"></i> Monthly Collection Summary
            </h3>
            <p class="activity-card__description">Material collection by selected month</p>
        </div>
        <div class="activity-card__content">
            <div style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: 12px; flex-wrap: wrap;">
                <label for="monthly-collection-month-select" style="font-size: 0.9rem; color: var(--neutral-700);">Month</label>
                <select id="monthly-collection-month-select" style="padding: 6px 10px; border: 1px solid var(--neutral-300); border-radius: var(--radius-md); min-width: 160px; font-size: 0.9rem;"></select>
                <!-- <span id="monthly-collection-range" style="color: var(--neutral-600); font-size: 0.9rem;">Month: --</span> -->
            </div>
            <div style="padding: 0;">
                <canvas id="monthlyCollectionChart" style="width: 100%; max-height: 360px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Feedback Table -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-comments" style="margin-right: 8px;"></i> Collector Feedback Report
            </h3>
            <p class="activity-card__description">Recent reports and feedbacks</p>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto; max-height: 400px; overflow-y: auto;">
                <table class="data-table">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <tr>
                            <th><i class="fa-solid fa-id-card"></i> Customer ID</th>
                            <th><i class="fa-solid fa-user"></i> Customer Name</th>
                            <th><i class="fa-solid fa-calendar-day"></i> Date</th>
                            <th><i class="fa-solid fa-message"></i> Feedback</th>
                            <th><i class="fa-solid fa-star"></i> Rating</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 16px;">
                                <span class="loading">Loading feedback data...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const CURRENT_COLLECTOR_ID = <?= (int)($user['id'] ?? 0) ?>;
const avgRatingValueEl = document.getElementById('avgRatingValue');
const pendingReportsValueEl = document.getElementById('pendingReportsValue');
const totalFeedbackValueEl = document.getElementById('totalFeedbackValue');
const satisfactionRateValueEl = document.getElementById('satisfactionRateValue');

// Immediate validation on page load
console.log('=== Collector Analytics Debug ===');
console.log('Collector ID:', CURRENT_COLLECTOR_ID);
console.log('User Data:', <?= json_encode($user ?? []) ?>);

let monthlyCollectionChart = null;
const monthlyCollectionRangeEl = document.getElementById('monthly-collection-range');
const monthlyCollectionMonthSelectEl = document.getElementById('monthly-collection-month-select');
const monthlyCollectionChartContainer = document.getElementById('monthlyCollectionChart')?.parentElement || null;
const FIXED_MATERIAL_CATEGORIES = [
    { key: 'plastic', label: 'Plastic', color: '#3B82F6' },
    { key: 'paper', label: 'Paper', color: '#10B981' },
    { key: 'glass', label: 'Glass', color: '#06B6D4' },
    { key: 'metal', label: 'Metal', color: '#F59E0B' },
    { key: 'cardboard', label: 'Cardboard', color: '#8B5CF6' }
];

function buildRecentMonthOptions(limit = 12) {
    const options = [];
    const base = new Date();
    base.setDate(1);
    base.setHours(0, 0, 0, 0);

    for (let i = 0; i < limit; i++) {
        const d = new Date(base.getFullYear(), base.getMonth() - i, 1);
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const value = `${d.getFullYear()}-${month}`;
        const label = d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
        options.push({ value, label });
    }

    return options;
}

function initializeMonthlyCollectionMonthSelect() {
    if (!monthlyCollectionMonthSelectEl) return;

    const options = buildRecentMonthOptions(12);
    monthlyCollectionMonthSelectEl.innerHTML = options
        .map(option => `<option value="${option.value}">${option.label}</option>`)
        .join('');
}

function ensureMonthlyCollectionCanvas() {
    if (!monthlyCollectionChartContainer) return null;
    let canvas = document.getElementById('monthlyCollectionChart');
    if (!canvas) {
        monthlyCollectionChartContainer.innerHTML = '<canvas id="monthlyCollectionChart" style="width: 100%; max-height: 360px;"></canvas>';
        canvas = document.getElementById('monthlyCollectionChart');
    }
    return canvas;
}

function showMonthlyCollectionEmptyState(message) {
    if (!monthlyCollectionChartContainer) return;
    if (monthlyCollectionChart) {
        monthlyCollectionChart.destroy();
        monthlyCollectionChart = null;
    }
    monthlyCollectionChartContainer.innerHTML = `<p style="text-align: center; color: #999; padding: 40px;">${message}</p>`;
}

function formatMonthDate(isoDate) {
    if (!isoDate) return null;
    const d = new Date(`${isoDate}T00:00:00`);
    if (Number.isNaN(d.getTime())) return null;
    return d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
}

function updateMonthlyCollectionRange(monthLabel) {
    if (!monthlyCollectionRangeEl) return;
    monthlyCollectionRangeEl.textContent = monthLabel ? `Month: ${monthLabel}` : 'Month: --';
}

function normalizeMaterialName(name) {
    return String(name || '').trim().toLowerCase();
}

async function fetchAndRenderMonthlyCollection() {
    try {
        const selectedMonth = monthlyCollectionMonthSelectEl?.value || buildRecentMonthOptions(1)[0]?.value;
        const res = await fetch(`/api/collector/material-collection?period=monthly-by-material&month=${encodeURIComponent(selectedMonth)}`, { credentials: 'same-origin' });

        if (!res.ok) {
            let errorMessage = 'Unable to load monthly collection summary';
            try {
                const errJson = await res.json();
                errorMessage = errJson?.details || errJson?.message || errorMessage;
            } catch (_) {
                // keep default message
            }
            showMonthlyCollectionEmptyState(errorMessage);
            return;
        }

        const json = await res.json();
        if (!json || json.status !== 'success' || !Array.isArray(json.data)) {
            showMonthlyCollectionEmptyState(json?.details || json?.message || 'No collection data available');
            return;
        }

        updateMonthlyCollectionRange(json.selected_month_label || formatMonthDate(json.month_start));

        const categoryWeightMap = new Map(
            (json.data || []).map(item => [
                normalizeMaterialName(item.name),
                Number(item.weight || 0)
            ])
        );

        const labels = FIXED_MATERIAL_CATEGORIES.map(category => category.label);
        const values = FIXED_MATERIAL_CATEGORIES.map(category => categoryWeightMap.get(category.key) || 0);
        const colors = FIXED_MATERIAL_CATEGORIES.map(category => category.color);

        const canvas = ensureMonthlyCollectionCanvas();
        if (!canvas) return;

        if (monthlyCollectionChart) {
            monthlyCollectionChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        monthlyCollectionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Weight (kg)',
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 6,
                    maxBarThickness: 46
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = Number(context.parsed.y || 0);
                                return `Weight: ${value.toFixed(2)} kg`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Material Categories'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Weight (kg)'
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Failed to fetch monthly collection:', error);
        showMonthlyCollectionEmptyState('Unable to load monthly collection summary');
    }
}

/**
 * Main Orchestrator: Fetches all data for the page
 */
async function refreshDashboard() {
    console.log('Refreshing dashboard data...');
    console.log('Current Collector ID:', CURRENT_COLLECTOR_ID);
    
    // Validate collector ID before making any API calls
    if (!CURRENT_COLLECTOR_ID || CURRENT_COLLECTOR_ID === 0) {
        const errorMsg = 'Collector ID is missing or invalid. Please ensure you are logged in as a collector.';
        console.error(errorMsg);
        updateFeedbackTable([], errorMsg);
        updateWasteTable([], errorMsg);
        return;
    }
    
    const params = `?collector_id=${CURRENT_COLLECTOR_ID}`;
    
    try {
        // Add timeout to prevent hanging requests
        const timeout = 10000; // 10 seconds
        const fetchWithTimeout = (url) => {
            return Promise.race([
                fetch(url, { credentials: 'include' }),
                new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Request timeout')), timeout)
                )
            ]);
        };
        
        // Run fetches in parallel for better performance
        const [metricsReq, feedbackReq] = await Promise.all([
            fetchWithTimeout(`/api/collector/metrics${params}`),
            fetchWithTimeout(`/api/collector/feedback${params}&limit=50`)
        ]);

        // Handle metrics
        if (metricsReq.ok) {
            const mData = await metricsReq.json();
            console.log('Metrics response:', mData);
            if (mData.success && mData.data?.feedbackMetrics) {
                updateMetricsCards(mData.data.feedbackMetrics);
            } else {
                console.error('Metrics data invalid:', mData);
                const errMsg = mData.error || 'Invalid data';
                if (avgRatingValueEl) avgRatingValueEl.innerHTML = `<small style="color: #dc3545;">${errMsg.substring(0, 20)}</small>`;
                if (pendingReportsValueEl) pendingReportsValueEl.innerHTML = `<small style="color: #dc3545;">${errMsg.substring(0, 20)}</small>`;
                if (totalFeedbackValueEl) totalFeedbackValueEl.innerHTML = `<small style="color: #dc3545;">${errMsg.substring(0, 20)}</small>`;
                if (satisfactionRateValueEl) satisfactionRateValueEl.innerHTML = `<small style="color: #dc3545;">${errMsg.substring(0, 20)}</small>`;
            }
        } else {
            const errorText = await metricsReq.text();
            console.error('Metrics API failed:', metricsReq.status, errorText);
            
            // Try to parse error message
            let errorMsg = `Error ${metricsReq.status}`;
            try {
                const errorJson = JSON.parse(errorText);
                errorMsg = errorJson.error || errorMsg;
            } catch (e) {
                errorMsg = errorText.substring(0, 50) || errorMsg;
            }
            
            if (avgRatingValueEl) avgRatingValueEl.innerHTML = `<small style="color: #dc3545; font-size: 0.7em;">${errorMsg}</small>`;
            if (pendingReportsValueEl) pendingReportsValueEl.innerHTML = `<small style="color: #dc3545; font-size: 0.7em;">${errorMsg}</small>`;
            if (totalFeedbackValueEl) totalFeedbackValueEl.innerHTML = `<small style="color: #dc3545; font-size: 0.7em;">${errorMsg}</small>`;
            if (satisfactionRateValueEl) satisfactionRateValueEl.innerHTML = `<small style="color: #dc3545; font-size: 0.7em;">${errorMsg}</small>`;
        }

        // Handle feedback
        if (feedbackReq.ok) {
            const fData = await feedbackReq.json();
            console.log('Feedback response:', fData);
            if (fData.success) {
                updateFeedbackTable(fData.data);
            } else {
                console.error('Feedback API error:', fData.error);
                updateFeedbackTable([], fData.error || 'Failed to load feedback');
            }
        } else {
            const errorText = await feedbackReq.text();
            console.error('Feedback API failed:', feedbackReq.status, errorText);
            updateFeedbackTable([], `API Error ${feedbackReq.status}: ${errorText.substring(0, 100)}`);
        }

    } catch (error) {
        console.error('Polling Error:', error);
        const errorMsg = `Network Error: ${error.message}`;
        updateFeedbackTable([], errorMsg);
    }
}

/**
 * Updates UI Cards
 */
function updateMetricsCards(metrics) {
    if (!metrics) return;
    
    const avgRating = metrics.averageRating || 0;
    const pendingReports = metrics.lowRatings || 0;
    const totalFeedback = metrics.totalFeedback || 0;
    const satisfiedFeedback = Math.max(0, totalFeedback - pendingReports);
    const satisfactionRate = totalFeedback > 0
        ? (satisfiedFeedback / totalFeedback) * 100
        : 0;
    
    if (avgRatingValueEl) avgRatingValueEl.textContent = avgRating.toFixed(1);
    if (pendingReportsValueEl) pendingReportsValueEl.textContent = pendingReports;
    if (totalFeedbackValueEl) totalFeedbackValueEl.textContent = totalFeedback;
    if (satisfactionRateValueEl) satisfactionRateValueEl.textContent = `${satisfactionRate.toFixed(1)}%`;
    
    console.log('Metrics updated:', { avgRating, pendingReports, totalFeedback });
}

/**
 * Updates Feedback Table
 */
function updateFeedbackTable(data, error = null) {
    const tableBody = document.getElementById('feedbackTableBody');
    if (error) {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:16px; color:#dc3545;">Error: ${escapeHtml(error)}</td></tr>`;
    } else if (data && data.length > 0) {
        tableBody.innerHTML = data.map(fb => `
            <tr>
                <td>${escapeHtml(String(fb.customer_id))}</td>
                <td>${escapeHtml(fb.customer_name)}</td>
                <td>${new Date(fb.rating_date).toLocaleDateString()}</td>
                <td>${escapeHtml(fb.description)}</td>
                <td>${renderStars(fb.rating)}</td>
            </tr>
        `).join('');
    } else {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:16px; color:#888;">No feedback records found.</td></tr>';
    }
}

/**
 * Updates Waste Table
 */
function updateWasteTable(data, error = null) {
    const tableBody = document.getElementById('wasteCollectionTableBody');
    if (!tableBody) return;
    if (error) {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:16px; color:#dc3545;">Error: ${escapeHtml(error)}</td></tr>`;
    } else if (data && data.length > 0) {
        tableBody.innerHTML = data.map(r => `
            <tr>
                <td>${escapeHtml(String(r.customer_id))}</td>
                <td>${escapeHtml(r.customer_name)}</td>
                <td>${escapeHtml(r.category)}</td>
                <td>${r.weight} kg</td>
                <td>Rs. ${parseFloat(r.amount).toFixed(2)}</td>
            </tr>
        `).join('');
    } else {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:16px; color:#888;">No waste records found.</td></tr>';
    }
}

// Helper: Render Stars
function renderStars(count) {
    let stars = '';
    for (let i = 0; i < 5; i++) {
        stars += i < count ? '<i class="fa-solid fa-star" style="color: #000;"></i>' : '<i class="fa-regular fa-star" style="color: #ccc;"></i>';
    }
    return stars;
}

// Helper: Escape HTML
function escapeHtml(text) {
    if (!text) return '-';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Initialize Polling
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded - Starting initialization');
    console.log('Collector ID available:', CURRENT_COLLECTOR_ID);
    
    // Immediate visual feedback
    if (avgRatingValueEl) avgRatingValueEl.textContent = '...';
    if (pendingReportsValueEl) pendingReportsValueEl.textContent = '...';
    if (totalFeedbackValueEl) totalFeedbackValueEl.textContent = '...';
    if (satisfactionRateValueEl) satisfactionRateValueEl.textContent = '...';
    
    // Visual confirmation that JS is running
    if (!CURRENT_COLLECTOR_ID) {
        if (avgRatingValueEl) avgRatingValueEl.textContent = '⚠️';
        if (pendingReportsValueEl) pendingReportsValueEl.textContent = '⚠️';
        if (totalFeedbackValueEl) totalFeedbackValueEl.textContent = '⚠️';
        if (satisfactionRateValueEl) satisfactionRateValueEl.textContent = '⚠️';
        updateFeedbackTable([], 'ERROR: No collector ID found. User data may not be loaded properly.');
        return;
    }

    initializeMonthlyCollectionMonthSelect();
    monthlyCollectionMonthSelectEl?.addEventListener('change', () => {
        fetchAndRenderMonthlyCollection();
    });
    
    refreshDashboard(); // Initial run
    fetchAndRenderMonthlyCollection(); // Initial monthly collection chart load
    setInterval(refreshDashboard, 30000); // Poll every 30 seconds for smoother performance
    setInterval(fetchAndRenderMonthlyCollection, 30000);
    console.log('Dashboard refresh scheduled every 30 seconds');
});

</script>
