<?php
$reportData = $reportData ?? [
    'totalBids' => 0,
    'successfulBids' => 0,
    'successRate' => 0,
    'months' => [],
    'totalBidsPerMonth' => [],
    'wonBidsPerMonth' => [],
    'categorySeries' => [],
];

$months = $reportData['months'];
$biddingValues = $reportData['categorySeries'];
$totalBidsPerMonth = $reportData['totalBidsPerMonth'];
$wonBidsPerMonth = $reportData['wonBidsPerMonth'];
$totalBids = $reportData['totalBids'];
$successfulBids = $reportData['successfulBids'];
$successRate = $reportData['successRate'];
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Reports & Analytics</h2>
        </div>
    </header>
    <div class="filters">
        <input type="text" placeholder="Search reports...">
        <select>
            <option>Report type</option>
        </select>
        <select>
            <option>Date range</option>
        </select>
        <button class="btn btn-outline">Filter</button>
        <button class="btn btn-primary">Export</button>
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
        <div class="stat blue">
            <h2><?= (int) $totalBids ?></h2>
            <p>Total Bids Placed</p>
        </div>
        <div class="stat green">
            <h2><?= (int) $successfulBids ?></h2>
            <p>Successful Bids</p>
        </div>
        <div class="stat purple">
            <h2><?= htmlspecialchars(number_format((float) $successRate, 2)) ?>%</h2>
            <p>Success Rate</p>
        </div>
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
                        display: true, text: 'Months', font: { size: 12, weight: 'bold' }
                    }
                },
                y: {
                    title: {
                        display: true, text: 'Bidding Value', font: { size: 12, weight: 'bold' }
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
                        display: true, text: 'Months', font: { size: 12, weight: 'bold' }
                    }
                },
                y: {
                    title: {
                        display: true, text: 'Number of Bids', font: { size: 12, weight: 'bold' }
                    }
                }
            }
        }
    });
</script>