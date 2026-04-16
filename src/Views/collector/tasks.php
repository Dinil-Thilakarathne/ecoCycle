<?php
$assignedPickups = $assignedPickups ?? [];
$selectedTimeSlot = $selectedTimeSlot ?? 'all';
$selectedStatus = $selectedStatus ?? 'all';
$assignedRequests = array_values($assignedPickups);
$csrfToken = csrf_token();

// Status badge generator
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

<!-- Pickup Details Modal -->
<div id="pickup-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close" onclick="closeDetailModal()">&times;</button>
        <h3>Pickup Task Details</h3>
        <div class="user-modal__grid">
            <!-- <div><strong>Request ID</strong></div>
            <div class="pd-id"></div> -->
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
                <!-- Weight inputs will be injected here -->
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

            <!-- <div id="wasteBreakdown" class="tasks-waste-breakdown"></div> -->
        </div>

        <div class="tasks-action-wrap">
            <!-- <button class="btn" onclick="closeDetailModal()">Close</button> -->
            <button class="btn btn-primary" id="taskActionBtn" onclick="updateTaskStatus()">Start Task</button>
        </div>
    </div>
</div>

<script>
    // Grab modal elements
    const weightInput = document.getElementById('weightInput');
    const calculatedPriceEl = document.getElementById('calculatedPrice');
    const weightError = document.getElementById('weightError');
    const enterBtn = document.getElementById('enterWeightBtn');

    // Close modal
    function closeDetailModal() {
        const modal = document.getElementById('pickup-detail-modal');
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function calculateTotal() {
        const inputs = document.querySelectorAll('.weight-input');
        let total = 0;
        inputs.forEach(input => {
            const weight = parseFloat(input.value) || 0;
            const price = parseFloat(input.getAttribute('data-price')) || 0;
            total += weight * price;
        });
        const display = document.getElementById('calculatedPriceDisplay');
        if (display) {
            display.textContent = 'Rs. ' + total.toFixed(2);
        }
    }

    function viewDetails(el, pickupId) {
        const record = (window.__PICKUP_DATA || []).find(r => (r.id || '') == pickupId);
        const modal = document.getElementById('pickup-detail-modal');
        if (!record || !modal) return;

        // modal.querySelector('.pd-id').textContent = record.id;
        modal.querySelector('.pd-customer').textContent = record.customerName;
        modal.querySelector('.pd-address').textContent = record.address;
        modal.querySelector('.pd-waste').textContent = record.wasteCategories.join(', ');
        modal.querySelector('.pd-timeslot').textContent = record.timeSlot;
        const statusValue = normalizeStatusValue(record.status);
        modal.querySelector('.pd-status').textContent = statusValue;

        const btn = document.getElementById('taskActionBtn');
        btn.style.display = '';
        btn.disabled = false;
        btn.textContent = 'Start Task'; // Default

        const weightRow = document.getElementById('weight-entry-row');
        weightRow.style.display = 'none';

        // Find container for inputs - it's the second div inside weight-entry-row
        // We'll give it a clean class or ID to make selecting easier, 
        // but since I can't edit HTML structure easily without replacing huge block, 
        // I will select it by structure or just clear innerHTML of the container div used previously.
        // Actually, let's create a specific container in the replacement above, but for now I'll use the div following the label
        const inputContainer = weightRow.querySelector('div:nth-child(2)');
        inputContainer.innerHTML = '';

        if (statusValue === 'assigned') {
            btn.textContent = 'Start Task';
        } else if (statusValue === 'in progress') {
            btn.textContent = 'Mark as Completed';

            // Show weight input for each category
            weightRow.style.display = 'block';

            // Re-initialize total price
            const display = document.getElementById('calculatedPriceDisplay');
            if (display) display.textContent = 'Rs. 0.00';

            if (record.wasteCategoryDetails && record.wasteCategoryDetails.length > 0) {
                record.wasteCategoryDetails.forEach(cat => {
                    const div = document.createElement('div');
                    div.style.marginBottom = '0.75rem';

                    const label = document.createElement('label');
                    // Show price hint in label
                    const priceHint = cat.price_per_unit ? ` (Rs. ${parseFloat(cat.price_per_unit).toFixed(2)}/kg)` : '';
                    label.textContent = `${cat.name}${priceHint}`;
                    label.style.display = 'block';
                    label.style.fontSize = '0.9rem';
                    label.style.marginBottom = '0.25rem';

                    const input = document.createElement('input');
                    input.type = 'number';
                    input.step = '0.01';
                    input.min = '0';
                    input.className = 'weight-input';
                    input.style.width = '100%';
                    input.style.padding = '0.5rem';
                    input.style.border = '1px solid #e5e7eb';
                    input.style.borderRadius = '4px';
                    input.setAttribute('data-cat-id', cat.id);
                    // Store price in data attribute
                    input.setAttribute('data-price', cat.price_per_unit || 0);
                    input.placeholder = '0.00';

                    // Attach listener
                    input.addEventListener('input', calculateTotal);

                    div.appendChild(label);
                    div.appendChild(input);
                    inputContainer.appendChild(div);
                });
            } else {
                inputContainer.innerHTML += '<div class="tasks-no-categories">No waste categories found.</div>';
            }

            const errorDiv = document.getElementById('weightError');
            if (errorDiv) errorDiv.style.display = 'none';

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
        let nextTarget = '';
        if (current === 'assigned') nextTarget = 'in_progress';
        else if (current === 'in progress') nextTarget = 'completed';
        else if (current === 'in_progress') nextTarget = 'completed';

        if (!nextTarget) return;

        const btn = document.getElementById('taskActionBtn');
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
                    // If no inputs (no categories), we might still want to complete? 
                    // Usually requires at least one weight.
                    // Let's assume validation is required if inputs exist.
                }

                inputs.forEach(input => {
                    const val = parseFloat(input.value);
                    if (isNaN(val) || val <= 0) {
                        allValid = false;
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

            viewDetails(null, pickupId);

        } catch (error) {
            btn.textContent = originalText;
            btn.disabled = false;
            alert(error.message || 'Unable to update task status.');
        }
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