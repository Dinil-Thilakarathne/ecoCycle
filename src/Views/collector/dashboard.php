<!-- Page Header -->
<div class="page-header">
  <div class="page-header__content">
    <?php
// Prefer collector profile name if provided, otherwise resolve from auth/session and fall back to 'Collector'
$collectorProfile = $collectorProfile ?? null;
$profileName = is_array($collectorProfile) ? ($collectorProfile['name'] ?? null) : null;
$collectorName = $profileName ?: (auth()['name'] ?? session('user_name') ?? 'Collector');


// Optional: debug log
consoleLog('Collector Name (resolved): ' . $collectorName);

// Escape for HTML output
$collectorName = htmlspecialchars((string) $collectorName, ENT_QUOTES, 'UTF-8');

// SAME image logic as profile page
$profileImage = $collectorProfile['profile_pic']
  ?? ($collectorProfile['profileImage']
  ?? ($collectorProfile['profileImagePath'] ?? null));

if (is_string($profileImage) && preg_match('#^https?://#i', $profileImage)) {
  $profileImageSrc = $profileImage;
} elseif (is_string($profileImage) && $profileImage !== '') {
  $profileImageSrc = '/' . ltrim($profileImage, '/');
} else {
  $profileImageSrc = '/assets/avatar.png';
}
?>

<!-- <img
      src="<?= htmlspecialchars($profileImageSrc) ?>"
      alt="Profile Picture"
      class="header-user__avatar" width="100"
    >
<h2 class="page-header__title">Welcome back, <?= $collectorName ?>!</h2>
<p class="page-header__description">Here is your latest update on your Dashboard</p>
  </div>
</div> -->

<table>
      <tr>
        <td>
          <img
            src="<?= htmlspecialchars($profileImageSrc) ?>"
            alt="Profile Picture"
            width="100"
          >
        </td>

        <td>
          <h2 class="page-header__title">
            Welcome back, <?= $collectorName ?>!
          </h2>
          <p class="page-header__description">
            Here is your latest update on your Dashboard
          </p>
        </td>
      </tr>
    </table>

  </div>
</div>

<!-- Availability Widget -->
<!-- <div style="margin-bottom: 2rem;">
  <?php include __DIR__ . '/availability-widget.php'; ?>
</div> -->

<div class="feature-cards">
  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Today's Tasks</div>
      <div class="feature-card__icon"><i class="fa-solid fa-list-check"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-today-tasks"><?= $todayPickups ?? 0 ?></span></div>
    <!-- <div class="feature-card__footer">
      <span class="desc">assigned tasks</span> 
    </div> -->
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Completed</div>
      <div class="feature-card__icon"><i class="fa-solid fa-table-list"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-completed"><?= $completedPickups ?? 0 ?></span></div>
    <!-- <div class="feature-card__footer">
       <span class="desc">tasks finished</span>
    </div> -->
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Pending</div>
      <div class="feature-card__icon"><i class="fa-solid fa-clock"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-pending"><?= count($pendingPickups ?? []) ?></span></div>
     <!-- <div class="feature-card__footer"> 
       <span class="desc">tasks left</span> 
    </div> -->
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Total Weight</div>
      <div class="feature-card__icon"><i class="fa-solid fa-weight-hanging"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-total-weight">0kg</span></div>
     <!-- <div class="feature-card__footer"> 
       <span class="desc">collected today</span>
    </div> -->
  </div>


  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Rating</div>
      <div class="feature-card__icon"><i class="fa-solid fa-star"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-rating">-</span></div>
    <!-- <div class="feature-card__footer">
      <span class="desc">customer rating</span>
    </div> -->
  </div>
</div>

<!-- Bottom Section -->
<div class="bottom-container">

  <!-- Recent Tasks -->
  <activity-card title="Recent Tasks" description="Your latest pickup activities">
    <?php if (!empty($pendingPickups)): ?>
      <?php foreach (array_slice($pendingPickups, 0, 5) as $pickup): ?>
        <div class="task">
          <div class="task-info">
            <div class="task-name">
              <span><?= htmlspecialchars($pickup['customerName'] ?? '') ?></span>
              <span class="tag <?= ($pickup['status'] ?? '') === 'completed' ? 'success' : 'warning' ?>">
                <?= ucfirst(str_replace('_', ' ', $pickup['status'] ?? 'pending')) ?>
              </span>
            </div>
            <div class="task-meta">
              <i class="fa-solid fa-location-dot"></i>
              <?= htmlspecialchars($pickup['address'] ?? 'Not provided') ?> ·
              <?= htmlspecialchars(implode(', ', $pickup['wasteCategories'] ?? [])) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align: center; color: #999; padding: 20px;">No pending tasks</p>
    <?php endif; ?>
  </activity-card>

  <activity-card title="Material Collection Summary" description="Breakdown of This Week's Collected Materials">
    <!-- Material Chart -->
    <div class="" style="padding: 0;">
      <canvas id="materialCollectionChart" style="width: 100%; max-height: 360px;"></canvas>
    </div>
  </activity-card>


<!-- Scripts -->
<script>
  // Global chart instance
  let materialCollectionChart = null;

  // Fetch and render material collection chart in real-time
  async function fetchAndRenderMaterialCollection() {
    try {
      const res = await fetch('/api/collector/material-collection', { credentials: 'same-origin' });
      if (!res.ok) return;
      const json = await res.json();
      if (!json || json.status !== 'success' || !Array.isArray(json.data)) return;

      const materials = json.data;

      // If no data, show empty state
      if (materials.length === 0) {
        const el = document.getElementById('materialCollectionChart');
        if (el) {
          const parent = el.parentElement;
          parent.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">No materials collected this week</p>';
        }
        return;
      }

      // Extract data for chart
      const materialLabels = materials.map(m => m.name);
      const materialWeights = materials.map(m => m.weight);
      const materialColors = materials.map(m => m.color);

      // Render or update Chart.js doughnut chart
      const el = document.getElementById('materialCollectionChart');
      if (!el) return;

      // Destroy existing chart if it exists
      if (materialCollectionChart) {
        materialCollectionChart.destroy();
      }

      const ctx = el.getContext('2d');
      materialCollectionChart = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: materialLabels,
          datasets: [{
            label: 'Weight (kg)',
            data: materialWeights,
            backgroundColor: materialColors,
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: {
              position: 'right',
              labels: {
                padding: 15,
                font: {
                  size: 13
                },
                generateLabels: function (chart) {
                  const data = chart.data;
                  return data.labels.map((label, i) => {
                    const material = materials[i];
                    const value = data.datasets[0].data[i];
                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                    const percentage = ((value / total) * 100).toFixed(0);
                    const price = material.price ? ` Rs.${material.price.toFixed(2)}` : '';
                    return {
                      text: `${label}: ${value}kg (${percentage}%)${price}`,
                      fillStyle: data.datasets[0].backgroundColor[i],
                      hidden: false,
                      index: i
                    };
                  })
                }
              }
            },
            tooltip: {
              callbacks: {
                label: function (context) {
                  const material = materials[context.dataIndex];
                  const label = context.label || '';
                  const value = context.parsed || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = ((value / total) * 100).toFixed(1);
                  const price = material.price ? ` - Rs.${material.price.toFixed(2)}` : '';
                  return `${label}: ${value}kg (${percentage}%)${price}`;
                }
              }
            }
          }
        }
      });
    } catch (e) {
      console.error('Failed to fetch material collection:', e);
    }
  }

  // Initial fetch and update every 10 seconds
  fetchAndRenderMaterialCollection();
  setInterval(fetchAndRenderMaterialCollection, 10000);
</script>

<script>
  // Poll collector stats endpoint and update cards in real time
  (function () {
    const endpoint = '/api/collector/stats';
    const elToday = document.getElementById('stat-today-tasks');
    const elCompleted = document.getElementById('stat-completed');
    const elPending = document.getElementById('stat-pending');
    const elWeight = document.getElementById('stat-total-weight');

    function formatWeight(val) {
      if (val === null || val === undefined) return '0kg';
      return Number(val).toFixed(2) + 'kg';
    }

    async function fetchStats() {
      try {
        const res = await fetch(endpoint, { credentials: 'same-origin' });
        if (!res.ok) return;
        const json = await res.json();
        if (!json || json.status !== 'success') return;
        const d = json.data || {};
        if (elToday) elToday.textContent = (d.todays_tasks ?? 0);
        if (elCompleted) elCompleted.textContent = (d.completed ?? 0);
        if (elPending) elPending.textContent = (d.pending ?? 0);
        if (elWeight) elWeight.textContent = formatWeight(d.total_weight ?? 0);
      } catch (e) {
        // silent fail
      }
    }

    // Initial fetch and interval
    fetchStats();
    setInterval(fetchStats, 10000);
  })();

  // Poll collector material prices and update in real time
  (function () {
    const endpoint = '/api/collector/material-prices';
    const container = document.getElementById('material-prices-container');
    
    // Skip if container doesn't exist (section is commented out)
    if (!container) return;

    function formatPrice(val) {
      if (val === null || val === undefined) return 'Rs. 0.00';
      const num = parseFloat(val);
      return 'Rs. ' + num.toFixed(2);
    }

    function getColorForMaterial(name) {
      const lowerName = (name || '').toLowerCase();
      const colorMap = {
        'plastic': '#fbbf24',
        'glass': '#60a5fa',
        'metal': '#a78bfa',
        'paper': '#34d399',
        'organic': '#f97316'
      };
      return colorMap[lowerName] || '#6b7280';
    }

    async function fetchMaterialPrices() {
      try {
        const res = await fetch(endpoint, { credentials: 'same-origin' });
        if (!res.ok) return;
        const json = await res.json();
        if (!json || json.status !== 'success' || !Array.isArray(json.data)) return;

        // Clear and rebuild the container
        container.innerHTML = '';
        json.data.forEach(material => {
          const div = document.createElement('div');
          div.className = 'goal';
          div.innerHTML = `
            <div class="goal-header">
              <span style="display: flex; align-items: center; gap: var(--space-2);">
                <div style="width: 12px; height: 12px; border-radius: 50%; background-color: ${getColorForMaterial(material.name)};"></div>
                <span class="font-medium">${material.name}</span>
              </span>
              <span class="goal-status" style="font-weight: var(--font-weight-bold); color: var(--neutral-900);">
                ${formatPrice(material.price_per_unit)}
              </span>
            </div>
          `;
          container.appendChild(div);
        });
      } catch (e) {
        // silent fail
      }
    }

    // Initial fetch and interval
    fetchMaterialPrices();
    setInterval(fetchMaterialPrices, 10000);
  })();

  // Real-time rating update
  (function() {
    const collectorId = <?= (int)($user['id'] ?? 0) ?>;
    
    async function updateRating() {
      if (!collectorId) return;
      
      try {
        const res = await fetch(`/api/collector/metrics?collector_id=${collectorId}`, { 
          credentials: 'include' 
        });
        
        if (!res.ok) return;
        const json = await res.json();
        
        if (json.success && json.data?.feedbackMetrics) {
          const avgRating = json.data.feedbackMetrics.averageRating || 0;
          document.getElementById('stat-rating').textContent = avgRating.toFixed(1);
        }
      } catch (e) {
        console.error('Failed to fetch rating:', e);
      }
    }
    
    // Initial fetch and update every 30 seconds
    updateRating();
    setInterval(updateRating, 30000);
  })();

  lucide.createIcons();
</script>