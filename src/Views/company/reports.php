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

$currentMonth = (int) date('n');
$currentYear  = (int) date('Y');

// Build a flat list of {year, month} entries covering the last N fiscal years.
// Each fiscal year runs January–December of a single calendar year.
// We expose the current year and the two preceding years so the user can pick.
$availableYears = [$currentYear - 2, $currentYear - 1, $currentYear];

// For each year, store month labels, normalised totals, won, and category series.
// ──────────────────────────────────────────────────────────────────────────────
// Helper: normalise a series keyed by month name / index to a 12-element array
// for a specific calendar year.
// $months is now ["September 2025", "October 2025", ...] — full "F Y" labels.
// $totalBidsPerMonth and $wonBidsPerMonth are positional arrays aligned to $months.
// $categorySeries[$cat] is a string-keyed array: ["September 2025" => 12.5, ...]
//
// Build a positional lookup: "F Y" label => index in $months array.
$monthIndexMap = [];
foreach ($months as $idx => $label) {
    $t = strtotime($label);
    $key = $t !== false ? date('F Y', $t) : trim($label);
    $monthIndexMap[$key] = $idx;
}

// Returns a strict 12-element array (Jan–Dec) for $year.
// - Category series: looked up directly by "Month YYYY" string key
// - Positional series (totals/won): looked up via $monthIndexMap
// Only data whose label matches BOTH the month AND $year is used. No cross-year bleed.
$normalizeSeriesForYear = function($series, $year) use ($allMonths, $monthIndexMap) {
    $out = [];
    foreach ($allMonths as $m) {
        $key = $m . ' ' . $year;
        $val = 0;
        if (is_array($series)) {
            if (array_key_exists($key, $series)) {
                // Category series: keyed by "F Y" label
                $val = $series[$key];
            } elseif (isset($monthIndexMap[$key]) && array_key_exists($monthIndexMap[$key], $series)) {
                // Positional series (totals/won): look up by index
                $val = $series[$monthIndexMap[$key]];
            }
            // No other fallbacks — different year always returns 0.
        }
        $out[] = is_numeric($val) ? (float) $val : 0.0;
    }
    return $out;
};

$expectedCats = ['Plastic','Paper','Metal','Glass','Organic'];

// Build per-year data structures
$yearData = [];
foreach ($availableYears as $yr) {
    $catData = [];
    if (is_array($biddingValues) && count($biddingValues) > 0) {
        foreach ($biddingValues as $cat => $series) {
            $catData[$cat] = $normalizeSeriesForYear($series, $yr);
        }
    }
    foreach ($expectedCats as $cat) {
        if (!isset($catData[$cat])) {
            $catData[$cat] = array_fill(0, 12, 0.0);
        }
    }

    $yearData[$yr] = [
        'months'     => $allMonths,
        'shortMonths'=> array_map(fn($m) => substr($m, 0, 3), $allMonths),
        'totalBids'  => $normalizeSeriesForYear($totalBidsPerMonth, $yr),
        'wonBids'    => $normalizeSeriesForYear($wonBidsPerMonth, $yr),
        'categories' => $catData,
    ];
}
?>

<main class="content">
  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title">Reports &amp; Analytics</h2>
    </div>
  </header>

  <div class="stats">
    <div class="stat blue"><h2><?= (int) $totalBids ?></h2><p>Total Bids Placed</p></div>
    <div class="stat green"><h2><?= (int) $successfulBids ?></h2><p>Successful Bids</p></div>
    <div class="stat purple"><h2><?= htmlspecialchars(number_format((float) $successRate, 2)) ?>%</h2><p>Success Rate</p></div>
  </div>

  <!-- Filter UI -->
  <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:1rem 1.25rem; background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:1rem;">

    <!-- Year selector -->
    <label style="font-size:16px; font-weight:500;">Year:</label>
    <select id="yearSelect" style="font-size:13px; padding:5px 8px; border:1px solid #e5e7eb; border-radius:5px;">
      <?php foreach ($availableYears as $yr): ?>
        <option value="<?= $yr ?>" <?= $yr === $currentYear ? 'selected' : '' ?>><?= $yr ?></option>
      <?php endforeach; ?>
    </select>

    <div style="width:1px; height:24px; background:#e5e7eb; margin:0 4px;"></div>

    <!-- Preset ranges -->
    <label style="font-size:16px;">Preset range:</label>
    <button class="range-btn" style="border:1px solid #e5e7eb; padding:5px 8px; border-radius:5px;" data-range="0">Jan – Apr</button>
    <button class="range-btn" style="border:1px solid #e5e7eb; padding:5px 8px; border-radius:5px;" data-range="1">May – Aug</button>
    <button class="range-btn" style="border:1px solid #e5e7eb; padding:5px 8px; border-radius:5px;" data-range="2">Sep – Dec</button>

    <div style="width:1px; height:24px; background:#e5e7eb; margin:0 4px;"></div>

    <!-- Custom month range -->
    <label style="font-size:16px;">Custom:</label>
    <select id="customStart" style="font-size:13px; padding:5px 8px; border:1px solid #e5e7eb; border-radius:5px;"></select>
    <span style="font-size:13px; color:#666;">to</span>
    <select id="customEnd" style="font-size:13px; padding:5px 8px; border:1px solid #e5e7eb; border-radius:5px;"></select>
    <button id="applyCustom" style="border:1px solid #e5e7eb; padding:5px 8px; border-radius:8px; cursor:pointer;">Apply</button>
  </div>

  <!-- Side-by-side charts -->
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
    <div class="chart-box">
      <h3 style="font-size:20px; font-weight:bold; margin-bottom:12px;">Bidding values per waste type</h3>
      <div style="position:relative; width:100%; height:520px;">
        <canvas id="biddingChart"></canvas>
      </div>
    </div>
    <div class="chart-box">
      <h3 style="font-size:20px; font-weight:bold; margin-bottom:12px;">Monthly performance</h3>
      <div style="position:relative; width:100%; height:520px;">
        <canvas id="performanceChart"></canvas>
      </div>
    </div>
  </div>
</main>

<script>
// All per-year data from PHP
const yearData = <?= json_encode($yearData) ?>;
const presets  = [[0,3],[4,7],[8,11]];

// ── DOM refs ──────────────────────────────────────────────────────────────────
const yearSelect   = document.getElementById('yearSelect');
const startSel     = document.getElementById('customStart');
const endSel       = document.getElementById('customEnd');
const applyBtn     = document.getElementById('applyCustom');

// ── Chart setup ───────────────────────────────────────────────────────────────
const biddingChart = new Chart(document.getElementById('biddingChart'), {
  type: 'line',
  data: { labels: [], datasets: [
    { label:'Plastic', data:[], borderColor:'#3b82f6', fill:false, tension:0.3 },
    { label:'Paper',   data:[], borderColor:'#22c55e', fill:false, tension:0.3 },
    { label:'Metal',   data:[], borderColor:'#f59e0b', fill:false, tension:0.3 },
    { label:'Glass',   data:[], borderColor:'#ef4444', fill:false, tension:0.3 },
    { label:'Organic', data:[], borderColor:'#a16207', fill:false, tension:0.3 },
  ]},
  options: {
    responsive: true, maintainAspectRatio: false,
    scales: {
      x: { ticks:{ autoSkip:false }, title:{ display:true, text:'Month' } },
      y: { title:{ display:true, text:'Bidding value' } }
    }
  }
});

const perfChart = new Chart(document.getElementById('performanceChart'), {
  type: 'bar',
  data: { labels: [], datasets: [
    { label:'Total bids', data:[], backgroundColor:'lightblue' },
    { label:'Won bids',   data:[], backgroundColor:'green' },
  ]},
  options: {
    responsive: true, maintainAspectRatio: false,
    scales: {
      x: { ticks:{ autoSkip:false }, title:{ display:true, text:'Month' } },
      y: { title:{ display:true, text:'Number of bids' } }
    }
  }
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function getSlice(arr, s, e) { return arr.slice(s, e + 1); }

function populateMonthDropdowns(shortMonths) {
  [startSel, endSel].forEach(sel => {
    const cur = parseInt(sel.value) || 0;
    sel.innerHTML = '';
    shortMonths.forEach((m, i) => {
      sel.innerHTML += `<option value="${i}">${m}</option>`;
    });
    // Restore previous value if valid, otherwise clamp
    sel.value = Math.min(cur, shortMonths.length - 1);
  });
}

function updateCharts(s, e) {
  const yr   = parseInt(yearSelect.value);
  const data = yearData[yr];
  if (!data) return;

  const labels = data.shortMonths.slice(s, e + 1);

  biddingChart.data.labels = labels;
  ['Plastic','Paper','Metal','Glass','Organic'].forEach((cat, i) => {
    biddingChart.data.datasets[i].data = getSlice(data.categories[cat], s, e);
  });
  biddingChart.update();

  perfChart.data.labels = labels;
  perfChart.data.datasets[0].data = getSlice(data.totalBids, s, e);
  perfChart.data.datasets[1].data = getSlice(data.wonBids, s, e);
  perfChart.update();
}

function currentRange() {
  let s = parseInt(startSel.value);
  let e = parseInt(endSel.value);
  if (s > e) [s, e] = [e, s];
  return [s, e];
}

// ── Year change: rebuild month dropdowns, keep range ─────────────────────────
yearSelect.addEventListener('change', () => {
  const yr   = parseInt(yearSelect.value);
  const data = yearData[yr];
  populateMonthDropdowns(data.shortMonths);
  const [s, e] = currentRange();
  updateCharts(s, e);
});

// ── Preset buttons ────────────────────────────────────────────────────────────
document.querySelectorAll('.range-btn[data-range]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.range-btn[data-range]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const [s, e] = presets[parseInt(btn.dataset.range)];
    startSel.value = s;
    endSel.value   = e;
    updateCharts(s, e);
  });
});

// ── Custom apply ──────────────────────────────────────────────────────────────
applyBtn.addEventListener('click', () => {
  document.querySelectorAll('.range-btn[data-range]').forEach(b => b.classList.remove('active'));
  const [s, e] = currentRange();
  updateCharts(s, e);
});

// ── Init ──────────────────────────────────────────────────────────────────────
(function init() {
  const yr   = parseInt(yearSelect.value);
  const data = yearData[yr];
  populateMonthDropdowns(data.shortMonths);
  startSel.value = 0;
  endSel.value   = 3;
  updateCharts(0, 3);
})();
</script>