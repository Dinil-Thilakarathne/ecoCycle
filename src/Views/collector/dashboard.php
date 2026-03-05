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

$googleMapsApiKey = (string) env('GOOGLE_MAPS_API_KEY', '');
$pendingPickupLocations = [];
foreach (($pendingPickups ?? []) as $pickupLocationItem) {
  $address = trim((string) ($pickupLocationItem['address'] ?? ''));
  $latitude = $pickupLocationItem['latitude'] ?? null;
  $longitude = $pickupLocationItem['longitude'] ?? null;
  $hasCoordinates = is_numeric($latitude) && is_numeric($longitude);

  if ($address === '' && !$hasCoordinates) {
    continue;
  }

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

  <activity-card title="Pending Pickup Locations" description="Map view of pending pickup request locations">
    <div id="pending-pickups-map" style="width: 100%; height: 360px; border-radius: var(--radius-lg); overflow: hidden; background: var(--neutral-100);"></div>
    <div id="pending-pickups-map-message" style="margin-top: 12px; color: var(--neutral-600); font-size: 0.9rem;"></div>
  </activity-card>

<script>
  const PENDING_PICKUP_LOCATIONS = <?= json_encode(array_values($pendingPickupLocations), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const GOOGLE_MAPS_API_KEY = <?= json_encode($googleMapsApiKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  function updatePendingMapMessage(message) {
    const messageEl = document.getElementById('pending-pickups-map-message');
    if (!messageEl) return;
    messageEl.textContent = message || '';
  }

  function setPendingMapEmptyState(message) {
    const mapEl = document.getElementById('pending-pickups-map');
    if (mapEl) {
      mapEl.innerHTML = `<p style="text-align: center; color: #999; padding: 140px 20px;">${message}</p>`;
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

  function geocodeAddress(geocoder, address) {
    return new Promise((resolve) => {
      geocoder.geocode({ address }, (results, status) => {
        if (status === 'OK' && Array.isArray(results) && results[0]?.geometry?.location) {
          resolve({ location: results[0].geometry.location, status: 'OK' });
          return;
        }
        resolve({ location: null, status: status || 'UNKNOWN_ERROR' });
      });
    });
  }

  async function resolveLocationForPickup(geocoder, locationItem) {
    const directCoordinates = parseCoordinates(locationItem);
    if (directCoordinates) {
      return { location: directCoordinates, source: 'coordinates', status: 'OK' };
    }

    const address = String(locationItem?.address || '').trim();
    if (!address) {
      return { location: null, source: 'address', status: 'ZERO_RESULTS' };
    }

    const variants = [address];
    if (!/,\s*Sri\s*Lanka$/i.test(address)) {
      variants.push(`${address}, Sri Lanka`);
    }

    let lastStatus = 'ZERO_RESULTS';
    for (const variant of variants) {
      const result = await geocodeAddress(geocoder, variant);
      if (result.location) {
        return { location: result.location, source: 'address', status: 'OK' };
      }
      lastStatus = result.status || lastStatus;
    }

    return { location: null, source: 'address', status: lastStatus };
  }

  function getGeocodeFailureMessage(status) {
    if (status === 'REQUEST_DENIED') {
      return 'Geocoding request denied. Enable Geocoding API and billing for this key.';
    }
    if (status === 'OVER_QUERY_LIMIT') {
      return 'Geocoding quota exceeded for the current Google Maps project.';
    }
    if (status === 'INVALID_REQUEST') {
      return 'Invalid geocoding request. Please verify pickup addresses.';
    }
    return 'Pending locations could not be mapped from addresses';
  }

  function getMarkerColorByStatus(status) {
    const normalized = String(status || '').toLowerCase();
    if (normalized === 'in_progress') return '#f59e0b';
    if (normalized === 'assigned') return '#3b82f6';
    return '#6b7280';
  }

  window.initPendingPickupsMap = async function initPendingPickupsMap() {
    const mapEl = document.getElementById('pending-pickups-map');
    if (!mapEl) return;

    if (!Array.isArray(PENDING_PICKUP_LOCATIONS) || PENDING_PICKUP_LOCATIONS.length === 0) {
      setPendingMapEmptyState('No pending pickup locations available');
      return;
    }

    const defaultCenter = { lat: 6.9271, lng: 79.8612 };
    const map = new google.maps.Map(mapEl, {
      center: defaultCenter,
      zoom: 12,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false,
    });

    const geocoder = new google.maps.Geocoder();
    const infoWindow = new google.maps.InfoWindow();
    const bounds = new google.maps.LatLngBounds();
    let markerCount = 0;
    let lastGeocodeFailureStatus = '';

    for (const locationItem of PENDING_PICKUP_LOCATIONS) {
      const address = String(locationItem.address || '').trim();

      const resolved = await resolveLocationForPickup(geocoder, locationItem);
      if (!resolved.location) {
        if (resolved.status && resolved.status !== 'OK') {
          lastGeocodeFailureStatus = resolved.status;
        }
        continue;
      }

      const markerPosition = resolved.location;

      const marker = new google.maps.Marker({
        map,
        position: markerPosition,
        title: locationItem.customerName || 'Pending Pickup',
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 8,
          fillColor: getMarkerColorByStatus(locationItem.status),
          fillOpacity: 1,
          strokeColor: '#ffffff',
          strokeWeight: 2,
        },
      });

      marker.addListener('click', () => {
        infoWindow.setContent(`
          <div style="max-width: 260px;">
            <strong>${String(locationItem.customerName || 'Pending Pickup')}</strong><br>
            <span>${String(address || 'Coordinates available')}</span>
            <br><small>Status: ${String(locationItem.status || 'pending').replace('_', ' ')}</small>
          </div>
        `);
        infoWindow.open({ anchor: marker, map });
      });

      bounds.extend(markerPosition);
      markerCount += 1;
    }

    if (markerCount > 0) {
      map.fitBounds(bounds);
      updatePendingMapMessage(`${markerCount} pending location(s) shown on map`);
      return;
    }

    setPendingMapEmptyState(getGeocodeFailureMessage(lastGeocodeFailureStatus));
  };

  (function initializePendingPickupMap() {
    if (!GOOGLE_MAPS_API_KEY) {
      setPendingMapEmptyState('Google Maps is not configured. Please set GOOGLE_MAPS_API_KEY.');
      return;
    }

    window.gm_authFailure = function gmAuthFailure() {
      setPendingMapEmptyState('Google Maps authentication failed. Check API key restrictions for this domain.');
    };

    if (window.google?.maps) {
      window.initPendingPickupsMap();
      return;
    }

    const existingScript = document.getElementById('google-maps-js-sdk');
    if (existingScript) return;

    const script = document.createElement('script');
    script.id = 'google-maps-js-sdk';
    script.async = true;
    script.defer = true;
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(GOOGLE_MAPS_API_KEY)}&callback=initPendingPickupsMap`;
    script.onerror = () => {
      setPendingMapEmptyState('Failed to load Google Maps. Please verify the API key and network access.');
    };
    document.head.appendChild(script);

    window.setTimeout(() => {
      if (!window.google?.maps) {
        setPendingMapEmptyState('Google Maps SDK did not initialize. Verify key restrictions and allowed referrers.');
      }
    }, 10000);
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