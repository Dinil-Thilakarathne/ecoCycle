<?php
// ---------- PHP Variables ----------
$totalWasteThisMonth = 96;
$totalWasteLastMonth = 78;
$wasteIncrease = round((($totalWasteThisMonth - $totalWasteLastMonth) / $totalWasteLastMonth) * 100, 1);

$recyclingRate = 89;
$co2SavedThisMonth = 78;
$energySavedThisMonth = 195;

// Monthly data for line chart
$monthlyData = [
    ["month" => "Jan", "plastic" => 15, "paper" => 8, "glass" => 5, "metal" => 12, "organic" => 18],
    ["month" => "Feb", "plastic" => 18, "paper" => 10, "glass" => 7, "metal" => 14, "organic" => 22],
    ["month" => "Mar", "plastic" => 12, "paper" => 7, "glass" => 6, "metal" => 16, "organic" => 20],
    ["month" => "Apr", "plastic" => 22, "paper" => 14, "glass" => 9, "metal" => 19, "organic" => 25],
    ["month" => "May", "plastic" => 16, "paper" => 11, "glass" => 7, "metal" => 17, "organic" => 21],
    ["month" => "Jun", "plastic" => 20, "paper" => 13, "glass" => 10, "metal" => 20, "organic" => 28]
];

// Waste type data for pie chart
$wasteTypeData = [
    ["name" => "Plastic", "value" => 103, "color" => "#3b82f6"],
    ["name" => "Paper", "value" => 63, "color" => "#10b981"],
    ["name" => "Glass", "value" => 44, "color" => "#f59e0b"],
    ["name" => "Metal", "value" => 98, "color" => "#ef4444"],
    ["name" => "Organic", "value" => 134, "color" => "#8b5cf6"],
];
?>



<div class="dashboard-page">
    <div class="page-header">
        <div class="header-content">
            
            <p ><b>Track your recycling performance and environmental impact</b></p>
        </div>
    </div>

        <div class="stats-grid" style="margin-bottom:2.5rem;">
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Total Weight</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-dumpster"></i></div>
                </div>
                <p class="feature-card__body" style="font-size:2rem;font-weight:700;color:#1e293b;">
                    <?= $totalWasteThisMonth ?> kg
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">Total waste you have given</span>
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Income</h3>
                </div>
                <p class="feature-card__body" style="font-size:2rem;font-weight:700;color:#1e293b;">
                    <!-- Example income, replace with real value if available -->
                    Rs 0.00
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">Total income this month</span>
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Progress</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-star"></i></div>
                </div>
                <?php $customerRating = 82; // Example rating percent ?>
                <p class="feature-card__body" style="font-size:2rem;font-weight:700;color:#1e293b;">
                    <?= $customerRating ?>%
                </p>
                <div style="width:100%;background:#f1f5f9;border-radius:8px;height:12px;margin-bottom:0.5rem;">
                  <div style="width:<?= $customerRating ?>%;background:#22c55e;height:100%;border-radius:8px;"></div>
                </div>
                <div class="feature-card__footer">
                    <span class="tag success">Your customer rating progress</span>
                </div>
            </div>
        </div>

        <div class="cards-grid" style="grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:2.5rem;">
            <!-- Multi-bar graph for waste types per month (last 3 months) -->
            <div class="card">
                <div class="section-header" style="margin-bottom:1rem;">
                    <h2 class="section-title" style="font-size:1.25rem;font-weight:600;color:#1e293b;text-align:center;margin-left:2.5rem;">Weight of Waste Types</h2>
                </div>
                <div style="height:320px;width:100%;">
                    <canvas id="wasteTypeBarChart" style="width:100%;height:100%;"></canvas>
                </div>
            </div>
            <!-- Bar graph for income per month -->
            <div class="card">
                <div class="section-header" style="margin-bottom:1rem;">
                    <h2 class="section-title" style="font-size:1.25rem;font-weight:600;color:#1e293b;text-align:center;margin-left:2.5rem;">Monthly Income</h2>
                </div>
                <div style="height:320px;width:100%;">
                    <canvas id="incomeBarChart" style="width:100%;height:100%;"></canvas>
                </div>
            </div>
        </div>
</div>

<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Example data for last 3 months
const months = ['Apr', 'May', 'Jun'];
const wasteTypeChartData = {
    'Plastic': [22, 16, 20],
    'Glass': [9, 7, 10],
    'Paper': [14, 11, 13],
    'Metal': [19, 17, 20], // Added Metal bar
    'Organic': [25, 21, 28]
};
const wasteTypeColors = {
    'Plastic': '#3b82f6', // blue
    'Glass': '#f59e0b',   // yellow
    'Paper': '#10b981',   // green
    'Metal': '#ef4444',   // red
    'Organic': '#8b5cf6'  // purple
};

const wasteTypeDatasets = Object.keys(wasteTypeChartData).map(type => ({
    label: type,
    data: wasteTypeChartData[type],
    backgroundColor: wasteTypeColors[type],
    borderRadius: 6,
    maxBarThickness: 40
}));

const ctxWaste = document.getElementById('wasteTypeBarChart').getContext('2d');
new Chart(ctxWaste, {
    type: 'bar',
    data: {
        labels: months,
        datasets: wasteTypeDatasets
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top', labels: { color: '#1e293b', font: { weight: 'bold' } } },
            title: { display: false }
        },
        scales: {
            x: {
                stacked: false,
                grid: { display: false },
                ticks: { color: '#64748b', font: { weight: 'bold' } },
                title: {
                    display: true,
                    text: 'Month',
                    color: '#1e293b',
                    font: { weight: 'bold' }
                }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: { color: '#64748b' },
                title: {
                    display: true,
                    text: 'Weight (kg)',
                    color: '#1e293b',
                    font: { weight: 'bold' }
                }
            }
        }
    }
});

// Example income data for last 5 months (in Rs)
const incomeMonths = ['Feb', 'Mar', 'Apr', 'May', 'Jun'];
const incomeData = [9500, 11200, 12000, 13500, 14200];
const ctxIncome = document.getElementById('incomeBarChart').getContext('2d');
new Chart(ctxIncome, {
    type: 'bar',
    data: {
        labels: incomeMonths,
        datasets: [{
            label: 'Income (Rs)',
            data: incomeData,
            backgroundColor: '#22c55e',
            borderRadius: 6,
            maxBarThickness: 28,
            barPercentage: 0.6,
            categoryPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { display: false }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#64748b', font: { weight: 'bold' } },
                title: {
                    display: true,
                    text: 'Month',
                    color: '#1e293b',
                    font: { weight: 'bold' }
                }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    color: '#64748b',
                    callback: function(value) { return 'Rs ' + value.toLocaleString(); }
                },
                title: {
                    display: true,
                    text: 'Income (Rs)',
                    color: '#1e293b',
                    font: { weight: 'bold' }
                }
            }
        }
    }
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
    // PHP to JS data transfer
    const monthlyData = <?= json_encode($monthlyData) ?>;
    const wasteTypeData = <?= json_encode($wasteTypeData) ?>;

    // Line Chart for Waste Trends
    const ctx1 = document.getElementById('wasteTrendChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'Plastic',
                    data: monthlyData.map(d => d.plastic),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Paper',
                    data: monthlyData.map(d => d.paper),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Glass',
                    data: monthlyData.map(d => d.glass),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Metal',
                    data: monthlyData.map(d => d.metal),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Organic',
                    data: monthlyData.map(d => d.organic),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Weight (kg)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });

</script>

