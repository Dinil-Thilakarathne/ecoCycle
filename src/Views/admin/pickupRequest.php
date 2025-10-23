<?php
$pickupRequests = $pickupRequests ?? [];
$pickupRequests = is_array($pickupRequests) ? $pickupRequests : [];
$collectors = is_array($collectors ?? null) ? $collectors : [];
$timeSlots = $timeSlots ?? [];
$selectedTimeSlot = $selectedTimeSlot ?? 'all';
$filteredRequests = $filteredPickupRequests ?? $pickupRequests;
$filteredRequests = is_array($filteredRequests) ? array_values($filteredRequests) : [];

if (empty($timeSlots)) {
    $timeSlots = ['09:00-11:00', '11:00-13:00', '14:00-16:00', '16:00-18:00'];
}
?>
<?php
// Expose client-side pickup data for modal lookups (use full dataset to allow lookups even when filtered)
?>
<script>
    window.__PICKUP_DATA = <?php echo json_encode($pickupRequests, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<script>
    // Expose collectors list for edit modal
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

        try {
            payload = await response.json();
        } catch (error) {
            payload = null;
        }

        if (!response.ok || (payload && payload.success === false)) {
            const message = payload && payload.message ? payload.message : `Request failed (${response.status})`;
            const detail = payload && payload.errors ? Object.values(payload.errors).join('\n') : '';
            throw new Error(detail ? `${message}\n${detail}` : message);
        }

        return payload || {};
    }

    function renderPickupStatusBadge(status) {
        const value = String(status ?? '').toLowerCase();
        if (value === 'pending') {
            return '<div class="tag pending">Pending</div>';
        }
        if (value === 'assigned') {
            return '<div class="tag assigned">Assigned</div>';
        }
        if (value === 'completed') {
            return '<div class="tag completed">Completed</div>';
        }
        if (value === 'cancelled') {
            return '<div class="tag cancelled">Cancelled</div>';
        }
        const fallback = status ?? 'Unknown';
        return '<div class="tag">' + escapeHtml(fallback) + '</div>';
    }

    function syncPickupCache(pickup) {
        if (!pickup || pickup.id === undefined) {
            return;
        }

        if (!Array.isArray(window.__PICKUP_DATA)) {
            window.__PICKUP_DATA = [];
        }

        const targetId = String(pickup.id).toLowerCase();
        const index = window.__PICKUP_DATA.findIndex(function (item) {
            return String(item.id).toLowerCase() === targetId;
        });

        if (index >= 0) {
            window.__PICKUP_DATA[index] = pickup;
        } else {
            window.__PICKUP_DATA.push(pickup);
        }
    }

    function updatePickupRow(pickup) {
        if (!pickup || pickup.id === undefined) {
            return;
        }

        const row = document.querySelector(pickupRowSelector(pickup.id));
        if (!row) {
            return;
        }

        const statusCell = row.querySelector('[data-field="status"]') || row.querySelectorAll('td')[5];
        if (statusCell) {
            statusCell.innerHTML = renderPickupStatusBadge(pickup.status);
        }

        const collectorCell = row.querySelector('[data-field="collector"]') || row.querySelectorAll('td')[6];
        if (collectorCell) {
            if (pickup.collectorName) {
                collectorCell.textContent = pickup.collectorName;
            } else {
                collectorCell.innerHTML = '<span style="color: var(--neutral-500);">Unassigned</span>';
            }
        }

        const badgeGroup = row.querySelector('.badge-group');
        if (badgeGroup && Array.isArray(pickup.wasteCategories)) {
            badgeGroup.innerHTML = '';
            pickup.wasteCategories.forEach(function (category) {
                const badge = document.createElement('div');
                badge.className = 'tag secondary';
                badge.textContent = category;
                badgeGroup.appendChild(badge);
            });
        }
    }

    function refreshPickupDetailModal(pickup) {
        const modal = document.getElementById('pickup-detail-modal');
        if (!modal || !modal.classList.contains('open')) {
            return;
        }

        const setText = function (selector, value) {
            const element = modal.querySelector(selector);
            if (!element) {
                return;
            }
            const label = element.previousElementSibling;
            if (value === null || value === undefined || String(value).trim() === '') {
                element.textContent = '';
                element.style.display = 'none';
                if (label) {
                    label.style.display = 'none';
                }
            } else {
                element.textContent = String(value);
                element.style.display = '';
                if (label) {
                    label.style.display = '';
                }
            }
        };

        setText('.pd-id', pickup.id ?? '');
        setText('.pd-customer', pickup.customerName ?? '');
        setText('.pd-address', pickup.address ?? '');
        setText('.pd-waste', Array.isArray(pickup.wasteCategories) ? pickup.wasteCategories.join(', ') : '');
        setText('.pd-timeslot', pickup.timeSlot ?? '');
        setText('.pd-status', pickup.status ? String(pickup.status).charAt(0).toUpperCase() + String(pickup.status).slice(1) : '');
        setText('.pd-collector', pickup.collectorName ?? '');
    }
</script>

<!-- Pickup Detail Modal -->
<div id="pickup-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close">&times;</button>
        <h3>Pickup Request Details</h3>
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

            <div><strong>Collector</strong></div>
            <div class="pd-collector"></div>
        </div>
    </div>
</div>

<script>
    // Close modal handler (delegated)
    document.addEventListener('click', function (e) {
        const modal = document.getElementById('pickup-detail-modal');
        if (!modal) return;
        if (e.target.matches('#pickup-detail-modal .close') || e.target.matches('#pickup-detail-modal')) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }
    });
</script>

<!-- Edit Pickup Modal -->
<div id="pickup-edit-modal" class="user-modal" role="dialog" aria-modal="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close">&times;</button>
        <h3>Edit Pickup Request</h3>
        <div class="user-modal__grid">
            <div><strong>Request ID</strong></div>
            <div class="pe-id"></div>

            <div><strong>Customer</strong></div>
            <div class="pe-customer"></div>

            <div><strong>Assign Collector</strong></div>
            <div>
                <div class="form-select" style="width: fit-content;">
                    <select id="pe-collector-select">
                        <option value="">-- Unassigned --</option>
                    </select>
                </div>
            </div>
        </div>
        <div style="margin-top: var(--space-8); display:flex; gap:8px; justify-content:flex-end;">
            <button class="btn" onclick="closeEditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveEdit()">Save</button>
        </div>
    </div>
</div>

<script>
    function openEditModal(el, pickupId) {
        const modal = document.getElementById('pickup-edit-modal');
        if (!modal) return;

        const record = (window.__PICKUP_DATA || []).find(r => (r.id || '').toString().toLowerCase() === (pickupId || '').toString().toLowerCase()) || null;
        const row = el && el.closest ? el.closest('tr') : document.querySelector(`tr[data-id="${pickupId}"]`);

        if (!record && !row) return;

        // Populate fields
        modal.querySelector('.pe-id').textContent = record ? record.id : pickupId;
        // compute customer display text (avoid nested ternary syntax issues)
        var customerText = '';
        if (record) {
            customerText = record.customerName || '';
        } else if (row) {
            var custCell = row.querySelector('td:nth-child(2)');
            customerText = custCell ? custCell.textContent.trim() : '';
        }
        modal.querySelector('.pe-customer').textContent = customerText;

        // Populate collectors select
        const sel = document.getElementById('pe-collector-select');
        sel.innerHTML = '<option value="">-- Unassigned --</option>';
        (window.__COLLECTORS || []).forEach(c => {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.name;
            sel.appendChild(o);
        });

        // Set currently assigned collector if present
        const current = record ? (record.collectorId || '') : '';
        sel.value = current;

        // Store current editing id on modal element
        modal.setAttribute('data-editing-id', pickupId);

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeEditModal() {
        const modal = document.getElementById('pickup-edit-modal');
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    async function saveEdit() {
        const modal = document.getElementById('pickup-edit-modal');
        if (!modal) return;

        const pickupId = modal.getAttribute('data-editing-id');
        if (!pickupId) return;

        const select = document.getElementById('pe-collector-select');
        const collectorIdValue = select ? select.value : '';
        const payload = {};

        if (collectorIdValue === '') {
            payload.collectorId = null;
        } else {
            payload.collectorId = collectorIdValue;
        }

        const currentRecord = (window.__PICKUP_DATA || []).find(function (item) {
            return String(item.id).toLowerCase() === String(pickupId).toLowerCase();
        });

        if (currentRecord && String(currentRecord.status || '').toLowerCase() === 'completed') {
            payload.status = 'completed';
        }

        const saveButton = modal.querySelector('.btn.btn-primary');
        const originalLabel = saveButton ? saveButton.textContent : '';

        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';
        }

        try {
            const response = await pickupApiRequest(`${PICKUP_API_BASE}/${encodeURIComponent(pickupId)}`, {
                method: 'PUT',
                body: payload,
            });

            const updated = response.pickup;
            if (!updated) {
                throw new Error('Server returned an empty response.');
            }

            syncPickupCache(updated);
            updatePickupRow(updated);
            refreshPickupDetailModal(updated);

            closeEditModal();

            if (typeof showToast === 'function') {
                showToast('Pickup updated successfully.', 'success');
            }
        } catch (error) {
            console.error('Failed to update pickup request', error);
            if (typeof showToast === 'function') {
                showToast(error.message || 'Failed to update pickup request.', 'error');
            } else {
                alert(error.message || 'Failed to update pickup request.');
            }
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.textContent = originalLabel;
            }
        }
    }

    // Close edit modal when clicking backdrop or close button
    document.addEventListener('click', function (e) {
        const modal = document.getElementById('pickup-edit-modal');
        if (!modal) return;
        if (e.target.matches('#pickup-edit-modal .close') || e.target.matches('#pickup-edit-modal')) {
            closeEditModal();
        }
    });
</script>
<?php

function getStatusBadge($status)
{
    switch ($status) {
        case 'pending':
            return '<div class="tag pending">Pending</div>';
        case 'assigned':
            return '<div class="tag assigned">Assigned</div>';
        case 'completed':
            return '<div class="tag completed">Completed</div>';
        default:
            return '<div class="tag">' . htmlspecialchars($status) . '</div>';
    }
}
?>

<div>
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Today's Pickup Requests</h2>
            <p class="page-header__description">Manage and assign pickup requests</p>
        </div>
        <div class="form-select">
            <select id="timeSlotFilter" onchange="filterByTimeSlot()">
                <option value="all" <?= $selectedTimeSlot === 'all' ? 'selected' : '' ?>>All Time Slots</option>
                <?php foreach ($timeSlots as $slot): ?>
                    <option value="<?= htmlspecialchars($slot) ?>" <?= $selectedTimeSlot === $slot ? 'selected' : '' ?>>
                        <?= htmlspecialchars($slot) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Pickup Requests Card -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-box" style="margin-right: 8px;"></i>
                Pickup Requests
            </h3>
            <p class="activity-card__description">
                <?= count($filteredRequests) ?> requests
                <?= $selectedTimeSlot === 'all' ? 'scheduled for today' : 'for ' . htmlspecialchars($selectedTimeSlot) ?>
            </p>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Customer</th>
                            <th>Address</th>
                            <th>Waste Categories</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th>Collector</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredRequests as $request): ?>
                            <tr data-id="<?= htmlspecialchars($request['id'] ?? '') ?>">
                                <td class="font-medium"><?= htmlspecialchars($request['id'] ?? '') ?></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-user"></i>
                                        <?= htmlspecialchars($request['customerName'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-map-marker-alt"></i>
                                        <span
                                            class="cell-truncate"><?= htmlspecialchars($request['address'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td data-field="wasteCategories">
                                    <div class="badge-group">
                                        <?php foreach ((array) ($request['wasteCategories'] ?? []) as $category): ?>
                                            <div class="tag secondary"><?= htmlspecialchars($category) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-clock"></i>
                                        <?= htmlspecialchars($request['timeSlot'] ?? '') ?>
                                    </div>
                                </td>
                                <td data-field="status"><?= getStatusBadge($request['status'] ?? 'pending') ?></td>
                                <td data-field="collector">
                                    <?php if (!empty($request['collectorName'])): ?>
                                        <?= htmlspecialchars($request['collectorName']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--neutral-500);">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">

                                        <!-- Icon-only action buttons: view and edit -->
                                        <button class="icon-button"
                                            onclick="viewDetails(this, '<?= htmlspecialchars($request['id'] ?? '') ?>')"
                                            title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="icon-button"
                                            onclick="openEditModal(this, '<?= htmlspecialchars($request['id'] ?? '') ?>')"
                                            title="Edit / Assign Collector">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($filteredRequests)): ?>
                            <tr>
                                <td colspan="8"
                                    style="text-align: center; padding: var(--space-16); color: var(--neutral-500);">
                                    No pickup requests found for the selected time slot.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function filterByTimeSlot() {
        const select = document.getElementById('timeSlotFilter');
        const timeSlot = select.value;
        const url = new URL(window.location);

        if (timeSlot === 'all') {
            url.searchParams.delete('time_slot');
        } else {
            url.searchParams.set('time_slot', timeSlot);
        }

        window.location.href = url.toString();
    }

    function assignCollector(pickupId, collectorId) {
        if (!collectorId) return;

        // In a real application, you would make an AJAX request to your backend
        console.log(`Assigning collector ${collectorId} to pickup ${pickupId}`);

        // For demo purposes, show an alert and reload the page
        alert(`Collector assigned to pickup ${pickupId}. In a real application, this would be saved to the database.`);

        // You could make an AJAX request here:
        /*
        fetch('/api/assign-collector', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pickupId: pickupId,
                collectorId: collectorId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to assign collector');
            }
        });
        */
    }

    function viewDetails() {
        // signature: viewDetails(el, pickupId) or legacy viewDetails(pickupId)
        let el, pickupId;
        if (arguments.length === 1) {
            pickupId = arguments[0];
            el = document.querySelector(`tr[data-id="${pickupId}"]`);
        } else {
            el = arguments[0];
            pickupId = arguments[1];
        }

        // Lookup in-memory first
        let record = null;
        try {
            if (window.__PICKUP_DATA && Array.isArray(window.__PICKUP_DATA)) {
                record = window.__PICKUP_DATA.find(r => (r.id || '').toString().toLowerCase() === (pickupId || '').toString().toLowerCase()) || null;
            }
        } catch (e) {
            console.warn('pickup lookup failed', e);
            record = null;
        }

        // Fallback to reading table cells
        if (!record && el) {
            const cells = el.querySelectorAll('td');
            record = {
                id: pickupId,
                customerName: (cells[1] && cells[1].textContent.trim()) || '',
                address: (cells[2] && cells[2].textContent.trim()) || '',
                wasteCategories: Array.from(el.querySelectorAll('.badge-group .tag')).map(t => t.textContent.trim()),
                timeSlot: (cells[4] && cells[4].textContent.trim()) || '',
                status: (cells[5] && cells[5].textContent.trim()) || '',
                collectorName: (cells[6] && cells[6].textContent.trim()) || ''
            };
        }

        const modal = document.getElementById('pickup-detail-modal');
        if (!modal) return;

        // Do not open if no record
        if (!record) return;

        const setText = (sel, txt) => {
            const elm = modal.querySelector(sel);
            if (!elm) return;
            if (!txt || String(txt).trim() === '') {
                const lbl = elm.previousElementSibling;
                if (lbl) lbl.style.display = 'none';
                elm.style.display = 'none';
            } else {
                const lbl = elm.previousElementSibling;
                if (lbl) lbl.style.display = '';
                elm.style.display = '';
                elm.textContent = String(txt).trim();
            }
        };

        setText('.pd-id', record.id || '');
        setText('.pd-customer', record.customerName || '');
        setText('.pd-address', record.address || '');
        setText('.pd-waste', (record.wasteCategories && record.wasteCategories.join(', ')) || '');
        setText('.pd-timeslot', record.timeSlot || '');
        setText('.pd-status', record.status || '');
        setText('.pd-collector', record.collectorName || '');

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }
</script>