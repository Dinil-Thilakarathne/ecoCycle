<!-- Customer Feedback Table 
/*<div class="feedback">
  <h3><b>Customer Feedbacks</b></h3>
  <table>
    <thead>
      <tr>
        <th>Customer</th>
        <th>Rating</th>
        <th>Feedback</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>John Smith</td>
        <td class="rating">5 <i class="fa-solid fa-star" style="color: gold;"></i></td>
        <td class="comment">Very punctual and friendly service.</td>
        <td>19-Aug-2025</td>
      </tr>
      <tr>
        <td>Sarah Johnson</td>
        <td class="rating">4 <i class="fa-solid fa-star" style="color: gold;"></i></td>
        <td class="comment">Collected items efficiently, but a bit late.</td>
        <td>18-Aug-2025</td>
      </tr>
      <tr>
        <td>Mike Wilson</td>
        <td class="rating">5 <i class="fa-solid fa-star" style="color: gold;"></i></td>
        <td class="comment">Excellent service, highly recommended.</td>
        <td>17-Aug-2025</td>
      </tr>
    </tbody>
  </table>
</div>
<div class = "bottom-container">
<div class="feedback-section">
  <h2 class="feedback-title">Customer Feedbacks</h2>

  Feedback Table 
  <div class="feedback-table-container">
    <table class="feedback-table">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Rating</th>
          <th>Feedback</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>John Smith</td>
          <td>5 ⭐</td>
          <td>Very punctual and friendly service.</td>
          <td>19-Aug-2025</td>
        </tr>
        <tr>
          <td>Sarah Johnson</td>
          <td>2 ⭐</td>
          <td>Collected items late and not very polite.</td>
          <td>18-Aug-2025</td>
        </tr>
        <tr>
          <td>Mike Wilson</td>
          <td>4 ⭐</td>
          <td>Good service overall, could be faster.</td>
          <td>17-Aug-2025</td>
        </tr>
      </tbody>
    </table>
  </div>

   Reports with Bad Ratings 
  <div class="bad-feedbacks">
    <h3>⚠ Reports Needing Attention</h3>
    <div class="bad-report">
      <p><strong>Customer:</strong> Sarah Johnson</p>
      <p><strong>Issue:</strong> Pickup was delayed, customer was unhappy.</p>
      <p><strong>Rating:</strong> 2 ⭐</p>
      <p><strong>Date:</strong> 18-Aug-2025</p>
    </div>
  </div>
</div></div>


  Reports Section 
  <div class="reports">
    <h3><b>Incident Reports</b></h3>
    <div class="report-item">
      <div class="report-title">Late Pickup at 456 Pine Avenue</div>
      <div class="report-date">Reported on 18-Aug-2025</div>
      <div class="report-comment">Customer noted delay of 30 mins.</div>
    </div>
    <div class="report-item">
      <div class="report-title">Customer Complaint: Mixed Materials</div>
      <div class="report-date">Reported on 16-Aug-2025</div>
      <div class="report-comment">Plastic and metal were collected together accidentally.</div>
    </div>
    <div class="report-item">
      <div class="report-title">Positive Feedback: Excellent Service</div>
      <div class="report-date">Reported on 14-Aug-2025</div>
      <div class="report-comment">Customer appreciated punctuality and cleanliness.</div>
    </div>
  </div>-->

<?php
// Sample feedback data (replace with database query in production)
$collectorFeedback = [
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

// Helper function for rating stars
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

    <div class="feature-cards">
    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Average Ratings</div>
        <div class="feature-card__icon"><i class="fa-solid fa-star"></i></div>
      </div>
      <div class="feature-card__body">4.7</div>
      <div class="feature-card__footer">
        <span class="desc">Based on customer feedback</span>
      </div>
    </div>

    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Pending Reports</div>
        <div class="feature-card__icon"><i class="fa-solid fa-flag"></i></div>
      </div>
      <div class="feature-card__body">3</div>
      <div class="feature-card__footer">
        <span class="desc">Need Attention</span>
      </div>
    </div>

    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Total Feedbacks</div>
        <div class="feature-card__icon"><i class="fa-solid fa-comment"></i></div>
      </div>
      <div class="feature-card__body">3</div>
      <div class="feature-card__footer">
        <span class="desc">Recieved from customers</span>
      </div>
    </div>
  </div>

  <!-- Waste Collection Chart -->
<!--<script>
const ctx = document.getElementById('wasteChart').getContext('2d');
const wasteChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['January', 'February', 'March', 'April', 'May', 'June'],
        datasets: [{
            label: 'Total Waste Collected (kg)',
            data: [1200, 1500, 900, 1800, 2000, 1700], // sample data
            backgroundColor: 'rgba(76, 175, 80, 0.6)',
            borderColor: 'rgba(76, 175, 80, 1)',
            borderWidth: 1,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 500
                },
                title: {
                    display: true,
                    text: 'Kilograms (kg)',
                    font: { size: 14 }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Months',
                    font: { size: 14 }
                }
            }
        }
    }
});
</script>-->

<!-- Waste Collection Chart -->
<div class="pc-card">
  <h3 style="font-size: 20px; font-weight: bold;">Monthly Waste Collection by Type (kg)</h3>
  <canvas id="wasteChart" style="max-height: 380px;"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('wasteChart').getContext('2d');

const wasteChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['January', 'February', 'March', 'April', 'May', 'June'], // Months
        datasets: [
            {
                label: 'Organic',
                data: [400, 450, 300, 500, 550, 480],
                backgroundColor: ' #8b5a2b'
            },
            {
                label: 'Glass',
                data: [200, 220, 180, 250, 270, 230],
                backgroundColor:' #ff0000'
            },
            {
                label: 'Paper',
                data: [300, 350, 280, 400, 420, 390],
                backgroundColor: '#008000'
            },
            {
                label: 'Metal',
                data: [150, 180, 120, 200, 210, 170],
                backgroundColor: '#ffa500'
            },
            {
                label: 'Plastic',
                data: [250, 300, 220, 350, 370, 310],
                backgroundColor: '#0000ff'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
                labels: { font: { size: 13 } }
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Kilograms (kg)',
                    font: { size: 14 }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Months',
                    font: { size: 14 }
                }
            }
        }
    }
});
</script>


  
    <!-- Feedback Table -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-comments" style="margin-right: 8px;"></i>
                Collector Feedback Reports
            </h3>
            <p class="activity-card__description">Recent reports and feedback on collectors</p>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collectorFeedback as $fb): ?>
                            <tr>
                                <td><?= htmlspecialchars($fb['collectorName']) ?></td>
                                <td><?= htmlspecialchars($fb['date']) ?></td>
                                <td><?= htmlspecialchars($fb['feedback']) ?></td>
                                <td><?= renderStars($fb['rating']) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($collectorFeedback)): ?>
                            <tr>
                                <td colspan="6"
                                    style="text-align: center; padding: var(--space-16); color: var(--neutral-500);">
                                    No feedback records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function addFeedback() {
        alert('Open feedback form - in production, this would allow adding a new feedback record.');
    }
    function viewFeedback(id) {
        alert('Viewing feedback details for ' + id);
    }
    function approveFeedback(id) {
        alert('Approved feedback for ' + id);
    }
    function rejectFeedback(id) {
        alert('Rejected feedback for ' + id);
    }
</script>
