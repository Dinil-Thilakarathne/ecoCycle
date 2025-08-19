<?php
// Example dynamic values from backend (replace with DB queries)
$totalBids = 127;
$successfulBids = 65;
$successRate = round(($successfulBids / $totalBids) * 100, 2);
$totalRevenue = 28300;

// Example chart data (could be loaded from DB)
$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun"];
$biddingValues = [
    "Plastic" => [1200, 1400, 1100, 1600, 1300, 1500],
    "Paper"   => [800, 900, 750, 1050, 950, 1000],
    "Metal"   => [1500, 1550, 1450, 1800, 1700, 1900],
    "Glass"   => [600, 700, 550, 800, 650, 700]
];
$totalBidsPerMonth = [15, 18, 12, 21, 17, 25];
$wonBidsPerMonth = [7, 9, 6, 14, 10, 15];
?>

<main class="content">
        <header class="header">
            <h1>Reports & Analytics</h1>
        </header>
        <div class="filters">
            <input type="text" placeholder="Search reports...">
            <select><option>Report type</option></select>
            <select><option>Date range</option></select>
            <button class="filter-btn">Filter</button>
            <button class="export-btn">Export</button>
        </div>

        <div class="charts">
            <div class="chart-box">
                <h3>Bidding Values for Each Waste Type</h3>
                <canvas id="biddingChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Monthly Performance</h3>
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <div class="stats">
            <div class="stat blue"><h2><?= $totalBids ?></h2><p>Total Bids Placed</p></div>
            <div class="stat green"><h2><?= $successfulBids ?></h2><p>Successful Bids</p></div>
            <div class="stat purple"><h2><?= $successRate ?>%</h2><p>Success Rate</p></div>
            <div class="stat orange"><h2>$<?= number_format($totalRevenue) ?></h2><p>Total Revenue</p></div>
        </div>
    </main>
</div>

<script>
// PHP to JS data
const months = <?= json_encode($months) ?>;
const biddingValues = <?= json_encode($biddingValues) ?>;
const totalBids = <?= json_encode($totalBidsPerMonth) ?>;
const wonBids = <?= json_encode($wonBidsPerMonth) ?>;

// Line Chart - Bidding Values
new Chart(document.getElementById('biddingChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [
            { label: 'Plastic', data: biddingValues.Plastic, borderColor: 'blue', fill: false },
            { label: 'Paper', data: biddingValues.Paper, borderColor: 'green', fill: false },
            { label: 'Metal', data: biddingValues.Metal, borderColor: 'orange', fill: false },
            { label: 'Glass', data: biddingValues.Glass, borderColor: 'red', fill: false }
        ]
    }
});

// Bar Chart - Monthly Performance
new Chart(document.getElementById('performanceChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Total Bids', data: totalBids, backgroundColor: 'lightblue' },
            { label: 'Won Bids', data: wonBids, backgroundColor: 'green' }
        ]
    }
});
</script>