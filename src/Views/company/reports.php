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
    'July','August','September','October','November','December'];

// Replace the above with a fiscal window 
$fiscalOrder = ['January','February','March','April','May','June',
                'July','August','September','October','November','December'];

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
    <div class="stat blue"><h2><?= (int) $totalBids ?></h2><p>Total Bids Placed</p></div>
    <div class="stat green"><h2><?= (int) $successfulBids ?></h2><p>Successful Bids</p></div>
    <div class="stat purple"><h2><?= htmlspecialchars(number_format((float) $successRate, 2)) ?>%</h2><p>Success Rate</p></div>
  </div>

<!-- Filter UI -->
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:1rem 1.25rem; background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:1rem;">
  <label style="font-size:16px;">Preset range:</label>
  <button class="range-btn" style="border:1px solid #e5e7ebdb; padding:5px 8px; border-radius: 5px;" data-range="0">Jan – Apr</button>
  <button class="range-btn" style="border:1px solid #e5e7ebdb; padding:5px 8px; border-radius: 5px;" data-range="1">May – Aug</button>
  <button class="range-btn" style="border:1px solid #e5e7ebdb; padding:5px 8px; border-radius: 5px;" data-range="2">Sep – Dec</button>
  <div style="width:1px; height:24px; background:#e5e7eb; margin:0 4px;"></div>
  <label style="font-size:16px;">Custom:</label>
  <select id="customStart" style="font-size:13px; padding:5px 8px; border:1px solid #e5e7eb;"></select>
  <span style="font-size:13px; color:#666;">to</span>
  <select id="customEnd" style="font-size:13px; padding:5px 8px; border:1px solid #e5e7eb;"></select>
  <button class="range-btn" id="applyCustom" style="border:1px solid #e5e7ebdb; padding:5px 8px; border-radius: 8px;">Apply</button>
</div>

<!-- Side-by-side charts -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
  <div class="chart-box">
    <h3 style="font-size:20px; font-weight:bold; margin-bottom:12px;">Bidding values per waste type</h3>
    <div style="position:relative; width:100%; height:420px;">
      <canvas id="biddingChart"></canvas>
    </div>
  </div>
  <div class="chart-box">
    <h3 style="font-size:20px; font-weight:bold; margin-bottom:12px;">Monthly performance</h3>
    <div style="position:relative; width:100%; height:420px;">
      <canvas id="performanceChart"></canvas>
    </div>
  </div>
</div>
</main>

<script>
const fiscalMonths = <?= json_encode($months) ?>;
const shortMonths  = fiscalMonths.map(m => m.split(' ')[0].substring(0,3));
const biddingValues = <?= json_encode($normalizedBidding) ?>;
const totalBids = <?= json_encode($normalizedTotalBids) ?>;
const wonBids   = <?= json_encode($normalizedWonBids) ?>;

const presets = [[0,3],[4,7],[8,11]];

// Populate custom dropdowns
['customStart','customEnd'].forEach(id => {
  const sel = document.getElementById(id);
  shortMonths.forEach((m,i) => sel.innerHTML += `<option value="${i}">${m}</option>`);
});
document.getElementById('customEnd').value = 3;

function getSlice(arr,s,e){ return arr.slice(s,e+1); }

const biddingChart = new Chart(document.getElementById('biddingChart'), {
  type: 'line',
  data: { labels: [], datasets: [
    { label:'Plastic', data:[], borderColor:'#3b82f6', fill:false, tension:0.3 },
    { label:'Paper',   data:[], borderColor:'#22c55e', fill:false, tension:0.3 },
    { label:'Metal',   data:[], borderColor:'#f59e0b', fill:false, tension:0.3 },
    { label:'Glass',   data:[], borderColor:'#ef4444', fill:false, tension:0.3 },
    { label:'Organic', data:[], borderColor:'#a16207', fill:false, tension:0.3 },
  ]},
  options: { responsive:true, maintainAspectRatio:false,
    scales: { x:{ ticks:{ autoSkip:false }, title:{ display:true, text:'Month' } },
              y:{ title:{ display:true, text:'Bidding value' } } } }
});

const perfChart = new Chart(document.getElementById('performanceChart'), {
  type: 'bar',
  data: { labels: [], datasets: [
    { label:'Total bids', data:[], backgroundColor:'lightblue' },
    { label:'Won bids',   data:[], backgroundColor:'green' },
  ]},
  options: { responsive:true, maintainAspectRatio:false,
    scales: { x:{ ticks:{ autoSkip:false }, title:{ display:true, text:'Month' } },
              y:{ title:{ display:true, text:'Number of bids' } } } }
});

function updateCharts(s, e) {
  const labels = shortMonths.slice(s, e+1);
  biddingChart.data.labels = labels;
  ['Plastic','Paper','Metal','Glass','Organic'].forEach((cat,i) => {
    biddingChart.data.datasets[i].data = getSlice(biddingValues[cat], s, e);
  });
  biddingChart.update();
  perfChart.data.labels = labels;
  perfChart.data.datasets[0].data = getSlice(totalBids, s, e);
  perfChart.data.datasets[1].data = getSlice(wonBids, s, e);
  perfChart.update();
}

updateCharts(0, 3);

document.querySelectorAll('.range-btn[data-range]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.range-btn[data-range]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const [s,e] = presets[btn.dataset.range];
    document.getElementById('customStart').value = s;
    document.getElementById('customEnd').value   = e;
    updateCharts(s, e);
  });
});

document.getElementById('applyCustom').addEventListener('click', () => {
  let s = parseInt(document.getElementById('customStart').value);
  let e = parseInt(document.getElementById('customEnd').value);
  if (s > e) [s,e] = [e,s];
  document.querySelectorAll('.range-btn[data-range]').forEach(b => b.classList.remove('active'));
  updateCharts(s, e);
});
</script>