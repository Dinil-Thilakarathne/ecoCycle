<?php
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
        <i class="fa-solid fa-comments" style="margin-right: 8px;"></i> Collector Feedback Report
      </h3>
      <p class="activity-card__description">Recent reports and feedbacks</p>
    </div>
    <div class="activity-card__content">
      <div style="overflow-x: auto;">
        <table class="data-table">
          <thead>
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
//   function renderStars(count) {
//     let stars = '';
//     for (let i = 0; i < count; i++) stars += '<i class="fa-solid fa-star filled"></i>';
//     for (let i = count; i < 5; i++) stars += '<i class="fa-regular fa-star"></i>';
//     return stars;
//   }


//   function getFeedbackBadge(status) {
//     const badgeMap = {
//       'active': '<span class="status info"><i class="fa-solid fa-circle-info"></i> Active</span>',
//       'flagged': '<span class="status warning"><i class="fa-solid fa-flag"></i> Flagged</span>',
//       'archived': '<span class="status secondary"><i class="fa-solid fa-archive"></i> Archived</span>'
//     };
//     return badgeMap[status] || `<span class="status secondary">${status}</span>`;
//   }

//   function escapeHtml(text) {
//     const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
//     return text.replace(/[&<>"']/g, m => map[m]);
//   }

// async function loadAnalyticsData() {
//   try {
//     // 1️⃣ Metrics
//     const metricsResp = await fetch('/api/collector/metrics', { credentials: 'include' });
//     if (metricsResp.ok) {
//       const metricsData = await metricsResp.json();
//       const metrics = metricsData.data.feedbackMetrics;
//       document.getElementById('avgRatingValue').textContent = metrics.averageRating.toFixed(1);
//       document.getElementById('pendingReportsValue').textContent = metrics.pendingReview;
//       document.getElementById('totalFeedbackValue').textContent = metrics.totalFeedback;
//     }

//     // 2️⃣ Feedback Table
//     const feedbackResp = await fetch('/api/collector/feedback?limit=50', { credentials: 'include' });
//     if (feedbackResp.ok) {
//       const feedbackData = await feedbackResp.json();
//       const tableBody = document.getElementById('feedbackTableBody');

//       if (feedbackData.success && feedbackData.data.length) {
//         tableBody.innerHTML = feedbackData.data.map(fb => `
//           <tr>
//             <td>${escapeHtml(fb.customer_id || '-')}</td>
//             <td>${escapeHtml(fb.customer_name || '-')}</td>
//             <td>${new Date(fb.created_at).toLocaleDateString()}</td>
//             <td>${escapeHtml(fb.feedback || '-')}</td>
//             <td>${renderStars(fb.rating || 0)}</td>
//           </tr>
//         `).join('');
//       } else {
//         tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:16px; color:#888;">No feedback records found.</td></tr>';
//       }
//     }
//   } catch (e) {
//     console.error('Error loading analytics:', e);
//     document.getElementById('feedbackTableBody').innerHTML =
//       '<tr><td colspan="5" style="text-align:center; padding:16px; color:red;">Error loading feedback.</td></tr>';
//   }
// }


//   function getCsrfToken() {
//     return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
//   }

//   function addFeedback() {
//     const collectorId = prompt('Enter collector ID:');
//     if (!collectorId) return;

//     const rating = parseInt(prompt('Rating (1-5):'), 10);
//     if (!rating || rating < 1 || rating > 5) {
//       alert('Invalid rating');
//       return;
//     }

//     const feedback = prompt('Feedback message:');
//     if (!feedback) return;

//     fetch('/api/collector/feedback', {
//       method: 'POST',
//       headers: {
//         'Content-Type': 'application/json',
//         'X-CSRF-Token': getCsrfToken()
//       },
//       credentials: 'include',
//       body: JSON.stringify({ collector_id: collectorId, rating: rating, feedback: feedback })
//     })
//     .then(r => r.json())
//     .then(d => {
//       alert('Feedback added successfully');
//       loadAnalyticsData();
//     })
//     .catch(e => alert('Error: ' + e.message));
//   }

//   async function loadFeedbackData() {
//   try {
//     const resp = await fetch('/api/collector/feedback?limit=50', { credentials: 'include' });
//     const result = await resp.json();

//     const tableBody = document.getElementById('feedbackTableBody');

//     if (result.success && result.data.length > 0) {
//       tableBody.innerHTML = result.data.map(fb => `
//         <tr>
//           <td>${escapeHtml(fb.customer_id)}</td>
//           <td>${escapeHtml(fb.customer_name)}</td>
//           <td>${new Date(fb.created_at).toLocaleDateString()}</td>
//           <td>${escapeHtml(fb.feedback)}</td>
//           <td>${renderStars(fb.rating)}</td>
//         </tr>
//       `).join('');
//     } else {
//       tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:16px; color:#888;">No feedback records found.</td></tr>';
//     }
//   } catch (e) {
//     console.error('Error loading feedback:', e);
//     document.getElementById('feedbackTableBody').innerHTML =
//       '<tr><td colspan="5" style="text-align:center; padding:16px; color:red;">Error loading feedback.</td></tr>';
//   }
// }

// async function loadWasteCollectionData() {
//   try {
//     const resp = await fetch('/api/collector/waste-collection?limit=50', { credentials: 'include' });
//     const result = await resp.json();

//     const tableBody = document.getElementById('wasteCollectionTableBody');

//     if (result.success && result.data.length > 0) {
//       tableBody.innerHTML = result.data.map(r => `
//         <tr>
//           <td>${escapeHtml(r.customer_id)}</td>
//           <td>${escapeHtml(r.customer_name)}</td>
//           <td>${escapeHtml(r.category)}</td>
//           <td>${r.weight}</td>
//           <td>${r.amount.toFixed(2)}</td>
//         </tr>
//       `).join('');
//     } else {
//       tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:16px; color:#888;">No waste collection records found.</td></tr>';
//     }
//   } catch (e) {
//     console.error('Error loading waste collection:', e);
//     document.getElementById('wasteCollectionTableBody').innerHTML =
//       '<tr><td colspan="5" style="text-align:center; padding:16px; color:red;">Error loading data.</td></tr>';
//   }
// }

// // Polling for waste collection updates
// document.addEventListener('DOMContentLoaded', () => {
//   loadWasteCollectionData();
//   setInterval(loadWasteCollectionData, 5000);
// });


// // Poll every 5 seconds for real-time updates
// document.addEventListener('DOMContentLoaded', () => {
//   loadFeedbackData();
//   setInterval(loadFeedbackData, 5000);
// });


//   document.addEventListener('DOMContentLoaded', loadAnalyticsData);

// 1. Configuration: Set the collector ID here 
// (In a real app, this might come from a hidden input or session variable)
const CURRENT_COLLECTOR_ID = 1; 

/**
 * Main Orchestrator: Fetches all data for the page
 */
async function refreshDashboard() {
    console.log('Refreshing dashboard data...');
    const params = `?collector_id=${CURRENT_COLLECTOR_ID}`;

    try {
        // Run fetches in parallel for better performance
        const [metricsReq, feedbackReq, wasteReq] = await Promise.all([
            fetch(`/api/collector/metrics${params}`, { credentials: 'include' }),
            fetch(`/api/collector/feedback${params}&limit=50`, { credentials: 'include' }),
            fetch(`/api/collector/waste-collection${params}`, { credentials: 'include' })
        ]);

        if (metricsReq.ok) {
            const mData = await metricsReq.json();
            updateMetricsCards(mData.data.feedbackMetrics);
        }

        if (feedbackReq.ok) {
            const fData = await feedbackReq.json();
            updateFeedbackTable(fData.data);
        }

        if (wasteReq.ok) {
            const wData = await wasteReq.json();
            updateWasteTable(wData.data);
        }

    } catch (error) {
        console.error('Polling Error:', error);
    }
}

/**
 * Updates UI Cards
 */
function updateMetricsCards(metrics) {
    if (!metrics) return;
    document.getElementById('avgRatingValue').textContent = metrics.averageRating.toFixed(1);
    // Showing low ratings as 'Pending Reports' or issues to address
    document.getElementById('pendingReportsValue').textContent = metrics.lowRatings || 0;
    document.getElementById('totalFeedbackValue').textContent = metrics.totalFeedback;
}

/**
 * Updates Feedback Table
 */
function updateFeedbackTable(data) {
    const tableBody = document.getElementById('feedbackTableBody');
    if (data && data.length > 0) {
        tableBody.innerHTML = data.map(fb => `
          <tr>
            <td>${escapeHtml(String(fb.customer_id))}</td>
            <td>${escapeHtml(fb.customer_name)}</td>
            <td>${new Date(fb.created_at).toLocaleDateString()}</td>
            <td>${escapeHtml(fb.feedback)}</td>
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
function updateWasteTable(data) {
    const tableBody = document.getElementById('wasteCollectionTableBody');
    if (data && data.length > 0) {
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
        stars += i < count 
            ? '<i class="fa-solid fa-star" style="color: #ffc107;"></i>' 
            : '<i class="fa-regular fa-star" style="color: #ccc;"></i>';
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
    refreshDashboard(); // Initial run
    setInterval(refreshDashboard, 5000); // Poll every 5 seconds
});
</script>
