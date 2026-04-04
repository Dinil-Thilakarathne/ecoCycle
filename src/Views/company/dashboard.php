<?php
$availableWaste = $availableWaste ?? [];
$highestBids = $highestBids ?? [];
$totalWaste = array_reduce($availableWaste, function (float $carry, array $item): float {
  $quantity = (float) ($item['quantity'] ?? 0);
  if ($quantity > 0) {
    return $carry + $quantity;
  }

  $value = (string) ($item['value'] ?? '');
  $numeric = preg_replace('/[^0-9.]/', '', $value);
  return $carry + (float) $numeric;
}, 0.0);
?>

<!-- Main Content -->
<main class="content">
  <header class="page-header">
    <div class="page-header__content">
      <?php
      // Resolve company name from authenticated user's metadata.companyname (fallbacks below)
      $companyProfile = $companyProfile ?? null;
      $authUser = function_exists('auth') ? auth() : null;

      // Try metadata from auth user first (may be JSON string or array)
      $meta = null;
      if (is_array($authUser) && !empty($authUser['metadata'])) {
          $meta = $authUser['metadata'];
          if (is_string($meta)) {
              $decoded = json_decode($meta, true);
              if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                  $meta = $decoded;
              }
          }
      }

      // Fallback to companyProfile metadata if auth meta not available
      if (!is_array($meta) && is_array($companyProfile) && !empty($companyProfile['metadata'])) {
          $meta = $companyProfile['metadata'];
          if (is_string($meta)) {
              $decoded = json_decode($meta, true);
              if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                  $meta = $decoded;
              }
          }
      }

      // Prefer metadata.companyname (or companyName), then companyProfile.companyName, then auth name, then default
      $companyName = $meta['companyname'] ?? $meta['companyName'] ?? ($companyProfile['companyName'] ?? ($authUser['name'] ?? 'EcoWaste'));
      $companyName = htmlspecialchars((string) $companyName, ENT_QUOTES, 'UTF-8');

      // Resolve profile image (prefer auth user's profileImage, then companyProfile)
      $profileImagePath = $companyProfile['profile_picture'] ?? null;
      $profilePic = $profileImagePath ? asset($profileImagePath) : asset('assets/logo-icon.png');
      $profilePic = htmlspecialchars((string) $profilePic, ENT_QUOTES, 'UTF-8');
      ?>
      <div style="display:flex;align-items:center;gap:1rem;">
        <img src="<?= $profilePic ?>" alt="Company profile" class="avatar"
          style="width:56px;height:56px;object-fit:cover;border-radius:50%;border:2px solid #e0f2fe;box-shadow:0 2px 8px rgba(34,197,94,0.08);">
        <div>
          <h2 class="page-header__title" style="margin:0;">Welcome back, <?= $companyName ?>!</h2>
          <p class="page-header__description" style="margin:0;">Here is your latest update on your Dashboard</p>
        </div>
      </div>
    </div>
  </header>

  <section class="companyDashboard">

    <div class="c-dashboard-card" style="margin-bottom: 30px;">
      <h3>Available Waste Amount</h3>
      <p class="value"><?= number_format($totalWaste) ?> kg</p>

      <ul class="stats-grid">
        <?php foreach ($availableWaste as $type): ?>
          <feature-card unwrap title="<?= htmlspecialchars($type['title'] ?? 'Unknown') ?>"
            value="<?= htmlspecialchars($type['value'] ?? ($type['quantity'] ?? 0) . ' ' . ($type['unit'] ?? 'kg')) ?>"
            icon="<?= htmlspecialchars($type['icon'] ?? 'fa-solid fa-recycle') ?>"></feature-card>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="c-dashboard-grid">

      <div class="c-dashboard-card">
        <h3>Current Highest Bid for Each Waste Type</h3>

        <div class="bids">
          <?php foreach ($highestBids as $data):
            $safeType = htmlspecialchars($data['title'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
            $safeBid = htmlspecialchars($data['bid'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $safeAmount = htmlspecialchars($data['amount'] ?? '0 kg', ENT_QUOTES, 'UTF-8');
            $safeStatus = htmlspecialchars($data['status'] ?? 'Active', ENT_QUOTES, 'UTF-8');
            // map common waste type text to CSS custom property names defined in theme.css
            $typeKey = strtolower($safeType);
            $typeVarMap = [
              'plastic' => '--plastic',
              'paper'   => '--paper',
              'metal'   => '--metal',
              'glass'   => '--glass',
              'organic' => '--organic'
            ];
            $cssVar = $typeVarMap[$typeKey] ?? null;
            // fallback neutral color when no theme variable available
            $dotStyle = $cssVar ? "background: var({$cssVar});" : "background: #9CA3AF;";
            ?>
            <div class="bid-item">
              <div style="display:flex; justify-content:space-between; align-items:center; font-size:1.05rem;">
                <div class="waste-type" style="font-weight:bold;"><span class="waste-dot" aria-hidden="true"
                        style="width:11px;height:11px;border-radius:50%;display:inline-block;margin-right:10px;<?php echo $dotStyle ?>box-shadow:0 0 0 2px rgba(0,0,0,0.03);"></span>
                  <span><?= $safeType ?></span>
                </div>
                <div><span
                    class="<?= strtolower($safeStatus) === 'active' ? 'verified' : 'unverified' ?>"><?= $safeStatus ?></span>
                </div>
              </div>

              <div style="display:flex; justify-content:space-between; align-items:center; font-size:1.05rem;">
                <div style="align-items: right;"><?= $safeAmount ?></div>
                <div class="bid-amount"><?= $safeBid ?></div>
              </div>
            </div>

          <?php endforeach; ?>
        </div>
      </div>

      <div class="c-dashboard-card">
        <h3>Purchased waste amounts last month</h3>
        <div style="height:400px; margin-bottom:1rem;">
          <canvas id="availableWasteChart" aria-label="Available waste distribution" role="img"></canvas>
        </div>
      </div>
    </div>
  </section>
</main>

<script>
  // Build chart data from server-side $availableWaste
  (function () {
    const availableWaste = <?= json_encode(array_values($availableWaste), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];

    // Extract labels and numeric values (prefer 'quantity' then numeric part of 'value')
    const labels = [];
    const data = [];
    const units = []; // capture unit for tooltip
    availableWaste.forEach(item => {
      const title = item.title || item.name || 'Unknown';
      labels.push(title);

      let qty = 0;
      if (item.quantity !== undefined && item.quantity !== null && !isNaN(Number(item.quantity))) {
        qty = Number(item.quantity);
      } else if (item.value !== undefined) {
        const numeric = String(item.value).replace(/[^0-9.]/g, '');
        qty = numeric === '' ? 0 : Number(numeric);
      }
      data.push(Number(qty.toFixed(2)));
      units.push(item.unit || 'kg');
    });

    if (labels.length === 0) return;

    // Default fallback palette (used when no theme mapping)
    const fallbackColors = [
      '#60a5fa','#fbbf24','#34d399','#a78bfa','#f97316','#60c3ff','#fca5a5','#c7f9cc','#ffd6a5','#bdb2ff'
    ];

    // Helper to read CSS custom property value
    function cssVar(name, fallback = '') {
      const v = getComputedStyle(document.documentElement).getPropertyValue(name);
      return (v || '').trim() || fallback;
    }

    // Map common waste types to theme variables (as defined in theme.css)
    const themeTypeMap = {
      plastic: cssVar('--plastic', fallbackColors[0]),
      paper: cssVar('--paper', fallbackColors[1]),
      metal: cssVar('--metal', fallbackColors[2]),
      glass: cssVar('--glass', fallbackColors[3]),
      organic: cssVar('--organic', fallbackColors[4])
    };

    // try to detect a simple type key from label text
    function detectTypeKey(label) {
      const s = (label || '').toLowerCase();
      if (s.includes('plastic')) return 'plastic';
      if (s.includes('paper')) return 'paper';
      if (s.includes('metal')) return 'metal';
      if (s.includes('glass')) return 'glass';
      if (s.includes('organic') || s.includes('bio') || s.includes('food')) return 'organic';
      return null;
    }

    // Build background color array using theme variables when possible
    const bg = labels.map((lbl, i) => {
      const key = detectTypeKey(lbl);
      if (key && themeTypeMap[key]) return themeTypeMap[key];
      return fallbackColors[i % fallbackColors.length];
    });

    function ensureChartJs(cb) {
      if (window.Chart) return cb();
      const src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
      const s = document.createElement('script');
      s.src = src;
      s.onload = cb;
      document.head.appendChild(s);
    }

    ensureChartJs(() => {
      const canvas = document.getElementById('availableWasteChart');
      // make sure canvas fills its parent container
      canvas.width = canvas.parentElement.clientWidth;
      canvas.height = canvas.parentElement.clientHeight;

      const ctx = canvas.getContext('2d');
      // Destroy previous chart instance if re-rendering
      if (window.__availableWasteChart instanceof Chart) {
        window.__availableWasteChart.destroy();
      }
      window.__availableWasteChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: bg,
            borderColor: '#fff',
            borderWidth: 2,
            hoverOffset: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right',
              labels: {
                boxWidth: 12,
                padding: 12,
              }
            },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  const v = ctx.parsed;
                  const idx = ctx.dataIndex;
                  const unit = units[idx] || 'kg';
                  const label = ctx.label || '';
                  return `${label}: ${v} ${unit}`;
                }
              }
            }
          }
        }
      });
    });
  })();
</script>