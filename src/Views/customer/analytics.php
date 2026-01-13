<?php
// ---------- PHP Variables ----------
$totalWasteThisMonth = 96;
$totalWasteLastMonth = 78;
$wasteIncrease = round((($totalWasteThisMonth - $totalWasteLastMonth) / $totalWasteLastMonth) * 100, 1);

$recyclingRate = 89;
$co2SavedThisMonth = 78;
$energySavedThisMonth = 195;

// Monthly data for line chart (month in YYYY-MM, label is user-visible)
$monthlyData = [
    ["month" => "2026-01", "label" => "Jan 2026", "plastic" => 15, "paper" => 8, "glass" => 5, "metal" => 12, "organic" => 18],
    ["month" => "2026-02", "label" => "Feb 2026", "plastic" => 18, "paper" => 10, "glass" => 7, "metal" => 14, "organic" => 22],
    ["month" => "2026-03", "label" => "Mar 2026", "plastic" => 12, "paper" => 7, "glass" => 6, "metal" => 16, "organic" => 20],
    ["month" => "2026-04", "label" => "Apr 2026", "plastic" => 22, "paper" => 14, "glass" => 9, "metal" => 19, "organic" => 25],
    ["month" => "2026-05", "label" => "May 2026", "plastic" => 16, "paper" => 11, "glass" => 7, "metal" => 17, "organic" => 21],
    ["month" => "2026-06", "label" => "Jun 2026", "plastic" => 20, "paper" => 13, "glass" => 10, "metal" => 20, "organic" => 28]
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

            <h1><b>Track your recycling performance and environmental impact</b></h1>
        </div>
    </div>

    <div class="stats-grid analytics-grid" style="margin-bottom:2.5rem;">
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
                Rs10000.00
            </p>
            <div class="feature-card__footer">
                <span class="tag success">Total income this month</span>
            </div>
        </div>
    </div>

    <div class="stats-grid analytics-grid">
        <!-- Multi-bar graph for waste types per month (last 3 months) -->
        <div class="feature-card">
            <div class="section-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h2 class="section-title" style="font-size:1.125rem;font-weight:500;color:#1e293b;">Weight of Waste Types</h2>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="month-picker-control" style="position:relative;display:flex;align-items:center;">
                        <input type="text" id="wasteMonthDisplay" readonly aria-haspopup="dialog" aria-controls="wasteMonthPanel" style="padding:6px 8px;font-size:1.12rem;min-width:160px;font-weight:600;" />
                        <button id="wasteMonthToggle" aria-label="Open month picker" title="Open month picker" style="margin-left:6px;padding:4px;border:1px solid transparent;background:transparent;font-size:14px;display:flex;align-items:center;justify-content:center;height:34px;width:34px;border-radius:6px;cursor:pointer;color:#000;">
                            <i class="fa-solid fa-calendar-days" style="color:#000;font-size:16px;line-height:1;"></i>
                        </button>

                        <div id="wasteMonthPanel" role="dialog" aria-modal="false" aria-labelledby="monthPanelYear" style="display:none;position:absolute;right:0;top:110%;z-index:20;background:#fff;border:1px solid #e5e7eb;padding:12px;border-radius:6px;box-shadow:0 8px 24px rgba(0,0,0,0.08);width:320px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;gap:8px;">
                                <button id="monthPrevYear" aria-label="Previous year" style="padding:4px 6px;font-size:14px;height:32px;width:32px;border-radius:6px;">‹</button>
                                <div id="monthPanelYear" style="font-weight:600;font-size:1.05rem;">2026</div>
                                <button id="monthNextYear" aria-label="Next year" style="padding:4px 6px;font-size:14px;height:32px;width:32px;border-radius:6px;">›</button>
                            </div>
                            <div id="monthGrid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                                <!-- months injected here -->
                            </div>
                            <div style="margin-top:10px;text-align:right;">
                                <button id="monthPanelClose" aria-label="Close" title="Close" style="padding:6px 8px;font-size:14px;border-radius:6px;">✖</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="wasteChartWrapper" style="position:relative;height:340px;width:100%;">
                <canvas id="wasteByCategoryChart" style="width:100%;height:100%;"></canvas>
                <div id="wasteNoData" role="status" aria-live="polite" style="display:none;position:absolute;left:0;right:0;top:0;bottom:0;align-items:center;justify-content:center;background:rgba(255,255,255,0.8);font-weight:700;color:#64748b;display:flex;font-size:16px;padding:12px;text-align:center;">No data available</div> 
            </div>
        </div>
        <!-- Bar graph for income per month -->
        <div class="feature-card">
            <div class="section-header" style="margin-bottom:1rem;">
                <h2 class="section-title"
                    style="font-size:1.25rem;font-weight:600;color:#1e293b;text-align:center;margin-left:2.5rem;">
                    Monthly Income</h2>
            </div>
            <div style="height:320px;width:100%;">
                <canvas id="incomeBarChart" style="width:100%;height:100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<style>
    /* Highlight the selected month display when showing Jan 2026 */
    .mp-highlight {
        background: #dcfce7; /* light green */
        border: 1px solid #bbf7d0;
        color: #065f46; /* dark green text */
        padding: 6px 8px;
        border-radius: 6px;
        font-weight: 700;
    }
</style>

<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    // Renders a single-month view for waste categories using the existing `monthlyData` JS variable
    const palette = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f472b6','#a3a3a3'];

    function initWhenReady() {
        if (typeof monthlyData === 'undefined') {
            // monthlyData is defined later in the script; try again shortly
            return setTimeout(initWhenReady, 50);
        }

        const monthDisplay = document.getElementById('wasteMonthDisplay');
        const monthToggle = document.getElementById('wasteMonthToggle');
        const monthPanel = document.getElementById('wasteMonthPanel');
        const monthGrid = document.getElementById('monthGrid');
        const monthPanelYear = document.getElementById('monthPanelYear');
        const prevYearBtn = document.getElementById('monthPrevYear');
        const nextYearBtn = document.getElementById('monthNextYear');
        const closePanelBtn = document.getElementById('monthPanelClose');
        const noData = document.getElementById('wasteNoData');
        const ctx = document.getElementById('wasteByCategoryChart').getContext('2d');

        const availableMonths = monthlyData.map(m => m.month);
        const labelMap = Object.fromEntries(monthlyData.map(m => [m.month, m.label || m.month]));

        // Year boundaries: earliest data year or (currentYear - 5), whichever is earlier
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentYYYYMM = `${currentYear}-${String(now.getMonth()+1).padStart(2,'0')}`;
        const earliestYear = availableMonths.length > 0 ? Math.min(...availableMonths.map(s => Number(s.split('-')[0]))) : (currentYear - 5);
        const minYear = Math.min(currentYear - 5, earliestYear);
        const maxYear = currentYear;

        // internal selection state
        let panelYear = currentYear;
        let selectedYM = null;

        function pad(n){ return String(n).padStart(2,'0'); }

        function isFutureMonth(ym){ return ym > currentYYYYMM; }

        function renderPanelYear(y){
            panelYear = y;
            monthPanelYear.textContent = String(y);
            monthGrid.innerHTML = '';
            for (let m=1; m<=12; m++){
                const ym = `${y}-${pad(m)}`;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'mp-month-btn';
                btn.dataset.ym = ym;
                btn.style.padding = '8px';
                btn.style.fontSize = '14px';
                btn.style.fontWeight = '600';
                btn.style.textAlign = 'center';
                btn.style.border = '1px solid transparent';
                btn.style.borderRadius = '6px';
                btn.style.background = 'transparent';
                btn.style.cursor = 'pointer';
                const monthLabel = new Date(y, m-1).toLocaleString(undefined, { month: 'short' });
                btn.textContent = monthLabel;

                if (isFutureMonth(ym)){
                    btn.disabled = true;
                    btn.style.opacity = '0.45';
                    btn.title = 'Future month - not selectable';
                    btn.style.cursor = 'not-allowed';
                    btn.style.color = '#9ca3af';
                    btn.style.borderColor = 'transparent';
                } else {
                    btn.addEventListener('click', () => {
                        selectedYM = ym;
                        monthDisplay.value = labelMap[ym] || `${monthLabel} ${y}`;
                        try { localStorage.setItem('wasteChartSelectedMonth', selectedYM); } catch (e) {}
                        renderForMonth(selectedYM);
                        hidePanel();
                    });
                    btn.addEventListener('mouseover', () => { btn.style.background = '#f8fafc'; btn.style.borderColor = '#e2e8f0'; });
                    btn.addEventListener('mouseout', () => { if (selectedYM !== ym) { btn.style.background = 'transparent'; btn.style.borderColor = 'transparent'; } });
                }

                if (selectedYM === ym){
                    btn.style.background = '#eef2ff';
                    btn.style.borderColor = '#c7d2fe';
                }

                monthGrid.appendChild(btn);
            }
        }

        function showPanel(){
            monthPanel.style.display = 'block';
            renderPanelYear(panelYear);
            // focus first enabled month
            const firstEnabled = monthGrid.querySelector('button:not(:disabled)');
            if (firstEnabled) firstEnabled.focus();
            document.addEventListener('click', outsideClickHandler);
        }

        function hidePanel(){
            monthPanel.style.display = 'none';
            document.removeEventListener('click', outsideClickHandler);
        }

        function outsideClickHandler(e){
            if (!monthPanel.contains(e.target) && e.target !== monthToggle && e.target !== monthDisplay){
                hidePanel();
            }
        }

        prevYearBtn.addEventListener('click', () => { if (panelYear > minYear){ renderPanelYear(panelYear - 1); } });
        nextYearBtn.addEventListener('click', () => { if (panelYear < maxYear){ renderPanelYear(panelYear + 1); } });
        monthToggle.addEventListener('click', (e) => { e.stopPropagation(); if (monthPanel.style.display === 'block') hidePanel(); else showPanel(); });
        closePanelBtn.addEventListener('click', hidePanel);

        const wasteChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Weight (kg)',
                    data: [],
                    backgroundColor: [],
                    borderRadius: 6,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false, labels: { font: { size: 14 } } } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Weight (kg)', font: { size: 14 } }, ticks: { color: '#64748b', font: { size: 13 } } },
                    x: { ticks: { color: '#1e293b', font: { size: 13 } } }
                }
            }
        });

        function renderForMonth(yearMonth) {
            const monthObj = monthlyData.find(m => m.month === yearMonth);
            const labelText = labelMap[yearMonth] || yearMonth;

            if (!monthObj) {
                wasteChart.data.labels = [];
                wasteChart.data.datasets[0].data = [];
                wasteChart.update();
                noData.textContent = `No data available for ${labelText}`;
                noData.style.display = 'flex';
                return;
            }

            const keys = Object.keys(monthObj).filter(k => k !== 'month' && k !== 'label');
            const labels = keys.map(k => k.charAt(0).toUpperCase() + k.slice(1));
            const data = keys.map(k => Number(monthObj[k] || 0));
            const hasAny = data.some(v => v > 0);

            wasteChart.data.labels = labels;
            wasteChart.data.datasets[0].data = data;
            wasteChart.data.datasets[0].backgroundColor = labels.map((_, i) => palette[i % palette.length]);
            wasteChart.update();

            if (!hasAny) {
                noData.textContent = `No data available for ${labelText}`;
                noData.style.display = 'flex';
            } else {
                noData.textContent = '';
                noData.style.display = 'none';
            }

            // Highlight month display for Jan 2026 using the app's light green
            try {
                if (monthDisplay) {
                    if (yearMonth === '2026-01') monthDisplay.classList.add('mp-highlight');
                    else monthDisplay.classList.remove('mp-highlight');
                }
            } catch (e) { /* no-op in older browsers */ }
        }

        // Initialize selected month
        (function initSelection(){
            const stored = localStorage.getItem('wasteChartSelectedMonth');
            const lastNonFuture = availableMonths.slice().reverse().find(m => !isFutureMonth(m));
            let init = null;
            if (stored && availableMonths.includes(stored) && !isFutureMonth(stored)) init = stored;
            else if (lastNonFuture) init = lastNonFuture;
            else init = availableMonths.length ? availableMonths[availableMonths.length-1] : currentYYYYMM;

            selectedYM = init;
            monthDisplay.value = labelMap[selectedYM] || selectedYM;
            panelYear = Number(selectedYM.split('-')[0]);
            renderForMonth(selectedYM);
        })();
    }

    initWhenReady();
})();

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
                    title: { display: true, text: 'Month' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        color: '#64748b',
                        callback: function (value) { return 'Rs ' + value.toLocaleString(); }
                    },
                    title: { display: true, text: 'Income (Rs)' }
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
    const trendEl = document.getElementById('wasteTrendChart');
    if (trendEl) {
        const ctx1 = trendEl.getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.label),
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
                        labels: { font: { size: 12 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Weight (kg)',
                            font: { size: 12 }
                        },
                        ticks: { font: { size: 11 }, color: '#64748b' }
                    },
                    x: {
                        title: { display: false },
                        ticks: { font: { size: 11 }, color: '#1e293b' }
                    }
                }
            }
        });
    }

    // Pie Chart for Waste Breakdown
    const breakdownEl = document.getElementById('wasteBreakdownChart');
    if (breakdownEl) {
        const ctx2 = breakdownEl.getContext('2d');
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
                            label: function (context) {
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
    }
</script>