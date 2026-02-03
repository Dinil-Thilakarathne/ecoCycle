<?php
// Feedback data will be fetched via JavaScript API call
// This ensures real-time data from the database
$collectorFeedback = []; // Will be populated by JavaScript
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
            <div class="feature-card__footer">
                <span class="desc">Based on customer feedback</span>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Pending Reports</div>
                <div class="feature-card__icon"><i class="fa-solid fa-flag"></i></div>
            </div>
            <div class="feature-card__body" id="pendingReportsValue">-</div>
            <div class="feature-card__footer">
                <span class="desc">Need Attention</span>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Total Feedbacks</div>
                <div class="feature-card__icon"><i class="fa-solid fa-comment"></i></div>
            </div>
            <div class="feature-card__body" id="totalFeedbackValue">-</div>
            <div class="feature-card__footer">
                <span class="desc">Received from customers</span>
            </div>
        </div>
    </div>

    <!-- Waste Collection Table -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-trash" style="margin-right: 8px;"></i>
                Waste Collection Details
            </h3>
            <p class="activity-card__description">Track waste pickups by customer and category</p>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
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
                            <td colspan="5" style="text-align: center; padding: var(--space-16);">
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
</div>

<script>
    // Helper function for rating stars
  /*  function renderStars(count) {
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

            // Load waste collection details
            loadWasteCollectionTable();

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

    // Load waste collection details
    async function loadWasteCollectionTable() {
        try {
            const response = await fetch('/api/analytics/waste-stats?limit=50', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                const tableBody = document.getElementById('wasteCollectionTableBody');

                if (data.data && data.data.length > 0) {
                    tableBody.innerHTML = data.data.map(item => `
                        <tr>
                            <td>${escapeHtml(item.customer_id || '-')}</td>
                            <td>${escapeHtml(item.customer_name || 'Unknown')}</td>
                            <td><span class="badge" style="background-color: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px;">${escapeHtml(item.waste_category || '-')}</span></td>
                            <td>${parseFloat(item.weight || 0).toFixed(2)} kg</td>
                            <td style="font-weight: 600;">Rs ${parseFloat(item.amount || 0).toFixed(2)}</td>
                        </tr>
                    `).join('');
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: var(--space-16); color: var(--neutral-500);">No waste collection records found.</td></tr>';
                }
            }
        } catch (error) {
            console.error('Error loading waste collection data:', error);
            document.getElementById('wasteCollectionTableBody').innerHTML = `
                <tr><td colspan="5" style="text-align: center; color: red;">Error loading waste collection data</td></tr>
            `;
        }
    }*/


function renderStars(count) {
    let stars = '';
    for (let i = 0; i < count; i++) stars += '<i class="fa-solid fa-star filled"></i>';
    for (let i = count; i < 5; i++) stars += '<i class="fa-regular fa-star"></i>';
    return stars;
}

function getFeedbackBadge(status) {
    const badgeMap = {
        'active': '<span class="status info"><i class="fa-solid fa-circle-info"></i> Active</span>',
        'flagged': '<span class="status warning"><i class="fa-solid fa-flag"></i> Flagged</span>',
        'archived': '<span class="status secondary"><i class="fa-solid fa-archive"></i> Archived</span>'
    };
    return badgeMap[status] || `<span class="status secondary">${status}</span>`;
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

async function loadAnalyticsData() {
    try {
        // Metrics
        const metricsResp = await fetch('/collector/analytics/getMetrics', { credentials: 'include' });
        if (metricsResp.ok) {
            const metricsData = await metricsResp.json();
            const metrics = metricsData.data.feedbackMetrics;
            document.getElementById('avgRatingValue').textContent = metrics.averageRating.toFixed(1);
            document.getElementById('pendingReportsValue').textContent = metrics.pendingReview;
            document.getElementById('totalFeedbackValue').textContent = metrics.totalFeedback;
        }

        // Feedback Table
        const feedbackResp = await fetch('/collector/analytics/getFeedback?limit=50', { credentials: 'include' });
        if (feedbackResp.ok) {
            const feedbackData = await feedbackResp.json();
            const tableBody = document.getElementById('feedbackTableBody');

            if (feedbackData.data.length) {
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
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:16px; color:#888;">No feedback records found.</td></tr>';
            }
        }
    } catch (e) {
        console.error('Error loading analytics:', e);
    }
}

document.addEventListener('DOMContentLoaded', loadAnalyticsData);


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
