<?php
$assignedPickups = $assignedPickups ?? [];
$selectedTimeSlot = $selectedTimeSlot ?? 'all';
$selectedStatus = $selectedStatus ?? 'all';
$assignedRequests = array_values($assignedPickups);

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
</script>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header__content">
        <h2 class="page-header__title">
            <i class="fa-solid fa-recycle" style="margin-right:8px;"></i>
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
        modal.querySelector('.pd-status').textContent = record.status;

        const btn = document.getElementById('taskActionBtn');
        if (record.status === 'assigned') {
            btn.textContent = 'Start Task';
            btn.style.display = '';
        } else if (record.status === 'in progress') {
            btn.textContent = 'Mark as Completed';
            btn.style.display = '';
        } else {
            btn.style.display = 'none';
        }

        modal.setAttribute('data-current-id', record.id);
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function updateTaskStatus() {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        const idx = (window.__PICKUP_DATA || []).findIndex(r => r.id == pickupId);
        if (idx === -1) return;

        let current = window.__PICKUP_DATA[idx].status.toLowerCase();
        let next = '';

        if (current === 'assigned') next = 'in progress';
        else if (current === 'in progress') next = 'completed';

        if (next) {
            window.__PICKUP_DATA[idx].status = next;

            const row = document.querySelector(`tr[data-id="${pickupId}"]`);
            if (row) {
                const statusCell = row.querySelectorAll('td')[5];
                if (statusCell) statusCell.innerHTML = getStatusBadge(next);
            }

            modal.querySelector('.pd-status').textContent = next;
            const btn = document.getElementById('taskActionBtn');
            if (next === 'in progress') btn.textContent = 'Mark as Completed';
            else if (next === 'completed') btn.style.display = 'none';
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
        const map = {
            'pending': 'tag pending',
            'assigned': 'tag assigned',
            'in progress': 'tag inprogress',
            'completed': 'tag completed'
        };
        const cls = map[status.toLowerCase()] || 'tag';
        return `<div class="${cls}">${status.charAt(0).toUpperCase() + status.slice(1)}</div>`;
    }
</script>