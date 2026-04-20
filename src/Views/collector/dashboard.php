<div class="page-header">
  <div class="page-header__content">
    <?php

$collectorProfile = $collectorProfile ?? null;
$profileName = is_array($collectorProfile) ? ($collectorProfile['name'] ?? null) : null;
$collectorName = $profileName ?: (auth()['name'] ?? session('user_name') ?? 'Collector');


consoleLog('Collector Name (resolved): ' . $collectorName);

$collectorName = htmlspecialchars((string) $collectorName, ENT_QUOTES, 'UTF-8');

$profileImage = null;
$sessionImage = session('profileImagePath');
if (is_string($sessionImage) && trim($sessionImage) !== '') {
  $profileImage = trim($sessionImage);
}

$collectorProfileData = is_array($collectorProfile) ? $collectorProfile : [];
if ($profileImage === null) {
foreach (['profileImagePath', 'profileImage', 'profile_image_path', 'profile_pic'] as $imageKey) {
  $candidate = $collectorProfileData[$imageKey] ?? null;
  if (is_string($candidate) && trim($candidate) !== '') {
    $profileImage = trim($candidate);
    break;
  }
}
}

if ($profileImage === null) {
  foreach (['profileImagePath', 'profile_image_path'] as $authKey) {
    $candidate = auth()[$authKey] ?? null;
    if (is_string($candidate) && trim($candidate) !== '') {
      $profileImage = trim($candidate);
      break;
    }
  }
}

if (is_string($profileImage) && preg_match('#^https?://#i', $profileImage)) {
  $profileImageSrc = $profileImage;
  $hasCustomProfileImage = true;
} elseif (is_string($profileImage) && $profileImage !== '') {
  $profileImageSrc = asset(ltrim($profileImage, '/'));
  $hasCustomProfileImage = true;
} else {
  $profileImageSrc = asset('assets/avatar.png');
  $hasCustomProfileImage = false;
}

if ($hasCustomProfileImage) {
  $profileImageSrc .= (str_contains($profileImageSrc, '?') ? '&' : '?') . 'v=' . time();
}

$assignedTaskCount = max(0, (int) ($todayPickups ?? 0));
$completedTaskCount = max(0, (int) ($completedPickups ?? 0));
$pendingTaskCount = count($pendingPickups ?? []);
$performanceScore = $assignedTaskCount > 0
  ? (int) round(($completedTaskCount / $assignedTaskCount) * 100)
  : 0;
$performanceScore = max(0, min(100, $performanceScore));
$collectorLevel = max(1, min(10, (int) floor($completedTaskCount / 3) + 1));
if ($collectorLevel >= 9) {
  $collectorRank = 'Elite';
} elseif ($collectorLevel >= 7) {
  $collectorRank = 'Advanced';
} elseif ($collectorLevel >= 5) {
  $collectorRank = 'Skilled';
} elseif ($collectorLevel >= 3) {
  $collectorRank = 'Steady';
} else {
  $collectorRank = 'Rookie';
}
$nextLevelTarget = $collectorLevel >= 10 ? $completedTaskCount : ($collectorLevel * 3);
$nextLevelRemaining = $collectorLevel >= 10 ? 0 : max(0, $nextLevelTarget - $completedTaskCount);
$progressToNextLevel = $collectorLevel >= 10 ? 100 : (int) round((($completedTaskCount % 3) / 3) * 100);
?>

<div class="collector-header-row">
  <img
    src="<?= htmlspecialchars($profileImageSrc) ?>"
    alt="Profile Picture"
    width="100"
  >

  <div>
    <h2 class="page-header__title">
      Welcome back, <?= $collectorName ?>!
    </h2>
    <p class="page-header__description">
      Here is your latest update on your Dashboard
    </p>
  </div>
</div>

  </div>
</div>



  <!-- <?php include __DIR__ . '/availability-widget.php'; ?> -->

<div class="feature-cards">
  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Assigned Tasks</div>
      <div class="feature-card__icon"><i class="fa-solid fa-list-check"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-today-tasks"><?= $todayPickups ?? 0 ?></span></div>

  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Completed</div>
      <div class="feature-card__icon"><i class="fa-solid fa-table-list"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-completed"><?= $completedPickups ?? 0 ?></span></div>
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Pending</div>
      <div class="feature-card__icon"><i class="fa-solid fa-clock"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-pending"><?= count($pendingPickups ?? []) ?></span></div>
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Total Weight</div>
      <div class="feature-card__icon"><i class="fa-solid fa-weight-hanging"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-total-weight">0kg</span></div>
  </div>


  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Rating</div>
      <div class="feature-card__icon"><i class="fa-solid fa-star"></i></div>
    </div>
    <div class="feature-card__body"><span id="stat-rating">-</span></div>
  </div>
</div>

<div class="bottom-container">

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
      <p class="collector-empty-state">No pending tasks</p>
    <?php endif; ?>
  </activity-card>

  <activity-card class="collector-performance-card" title="Collector Performance" description="Your current level and completion progress">
    <div class="collector-performance-panel">
      <div class="collector-performance-ring" style="--collector-progress: <?= (int) $performanceScore ?>%;">
        <div class="collector-performance-ring__inner">
          <span class="collector-performance-level">Level <?= (int) $collectorLevel ?></span>
          <strong class="collector-performance-score"><?= (int) $performanceScore ?>%</strong>
          <small class="collector-performance-rank"><?= htmlspecialchars($collectorRank, ENT_QUOTES, 'UTF-8') ?></small>
        </div>
      </div>

      <div class="collector-performance-details">
        <div class="collector-performance-progress">
          <div class="collector-performance-progress__header">
            <span>Next level progress</span>
            <span><?= (int) $nextLevelRemaining ?> more pickup<?= $nextLevelRemaining === 1 ? '' : 's' ?></span>
          </div>
          <div class="collector-performance-progress__bar" aria-hidden="true">
            <span style="width: <?= (int) $progressToNextLevel ?>%;"></span>
          </div>
          <p class="collector-performance-note">
            Keep completing pickups to level up and improve your collector rank.
          </p>
        </div>
      </div>
    </div>
  </activity-card>

</div>

<script>
 
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
        const res = await fetch(endpoint, {
          credentials: 'same-origin',
          cache: 'no-store'
        });
        if (!res.ok) return;
        const json = await res.json();
        if (!json || json.status !== 'success') return;
        const d = json.data || {};
        if (elToday) elToday.textContent = (d.todays_tasks ?? 0);
        if (elCompleted) elCompleted.textContent = (d.completed ?? 0);
        if (elPending) elPending.textContent = (d.pending ?? 0);
        if (elWeight) elWeight.textContent = formatWeight(d.total_weight ?? 0);
      } catch (e) {
      
      }
    }

   
    fetchStats();
    setInterval(fetchStats, 10000);
  })();


  (function () {
    const endpoint = '/api/collector/material-prices';
    const container = document.getElementById('material-prices-container');
    
  
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

    function getDotClassForMaterial(name) {
      const lowerName = (name || '').toLowerCase();
      const classMap = {
        'plastic': 'dot-plastic',
        'glass': 'dot-glass',
        'metal': 'dot-metal',
        'paper': 'dot-paper',
        'organic': 'dot-organic'
      };
      return classMap[lowerName] || 'dot-default';
    }

    async function fetchMaterialPrices() {
      try {
        const res = await fetch(endpoint, { credentials: 'same-origin' });
        if (!res.ok) return;
        const json = await res.json();
        if (!json || json.status !== 'success' || !Array.isArray(json.data)) return;

       
        container.innerHTML = '';
        json.data.forEach(material => {
          const div = document.createElement('div');
          div.className = 'goal';
          div.innerHTML = `
            <div class="goal-header">
              <span class="dashboard-goal-material-chip">
                <div class="dashboard-goal-material-dot ${getDotClassForMaterial(material.name)}"></div>
                <span class="font-medium">${material.name}</span>
              </span>
              <span class="goal-status dashboard-goal-status-strong">
                ${formatPrice(material.price_per_unit)}
              </span>
            </div>
          `;
          container.appendChild(div);
        });
      } catch (e) {
      
      }
    }

   
    fetchMaterialPrices();
    setInterval(fetchMaterialPrices, 10000);
  })();

 
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
    
   
    updateRating();
    setInterval(updateRating, 30000);
  })();

  lucide.createIcons();
</script>