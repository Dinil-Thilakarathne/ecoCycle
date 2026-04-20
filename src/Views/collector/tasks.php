<?php
$assignedPickups = $assignedPickups ?? [];
$selectedTimeSlot = $selectedTimeSlot ?? 'all';
$selectedStatus = $selectedStatus ?? 'all';
$assignedRequests = array_values($assignedPickups);
$csrfToken = csrf_token();


function getStatusBadge($status)
{
    $status = strtolower($status);
    $class = '';
    switch ($status) {
        case 'pending':
            $class = 'pending';
            break;
        case 'assigned':
            $class = 'assigned';
            break;
        case 'in progress':
            $class = 'inprogress';
            break;
        case 'completed':
            $class = 'completed';
            break;
    }
    return "<div class='tag $class'>" . ucfirst($status) . "</div>";
}
?>

<script>
    window.__PICKUP_DATA = <?php echo json_encode($assignedRequests, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    window.__FILTERS = {
        timeSlot: <?php echo json_encode($selectedTimeSlot); ?>,
        status: <?php echo json_encode($selectedStatus); ?>
    };
    const csrfToken = <?php echo json_encode($csrfToken, JSON_UNESCAPED_UNICODE); ?>;
</script>

<div class="page-header">
    <div class="page-header__content">
        <h2 class="page-header__title">My Assigned Pickups & Daily Tasks</h2>
        <p class="page-header__description">Manage your assigned pickups and track progress in real time</p>
    </div>
</div>

<div class="activity-card">
    <div class="activity-card__header">
        <h3 class="activity-card__title"><i class="fa-solid fa-box analytics-icon-gap"></i>My Tasks</h3>
        <p class="activity-card__description"><?= count($assignedRequests) ?> assigned pickups</p>
    </div>
    <div class="activity-card__content">
        <div class="tasks-table-wrap">
            <table class="data-table tasks-data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Waste</th>
                        <th>Vehicle</th>
                        <th>Time Slot</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assignedRequests)): ?>
                        <?php foreach ($assignedRequests as $r): ?>
                            <tr data-id="<?= htmlspecialchars($r['id'] ?? '') ?>">
                                <td><?= htmlspecialchars($r['customerName'] ?? 'Unknown Customer') ?></td>
                                <td><?= htmlspecialchars($r['address'] ?? 'Not provided') ?></td>
                                <td><?= htmlspecialchars(implode(', ', $r['wasteCategories'] ?? [])) ?></td>
                                <td>
                                    <div class="vehicle-cell">
                                        <span title="<?= htmlspecialchars($r['vehicleType'] ?? $r['vehicle'] ?? $r['vehicleModel'] ?? 'N/A') ?>">
                                            <?= htmlspecialchars($r['vehicleType'] ?? $r['vehicle'] ?? $r['vehicleModel'] ?? '-') ?>
                                        </span>
                                        <?php if (!empty($r['vehiclePlate'] ?? null)): ?>
                                            <small class="tasks-vehicle-plate">
                                                <?= htmlspecialchars($r['vehiclePlate']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($r['timeSlot'] ?? '') ?></td>
                                <td><?= getStatusBadge($r['status'] ?? ($r['statusRaw'] ?? '')) ?></td>
                                <td>
                                    <button class="icon-button"
                                        onclick="viewDetails(this, '<?= htmlspecialchars($r['id'] ?? '') ?>')">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="tasks-empty">No tasks assigned.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="pickup-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close" onclick="closeDetailModal()">&times;</button>
        <h3>Pickup Task Details</h3>
        <div class="user-modal__grid">
            <div><strong>Customer</strong></div>
            <div class="pd-customer"></div>
            <div><strong>Address</strong></div>
            <div class="pd-address"></div>
            <div><strong>Waste Categories</strong></div>
            <div class="pd-waste"></div>
            <div><strong>Time Slot</strong></div>
            <div class="pd-timeslot"></div>
            <div><strong>Status</strong></div>
            <div class="pd-status"></div>
        </div>


        <div id="weight-entry-row" class="tasks-weight-entry-row">
            <div class="tasks-weight-entry-label"><strong>Measured Weight (kg)</strong></div>
            <div>
               
            </div>
            <div id="price-display-row" class="tasks-price-display-row">
                <div class="tasks-price-display-inner">
                    <strong class="tasks-price-label">Total Price:</strong>
                    <span id="calculatedPriceDisplay" class="tasks-price-value">Rs.
                        0.00</span>
                </div>
            </div>

            <div id="weightError" class="tasks-weight-error">Please
                enter a valid weight greater than 0.</div>

         
        </div>

        <div class="tasks-action-wrap">
            
            <button class="btn btn-primary" id="taskActionBtn" onclick="updateTaskStatus()">Start Task</button>
        </div>
    </div>
</div>

<div id="route-map-modal" class="user-modal tasks-route-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog tasks-route-modal__dialog">
        <button class="close" aria-label="Close" onclick="closeRouteModal()">&times;</button>
        <h3>Route to Pickup Destination</h3>
        <p id="routeMapSummary" class="tasks-route-summary">Finding the fastest route to customer destination...</p>
        <iframe id="routeMapFrame" class="tasks-route-map" title="Pickup route map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <div id="routeMapError" class="tasks-route-error"></div>
        <div class="tasks-route-actions">
            <a id="openExternalRouteBtn" class="btn" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
            <button class="btn btn-primary" id="routeStartTaskBtn" onclick="confirmStartTaskFromRoute()">Start Task Now</button>
        </div>
    </div>
</div>

<script>
  
    const weightInput = document.getElementById('weightInput');
    const calculatedPriceEl = document.getElementById('calculatedPrice');
    const weightError = document.getElementById('weightError');
    const enterBtn = document.getElementById('enterWeightBtn');
    let routePendingPickupId = null;
    let routePendingMode = 'start';


    function closeDetailModal() {
        const modal = document.getElementById('pickup-detail-modal');
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function closeRouteModal() {
        const modal = document.getElementById('route-map-modal');
        const iframe = document.getElementById('routeMapFrame');
        if (iframe) {
            iframe.src = 'about:blank';
        }
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function showWeightWarning(message) {
        const errorDiv = document.getElementById('weightError');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
        }
    }
    
    function calculateTotal() {
        const inputs = document.querySelectorAll('.weight-input');
        let total = 0;
        let hasError = false;
        
        inputs.forEach(input => {
            const weight = parseFloat(input.value) || 0;
          
            if (weight > 100) {
                hasError = true;
            }
            const price = parseFloat(input.getAttribute('data-price')) || 0;
            total += weight * price;
        });
        
        const display = document.getElementById('calculatedPriceDisplay');
        if (display) {
            display.textContent = 'Rs. ' + total.toFixed(2);
        }
        
        const errorDiv = document.getElementById('weightError');
        if (hasError && errorDiv) {
            errorDiv.textContent = 'Weight cannot exceed 100 kg per category';
            errorDiv.style.display = 'block';
        } else if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    function renderWeightInputs(record) {
        const weightRow = document.getElementById('weight-entry-row');
        const inputContainer = weightRow.querySelector('div:nth-child(2)');

        inputContainer.innerHTML = '';

        if (record.wasteCategoryDetails && record.wasteCategoryDetails.length > 0) {
            record.wasteCategoryDetails.forEach(cat => {
                const div = document.createElement('div');
                div.style.marginBottom = '0.75rem';

                const label = document.createElement('label');
                const priceHint = cat.price_per_unit ? ` (Rs. ${parseFloat(cat.price_per_unit).toFixed(2)}/kg)` : '';
                label.textContent = `${cat.name}${priceHint}`;
                label.style.display = 'block';
                label.style.fontSize = '0.9rem';
                label.style.marginBottom = '0.25rem';

                const input = document.createElement('input');
                input.type = 'number';
                input.step = '0.01';
                input.min = '0';
                input.max = '100';
                input.className = 'weight-input';
                input.style.width = '100%';
                input.style.padding = '0.5rem';
                input.style.border = '1px solid #e5e7eb';
                input.style.borderRadius = '4px';
                input.setAttribute('data-cat-id', cat.id);
                input.setAttribute('data-price', cat.price_per_unit || 0);
                input.placeholder = '0.00';
                
              
                function validateWeightInput(e) {
                    let value = e.target.value;
                    if (value === '') return;
                    
                    const val = parseFloat(value);
                    
                
                    if (val > 100) {
                        e.target.value = '100';
                        showWeightWarning('Weight cannot exceed 100 kg');
                        return;
                    }
                    
                    
                    if (value.includes('.')) {
                        const parts = value.split('.');
                        if (parts[1] && parts[1].length > 2) {
                            e.target.value = val.toFixed(2);
                            showWeightWarning('Only 2 decimal places allowed');
                        }
                    }
                    
                    calculateTotal();
                }
                
                input.addEventListener('change', validateWeightInput);
                input.addEventListener('blur', validateWeightInput);
                input.addEventListener('input', function() {
                    
                    calculateTotal();
                });

                div.appendChild(label);
                div.appendChild(input);
                inputContainer.appendChild(div);
            });
        } else {
            inputContainer.innerHTML = '<div class="tasks-no-categories">No waste categories found.</div>';
        }

        const display = document.getElementById('calculatedPriceDisplay');
        if (display) {
            display.textContent = 'Rs. 0.00';
        }

        const errorDiv = document.getElementById('weightError');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    function showWeightEntry(record) {
        const modal = document.getElementById('pickup-detail-modal');
        const weightRow = document.getElementById('weight-entry-row');
        const btn = document.getElementById('taskActionBtn');

        renderWeightInputs(record);
        weightRow.style.display = 'block';
        modal.dataset.weightEntryVisible = '1';
        btn.style.display = '';
        btn.disabled = false;
        btn.textContent = 'Mark as Completed';
    }

    function hideWeightEntry() {
        const modal = document.getElementById('pickup-detail-modal');
        const weightRow = document.getElementById('weight-entry-row');
        weightRow.style.display = 'none';
        modal.dataset.weightEntryVisible = '0';
    }

    function viewDetails(el, pickupId) {
        const record = (window.__PICKUP_DATA || []).find(r => (r.id || '') == pickupId);
        const modal = document.getElementById('pickup-detail-modal');
        if (!record || !modal) return;

        modal.querySelector('.pd-customer').textContent = record.customerName;
        modal.querySelector('.pd-address').textContent = record.address;
        modal.querySelector('.pd-waste').textContent = record.wasteCategories.join(', ');
        modal.querySelector('.pd-timeslot').textContent = record.timeSlot;
        const statusValue = normalizeStatusValue(record.status);
        modal.querySelector('.pd-status').textContent = statusValue;

        const btn = document.getElementById('taskActionBtn');
        btn.style.display = '';
        btn.disabled = false;
        btn.textContent = 'Open Map'; 

        const weightRow = document.getElementById('weight-entry-row');
        const weightVisible = modal.dataset.weightEntryVisible === '1';
        hideWeightEntry();

        if (statusValue === 'assigned') {
            btn.textContent = 'Open Map';
        } else if (statusValue === 'in progress') {
            btn.textContent = weightVisible ? 'Mark as Completed' : 'Open Map';

            if (weightVisible) {
                renderWeightInputs(record);
                weightRow.style.display = 'block';
            }

        } else {
            btn.style.display = 'none';
        }

        modal.setAttribute('data-current-id', record.id);
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    async function updateTaskStatus() {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        const idx = (window.__PICKUP_DATA || []).findIndex(r => r.id == pickupId);
        if (idx === -1) return;

        const current = normalizeStatusValue(window.__PICKUP_DATA[idx].status);
        if (current === 'assigned') {
            await openRoutePrompt(window.__PICKUP_DATA[idx]);
            return;
        }

        if (current === 'in progress' && modal.dataset.weightEntryVisible !== '1') {
            await openRoutePrompt(window.__PICKUP_DATA[idx], 'arrived');
            return;
        }

        let nextTarget = '';
        if (current === 'in progress' || current === 'in_progress') {
            nextTarget = 'completed';
        }

        if (!nextTarget) return;

        const btn = document.getElementById('taskActionBtn');
        await submitStatusUpdate(pickupId, nextTarget, btn);
    }

    async function confirmStartTaskFromRoute() {
        if (!routePendingPickupId) {
            return;
        }

        const routeBtn = document.getElementById('routeStartTaskBtn');
        const pickup = (window.__PICKUP_DATA || []).find(r => r.id == routePendingPickupId);
        if (routePendingMode === 'arrived') {
            closeRouteModal();
            if (pickup) {
                showWeightEntry(pickup);
                const detailModal = document.getElementById('pickup-detail-modal');
                detailModal.classList.add('open');
                detailModal.setAttribute('aria-hidden', 'false');
                detailModal.querySelector('.pd-status').textContent = 'in progress';
            }
            return;
        }

        await submitStatusUpdate(routePendingPickupId, 'in_progress', routeBtn);
    }

    async function submitStatusUpdate(pickupId, nextTarget, btn) {
        const modal = document.getElementById('pickup-detail-modal');
        const idx = (window.__PICKUP_DATA || []).findIndex(r => r.id == pickupId);
        if (idx === -1) return;

        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const payloadBody = { status: nextTarget };

            if (nextTarget === 'completed') {
                const inputs = modal.querySelectorAll('.weight-input');
                let allValid = true;
                const weights = [];

                if (inputs.length === 0) {
                   
                }

                inputs.forEach(input => {
                    const val = parseFloat(input.value);
                    if (isNaN(val) || val <= 0) {
                        allValid = false;
                    }
                   
                    if (val > 100) {
                        allValid = false;
                        showWeightWarning(`Weight for ${input.previousElementSibling.textContent} cannot exceed 100 kg`);
                    }
                    weights.push({
                        category_id: parseInt(input.getAttribute('data-cat-id')),
                        weight: val
                    });
                });

                if (!allValid || (inputs.length > 0 && weights.length === 0)) {
                    document.getElementById('weightError').style.display = 'block';
                    btn.textContent = originalText;
                    btn.disabled = false;
                    return;
                }

                payloadBody.weights = weights;
            }

            const response = await fetch(`/api/collector/pickup-requests/${pickupId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payloadBody)
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = (payload && payload.message) ? payload.message : 'Failed to update task status.';
                throw new Error(message);
            }

            const updated = payload.data || {};
            const normalizedStatus = normalizeStatusValue(updated.status || updated.statusRaw || nextTarget);

            window.__PICKUP_DATA[idx] = {
                ...window.__PICKUP_DATA[idx],
                ...updated,
                status: normalizedStatus
            };

            const row = document.querySelector(`tr[data-id="${pickupId}"]`);
            if (row) {
                const statusCell = row.querySelectorAll('td')[5];
                if (statusCell) statusCell.innerHTML = getStatusBadge(normalizedStatus);
            }

            modal.querySelector('.pd-status').textContent = normalizedStatus;

            function showToast(message, type = 'info') {
                if (typeof window.__createToast === 'function') {
                    window.__createToast(message, type, 100000);
                } else {
                    const prefix = type === 'error' ? 'Error: ' : '';
                    alert(prefix + message);
                }
            }

            if (nextTarget === 'completed') {
                const message = updated.price
                    ? `Pickup completed! Total Amount: Rs. ${parseFloat(updated.price).toFixed(2)}`
                    : `Pickup completed successfully!`;

                showToast(message, 'success', 10000);
            } else {
                showToast('Status updated successfully', 'success', 10000);
            }

            if (nextTarget === 'in_progress') {
                closeRouteModal();
            }

            viewDetails(null, pickupId);

        } catch (error) {
            btn.textContent = originalText;
            btn.disabled = false;
            alert(error.message || 'Unable to update task status.');
        }
    }

    async function openRoutePrompt(record, mode = 'start') {
        const modal = document.getElementById('route-map-modal');
        const summary = document.getElementById('routeMapSummary');
        const errorEl = document.getElementById('routeMapError');
        const startBtn = document.getElementById('routeStartTaskBtn');
        const iframe = document.getElementById('routeMapFrame');

        routePendingPickupId = record.id;
        routePendingMode = mode === 'arrived' ? 'arrived' : 'start';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        summary.textContent = 'Preparing map and destination...';
        errorEl.style.display = 'none';
        errorEl.textContent = '';
        startBtn.disabled = true;
        startBtn.textContent = routePendingMode === 'arrived' ? 'Loading Route...' : 'Loading Route...';
        if (iframe) {
            iframe.src = 'about:blank';
        }

        try {
            const destination = await resolveDestinationLocation(record);
            summary.textContent = destination.approximate
                ? `Showing the closest route found for ${destination.label || record.address || 'Pickup destination'}.`
                : 'Getting your current location and route...';

            let origin = null;
            try {
                origin = await getCollectorCurrentLocation();
            } catch (locationError) {
                origin = null;
                errorEl.style.display = 'block';
                errorEl.textContent = locationError.message || 'Using destination map because current location is unavailable.';
            }

            updateExternalDirectionsLink(origin, destination, record.address || destination.label || 'Pickup destination');
            if (iframe) {
                iframe.src = buildRouteIframeUrl(origin, destination, record.address || destination.label || 'Pickup destination');
            }

            startBtn.disabled = false;
            startBtn.textContent = routePendingMode === 'arrived' ? 'Reached Destination' : 'Start Task Now';
        } catch (error) {
            summary.textContent = 'Map is shown. Using the nearest available location instead of the exact destination.';
            errorEl.style.display = 'block';
            errorEl.textContent = error.message || 'Failed to load route map.';
            updateExternalDirectionsLink(null, null, record.address || 'Pickup destination');
            if (iframe) {
                iframe.src = buildRouteIframeUrl(null, null, record.address || 'Pickup destination');
            }

            startBtn.disabled = false;
            startBtn.textContent = routePendingMode === 'arrived' ? 'Reached Destination' : 'Start Task Anyway';
        }
    }

    function updateExternalDirectionsLink(origin, destination, destinationAddress) {
        const externalLink = document.getElementById('openExternalRouteBtn');
        if (!externalLink) {
            return;
        }

        let url = '';
        if (destination && Number.isFinite(destination.lat) && Number.isFinite(destination.lng)) {
            const originParam = origin && Number.isFinite(origin.lat) && Number.isFinite(origin.lng)
                ? `&origin=${encodeURIComponent(origin.lat + ',' + origin.lng)}`
                : '';
            url = `https://www.google.com/maps/dir/?api=1${originParam}&destination=${encodeURIComponent(destination.lat + ',' + destination.lng)}&travelmode=driving`;
        } else {
            url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destinationAddress || '')}&travelmode=driving`;
        }

        externalLink.href = url;
    }

    async function resolveDestinationLocation(record) {
        const lat = parseFloat(record.latitude);
        const lng = parseFloat(record.longitude);
        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            return {
                lat,
                lng,
                label: record.address || 'Pickup destination'
            };
        }

        const address = (record.address || '').trim();
        if (address === '') {
            throw new Error('Destination location is missing coordinates and address.');
        }

        return geocodeAddressBestEffort(address);
    }

    function buildDestinationSearchCandidates(address) {
        const normalized = String(address || '').replace(/\s+/g, ' ').trim();
        if (normalized === '') {
            return [];
        }

        const parts = normalized
            .split(',')
            .map(part => part.trim())
            .filter(Boolean);

        const candidates = [normalized];

        if (parts.length > 1) {
            for (let i = 1; i < parts.length; i++) {
                const tail = parts.slice(i).join(', ');
                if (tail && !candidates.includes(tail)) {
                    candidates.push(tail);
                }
            }
        }

        const cleanedTokens = normalized
            .replace(/\b(no\.?|number|#|flat|apt|unit)\s*\d+[a-zA-Z-]?\b/gi, ' ')
            .replace(/\b\d{4,6}\b/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (cleanedTokens && !candidates.includes(cleanedTokens)) {
            candidates.push(cleanedTokens);
        }

        const laneMatch = normalized.match(/\b([a-z0-9'\- ]*\b(?:lane|ln|road|rd|street|st|avenue|ave|way|drive|dr|village|city|town|district)\b[a-z0-9'\- ]*)/i);
        if (laneMatch && laneMatch[1]) {
            const laneCandidate = laneMatch[1].replace(/\s+/g, ' ').trim();
            if (laneCandidate && !candidates.includes(laneCandidate)) {
                candidates.push(laneCandidate);
            }
        }

        return candidates;
    }

    async function geocodeAddressBestEffort(address) {
        const candidates = buildDestinationSearchCandidates(address);
        let lastError = null;

        for (const candidate of candidates) {
            try {
                const result = await geocodeAddressCandidate(candidate);
                if (result) {
                    if (candidate !== address) {
                        result.label = `${candidate} (approx. from ${address})`;
                        result.approximate = true;
                    }
                    return result;
                }
            } catch (error) {
                lastError = error;
            }
        }

        throw lastError || new Error('No map result found for destination address.');
    }

    async function geocodeAddressCandidate(candidate) {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(candidate)}`);
        if (!response.ok) {
            throw new Error('Could not resolve destination address.');
        }

        const rows = await response.json();
        if (!Array.isArray(rows) || rows.length === 0) {
            return null;
        }

        return {
            lat: parseFloat(rows[0].lat),
            lng: parseFloat(rows[0].lon),
            label: candidate
        };
    }

    function getCollectorCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Your browser does not support location services.'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        label: 'Your location'
                    });
                },
                () => reject(new Error('Allow location access to preview the route map.')),
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 60000
                }
            );
        });
    }

    function buildRouteIframeUrl(origin, destination, destinationAddress) {
        const params = new URLSearchParams();

        if (origin && Number.isFinite(origin.lat) && Number.isFinite(origin.lng)) {
            params.set('origin_lat', String(origin.lat));
            params.set('origin_lng', String(origin.lng));
        }

        if (destination && Number.isFinite(destination.lat) && Number.isFinite(destination.lng)) {
            params.set('destination_lat', String(destination.lat));
            params.set('destination_lng', String(destination.lng));
            params.set('destination_label', destination.label || destinationAddress || 'Pickup destination');
        } else {
            params.set('destination_label', destinationAddress || 'Pickup destination');
        }

        return `/collector/route-preview?${params.toString()}`;
    }

    function filterByTimeSlot() {
        const select = document.getElementById('timeSlotFilter');
        const slot = select.value;
        const url = new URL(window.location);
        if (slot === 'all') url.searchParams.delete('time_slot');
        else url.searchParams.set('time_slot', slot);
        window.location.href = url.toString();
    }

    function getStatusBadge(status) {
        const normalized = normalizeStatusValue(status);
        const map = {
            'pending': 'tag pending',
            'assigned': 'tag assigned',
            'in progress': 'tag inprogress',
            'completed': 'tag completed'
        };
        const cls = map[normalized] || 'tag';
        return `<div class="${cls}">${normalized.charAt(0).toUpperCase() + normalized.slice(1)}</div>`;
    }

    function normalizeStatusValue(status) {
        const value = (status || '').toString().toLowerCase();
        if (value === 'in_progress' || value === 'in-progress') {
            return 'in progress';
        }
        return value;
    }


</script>