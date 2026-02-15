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

$allMonths = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

// Replace the above with a fiscal window starting in November and ending in October
$fiscalOrder = [
    'November','December','January','February','March','April',
    'May','June','July','August','September','October'
];

// Determine the fiscal end year (the most recent October that has completed)
// If current month is November or December, the most recent October is in the current year.
// Otherwise (Jan-Oct), the most recent October was in the previous year.
$currentMonth = (int) date('n');
$currentYear = (int) date('Y');
$fiscalEndYear = ($currentMonth >= 11) ? $currentYear : ($currentYear - 1);

// Build labels with year (e.g. "November 2024") and also a base-month list for mapping
$monthsWithYear = [];
$fiscalBaseMonths = []; // just month names in fiscal order for normalization mapping
foreach ($fiscalOrder as $m) {
    $yearForMonth = in_array($m, ['November','December']) ? ($fiscalEndYear - 1) : $fiscalEndYear;
    $monthsWithYear[] = $m . ' ' . $yearForMonth;
    $fiscalBaseMonths[] = $m;
}

// Build a map from normalized month name => original index in $months (if provided)
$monthPosMap = [];
foreach ($months as $idx => $m) {
    $t = strtotime($m);
    if ($t !== false) {
        $norm = date('F', $t);
    } elseif (is_numeric($m) && intval($m) >= 1 && intval($m) <= 12) {
        $norm = date('F', mktime(0, 0, 0, intval($m), 1));
    } else {
        $norm = ucfirst(strtolower($m));
    }
    // If multiple same months exist in source, keep the earliest index (existing behavior)
    if (!isset($monthPosMap[$norm])) {
        $monthPosMap[$norm] = $idx;
    }
}

// Normalizer: produce array of 12 values (one per fiscal month) from various possible input shapes
$normalizeSeries = function($series) use ($fiscalBaseMonths, $monthPosMap) {
    $out = [];
    for ($i = 0; $i < 12; $i++) {
        $m = $fiscalBaseMonths[$i]; // month name without year
        $val = 0;
        if (is_array($series)) {
            // If series uses month-name keys (e.g. 'November' or 'November 2024' => val)
            if (array_key_exists($m, $series)) {
                $val = $series[$m];
            } else {
                // try to find keys that include the month (e.g. 'November 2024')
                foreach ($series as $k => $v) {
                    $t = strtotime($k);
                    if ($t !== false && date('F', $t) === $m) {
                        $val = $v;
                        break;
                    }
                }
            }
            // If series is indexed and corresponds to the original $months order
            if ($val === 0 && isset($monthPosMap[$m]) && array_key_exists($monthPosMap[$m], $series)) {
                $val = $series[$monthPosMap[$m]];
            }
        }
        $out[] = is_numeric($val) ? (float)$val : 0.0;
    }
    return $out;
};

// Normalize category series. Ensure expected categories exist (safe defaults)
$expectedCats = ['Plastic','Paper','Metal','Glass','Organic'];
$normalizedBidding = [];
if (is_array($biddingValues) && count($biddingValues) > 0) {
    // For each provided category, normalize
    foreach ($biddingValues as $cat => $series) {
        $normalizedBidding[$cat] = $normalizeSeries($series);
    }
}
// Ensure all expected categories exist
foreach ($expectedCats as $cat) {
    if (!isset($normalizedBidding[$cat])) {
        $normalizedBidding[$cat] = array_fill(0, 12, 0.0);
    }
}

// Normalize monthly totals
$normalizedTotalBids = $normalizeSeries($totalBidsPerMonth);
$normalizedWonBids = $normalizeSeries($wonBidsPerMonth);

// Use fiscal 12 months (with year) for charts
$months = $monthsWithYear;
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Reports & Analytics</h2>
        </div>
    </header>

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

        <div class="chart-box">
            <h3 style="font-size: 20px; font-weight: bold;">Bidding Values for Each Waste Type</h3>
            <canvas id="biddingChart"></canvas>
        </div>

        <div class="chart-box">
            <h3 style="font-size: 20px; font-weight: bold;">Monthly Performance</h3>
            <canvas id="performanceChart"></canvas>
        </div>
</main>

<script>
    // PHP to JS data
    const months = <?= json_encode($months) ?>;
    const biddingValues = <?= json_encode($normalizedBidding) ?>;
    const totalBids = <?= json_encode($normalizedTotalBids) ?>;
    const wonBids = <?= json_encode($normalizedWonBids) ?>;

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
                        display: true, text: 'Months', font: { size: 14, weight: 'bold' }
                    }
                },
                y: {
                    title: {
                        display: true, text: 'Bidding Value', font: { size: 14, weight: 'bold' }
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
                        display: true, text: 'Months', font: { size: 14, weight: 'bold' }
                    }
                },
                y: {
                    title: {
                        display: true, text: 'Number of Bids', font: { size: 14, weight: 'bold' }
                    }
                }
            }
        }
    });
</script>