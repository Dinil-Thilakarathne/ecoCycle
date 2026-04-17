<?php
// Variables from controller: $todayRequests, $upcomingRequests, $inProgressRequests, $completedRequests, $cancelledRequests, $collectors, $timeSlots, $selectedTimeSlot
$todayRequests = $todayRequests ?? [];
$upcomingRequests = $upcomingRequests ?? [];
$inProgressRequests = $inProgressRequests ?? [];
$completedRequests = $completedRequests ?? [];
$cancelledRequests = $cancelledRequests ?? [];
$collectors = $collectors ?? [];
$timeSlots = $timeSlots ?? ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00'];

// Combine all for client-side data lookup in modals
$allPickupData = array_merge($todayRequests, $upcomingRequests, $inProgressRequests, $completedRequests, $cancelledRequests);
?>
<script>
    window.__PICKUP_DATA = <?php echo json_encode($allPickupData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    window.__COLLECTORS = <?php echo json_encode($collectors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>

<script>
    const PICKUP_API_BASE = '/api/pickup-requests';

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            switch (char) {
                case '&': return '&amp;';
                case '<': return '&lt;';
                case '>': return '&gt;';
                case '"': return '&quot;';
                case '\'': return '&#039;';
                default: return char;
            }
        });
    }

    function pickupRowSelector(id) {
        const raw = String(id ?? '');
        const escaped = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(raw) : raw.replace(/(?=["'\\])/g, '\\');
        return `tr[data-id="${escaped}"]`;
    }

    async function pickupApiRequest(url, options = {}) {
        const opts = Object.assign({ headers: {} }, options);
        opts.headers = Object.assign({ Accept: 'application/json' }, opts.headers || {});

        if (opts.body && !(opts.body instanceof FormData) && typeof opts.body === 'object') {
            opts.headers['Content-Type'] = opts.headers['Content-Type'] || 'application/json';
            opts.body = JSON.stringify(opts.body);
        }

        const response = await fetch(url, opts);
        let payload = null;

        try { payload = await response.json(); } catch (error) { payload = null; }

        if (!response.ok || (payload && payload.success === false)) {
            const message = payload && payload.message ? payload.message : `Request failed (${response.status})`;
            const detail = payload && payload.errors ? Object.values(payload.errors).join('\n') : '';
            throw new Error(detail ? `${message}\n${detail}` : message);
        }

        return payload || {};
    }

    function renderPickupStatusBadge(status) {
        const value = String(status ?? '').toLowerCase();
        if (value === 'pending') return '<div class="tag pending">Pending</div>';
        if (value === 'assigned') return '<div class="tag assigned">Assigned</div>';
        if (value === 'in_progress' || value === 'in progress') return '<div class="tag online">In Progress</div>';
        if (value === 'completed') return '<div class="tag completed">Completed</div>';
        if (value === 'cancelled') return '<div class="tag cancelled">Cancelled</div>';
        return '<div class="tag">' + escapeHtml(status ?? 'Unknown') + '</div>';
    }

    function syncPickupCache(pickup) {
        if (!pickup || pickup.id === undefined) return;
        const targetId = String(pickup.id).toLowerCase();
        const index = window.__PICKUP_DATA.findIndex(item => String(item.id).toLowerCase() === targetId);
        if (index >= 0) window.__PICKUP_DATA[index] = pickup;
        else window.__PICKUP_DATA.push(pickup);
    }

    function updatePickupRow(pickup) {
        if (!pickup || pickup.id === undefined) return;
        const rows = document.querySelectorAll(pickupRowSelector(pickup.id));
        rows.forEach(row => {
            const statusCell = row.querySelector('[data-field="status"]');
            if (statusCell) statusCell.innerHTML = renderPickupStatusBadge(pickup.status);

            const vehicleCell = row.querySelector('[data-field="vehicle"]');
            if (vehicleCell) vehicleCell.textContent = pickup.vehiclePlate || '-';

            const collectorCell = row.querySelector('[data-field="collector"]');
            if (collectorCell) {
                if (pickup.collectorName) collectorCell.textContent = pickup.collectorName;
                else collectorCell.innerHTML = '<span style="color: var(--neutral-500);">Unassigned</span>';
            }
        });
    }

    function refreshPickupDetailModal(pickup) {
        const modal = document.getElementById('pickup-detail-modal');
        if (!modal) return;
        const setText = (selector, value) => {
            const element = modal.querySelector(selector);
            if (!element) return;
            element.textContent = String(value ?? '');
        };

        setText('.pd-id', pickup.id);
        setText('.pd-customer', pickup.customerName);
        setText('.pd-address', pickup.address);
        setText('.pd-waste', Array.isArray(pickup.wasteCategories) ? pickup.wasteCategories.join(', ') : '');
        setText('.pd-timeslot', pickup.timeSlot);
        setText('.pd-date', pickup.scheduledAt ? new Date(pickup.scheduledAt).toLocaleDateString() : 'Unscheduled');
        setText('.pd-status', pickup.status ? String(pickup.status).toUpperCase() : '');
        setText('.pd-vehicle', pickup.vehiclePlate);
        setText('.pd-weight', pickup.weight ? parseFloat(pickup.weight).toFixed(2) + ' kg' : '-');
        setText('.pd-price', pickup.price ? 'Rs. ' + parseFloat(pickup.price).toFixed(2) : '-');
        setText('.pd-collector', pickup.collectorName);
    }
</script>

<?php
// Helper for table rendering
if (!function_exists('renderPickupTable')) {
    function renderPickupTable($requests, $isToday = false) {
    ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Waste Categories</th>
                    <?php if (!$isToday): ?><th>Scheduled Date</th><?php endif; ?>
                    <th>Time Slot</th>
                    <th>Status</th>
                    <th>Vehicle</th>
                    <th>Collector</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr data-id="<?= htmlspecialchars($request['id']) ?>">
                        <td>
                            <div class="cell-with-icon">
                                <i class="fa-solid fa-user"></i>
                                <?= htmlspecialchars($request['customerName']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="cell-truncate" title="<?= htmlspecialchars($request['address']) ?>"><?= htmlspecialchars($request['address']) ?></span>
                        </td>
                        <td>
                            <div class="badge-group">
                                <?php foreach ((array)($request['wasteCategories'] ?? []) as $category): ?>
                                    <div class="tag secondary"><?= htmlspecialchars($category) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <?php if (!$isToday): ?>
                            <td><?= $request['scheduledAt'] ? date('M j, Y', strtotime($request['scheduledAt'])) : '<span class="text-neutral-500">Unscheduled</span>' ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($request['timeSlot']) ?></td>
                        <td data-field="status"><?= getStatusBadge($request['statusRaw']) ?></td>
                        <td data-field="vehicle"><?= htmlspecialchars($request['vehiclePlate'] ?: '-') ?></td>
                        <td data-field="collector">
                            <?= !empty($request['collectorName']) ? htmlspecialchars($request['collectorName']) : '<span style="color: var(--neutral-500);">Unassigned</span>' ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:8px;">
                                <button class="icon-button" onclick="viewDetails(this, '<?= $request['id'] ?>')" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <?php $canEdit = !in_array($request['statusRaw'], ['completed', 'cancelled']); ?>
                                <button class="icon-button" onclick="openEditModal(this, '<?= $request['id'] ?>')" 
                                    title="<?= $canEdit ? 'Edit / Assign' : 'Cannot Edit Finished Request' ?>"
                                    <?= !$canEdit ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="<?= $isToday ? 8 : 9 ?>" style="text-align: center; padding: 2rem; color: var(--neutral-500);">
                            No pickup requests found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php }
}
?>

<?php
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        $status = strtolower($status);
        switch ($status) {
            case 'pending': return '<div class="tag pending">Pending</div>';
            case 'assigned': return '<div class="tag assigned">Assigned</div>';
            case 'in_progress':
            case 'in progress': return '<div class="tag online">In Progress</div>';
            case 'completed': return '<div class="tag completed">Completed</div>';
            case 'cancelled': return '<div class="tag cancelled">Cancelled</div>';
            default: return '<div class="tag">' . htmlspecialchars($status) . '</div>';
        }
    }
}
?>

<div>
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Pickup Management</h2>
            <p class="page-header__description">Manage schedules, assignments, and service history</p>
        </div>
        <div class="form-select">
            <select id="timeSlotFilter" onchange="filterByTimeSlot()">
                <option value="all" <?= ($selectedTimeSlot ?? 'all') === 'all' ? 'selected' : '' ?>>All Time Slots</option>
                <?php foreach ($timeSlots as $slot): ?>
                    <option value="<?= htmlspecialchars($slot) ?>" <?= ($selectedTimeSlot ?? '') === $slot ? 'selected' : '' ?>><?= htmlspecialchars($slot) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- 1. Today's Section -->
    <div class="activity-card" style="margin-bottom: 2rem;">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-calendar-day" style="margin-right: 8px; color: var(--primary-600);"></i>
                Today's Schedule
            </h3>
            <p class="activity-card__description"><?= count($todayRequests) ?> pickups for today</p>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto;">
                <?php renderPickupTable($todayRequests, true); ?>
            </div>
        </div>
    </div>

    <!-- 2. Tabs Section -->
    <div class="tabs">
        <div class="tabs-list">
            <button class="tabs-trigger active" onclick="showTab('upcoming')" id="upcoming-tab">
                <i class="fa-solid fa-clock"></i> Upcoming (<?= count($upcomingRequests) ?>)
            </button>
            <button class="tabs-trigger" onclick="showTab('inprogress')" id="inprogress-tab">
                <i class="fa-solid fa-spinner"></i> In Progress (<?= count($inProgressRequests) ?>)
            </button>
            <button class="tabs-trigger" onclick="showTab('completed')" id="completed-tab">
                <i class="fa-solid fa-check-circle"></i> Completed (<?= count($completedRequests) ?>)
            </button>
            <button class="tabs-trigger" onclick="showTab('cancelled')" id="cancelled-tab">
                <i class="fa-solid fa-times-circle"></i> Cancelled (<?= count($cancelledRequests) ?>)
            </button>
        </div>

        <div class="tabs-content active" id="upcoming-content">
            <div class="activity-card">
                <div class="activity-card__content" style="overflow-x: auto;">
                    <?php renderPickupTable($upcomingRequests); ?>
                </div>
            </div>
        </div>

        <div class="tabs-content" id="inprogress-content">
            <div class="activity-card">
                <div class="activity-card__content" style="overflow-x: auto;">
                    <?php renderPickupTable($inProgressRequests); ?>
                </div>
            </div>
        </div>

        <div class="tabs-content" id="completed-content">
            <div class="activity-card">
                <div class="activity-card__content" style="overflow-x: auto;">
                    <?php renderPickupTable($completedRequests); ?>
                </div>
            </div>
        </div>

        <div class="tabs-content" id="cancelled-content">
            <div class="activity-card">
                <div class="activity-card__content" style="overflow-x: auto;">
                    <?php renderPickupTable($cancelledRequests); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showTab(tabName, updateUrl = true) {
        document.querySelectorAll('.tabs-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tabs-trigger').forEach(t => t.classList.remove('active'));
        
        const content = document.getElementById(tabName + '-content');
        const trigger = document.getElementById(tabName + '-tab');
        if (content) content.classList.add('active');
        if (trigger) trigger.classList.add('active');

        if (updateUrl && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState(null, '', url.toString());
        }
    }

    function viewDetails(el, pickupId) {
        const record = window.__PICKUP_DATA.find(r => r.id == pickupId);
        if (!record) return;

        const content = document.createElement('div');
        content.className = 'user-modal__grid';
        content.style.display = 'grid';
        content.style.gridTemplateColumns = '1fr 1fr';
        content.style.gap = '12px';

        const fields = [
            { label: 'Request ID', value: record.id },
            { label: 'Customer', value: record.customerName },
            { label: 'Address', value: record.address },
            { label: 'Waste', value: Array.isArray(record.wasteCategories) ? record.wasteCategories.join(', ') : '-' },
            { label: 'Date', value: record.scheduledAt ? new Date(record.scheduledAt).toLocaleDateString() : 'Unscheduled' },
            { label: 'Time Slot', value: record.timeSlot },
            { label: 'Status', value: String(record.status || '').toUpperCase() },
            { label: 'Vehicle', value: record.vehiclePlate || '-' },
            { label: 'Weight', value: record.weight ? parseFloat(record.weight).toFixed(2) + ' kg' : '-' },
            { label: 'Price', value: record.price ? 'Rs. ' + parseFloat(record.price).toFixed(2) : '-' },
            { label: 'Collector', value: record.collectorName || '-' }
        ];

        fields.forEach(f => {
            const lbl = document.createElement('strong');
            lbl.textContent = f.label;
            const val = document.createElement('div');
            val.textContent = f.value;
            content.appendChild(lbl);
            content.appendChild(val);
        });

        window.Modal.open({
            title: 'Pickup Request Details',
            content: content,
            actions: [{ label: 'Close', variant: 'plain', dismiss: true }]
        });
    }

    function openEditModal(el, pickupId) {
        const record = window.__PICKUP_DATA.find(r => r.id == pickupId);
        if (!record) return;

        const container = document.createElement('div');
        container.innerHTML = `
            <div id="pe-error-message" style="display: none; background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 16px;"></div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 4px;">Assign Collector</label>
                <select id="pe-collector-select" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                    <option value="">-- Unassigned --</option>
                    ${(window.__COLLECTORS || []).map(c => `<option value="${c.id}" ${c.id == record.collectorId ? 'selected' : ''}>${escapeHtml(c.name)}</option>`).join('')}
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 4px;">Assign Vehicle</label>
                <select id="pe-vehicle-select" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                    <option value="">Loading...</option>
                </select>
            </div>
        `;

        const vSel = container.querySelector('#pe-vehicle-select');
        fetch('/api/vehicles').then(r => r.json()).then(data => {
            vSel.innerHTML = '<option value="">-- Unassigned --</option>';
            (data.vehicles || []).forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = `${v.plateNumber} (${v.type}) - ${v.status}`;
                if (v.status !== 'available' && v.id != record.vehicleId) opt.disabled = true;
                if (v.id == record.vehicleId) opt.selected = true;
                vSel.appendChild(opt);
            });
        }).catch(err => {
            vSel.innerHTML = '<option value="">Error loading vehicles</option>';
        });

        window.Modal.open({
            title: 'Edit Assignment',
            size: 'sm',
            content: container,
            actions: [
                { label: 'Cancel', variant: 'plain', dismiss: true },
                {
                    label: 'Save Changes',
                    variant: 'primary',
                    dismiss: false,
                    loadingLabel: 'Saving...',
                    onClick: async ({ body, close, setLoading }) => {
                        const collectorId = body.querySelector('#pe-collector-select').value;
                        const vehicleId = body.querySelector('#pe-vehicle-select').value;
                        const errorEl = body.querySelector('#pe-error-message');
                        
                        setLoading(true);
                        errorEl.style.display = 'none';

                        try {
                            const res = await pickupApiRequest(`${PICKUP_API_BASE}/${pickupId}`, {
                                method: 'PUT',
                                body: { collectorId: collectorId || null, vehicleId: vehicleId || null }
                            });
                            syncPickupCache(res.pickup);
                            updatePickupRow(res.pickup);
                            close();
                            if (window.toast) window.toast('Assignment updated successfully', 'success');
                        } catch (e) {
                            errorEl.textContent = e.message;
                            errorEl.style.display = 'block';
                            setLoading(false);
                        }
                    }
                }
            ]
        });
    }

    function filterByTimeSlot() {
        const slot = document.getElementById('timeSlotFilter').value;
        const url = new URL(window.location.href);
        if (slot === 'all') url.searchParams.delete('time_slot');
        else url.searchParams.set('time_slot', slot);
        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const params = new URL(window.location.href).searchParams;
        if (params.has('tab')) showTab(params.get('tab'), false);
    });
</script>