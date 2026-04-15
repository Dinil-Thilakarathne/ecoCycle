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
  ?? ($collectorProfile['profileImagePath']
  ?? (auth()['profileImagePath']
  ?? (auth()['profile_image_path']
  ?? (session('profileImagePath') ?? null)))));

if (is_string($profileImage) && preg_match('#^https?://#i', $profileImage)) {
  $profileImageSrc = $profileImage;
} elseif (is_string($profileImage) && $profileImage !== '') {
  $profileImageSrc = '/' . ltrim($profileImage, '/');
} else {
  $profileImageSrc = '/assets/avatar.png';
}

$pendingPickupLocations = [];
foreach (($pendingPickups ?? []) as $pickupLocationItem) {
  $address = trim((string) ($pickupLocationItem['address'] ?? ''));
  $latitude = $pickupLocationItem['latitude'] ?? null;
  $longitude = $pickupLocationItem['longitude'] ?? null;
  $hasCoordinates = is_numeric($latitude) && is_numeric($longitude);

  $pendingPickupLocations[] = [
    'id' => (string) ($pickupLocationItem['id'] ?? ''),
    'customerName' => (string) ($pickupLocationItem['customerName'] ?? 'Pickup Location'),
    'address' => $address,
    'status' => (string) ($pickupLocationItem['status'] ?? ''),
    'latitude' => $hasCoordinates ? (float) $latitude : null,
    'longitude' => $hasCoordinates ? (float) $longitude : null,
  ];
}
?>

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

  <!-- <?php include __DIR__ . '/availability-widget.php'; ?> -->

<div class="feature-cards">
  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Assigned Tasks</div>
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
      <p class="collector-empty-state">No pending tasks</p>
    <?php endif; ?>
  </activity-card>

  <activity-card title="Pending Pickup Locations" description="Map view of pending pickup request locations">
    <div class="pending-map-frame" id="pending-pickups-map-frame">
      <svg viewBox="0 0 1000 1200" preserveAspectRatio="xMidYMid meet" aria-label="Sri Lanka map close-up with pending pickup markers" role="img">
        <defs>
          <linearGradient id="seaGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#dff4ff" />
            <stop offset="100%" stop-color="#eaf8ff" />
          </linearGradient>
          <linearGradient id="landGradient" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#f8fafc" />
            <stop offset="100%" stop-color="#cbd5e1" />
          </linearGradient>
        </defs>
        <rect width="1000" height="1200" fill="url(#seaGradient)" />
        <circle cx="170" cy="170" r="110" fill="rgba(255,255,255,0.55)" />
        <circle cx="850" cy="240" r="150" fill="rgba(255,255,255,0.45)" />
        <path d="M520 70 C565 88 610 132 626 196 C638 247 627 290 610 348 C598 389 590 430 585 476 C580 530 594 581 608 639 C620 687 624 739 612 795 C598 864 565 941 531 1014 C506 1068 484 1107 455 1139 C430 1117 409 1073 401 1021 C391 960 405 898 416 845 C426 799 434 751 431 701 C428 648 410 597 398 548 C385 492 384 436 394 377 C403 322 426 260 454 207 C475 167 495 127 520 70 Z" fill="url(#landGradient)" stroke="#94a3b8" stroke-width="10" stroke-linejoin="round" />
        <path d="M453 210 C470 238 486 269 493 301 C500 334 495 367 485 400 C477 429 471 461 470 495 C469 537 478 579 488 616 C497 651 503 688 500 726 C496 773 485 818 472 862 C461 899 450 934 436 972" fill="none" stroke="rgba(148,163,184,0.35)" stroke-width="4" stroke-linecap="round" />
        <text x="500" y="1110" text-anchor="middle" fill="#64748b" font-size="34" font-weight="700" font-family="Arial, sans-serif">Sri Lanka</text>
      </svg>
      <div class="pending-map-spots" id="pending-pickups-map-spots"></div>
    </div>
    <div id="pending-pickups-map-message" class="pending-map-caption"></div>
  </activity-card>

<script>
  const PENDING_PICKUP_LOCATIONS = <?= json_encode(array_values($pendingPickupLocations), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  function updatePendingMapMessage(message) {
    const messageEl = document.getElementById('pending-pickups-map-message');
    if (!messageEl) return;
    messageEl.textContent = message || '';
  }

  function setPendingMapEmptyState(message) {
    const frameEl = document.getElementById('pending-pickups-map-frame');
    const spotsEl = document.getElementById('pending-pickups-map-spots');
    if (spotsEl) {
      spotsEl.innerHTML = '';
    }
    if (frameEl) {
      frameEl.innerHTML = `<p class="pending-map-empty">${message}</p>`;
    }
    updatePendingMapMessage('');
  }

  function parseCoordinates(locationItem) {
    const lat = Number(locationItem?.latitude);
    const lng = Number(locationItem?.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return null;
    }

    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
      return null;
    }

    return { lat, lng };
  }

  const SRI_LANKA_BOUNDS = {
    south: 5.85,
    north: 9.95,
    west: 79.45,
    east: 82.15
  };

  function projectToMapPosition(lat, lng) {
    const xRatio = (lng - SRI_LANKA_BOUNDS.west) / (SRI_LANKA_BOUNDS.east - SRI_LANKA_BOUNDS.west);
    const yRatio = 1 - ((lat - SRI_LANKA_BOUNDS.south) / (SRI_LANKA_BOUNDS.north - SRI_LANKA_BOUNDS.south));

    const x = Math.min(96, Math.max(4, xRatio * 100));
    const y = Math.min(94, Math.max(6, yRatio * 100));

    return { x, y };
  }

  function isWithinSriLanka(lat, lng) {
    return (
      lat >= SRI_LANKA_BOUNDS.south &&
      lat <= SRI_LANKA_BOUNDS.north &&
      lng >= SRI_LANKA_BOUNDS.west &&
      lng <= SRI_LANKA_BOUNDS.east
    );
  }

  const FALLBACK_LANDMARK_POINTS = [
    { x: 51, y: 14 },
    { x: 56, y: 20 },
    { x: 59, y: 27 },
    { x: 58, y: 35 },
    { x: 57, y: 43 },
    { x: 56, y: 51 },
    { x: 55, y: 59 },
    { x: 54, y: 67 },
    { x: 53, y: 75 },
    { x: 51, y: 83 },
    { x: 49, y: 90 },
    { x: 47, y: 96 },
    { x: 45, y: 86 },
    { x: 46, y: 78 },
    { x: 47, y: 70 },
    { x: 48, y: 62 },
    { x: 49, y: 54 },
    { x: 50, y: 46 },
    { x: 50, y: 38 },
    { x: 50, y: 30 },
    { x: 50, y: 22 },
  ];

  function getFallbackLandmark(index) {
    const base = FALLBACK_LANDMARK_POINTS[index % FALLBACK_LANDMARK_POINTS.length];
    const band = Math.floor(index / FALLBACK_LANDMARK_POINTS.length);

    const xOffset = ((band % 3) - 1) * 1.6;
    const yOffset = (Math.floor(band / 3) % 3) * 1.4;

    return {
      x: Math.min(62, Math.max(38, base.x + xOffset)),
      y: Math.min(98, Math.max(12, base.y + yOffset)),
    };
  }

  function resolveMapPosition(locationItem, index) {
    const directCoordinates = parseCoordinates(locationItem);
    if (directCoordinates && isWithinSriLanka(directCoordinates.lat, directCoordinates.lng)) {
      return projectToMapPosition(directCoordinates.lat, directCoordinates.lng);
    }

    return getFallbackLandmark(index);
  }

  function renderPendingPickupImage() {
    const spotsLayer = document.getElementById('pending-pickups-map-spots');
    if (!spotsLayer) return;

    if (!Array.isArray(PENDING_PICKUP_LOCATIONS) || PENDING_PICKUP_LOCATIONS.length === 0) {
      setPendingMapEmptyState('No pending pickup locations available');
      return;
    }

    spotsLayer.innerHTML = '';
    let markerCount = 0;

    const renderMarker = (locationItem, position) => {
      const spot = document.createElement('div');
      spot.className = 'pending-map-spot';
      spot.style.left = `${position.x}%`;
      spot.style.top = `${position.y}%`;

      const pin = document.createElement('div');
      pin.className = 'pending-map-pin';

      const label = document.createElement('div');
      label.className = 'pending-map-spot-label';
      label.textContent = locationItem.customerName || 'Pending Pickup';

      spot.appendChild(pin);
      spot.appendChild(label);
      spotsLayer.appendChild(spot);
    };

    PENDING_PICKUP_LOCATIONS.forEach((locationItem, index) => {
      const position = resolveMapPosition(locationItem, index);
      renderMarker(locationItem, position);
      markerCount += 1;
    });

    updatePendingMapMessage(`${markerCount} pending pickup request(s) shown as red landmarks on Sri Lanka map`);
  }

  (function initializePendingPickupMap() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', renderPendingPickupImage);
    } else {
      renderPendingPickupImage();
    }
  })();

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

        // Clear and rebuild the container
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