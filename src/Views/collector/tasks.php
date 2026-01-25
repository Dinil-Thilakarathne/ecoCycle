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

<!-- JavaScript data for front-end -->
<script>
    window.__PICKUP_DATA = <?php echo json_encode($assignedRequests, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    window.__FILTERS = {
        timeSlot: <?php echo json_encode($selectedTimeSlot); ?>,
        status: <?php echo json_encode($selectedStatus); ?>
    };
    const csrfToken = <?php echo json_encode($csrfToken, JSON_UNESCAPED_UNICODE); ?>;
</script>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header__content">
        <h2 class="page-header__title">
            My Assigned Pickups & Daily Tasks
        </h2>
        <p class="page-header__description">Manage your assigned pickups and track progress in real time</p>
    </div>
</div>

<!-- Task Table -->
<div class="activity-card">
    <div class="activity-card__header">
        <h3 class="activity-card__title">
            <i class="fa-solid fa-box" style="margin-right:8px;"></i>
            My Tasks
        </h3>
        <p class="activity-card__description"><?= count($assignedRequests) ?> assigned pickups</p>
    </div>

    <div class="activity-card__content">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Waste</th>
                        <th>Time Slot</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assignedRequests)): ?>
                        <?php foreach ($assignedRequests as $r): ?>
                            <tr data-id="<?= htmlspecialchars($r['id'] ?? '') ?>">
                                <td><?= htmlspecialchars($r['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['customerName'] ?? 'Unknown Customer') ?></td>
                                <td><?= htmlspecialchars($r['address'] ?? 'Not provided') ?></td>
                                <td><?= htmlspecialchars(implode(', ', $r['wasteCategories'] ?? [])) ?></td>
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
                            <td colspan="7" style="text-align:center;color:gray;">No tasks assigned.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Task Details -->
<div id="pickup-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close" onclick="closeDetailModal()">&times;</button>
        <h3>Pickup Task Details</h3>
        <div class="user-modal__grid">
            <div><strong>Request ID</strong></div>
            <div class="pd-id"></div>
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
        <div id="weight-entry-row" style="display:none;margin-top:var(--space-6);">
            <div style="margin-bottom:0.5rem;"><strong>Measured Weight (kg)</strong></div>
            <div>
                <input id="weightInput" type="number" step="0.01" min="0" placeholder="e.g. 12.50"
                    style="padding:0.5rem;border:1px solid #e5e7eb;border-radius:4px;width:100%;box-sizing:border-box;">
                <div id="weightError" style="color:#dc2626;margin-top:0.5rem;display:none;font-size:0.95rem;">Please
                    enter a valid weight greater than 0.</div>
            </div>
        </div>
        <div style="margin-top: var(--space-8); text-align: right;">
            <button class="btn" onclick="closeDetailModal()">Close</button>
            <button class="btn btn-primary" id="taskActionBtn" onclick="updateTaskStatus()">Start Task</button>
        </div>
    </div>
</div>

<script>
    function closeDetailModal() {
        const modal = document.getElementById('pickup-detail-modal');
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function viewDetails(el, pickupId) {
        const record = (window.__PICKUP_DATA || []).find(r => (r.id || '') == pickupId);
        const modal = document.getElementById('pickup-detail-modal');
        if (!record || !modal) return;

        modal.querySelector('.pd-id').textContent = record.id;
        modal.querySelector('.pd-customer').textContent = record.customerName;
        modal.querySelector('.pd-address').textContent = record.address;
        modal.querySelector('.pd-waste').textContent = record.wasteCategories.join(', ');
        modal.querySelector('.pd-timeslot').textContent = record.timeSlot;
        const statusValue = normalizeStatusValue(record.status);
        modal.querySelector('.pd-status').textContent = statusValue;

        const btn = document.getElementById('taskActionBtn');
        btn.style.display = '';
        btn.disabled = false;

        const weightRow = document.getElementById('weight-entry-row');
        weightRow.style.display = 'none';
        weightRow.innerHTML = ''; // Clear previous inputs

        if (statusValue === 'assigned') {
            btn.textContent = 'Start Task';
        } else if (statusValue === 'in progress') {
            btn.textContent = 'Mark as Completed';

            // Show weight input for each category
            weightRow.style.display = 'block';
            weightRow.innerHTML = '<div style="margin-bottom:0.5rem;font-weight:600;">Enter Measured Weights:</div>';

            if (record.wasteCategoryDetails && record.wasteCategoryDetails.length > 0) {
                record.wasteCategoryDetails.forEach(cat => {
                    const div = document.createElement('div');
                    div.style.marginBottom = '0.75rem';

                    const label = document.createElement('label');
                    label.textContent = `${cat.name} (kg)`;
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
                    input.placeholder = '0.00';

                    div.appendChild(label);
                    div.appendChild(input);
                    weightRow.appendChild(div);
                });
            } else {
                weightRow.innerHTML += '<div style="color:gray;font-style:italic;">No waste categories found.</div>';
            }

            const errorDiv = document.createElement('div');
            errorDiv.id = 'weightError';
            errorDiv.style.color = '#dc2626';
            errorDiv.style.marginTop = '0.5rem';
            errorDiv.style.display = 'none';
            errorDiv.style.fontSize = '0.95rem';
            errorDiv.textContent = 'Please enter valid weights for all categories.';
            weightRow.appendChild(errorDiv);

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

                inputs.forEach(input => {
                    const val = parseFloat(input.value);
                    if (isNaN(val) || val < 0) { // Allow 0, but usually weight > 0
                        allValid = false;
                    }
                    weights.push({
                        category_id: parseInt(input.getAttribute('data-cat-id')),
                        weight: val
                    });
                });

                if (!allValid || weights.length === 0) {
                    const err = document.getElementById('weightError');
                    if (err) err.style.display = 'block';
                    btn.textContent = originalText;
                    btn.disabled = false;
                    return;
                }

                payloadBody.weights = weights;
            }

            const response = await fetch(`/api/collector/pickup-requests/${encodeURIComponent(pickupId)}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
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

            // Refresh the view logic to update buttons/inputs
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