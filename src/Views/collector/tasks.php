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
        case 'pending': $class = 'pending'; break;
        case 'assigned': $class = 'assigned'; break;
        case 'in progress': $class = 'inprogress'; break;
        case 'completed': $class = 'completed'; break;
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
        <h3 class="activity-card__title"><i class="fa-solid fa-box" style="margin-right:8px;"></i>My Tasks</h3>
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
                                    <button class="icon-button" onclick="viewDetails(this, '<?= htmlspecialchars($r['id'] ?? '') ?>')">
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

<!-- Pickup Details Modal -->
<div id="pickup-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close" onclick="closeDetailModal()">&times;</button>
        <h3>Pickup Task Details</h3>
        <div class="user-modal__grid">
            <div><strong>Request ID</strong></div><div class="pd-id"></div>
            <div><strong>Customer</strong></div><div class="pd-customer"></div>
            <div><strong>Address</strong></div><div class="pd-address"></div>
            <div><strong>Waste Categories</strong></div><div class="pd-waste"></div>
            <div><strong>Time Slot</strong></div><div class="pd-timeslot"></div>
            <div><strong>Status</strong></div><div class="pd-status"></div>
        </div>

        <div id="weight-entry-row" style="display:none;margin-top:var(--space-6);">
            <div style="margin-bottom:0.5rem;"><strong>Measured Weight (kg)</strong></div>
            <div>
                <input id="weightInput" type="number" step="0.01" min="0" placeholder="e.g. 12.50"
                    style="padding:0.5rem;border:1px solid #e5e7eb;border-radius:4px;width:100%;box-sizing:border-box;">
                <div id="weightError" style="color:#dc2626;margin-top:0.5rem;display:none;font-size:0.95rem;">
                    Please enter a valid weight greater than 0.
                </div>
                <div style="margin-top:0.5rem;">
                    <button id="enterWeightBtn" class="btn btn-primary" type="button">Enter</button>
                </div>
            </div>

            <div id="totalPriceContainer" style="margin-top:1rem;">
                <strong>Calculated Price (Rs): </strong>
                <input id="calculatedPrice" type="text" placeholder="Rs0.00" readonly
                    style="padding:0.5rem; border:1px solid #d1d5db; border-radius:4px; width:150px; font-weight:600; color:#1f2937; text-align:right;">
            </div>

            <div id="wasteBreakdown" style="margin-top:0.5rem; font-size:0.9rem; color:#555;"></div>
        </div>

        <div style="margin-top: var(--space-8); text-align: right;">
            <button class="btn" onclick="closeDetailModal()">Close</button>
            <button class="btn btn-primary" id="taskActionBtn" onclick="startOrUpdateTask()">Start Task</button>
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

// Normalize status
function normalizeStatusValue(status) {
    const v = (status || '').toLowerCase();
    if (v === 'in_progress' || v === 'in-progress') return 'in progress';
    return v;
}

// Open modal and populate fields
function viewDetails(el, pickupId) {
    const record = window.__PICKUP_DATA.find(r => r.id == pickupId);
    if (!record) return;

    const modal = document.getElementById('pickup-detail-modal');
    modal.querySelector('.pd-id').textContent = record.id;
    modal.querySelector('.pd-customer').textContent = record.customerName;
    modal.querySelector('.pd-address').textContent = record.address;
    modal.querySelector('.pd-waste').textContent = (record.wasteCategories || []).join(', ');
    modal.querySelector('.pd-timeslot').textContent = record.timeSlot;
    modal.querySelector('.pd-status').textContent = normalizeStatusValue(record.status);

    // Show weight input only if in progress
    document.getElementById('weight-entry-row').style.display =
        ['assigned', 'in progress'].includes(normalizeStatusValue(record.status)) ? '' : 'none';

    weightInput.value = record.weight || '';
    calculatedPriceEl.value = record.price ? `₹${record.price.toFixed(2)}` : '';

    modal.setAttribute('data-current-id', record.id);
    modal.classList.add('open');
}

// Save weight + calculate price
enterBtn.addEventListener('click', async () => {
    const modal = document.getElementById('pickup-detail-modal');
    const pickupId = modal.getAttribute('data-current-id');
    const weightVal = parseFloat(weightInput.value);

    if (!weightVal || weightVal <= 0) {
        weightError.style.display = 'block';
        return;
    }
    weightError.style.display = 'none';

    try {
        enterBtn.disabled = true;
        enterBtn.textContent = 'Saving...';

        const response = await fetch(`/api/collector/pickup-requests/${pickupId}/weight`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ weight: weightVal })
        });

        const payload = await response.json();
        if (!payload.success) throw new Error(payload.error || 'Save failed');

        const amount = parseFloat(payload.data.amount);
        calculatedPriceEl.value = `₹${amount.toFixed(2)}`;

        // Update local window.__PICKUP_DATA
        const pickup = window.__PICKUP_DATA.find(p => p.id == pickupId);
        if (pickup) {
            pickup.weight = weightVal;
            pickup.price = amount;
            pickup.status = 'in progress';
        }

        // Update table row live
        const row = document.querySelector(`tr[data-id='${pickupId}']`);
        if (row) {
            row.querySelector('td:nth-child(6)').innerHTML = `<div class='tag inprogress'>In progress</div>`;
        }

        alert('Weight saved and amount calculated successfully!');

    } catch (err) {
        alert(err.message || 'Unable to save weight.');
    } finally {
        enterBtn.disabled = false;
        enterBtn.textContent = 'Enter';
    }
});

// Mark task as completed
async function startOrUpdateTask() {
    const modal = document.getElementById('pickup-detail-modal');
    const pickupId = modal.getAttribute('data-current-id');

    try {
        const response = await fetch(`/api/collector/pickup-requests/${pickupId}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ status: 'completed' })
        });

        const payload = await response.json();
        if (!payload.success) throw new Error(payload.error || 'Status update failed');

        const pickup = window.__PICKUP_DATA.find(p => p.id == pickupId);
        if (pickup) pickup.status = 'completed';

        // Update modal and table row
        modal.querySelector('.pd-status').textContent = 'completed';
        const row = document.querySelector(`tr[data-id='${pickupId}']`);
        if (row) {
            row.querySelector('td:nth-child(6)').innerHTML = `<div class='tag completed'>Completed</div>`;
        }

        alert('Pickup completed');
        closeDetailModal();

    } catch (err) {
        alert(err.message || 'Failed to update status');
    }
}

// Hook "Start Task" button
document.getElementById('taskActionBtn').addEventListener('click', startOrUpdateTask);
</script>