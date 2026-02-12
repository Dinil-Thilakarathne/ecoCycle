<?php
// analytics.php

// Feedback data will be fetched via JavaScript API call
$collectorFeedback = []; // Will be populated by JavaScript
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div>
    <!-- Page Header -->
    <page-header title="Collector Feedback & Reports" description="Monitor and review feedback from collectors">
        <div data-header-action style="display: flex; gap: var(--space-2);">
            <button class="btn btn-primary" onclick="addFeedback()">
                <i class="fa-solid fa-comment-dots" style="margin-right: 8px;"></i> Add Feedback
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
        </div>
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Pending Reports</div>
                <div class="feature-card__icon"><i class="fa-solid fa-flag"></i></div>
            </div>
            <div class="feature-card__body" id="pendingReportsValue">-</div>
        </div>
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Total Feedbacks</div>
                <div class="feature-card__icon"><i class="fa-solid fa-comment"></i></div>
            </div>
            <div class="feature-card__body" id="totalFeedbackValue">-</div>
        </div>
    </div>

    <!-- Waste Collection Table -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-trash" style="margin-right: 8px;"></i> Waste Collection Details
            </h3>
            <p class="activity-card__description">Track waste pickups by customer and category</p>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto; max-height: 400px; overflow-y: auto;">
                <table class="data-table">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <tr>
                            <th><i class="fa-solid fa-id-card"></i> Customer ID</th>
                            <th><i class="fa-solid fa-user"></i> Customer Name</th>
                            <th><i class="fa-solid fa-box"></i> Waste Category</th>
                            <th><i class="fa-solid fa-weight"></i> Weight (kg)</th>
                            <th><i class="fa-solid fa-money-bill"></i> Amount (Rs)</th>
                        </tr>
                    </thead>
                    <tbody id="wasteCollectionTableBody">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 16px;">
                                <span class="loading">Loading waste collection data...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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

// Immediate validation on page load
console.log('=== Collector Analytics Debug ===');
console.log('Collector ID:', CURRENT_COLLECTOR_ID);
console.log('User Data:', <?= json_encode($user ?? []) ?>);

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
        const [metricsReq, feedbackReq, wasteReq] = await Promise.all([
            fetchWithTimeout(`/api/collector/metrics${params}`),
            fetchWithTimeout(`/api/collector/feedback${params}&limit=50`),
            fetchWithTimeout(`/api/collector/waste-collection${params}`)
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
                document.getElementById('avgRatingValue').innerHTML = `<small style="color: #dc3545;">${errMsg.substring(0, 20)}</small>`;
                document.getElementById('pendingReportsValue').innerHTML = `<small style="color: #dc3545;">${errMsg.substring(0, 20)}</small>`;
                document.getElementById('totalFeedbackValue').innerHTML = `<small style="color: #dc3545;">${errMsg.substring(0, 20)}</small>`;
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
            
            document.getElementById('avgRatingValue').innerHTML = `<small style="color: #dc3545; font-size: 0.7em;">${errorMsg}</small>`;
            document.getElementById('pendingReportsValue').innerHTML = `<small style="color: #dc3545; font-size: 0.7em;">${errorMsg}</small>`;
            document.getElementById('totalFeedbackValue').innerHTML = `<small style="color: #dc3545; font-size: 0.7em;">${errorMsg}</small>`;
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

        // Handle waste collection
        if (wasteReq.ok) {
            const wData = await wasteReq.json();
            console.log('Waste collection response:', wData);
            if (wData.success) {
                updateWasteTable(wData.data);
            } else {
                console.error('Waste API error:', wData.error);
                updateWasteTable([], wData.error || 'Failed to load waste data');
            }
        } else {
            const errorText = await wasteReq.text();
            console.error('Waste API failed:', wasteReq.status, errorText);
            updateWasteTable([], `API Error ${wasteReq.status}: ${errorText.substring(0, 100)}`);
        }
    } catch (error) {
        console.error('Polling Error:', error);
        const errorMsg = `Network Error: ${error.message}`;
        updateFeedbackTable([], errorMsg);
        updateWasteTable([], errorMsg);
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
    
    document.getElementById('avgRatingValue').textContent = avgRating.toFixed(1);
    document.getElementById('pendingReportsValue').textContent = pendingReports;
    document.getElementById('totalFeedbackValue').textContent = totalFeedback;
    
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
    document.getElementById('avgRatingValue').textContent = '...';
    document.getElementById('pendingReportsValue').textContent = '...';
    document.getElementById('totalFeedbackValue').textContent = '...';
    
    // Visual confirmation that JS is running
    if (!CURRENT_COLLECTOR_ID) {
        document.getElementById('avgRatingValue').textContent = '⚠️';
        document.getElementById('pendingReportsValue').textContent = '⚠️';
        document.getElementById('totalFeedbackValue').textContent = '⚠️';
        updateFeedbackTable([], 'ERROR: No collector ID found. User data may not be loaded properly.');
        updateWasteTable([], 'ERROR: No collector ID found. User data may not be loaded properly.');
        return;
    }
    
    refreshDashboard(); // Initial run
    setInterval(refreshDashboard, 30000); // Poll every 30 seconds for smoother performance
    console.log('Dashboard refresh scheduled every 30 seconds');
});

// Optional: Add Feedback handler
function addFeedback() {
    alert('Add feedback functionality coming soon!');
}
</script>
