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


<h1>Analytics</h1>
<p>Track your recycling performance and environmental impact</p>

<div class="grid">
    <div class="card">
        <h3>Total Waste This Month</h3>
        <p class="value"><?= $totalWasteThisMonth ?> kg</p>
        <small>+<?= $wasteIncrease ?>% from last month</small>
    </div>
    <div class="card">
        <h3>Recycling Rate</h3>
        <p class="value"><?= $recyclingRate ?>%</p>
        <small>Success rate of collections</small>
    </div>
    <div class="card">
        <h3>CO₂ Saved</h3>
        <p class="value"><?= $co2SavedThisMonth ?> kg</p>
        <small>Environmental impact</small>
    </div>
    <div class="card">
        <h3>Energy Saved</h3>
        <p class="value"><?= $energySavedThisMonth ?> kWh</p>
        <small>Through recycling efforts</small>
    </div>
</div>

<!-- Charts -->
<div class="charts">
    <div class="chart-container">
        <h3>Monthly Waste Trends</h3>
        <canvas id="wasteTrendChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Waste Type Breakdown</h3>
        <canvas id="wasteBreakdownChart"></canvas>
    </div>
</div>

<!-- Include Chart.js from CDN -->
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

    // Pie Chart for Waste Breakdown
    const ctx2 = document.getElementById('wasteBreakdownChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: wasteTypeData.map(d => d.name),
            datasets: [{
                data: wasteTypeData.map(d => d.value),
                backgroundColor: wasteTypeData.map(d => d.color),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value}kg (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
</script>

