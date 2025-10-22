<?php

use function htmlspecialchars as e;

$csrfToken = csrf_token();
$timeSlots = $timeSlots ?? [];
$wasteCategories = $wasteCategories ?? [];
$pickupRequests = array_values($pickupRequests ?? []);
// Remove any cancelled requests from the initial server-side list so they don't show anywhere
$pickupRequests = array_values(array_filter($pickupRequests, static function ($r) {
    $status = strtolower((string) ($r['status'] ?? ''));
    return $status !== 'cancelled';
}));
$filter = $_GET['filter'] ?? 'all';
$profileData = is_array($userProfile ?? null) ? $userProfile : [];
$userData = is_array($user ?? null) ? $user : [];
$defaultAddress = trim((string) ($profileData['address'] ?? ($userData['address'] ?? '')));

$normalizedFilter = is_string($filter) ? strtolower($filter) : 'all';
$filteredRequests = $pickupRequests;
if ($normalizedFilter !== 'all') {
    $filteredRequests = array_values(array_filter($pickupRequests, static function ($request) use ($normalizedFilter) {
        $status = strtolower((string) ($request['status'] ?? ''));
        return $status === $normalizedFilter;
    }));
}

$pendingCount = 0;
$scheduledCount = 0;
$completedCount = 0;
foreach ($pickupRequests as $request) {
    $status = strtolower((string) ($request['status'] ?? ''));
    if ($status === 'pending') {
        $pendingCount++;
    }
    if (in_array($status, ['assigned', 'confirmed'], true)) {
        $scheduledCount++;
    }
    if ($status === 'completed') {
        $completedCount++;
    }
}
$totalCount = count($pickupRequests);

if (!function_exists('customer_pickup_status_class')) {
    function customer_pickup_status_class(string $status): string
    {
        $normalized = strtolower($status);
        switch ($normalized) {
            case 'pending':
                return 'pending';
            case 'assigned':
            case 'confirmed':
                return 'assigned';
            case 'completed':
                return 'completed';
            case 'cancelled':
                return 'warning';
            default:
                return 'secondary';
        }
    }
}

if (!function_exists('customer_pickup_format_datetime')) {
    function customer_pickup_format_datetime(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        return date('M d, Y', $timestamp);
    }
}
?>

<style>
    .checkbox-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .checkbox-grid label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
    }
</style>

<div class="dashboard-page">
    <div class="page-header" style="margin-bottom:2rem;">
        <div class="header-content">
            <h1><strong>Manage pickup requests</strong></h1>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showNewRequestForm()">+ New Request</button>
        </div>
    </div>

    <?php $pickupStats = [
        [
            'title' => 'Total Requests',
            'value' => $totalCount,
            'icon' => 'fa-solid fa-truck',
            'subtitle' => 'All time',
        ],
        [
            'title' => 'Pending',
            'value' => $pendingCount,
            'icon' => 'fa-solid fa-hourglass-half',
            'subtitle' => 'Awaiting confirmation',
        ],
        [
            'title' => 'Scheduled',
            'value' => $scheduledCount,
            'icon' => 'fa-solid fa-calendar-check',
            'subtitle' => 'Assigned/Confirmed',
        ],
        [
            'title' => 'Completed',
            'value' => $completedCount,
            'icon' => 'fa-solid fa-clipboard-check',
            'subtitle' => 'Finished',
        ],
    ]; ?>
    <div class="stats-grid" style="margin-bottom:2.5rem;">
        <?php foreach ($pickupStats as $stat): ?>
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">
                        <?= e($stat['title']) ?>
                    </h3>
                    <div class="feature-card__icon">
                        <i class="<?= e($stat['icon']) ?>"></i>
                    </div>
                </div>
                <p class="feature-card__body">
                    <?= e((string) $stat['value']) ?>
                </p>
                <div class="feature-card__footer">
                    <span class="tag success"><?= e($stat['subtitle']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="action-buttons" style="margin-bottom:2rem;">
        <?php
        $filters = [
            'all' => 'All Requests',
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            // 'cancelled' intentionally omitted: cancelled requests are not shown anywhere
        ];
        foreach ($filters as $key => $label):
            $isActive = $normalizedFilter === $key ? 'btn-primary' : 'btn-outline';
            ?>
            <button class="btn <?= $isActive ?>" data-filter="<?= e($key) ?>">
                <?= e($label) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="table-container" style="overflow-x:auto;">
        <table class="data-table" style="min-width:900px;">
            <thead>
                <tr>
                    <th style="width:60px;">PID</th>
                    <th>Address</th>
                    <th>Time Slot</th>
                    <th>Waste Categories</th>
                    <th>Created</th>
                    <th>Scheduled</th>
                    <th>Collector</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="pickup-requests-body">
                <?php if (empty($filteredRequests)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <div class="empty-content">
                                <div class="empty-icon">📦</div>
                                <h3>No pickup requests found</h3>
                                <p>No pickup requests match your current filter.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredRequests as $request):
                        $status = (string) ($request['status'] ?? 'pending');
                        $collector = $request['collectorName'] ?? '';
                        $categoryList = $request['wasteCategories'] ?? [];
                        $normalizedStatus = strtolower($status);
                        $canEdit = in_array($normalizedStatus, ['pending', 'assigned'], true);
                        $canCancel = in_array($normalizedStatus, ['pending', 'assigned', 'confirmed'], true);
                        ?>
                        <tr data-request-id="<?= e((string) $request['id']) ?>">
                            <td>#<?= e((string) $request['id']) ?></td>
                            <td><?= e((string) ($request['address'] ?? '')) ?></td>
                            <td><?= e((string) ($request['timeSlot'] ?? '')) ?></td>
                            <td>
                                <?php
                                $categoryNames = array_values(array_filter(array_map('strval', is_array($categoryList) ? $categoryList : [])));
                                ?>
                                <?php if (!empty($categoryNames)): ?>
                                    <div class="badge-group">
                                        <?php foreach ($categoryNames as $categoryName): ?>
                                            <span class="tag"><?= e($categoryName) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(customer_pickup_format_datetime($request['createdAt'] ?? null)) ?></td>
                            <td><?= e(customer_pickup_format_datetime($request['scheduledAt'] ?? null)) ?></td>
                            <td><?= e($collector !== '' ? $collector : '-') ?></td>
                            <td>
                                <span class="tag <?= e(customer_pickup_status_class($status)) ?>">
                                    <?= e(ucfirst($status)) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($canEdit || $canCancel): ?>
                                    <?php if ($canEdit): ?>
                                        <button class="action-btn view" data-action="edit"
                                            data-id="<?= e((string) $request['id']) ?>">Edit</button>
                                    <?php endif; ?>
                                    <?php if ($canCancel): ?>
                                        <button class="action-btn delete" data-action="cancel"
                                            data-id="<?= e((string) $request['id']) ?>">Cancel</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#64748b;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="newRequestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>New Pickup Request</h2>
            <span class="close" onclick="hideNewRequestForm()">&times;</span>
        </div>
        <form id="newRequestForm" class="request-form">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <div class="form-group">
                <label for="new_address">Pickup Address</label>
                <textarea name="address" id="new_address" rows="3" placeholder="Where should we collect from?"
                    required><?= e($defaultAddress) ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="new_time_slot">Preferred Time Slot</label>
                    <select name="timeSlot" id="new_time_slot" required>
                        <option value="">Select slot</option>
                        <?php foreach ($timeSlots as $slot): ?>
                            <option value="<?= e((string) $slot) ?>"><?= e((string) $slot) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_date">Preferred Date</label>
                    <input type="date" name="scheduledAt" id="new_date" required>
                </div>
            </div>
            <div class="form-group">
                <label>Waste Categories</label>
                <div class="checkbox-grid" id="new_waste_categories">
                    <?php foreach ($wasteCategories as $category): ?>
                        <label>
                            <input type="checkbox" name="wasteCategories[]"
                                value="<?= e((string) ($category['id'] ?? '')) ?>">
                            <?= e((string) ($category['name'] ?? '')) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-actions" style="display:flex;justify-content:flex-end;gap:1rem;">
                <button type="button" onclick="hideNewRequestForm()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<div id="editRequestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Request</h2>
            <span class="close" onclick="hideEditRequestForm()">&times;</span>
        </div>
        <form id="editRequestForm" class="request-form">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="id" id="edit_request_id">
            <div class="form-group">
                <label for="edit_address">Pickup Address</label>
                <textarea name="address" id="edit_address" rows="3" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_time_slot">Preferred Time Slot</label>
                    <select name="timeSlot" id="edit_time_slot" required>
                        <option value="">Select slot</option>
                        <?php foreach ($timeSlots as $slot): ?>
                            <option value="<?= e((string) $slot) ?>"><?= e((string) $slot) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_date">Preferred Date</label>
                    <input type="date" name="scheduledAt" id="edit_date" required>
                </div>
            </div>
            <div class="form-group">
                <label>Waste Categories</label>
                <div class="checkbox-grid" id="edit_waste_categories">
                    <?php foreach ($wasteCategories as $category): ?>
                        <label>
                            <input type="checkbox" name="wasteCategories[]"
                                value="<?= e((string) ($category['id'] ?? '')) ?>">
                            <?= e((string) ($category['name'] ?? '')) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-actions" style="display:flex;justify-content:flex-end;gap:1rem;">
                <button type="button" onclick="hideEditRequestForm()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Request</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const state = {
            requests: <?= json_encode(array_values(array_filter($pickupRequests, static function ($r) {
                return strtolower((string) ($r['status'] ?? '')) !== 'cancelled';
            })), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            filter: '<?= e($normalizedFilter) ?>'
        };
        const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
        const timeSlots = <?= json_encode($timeSlots, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const wasteCategories = <?= json_encode($wasteCategories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const defaultAddress = <?= json_encode($defaultAddress, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        const tableBody = document.getElementById('pickup-requests-body');
        const newModal = document.getElementById('newRequestModal');
        const editModal = document.getElementById('editRequestModal');
        const filterButtons = document.querySelectorAll('[data-filter]');

        function showAlert(message, type = 'success') {
            const toastFn = window.__createToast;
            if (typeof toastFn === 'function') {
                toastFn(message, type);
            } else if (type === 'error') {
                window.alert(message);
            } else {
                console.log(message);
            }
        }

        // Simple confirm modal that returns a Promise<boolean>
        function createConfirmModal({ title = 'Confirm', message = '', confirmLabel = 'OK', cancelLabel = 'Cancel' } = {}) {
            return new Promise((resolve) => {
                const backdrop = document.createElement('div');
                backdrop.className = 'simple-modal-backdrop';
                backdrop.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:2000;padding:1rem;';

                const dialog = document.createElement('div');
                dialog.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(15,23,42,0.16);width:480px;max-width:100%;padding:1.25rem;';

                const header = document.createElement('div');
                header.style.cssText = 'display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;';
                const titleEl = document.createElement('h3');
                titleEl.textContent = title;
                titleEl.style.cssText = 'margin:0;font-size:1.05rem;font-weight:700;color:#111827;';

                const body = document.createElement('div');
                body.innerHTML = `<p style="margin:0 0 1rem 0;color:#374151;">${escapeHtml(message)}</p>`;

                const footer = document.createElement('div');
                footer.style.cssText = 'display:flex;justify-content:flex-end;gap:0.5rem;';

                const btnCancel = document.createElement('button');
                btnCancel.type = 'button';
                btnCancel.textContent = cancelLabel;
                btnCancel.style.cssText = 'padding:0.5rem 0.85rem;border-radius:8px;border:none;background:#6b7280;color:#fff;cursor:pointer;';

                const btnConfirm = document.createElement('button');
                btnConfirm.type = 'button';
                btnConfirm.textContent = confirmLabel;
                btnConfirm.style.cssText = 'padding:0.5rem 0.85rem;border-radius:8px;border:none;background:#dc2626;color:#fff;cursor:pointer;';

                btnCancel.addEventListener('click', () => {
                    backdrop.remove();
                    resolve(false);
                });

                btnConfirm.addEventListener('click', () => {
                    backdrop.remove();
                    resolve(true);
                });

                footer.appendChild(btnCancel);
                footer.appendChild(btnConfirm);

                header.appendChild(titleEl);
                dialog.appendChild(header);
                dialog.appendChild(body);
                dialog.appendChild(footer);
                backdrop.appendChild(dialog);
                document.body.appendChild(backdrop);
            });
        }

        function statusClass(status) {
            const map = {
                pending: 'pending',
                assigned: 'assigned',
                confirmed: 'assigned',
                completed: 'completed',
                cancelled: 'warning'
            };
            return map[status.toLowerCase()] || 'secondary';
        }

        function formatDate(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                const parsed = new Date(value.replace(' ', 'T'));
                if (Number.isNaN(parsed.getTime())) {
                    return '-';
                }
                return parsed.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
            }
            return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
        }

        function renderStats() {
            const cards = document.querySelectorAll('.feature-card');
            if (cards.length < 4) return;
            const totals = {
                total: state.requests.length,
                pending: 0,
                scheduled: 0,
                completed: 0
            };
            state.requests.forEach((request) => {
                const status = (request.status || '').toLowerCase();
                if (status === 'pending') totals.pending += 1;
                if (status === 'assigned' || status === 'confirmed') totals.scheduled += 1;
                if (status === 'completed') totals.completed += 1;
            });

            const values = [totals.total, totals.pending, totals.scheduled, totals.completed];
            cards.forEach((card, index) => {
                const body = card.querySelector('.feature-card__body');
                if (body) {
                    body.textContent = values[index].toString();
                }
            });
        }

        function renderWasteCategories(rawList) {
            const list = Array.isArray(rawList) ? rawList : [];
            const normalized = list
                .map((item) => {
                    if (typeof item === 'string') {
                        return item.trim();
                    }
                    if (item == null) {
                        return '';
                    }
                    return String(item).trim();
                })
                .filter((item) => item !== '');

            if (!normalized.length) {
                return '<span>-</span>';
            }

            const tags = normalized
                .map((name) => `<span class="tag">${escapeHtml(name)}</span>`)
                .join('');

            return `<div class="badge-group">${tags}</div>`;
        }

        function renderTable() {
            if (!tableBody) return;
            tableBody.innerHTML = '';
            const filtered = state.filter === 'all'
                ? state.requests
                : state.requests.filter((request) => (request.status || '').toLowerCase() === state.filter);

            if (!filtered.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <div class="empty-content">
                                <div class="empty-icon">📦</div>
                                <h3>No pickup requests found</h3>
                                <p>No pickup requests match your current filter.</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            const rows = filtered.map((request) => {
                const status = (request.status || 'pending');
                const normalizedStatus = status.toLowerCase();
                const collector = request.collectorName ? request.collectorName : '-';
                const canEdit = ['pending', 'assigned'].includes(normalizedStatus);
                const canCancel = ['pending', 'assigned', 'confirmed'].includes(normalizedStatus);

                return `
                    <tr data-request-id="${request.id}">
                        <td>${request.id}</td>
                        <td>${escapeHtml(request.address || '')}</td>
                        <td>${escapeHtml(request.timeSlot || '')}</td>
                        <td>${renderWasteCategories(request.wasteCategories)}</td>
                        <td>${escapeHtml(formatDate(request.createdAt))}</td>
                        <td>${escapeHtml(formatDate(request.scheduledAt))}</td>
                        <td>${escapeHtml(collector)}</td>
                        <td><span class="tag ${statusClass(status)}">${escapeHtml(capitalize(status))}</span></td>
                        <td>
                            ${canEdit || canCancel
                        ? `${canEdit ? `<button class="action-btn view" data-action="edit" data-id="${request.id}">Edit</button>` : ''}
                                   ${canCancel ? `<button class="action-btn delete" data-action="cancel" data-id="${request.id}">Cancel</button>` : ''}`
                        : '<span style="color:#64748b;">-</span>'}
                        </td>
                    </tr>
                `;
            });

            tableBody.innerHTML = rows.join('');
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function capitalize(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function showNewRequestForm() {
            if (!newModal) return;
            newModal.classList.add('modal-open');

            const form = document.getElementById('newRequestForm');
            if (form) {
                const addressField = form.elements.namedItem('address');
                if (addressField) {
                    addressField.value = defaultAddress || '';
                }
            }

            const dateField = document.getElementById('new_date');
            if (dateField) {
                const minDate = new Date();
                minDate.setDate(minDate.getDate() + 1);
                dateField.min = minDate.toISOString().split('T')[0];
            }
        }

        function hideNewRequestForm() {
            if (newModal) {
                newModal.classList.remove('modal-open');
            }
            const form = document.getElementById('newRequestForm');
            if (form) {
                form.reset();
            }
        }

        function showEditRequestForm(requestId) {
            const modal = editModal;
            if (!modal) return;
            const request = state.requests.find((item) => item.id === requestId);
            if (!request) return;

            const form = document.getElementById('editRequestForm');
            if (!form) return;

            form.querySelector('#edit_request_id').value = request.id;
            form.querySelector('#edit_address').value = request.address || '';
            form.querySelector('#edit_time_slot').value = request.timeSlot || '';
            const scheduledInput = form.querySelector('#edit_date');
            if (scheduledInput) {
                if (request.scheduledAt) {
                    const date = new Date(request.scheduledAt.replace(' ', 'T'));
                    if (!Number.isNaN(date.getTime())) {
                        scheduledInput.value = date.toISOString().split('T')[0];
                    }
                } else {
                    scheduledInput.value = '';
                }
            }

            const selectedIds = new Set(
                Array.isArray(request.wasteCategoryDetails)
                    ? request.wasteCategoryDetails.map((item) => String(item.id))
                    : []
            );
            form.querySelectorAll('input[name="wasteCategories[]"]').forEach((checkbox) => {
                checkbox.checked = selectedIds.has(checkbox.value);
            });

            modal.classList.add('modal-open');
        }

        function hideEditRequestForm() {
            if (editModal) {
                editModal.style.display = 'none';
            }
        }

        async function loadPickupRequests() {
            try {
                const response = await fetch('/api/customer/pickup-requests', {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                if (!response.ok) {
                    throw new Error('Failed to load pickup requests');
                }
                const payload = await response.json();
                // Filter out cancelled requests so they never appear in the UI
                state.requests = Array.isArray(payload.data) ? payload.data.filter(r => (r.status || '').toLowerCase() !== 'cancelled') : [];
                renderStats();
                renderTable();
            } catch (error) {
                console.error(error);
                showAlert('Unable to refresh pickup requests. Please try again.', 'error');
            }
        }

        function collectWasteCategories(container) {
            if (!container) return [];
            const selected = Array.from(container.querySelectorAll('input[name="wasteCategories[]"]:checked'));
            return selected.map((checkbox) => ({
                id: parseInt(checkbox.value, 10)
            })).filter((item) => Number.isInteger(item.id));
        }

        async function submitNewRequest(event) {
            event.preventDefault();
            const form = event.target;
            const wasteCategoryContainer = document.getElementById('new_waste_categories');
            const payload = {
                address: form.address.value.trim(),
                timeSlot: form.timeSlot.value.trim(),
                scheduledAt: form.scheduledAt.value,
                wasteCategories: collectWasteCategories(wasteCategoryContainer)
            };

            try {
                const response = await fetch('/api/customer/pickup-requests', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok) {
                    const detail = result?.errors?.detail ? ` (${result.errors.detail})` : '';
                    throw new Error((result.message || 'Failed to create pickup request') + detail);
                }

                hideNewRequestForm();
                showAlert('Pickup request created successfully.');
                await loadPickupRequests();
            } catch (error) {
                console.error(error);
                showAlert(error.message || 'Failed to create pickup request.', 'error');
            }
        }

        async function submitEditRequest(event) {
            event.preventDefault();
            const form = event.target;
            const idField = form.querySelector('input[name="id"]');
            const requestId = idField ? idField.value : '';
            if (!requestId) {
                showAlert('Unable to identify the selected pickup request.', 'error');
                return;
            }

            const wasteCategoryContainer = document.getElementById('edit_waste_categories');
            const payload = {
                address: form.address.value.trim(),
                timeSlot: form.timeSlot.value.trim(),
                scheduledAt: form.scheduledAt.value,
                wasteCategories: collectWasteCategories(wasteCategoryContainer)
            };

            try {
                const response = await fetch(`/api/customer/pickup-requests/${requestId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok) {
                    const detail = result?.errors?.detail ? ` (${result.errors.detail})` : '';
                    throw new Error((result.message || 'Failed to update pickup request') + detail);
                }

                hideEditRequestForm();
                showAlert('Pickup request updated successfully.');
                await loadPickupRequests();
            } catch (error) {
                console.error(error);
                showAlert(error.message || 'Failed to update pickup request.', 'error');
            }
        }

        async function cancelRequest(requestId) {
            // Show a custom confirmation modal instead of native confirm
            const confirmed = await createConfirmModal({
                title: 'Cancel pickup request',
                message: 'Are you sure you want to cancel this pickup request? This action cannot be undone.',
                confirmLabel: 'Yes, cancel',
                cancelLabel: 'Keep request'
            });
            if (!confirmed) return;

            try {
                const response = await fetch(`/api/customer/pickup-requests/${requestId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();
                if (!response.ok) {
                    const detail = result?.errors?.detail ? ` (${result.errors.detail})` : '';
                    throw new Error((result.message || 'Failed to cancel pickup request') + detail);
                }

                // Remove the cancelled request from the local state so it disappears from the table
                removeRequestFromState(requestId);
                renderStats();
                renderTable();
                showAlert('Pickup request cancelled.');
            } catch (error) {
                console.error(error);
                showAlert(error.message || 'Failed to cancel pickup request.', 'error');
            }
        }

        function updateRequestStatusInState(requestId, status, fullObject) {
            const id = String(requestId ?? '');
            const index = state.requests.findIndex((r) => String(r.id) === id);
            if (index >= 0) {
                if (fullObject && typeof fullObject === 'object') {
                    state.requests[index] = Object.assign({}, state.requests[index], fullObject);
                } else {
                    state.requests[index].status = status;
                }
            }
        }

        function removeRequestFromState(requestId) {
            const id = typeof requestId === 'number' ? String(requestId) : String(requestId || '');
            const index = state.requests.findIndex((r) => String(r.id) === id);
            if (index >= 0) {
                state.requests.splice(index, 1);
            }
        }

        function attachEventListeners() {
            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    state.filter = button.getAttribute('data-filter') || 'all';
                    filterButtons.forEach((btn) => {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline');
                    });
                    button.classList.remove('btn-outline');
                    button.classList.add('btn-primary');
                    renderTable();
                });
            });

            document.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;
                const action = target.getAttribute('data-action');
                if (!action) return;
                const requestId = target.getAttribute('data-id');
                if (!requestId) return;

                if (action === 'edit') {
                    showEditRequestForm(requestId);
                } else if (action === 'cancel') {
                    cancelRequest(requestId);
                }
            });

            const newForm = document.getElementById('newRequestForm');
            if (newForm) {
                newForm.addEventListener('submit', submitNewRequest);
            }

            const editForm = document.getElementById('editRequestForm');
            if (editForm) {
                editForm.addEventListener('submit', submitEditRequest);
            }

            window.showNewRequestForm = showNewRequestForm;
            window.hideNewRequestForm = hideNewRequestForm;
            window.hideEditRequestForm = hideEditRequestForm;
        }

        function initialize() {
            attachEventListeners();
            renderStats();
            renderTable();
            loadPickupRequests();
            window.addEventListener('click', (event) => {
                if (event.target === newModal) {
                    hideNewRequestForm();
                }
                if (event.target === editModal) {
                    hideEditRequestForm();
                }
            });
        }

        initialize();
    })();
</script>