<?php
// Collector analytics view — merged: keep API-driven data loading but retain PHP helper functions
// Feedback data is loaded via API (preferred), but sample data is kept as a local fallback for dev.
$collectorFeedback = [];

// Sample fallback feedback (used if API is unavailable)
$sampleCollectorFeedback = [
    [
        'id' => 'CF001',
        'collectorName' => 'John Smith',
        'date' => '2025-08-20',
        'feedback' => 'Very punctual and efficient service.',
        'rating' => 5,
        'status' => 'positive',
    ],
    [
        'id' => 'CF002',
        'collectorName' => 'Sarah Johnson',
        'date' => '2025-08-18',
        'feedback' => 'Arrived late and missed some pickups.',
        'rating' => 2,
        'status' => 'review',
    ],
    [
        'id' => 'CF003',
        'collectorName' => 'Mike Wilson',
        'date' => '2025-08-17',
        'feedback' => 'Friendly and reliable, good communication.',
        'rating' => 4,
        'status' => 'positive',
    ],
];

// Sample fallback income data
$customerIncome = [
    [
        'customerName' => 'Emma Brown',
        'contact' => '077-1234567',
        'location' => 'Colombo 07',
        'category' => 'Plastic',
        'income' => 3500,
    ],
    [
        'customerName' => 'David Lee',
        'contact' => '071-9876543',
        'location' => 'Kandy',
        'category' => 'Organic',
        'income' => 2800,
    ],
    [
        'customerName' => 'Nimal Perera',
        'contact' => '075-4567890',
        'location' => 'Galle',
        'category' => 'Glass',
        'income' => 4200,
    ],
];

// Helper function for rating stars (PHP fallback for server-rendered contexts)
function renderStars($count)
{
    $stars = '';
    for ($i = 0; $i < $count; $i++) {
        $stars .= '<i class="fa-solid fa-star filled"></i>';
    }
    for ($i = $count; $i < 5; $i++) {
        $stars .= '<i class="fa-regular fa-star"></i>';
    }
    return $stars;
}

// Helper for status badge
function getFeedbackBadge($status)
{
    switch ($status) {
        case 'positive':
            return '<span class="status success"><i class="fa-solid fa-circle-check"></i> Positive</span>';
        case 'review':
            return '<span class="status danger"><i class="fa-solid fa-circle-exclamation"></i> Needs Review</span>';
        default:
            return '<span class="status secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div>
    <!-- Page Header -->
    <page-header title="Collector Feedback & Reports" description="Monitor and review feedback from collectors">
        <div data-header-action style="display: flex; gap: var(--space-2);">
            <button class="btn btn-primary" onclick="addFeedback()">
                <i class="fa-solid fa-comment-dots" style="margin-right: 8px;"></i>
                Add Feedback
            </button>
        </div>
    </page-header>

    <!-- Metrics Cards -->
    <div class="feature-cards">
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Average Ratings</div>
                <div class="feature-card__icon"><i class="fa-solid fa-star"></i></div>
            </div>
            <div class="feature-card__body" id="avgRatingValue">-</div>
         <!--   <div class="feature-card__footer">
                <span class="desc">Based on customer feedback</span>
            </div>-->
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Pending Reports</div>
                <div class="feature-card__icon"><i class="fa-solid fa-flag"></i></div>
            </div>
            <div class="feature-card__body" id="pendingReportsValue">-</div>
             <!-- <div class="feature-card__footer"> 
                 <span class="desc">Need Attention</span> 
            </div> -->
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Total Feedbacks</div>
                <div class="feature-card__icon"><i class="fa-solid fa-comment"></i></div>
            </div>
            <div class="feature-card__body" id="totalFeedbackValue">-</div>
             <!-- <div class="feature-card__footer"> 
                 <span class="desc">Received from customers</span>
            </div> -->
        </div>
    </div>

   <!-- Waste Collection Chart
    <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Monthly Waste Collection by Type (kg)</h3>
        <canvas id="wasteChart" style="max-height: 380px;"></canvas>
    </div>  -->



    <!-- Feedback Table -->
    <div class="activity-card" style = "margin-top: 24px;">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-comments" style="margin-right: 8px;"></i>
                Collector Feedback Report
            </h3>
            <p class="activity-card__description">Recent reports and feedbacks</p>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fa-solid fa-user"></i> Collector</th>
                            <th><i class="fa-solid fa-calendar-day"></i> Date</th>
                            <th><i class="fa-solid fa-message"></i> Feedback</th>
                            <th><i class="fa-solid fa-star"></i> Rating</th>
                            <th><i class="fa-solid fa-list"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: var(--space-16);">
                                <span class="loading">Loading feedback data...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Customer Waste Income Table -->
<div class="activity-card"  style = "margin-top: 24px;">
    <div class="activity-card__header">
        <h3 class="activity-card__title">
            <i class="fa-solid fa-money-bill-wave" style="margin-right: 8px;"></i>
            Customer Waste Income Report
        </h3>
        <p class="activity-card__description">
            Customer-wise waste category and total income
        </p>
    </div>

    <div class="activity-card__content">
        <div style="overflow-x: auto;">
            <table class="data-table" id="collectorIncomeTable">
                <thead>
                    <tr>
                        <!-- <th><i class="fa-solid fa-user"></i> Customer Id</th> -->
                        <th><i class="fa-solid fa-user"></i> Customer Name</th>
                        <th><i class="fa-solid fa-weight-hanging"></i> Weight (kg)</th>
                        <th><i class="fa-solid fa-coins"></i> Price (LKR)</th>
                    </tr>
                </thead>
                <tbody id="collectorIncomeBody">
                    <tr><td colspan="3" style="text-align:center; padding:16px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Collector-scoped customer income real-time integration
    (function(){
        const collectorId = <?= (int) (auth()['id'] ?? 0) ?>;
        const tbody = document.getElementById('collectorIncomeBody');
        const statusEl = document.getElementById('collectorReportStatus');
        const refreshBtn = document.getElementById('collectorReportRefresh');

        function renderRows(rows){
            if (!Array.isArray(rows) || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:16px;">No completed pickups found.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map(r => {
                // const customer = (r.customer_name || '') + ' (' + (r.customer_id || '') + ')';
                const customer = r.customer_name || '';
                const type = r.customer_type || '';
                const phone = r.customer_phone || '';
                const customerAddr = r.customer_address || '';
                const pickupAddr = r.pickup_address || '';
                const weight = typeof r.weight === 'number' ? r.weight.toFixed(2) : (r.weight || '');
                const price = typeof r.price === 'number' ? r.price.toFixed(2) : (r.price || '');
                const updated = r.updated_at || '';
               /* return `
                    <tr>
                    <td>${escapeHtml(customer)}</td>
                    <td>${escapeHtml(type)}</td>
                    <td>${escapeHtml(phone)}</td>
                    <td>${escapeHtml(customerAddr)}</td>
                    <td>${escapeHtml(pickupAddr)}</td>
                    <td>${escapeHtml(weight)}</td>
                    <td style="font-weight:bold;">${escapeHtml(price)}</td>
                    <td>${escapeHtml(updated)}</td>
                    </tr>`;
                    return `*/
                    return
    <tr>
        <td>${escapeHtml(customer)}</td>
        <td>${escapeHtml(weight)}</td>
        <td style="font-weight:bold;">
            LKR ${escapeHtml(price)}
        </td>
    </tr>`;
return `          
  }).join('');
        }

        async function loadCollectorReport(since = 0){
            try{
                const url = new URL('/api/collector/reports/customer-income', window.location.origin);
                if (since > 0) url.searchParams.append('since', since);

                const res = await fetch(url.toString(), {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) {
                    // Try to read JSON error details if provided
                    let detail = '';
                    try {
                        const errJson = await res.json();
                        // Prefer nested error detail if present
                        if (errJson && errJson.errors && typeof errJson.errors.detail !== 'undefined') {
                            detail = errJson.errors.detail;
                        } else if (errJson && errJson.message) {
                            detail = errJson.message;
                        } else if (errJson && errJson.errors) {
                            detail = JSON.stringify(errJson.errors);
                        }
                    } catch (e) {
                        // Not JSON
                    }

                    if (res.status === 401) {
                        // Unauthenticated - show message and optionally redirect
                        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Authentication required. Please log in.</td></tr>';
                        return;
                    }

                    if (res.status === 403) {
                        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Access denied. Insufficient permissions.</td></tr>';
                        return;
                    }

                    throw new Error('HTTP ' + res.status + (detail ? ' - ' + detail : ''));
                }

                const json = await res.json();
                renderRows(json.rows || []);
                statusEl.textContent = 'Last updated: ' + new Date().toLocaleTimeString();
            } catch (err){
                console.error('Failed to load collector report:', err);
                const msg = err.message || 'Error loading report';
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">' + escapeHtml(msg) + '</td></tr>';
            }
        }

        refreshBtn.addEventListener('click', () => loadCollectorReport());

        // Wire up real-time polling manager
        if (window.PickupRequestUpdateManager){
            const manager = new window.PickupRequestUpdateManager({ pollInterval: 5000 });

            manager.on('completed', async (data) => {
                // Only refresh if the completed pickup belongs to me
                if (!data || typeof data.collector_id === 'undefined') return;
                if (parseInt(data.collector_id) !== parseInt(collectorId)) return;
                console.log('Received pickup_completed for me, refreshing collector report', data);
                await loadCollectorReport();
            });

            manager.start();
        }

        // Initial load
        document.addEventListener('DOMContentLoaded', () => loadCollectorReport());
    })();
</script>

</div>

<script>
    // Helper function for rating stars
    function renderStars(count) {
        let stars = '';
        for (let i = 0; i < count; i++) {
            stars += '<i class="fa-solid fa-star filled"></i>';
        }
        for (let i = count; i < 5; i++) {
            stars += '<i class="fa-regular fa-star"></i>';
        }
        return stars;
    }

    // Helper for status badge
    function getFeedbackBadge(status) {
        const badgeMap = {
            'positive': '<span class="status success"><i class="fa-solid fa-circle-check"></i> Positive</span>',
            'review': '<span class="status danger"><i class="fa-solid fa-circle-exclamation"></i> Needs Review</span>',
            'active': '<span class="status info"><i class="fa-solid fa-circle-info"></i> Active</span>',
            'flagged': '<span class="status warning"><i class="fa-solid fa-flag"></i> Flagged</span>',
            'archived': '<span class="status secondary"><i class="fa-solid fa-archive"></i> Archived</span>'
        };
        return badgeMap[status] || `<span class="status secondary">${status}</span>`;
    }

    // Load analytics data from API
    async function loadAnalyticsData() {
        try {
            // Load metrics
            const metricsResponse = await fetch('/api/analytics/metrics', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });

            if (metricsResponse.ok) {
                const metricsData = await metricsResponse.json();
                const metrics = metricsData.data.feedback_metrics;

                // Update metric cards
                document.getElementById('avgRatingValue').textContent = metrics.average_rating.toFixed(1);
                document.getElementById('pendingReportsValue').textContent = metrics.pending_review_count;
                document.getElementById('totalFeedbackValue').textContent = metrics.total_feedback;

                // Load waste stats for chart
                loadWasteChart(metricsData.data.waste_collection);
            }

            // Load waste collection details (removed per request)
            // loadWasteCollectionTable();

            // Load feedback
            const feedbackResponse = await fetch('/api/analytics/collector-feedback?limit=50', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });

            if (feedbackResponse.ok) {
                const feedbackData = await feedbackResponse.json();
                const tableBody = document.getElementById('feedbackTableBody');

                if (feedbackData.data && feedbackData.data.length > 0) {
                    tableBody.innerHTML = feedbackData.data.map(fb => `
                        <tr>
                            <td>${escapeHtml(fb.collector_name || 'Unknown')}</td>
                            <td>${new Date(fb.created_at).toLocaleDateString()}</td>
                            <td>${escapeHtml(fb.feedback || '-')}</td>
                            <td>${renderStars(fb.rating)}</td>
                            <td>${getFeedbackBadge(fb.status)}</td>
                        </tr>
                    `).join('');
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: var(--space-16); color: var(--neutral-500);">No feedback records found.</td></tr>';
                }
            }
        } catch (error) {
            console.error('Error loading analytics data:', error);
            document.getElementById('feedbackTableBody').innerHTML = `
                <tr><td colspan="5" style="text-align: center; color: red;">Error loading feedback data</td></tr>
            `;
        }
    }



    // Load and render waste collection chart
    function loadWasteChart(wasteData) {
        // Organize waste data by month and type
        const monthlyData = {};
        const categories = new Set();

        (wasteData || []).forEach(item => {
            if (item.total_collected) {
                const month = new Date(item.month).toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
                if (!monthlyData[month]) monthlyData[month] = {};
                monthlyData[month][item.name] = parseFloat(item.total_collected);
                categories.add(item.name);
            }
        });

        const months = Object.keys(monthlyData).slice(-6);
        const colors = {
            'Organic': '#8b5a2b',
            'Glass': '#ff0000',
            'Paper': '#008000',
            'Metal': '#ffa500',
            'Plastic': '#0000ff'
        };

        const datasets = Array.from(categories).map(category => ({
            label: category,
            data: months.map(month => monthlyData[month][category] || 0),
            backgroundColor: colors[category] || '#cccccc'
        }));

        const ctx = document.getElementById('wasteChart').getContext('2d');
        if (window.wasteChartInstance) window.wasteChartInstance.destroy();

        window.wasteChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months.length > 0 ? months : ['No Data'],
                datasets: datasets.length > 0 ? datasets : [{
                    label: 'No Data',
                    data: [0],
                    backgroundColor: '#cccccc'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 13 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Kilograms (kg)', font: { size: 14 } } },
                    x: { title: { display: true, text: 'Months', font: { size: 14 } } }
                }
            }
        });
    }

    // Utility: escape HTML special characters
    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Add new feedback
    function addFeedback() {
        const collectorId = prompt('Enter collector ID:');
        if (!collectorId) return;

        const rating = prompt('Rating (1-5):');
        if (!rating || rating < 1 || rating > 5) {
            alert('Invalid rating');
            return;
        }

        const feedback = prompt('Feedback message:');
        if (!feedback) return;

        fetch('/api/analytics/feedback', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            credentials: 'include',
            body: JSON.stringify({ collector_id: collectorId, rating: rating, feedback: feedback })
        })
            .then(r => r.json())
            .then(d => {
                alert('Feedback added successfully');
                loadAnalyticsData();
            })
            .catch(e => alert('Error: ' + e.message));
    }

    // Get CSRF token (placeholder - implement based on your framework)
    function getCsrfToken() {
        // Extract from meta tag or cookies
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    // Load data on page load
    document.addEventListener('DOMContentLoaded', loadAnalyticsData);
</script>
