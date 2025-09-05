<?php
// Example dynamic values from backend (replace with DB queries)
$totalBids = 112;
$successfulBids = 61;
$successRate = round(($successfulBids / $totalBids) * 100, 2);

// Example chart data (could be loaded from DB)
$months = ["Feb", "Mar", "Apr", "May", "Jun", "Jul"];
$biddingValues = [
    "Plastic" => [1200, 1400, 1100, 1600, 1300, 1500],
    "Paper"   => [800, 900, 750, 1050, 950, 1000],
    "Metal"   => [1500, 1550, 1450, 1800, 1700, 1900],
    "Glass"   => [600, 700, 550, 800, 650, 750],
    "Organic" => [500, 600, 650, 450, 600, 700]
];
$totalBidsPerMonth = [15, 18, 12, 21, 17, 25];
$wonBidsPerMonth = [7, 9, 6, 14, 10, 15];
?>

<main class="content">
    <header class="page-header">
            <div class="page-header__content">
                    <h2 class="page-header__title">Reports & Analytics</h2>
            </div>
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
                <h3 style="font-size: 20px; font-weight: bold;">Bidding Values for Each Waste Type</h3>
                <canvas id="biddingChart"></canvas>
            </div>
            <div class="chart-box">
                <h3 style="font-size: 20px; font-weight: bold;">Monthly Performance</h3>
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <div class="stats">
            <div class="stat blue"><h2><?= $totalBids ?></h2><p>Total Bids Placed</p></div>
            <div class="stat green"><h2><?= $successfulBids ?></h2><p>Successful Bids</p></div>
            <div class="stat purple"><h2><?= $successRate ?>%</h2><p>Success Rate</p></div>
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
            { label: 'Plastic', data: biddingValues.Plastic, borderColor: '#0000ff', fill: false },
            { label: 'Paper', data: biddingValues.Paper, borderColor: '#008000', fill: false },
            { label: 'Metal', data: biddingValues.Metal, borderColor: '#ffa500', fill: false },
            { label: 'Glass', data: biddingValues.Glass, borderColor: '#ff0000', fill: false },
            { label: 'Organic', data: biddingValues.Organic, borderColor: '#8b5a2b', fill: false }
        ]
    },
    options: {
        scales: {
            x: {
                title: {
                    display: true, text: 'Months', font: { size: 12, weight: 'bold'}
                }
            },
            y: {
                title: {
                    display: true, text: 'Bidding Value', font: { size: 12, weight: 'bold'}
                }
            }
        }
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
    },
    options: {
        scales: {
            x: {
                title: {
                    display: true, text: 'Months', font: { size: 12, weight: 'bold'}
                }
            },
            y: {
                title: {
                    display: true, text: 'Number of Bids', font: { size: 12, weight: 'bold'}
                }
            }
        }
    }
});
</script>