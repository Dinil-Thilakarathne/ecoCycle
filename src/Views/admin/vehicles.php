<?php
$vehicles = $vehicles ?? [];
consoleLog('Vehicles:', $vehicles);
$vehicles = is_array($vehicles) ? $vehicles : [];

// Vehicle plate number validation function
function validatePlateNumber($plateNumber)
{
    // Pattern: XXX-XXXX (3 capital letters - 4 digits)
    $pattern = '/^[A-Z]{3}-[0-9]{4}$/';
    return preg_match($pattern, $plateNumber);
}

// Function to format plate number input
function formatPlateNumber($plateNumber)
{
    // Remove any existing formatting and convert to uppercase
    $cleaned = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plateNumber));

    // If we have at least 3 characters, format as XXX-XXXX
    if (strlen($cleaned) >= 3) {
        $letters = substr($cleaned, 0, 3);
        $numbers = substr($cleaned, 3, 4);
        return $letters . '-' . $numbers;
    }

    return $cleaned;
}

// Helper function for status badges
function getStatusBadge($status)
{
    switch ($status) {
        case 'available':
            return '<div class="tag online">Available</div>';
        case 'in-use':
            return '<div class="tag warning">In Use</div>';
        case 'maintenance':
            return '<div class="tag danger">Maintenance</div>';
        default:
            return '<div class="tag">' . htmlspecialchars($status) . '</div>';
    }
}

// Calculate statistics
$totalVehicles = count($vehicles);
$availableVehicles = count(array_filter($vehicles, function ($v) {
    return ($v['status'] ?? '') === 'available';
}));
$inMaintenanceVehicles = count(array_filter($vehicles, function ($v) {
    return ($v['status'] ?? '') === 'maintenance';
}));
$inUseVehicles = count(array_filter($vehicles, function ($v) {
    return ($v['status'] ?? '') === 'in-use';
}));
?>

<script>
    window.__VEHICLES = <?php echo json_encode($vehicles, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>

<div>
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Vehicle Management</h2>
            <p class="page-header__description">Manage fleet vehicles and maintenance schedules</p>
        </div>
        <button class="btn btn-primary" onclick="addVehicle()">
            <i class="fa-solid fa-plus"></i>
            Add Vehicle
        </button>
    </div>

    <!-- Statistics Cards (feature-card components) -->
    <?php
    $vehicleStats = [
        [
            'title' => 'Total Vehicles',
            'value' => $totalVehicles,
            'icon' => 'fa-solid fa-truck',
            'period' => 'Fleet size',
        ],
        [
            'title' => 'Available',
            'value' => $availableVehicles,
            'icon' => 'fa-solid fa-truck',
            'period' => 'Ready for assignment',
        ],
        [
            'title' => 'In Use',
            'value' => $inUseVehicles,
            'icon' => 'fa-solid fa-truck-moving',
            'period' => 'Currently deployed',
        ],
        [
            'title' => 'In Maintenance',
            'value' => $inMaintenanceVehicles,
            'icon' => 'fa-solid fa-wrench',
            'period' => 'Under service',
        ],
    ];
    ?>
    <div class="dashboard-grid feature-cards">
        <?php foreach ($vehicleStats as $card): ?>
            <feature-card unwrap title="<?= htmlspecialchars($card['title']) ?>"
                value="<?= htmlspecialchars($card['value']) ?>" icon="<?= htmlspecialchars($card['icon']) ?>"
                period="<?= htmlspecialchars($card['period']) ?>"></feature-card>
        <?php endforeach; ?>
    </div>

    <!-- Vehicle Fleet Table -->
    <div class="activity-card">
        <div class="activity-card__header">
            <div class="activity-card__title-section">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-truck"></i>
                    Vehicle Fleet
                </h3>
                <p class="activity-card__description">Manage vehicle availability and maintenance schedules</p>
            </div>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vehicle ID</th>
                            <th>Plate Number</th>
                            <th>Type</th>
                            <th>Capacity (kg)</th>
                            <th>Status</th>
                            <th>Last Maintenance</th>
                            <th>Next Maintenance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr data-id="<?= htmlspecialchars($vehicle['id'] ?? '') ?>">
                                <td class="font-medium" data-field="id"><?= htmlspecialchars($vehicle['id'] ?? '') ?></td>
                                <td data-field="plateNumber"><?= htmlspecialchars($vehicle['plateNumber'] ?? '') ?></td>
                                <td data-field="type"><?= htmlspecialchars($vehicle['type'] ?? '') ?></td>
                                <td data-field="capacity"><?= number_format((int) ($vehicle['capacity'] ?? 0)) ?></td>
                                <td data-field="status"><?= getStatusBadge($vehicle['status'] ?? 'available') ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-calendar-days"
                                            style="color: var(--text-muted); font-size: 14px;"></i>
                                        <span
                                            data-field="lastMaintenance"><?= htmlspecialchars($vehicle['lastMaintenance'] ?? '-') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-calendar-days"
                                            style="color: var(--text-muted); font-size: 14px;"></i>
                                        <span
                                            data-field="nextMaintenance"><?= htmlspecialchars($vehicle['nextMaintenance'] ?? '-') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="icon-button" onclick="scheduleMaintenance('<?= $vehicle['id'] ?>')"
                                            title="Schedule Maintenance">
                                            <i class="fa-solid fa-wrench"></i>
                                        </button>
                                        <button class="icon-button" onclick="viewVehicleDetails('<?= $vehicle['id'] ?>')"
                                            title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="icon-button" onclick="editVehicle('<?= $vehicle['id'] ?>')"
                                            title="Edit Vehicle">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vehicles)): ?>
                            <tr data-empty="true">
                                <td colspan="8"
                                    style="text-align:center; padding: var(--space-16); color: var(--neutral-500);">
                                    No vehicles found.
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
    const VEHICLE_API_BASE = '/api/vehicles';
    const VEHICLE_STATUS_OPTIONS = ['available', 'in-use', 'maintenance'];

    function showToast(message, type = 'info') {
        if (typeof window.__createToast === 'function') {
            window.__createToast(message, type, 5000);
        } else {
            const prefix = type === 'error' ? 'Error: ' : '';
            alert(prefix + message);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function statusLabel(status) {
        switch (status) {
            case 'available':
                return 'Available';
            case 'in-use':
                return 'In Use';
            case 'maintenance':
                return 'Maintenance';
            default:
                return status;
        }
    }

    function renderStatusBadge(status) {
        const normalized = (status || '').toLowerCase();
        if (normalized === 'available') {
            return '<div class="tag online">Available</div>';
        }
        if (normalized === 'in-use') {
            return '<div class="tag warning">In Use</div>';
        }
        if (normalized === 'maintenance') {
            return '<div class="tag danger">Maintenance</div>';
        }

        return '<div class="tag">' + escapeHtml(status || 'Unknown') + '</div>';
    }

    function validatePlateNumber(plateNumber) {
        const pattern = /^[A-Z]{3}-[0-9]{4}$/;
        return pattern.test(plateNumber);
    }

    function formatPlateNumberInput(input) {
        let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (value.length >= 3) {
            value = value.substring(0, 3) + '-' + value.substring(3, 7);
        }
        input.value = value;

        const isValid = validatePlateNumber(value);
        if (value.length >= 7) {
            input.style.borderColor = isValid ? '#16a34a' : '#dc2626';
            input.style.backgroundColor = isValid ? '#f0fdf4' : '#fef2f2';
        } else {
            input.style.borderColor = '';
            input.style.backgroundColor = '';
        }
    }

    async function apiRequest(url, options = {}) {
        const opts = Object.assign({ headers: {} }, options);
        if (opts.body && !(opts.body instanceof FormData) && typeof opts.body === 'object') {
            opts.headers['Content-Type'] = opts.headers['Content-Type'] || 'application/json';
            opts.body = JSON.stringify(opts.body);
        }

        const response = await fetch(url, opts);
        let payload = null;

        try {
            payload = await response.json();
        } catch (err) {
            payload = null;
        }

        if (!response.ok || (payload && payload.success === false)) {
            const message = payload && payload.message ? payload.message : `Request failed (${response.status})`;
            let detail = '';
            if (payload && payload.errors) {
                detail = Object.values(payload.errors).join('\n');
            }
            throw new Error(detail ? `${message}\n${detail}` : message);
        }

        return payload || {};
    }

    function syncVehicleCache(vehicle) {
        if (!Array.isArray(window.__VEHICLES)) {
            window.__VEHICLES = [];
        }
        const id = Number(vehicle.id);
        const index = window.__VEHICLES.findIndex((item) => Number(item.id) === id);
        if (index >= 0) {
            window.__VEHICLES[index] = vehicle;
        } else {
            window.__VEHICLES.push(vehicle);
        }
    }

    function buildVehicleForm(initialValues = {}) {
        const defaults = {
            plateNumber: '',
            type: '',
            capacity: '',
            status: 'available',
            lastMaintenance: '',
            nextMaintenance: '',
        };

        const values = Object.assign({}, defaults, initialValues);
        const statusOptions = VEHICLE_STATUS_OPTIONS.map((status) => {
            const selected = status === values.status ? 'selected' : '';
            return `<option value="${status}" ${selected}>${statusLabel(status)}</option>`;
        }).join('');

        const vehicleTypes = ['Van', 'Pickup Truck', 'Small Truck', 'Large Truck'];
        const typeOptions = ['<option value="">Select Type</option>']
            .concat(vehicleTypes.map((type) => {
                const selected = type === values.type ? 'selected' : '';
                return `<option value="${escapeHtml(type)}" ${selected}>${escapeHtml(type)}</option>`;
            }))
            .join('');

        const form = document.createElement('form');
        form.innerHTML = `
            <div style="display:grid;gap:1rem;">
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Plate Number</label>
                    <input type="text" name="plateNumber" maxlength="8" required placeholder="ABC-1234"
                        value="${escapeHtml(values.plateNumber || '')}"
                        style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;font-family:monospace;"
                    />
                    <small style="color:#6b7280;display:block;margin-top:0.25rem;">Format: 3 capital letters followed by 4 numbers</small>
                </div>
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Vehicle Type</label>
                    <select name="type" required style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;">
                        ${typeOptions}
                    </select>
                </div>
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Capacity (kg)</label>
                    <input type="number" name="capacity" min="100" max="20000" required
                        value="${escapeHtml(values.capacity ?? '')}"
                        style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                </div>
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Status</label>
                    <select name="status" required style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;">
                        ${statusOptions}
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Last Maintenance</label>
                        <input type="date" name="lastMaintenance"
                            value="${escapeHtml(values.lastMaintenance || '')}"
                            style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Next Maintenance</label>
                        <input type="date" name="nextMaintenance"
                            value="${escapeHtml(values.nextMaintenance || '')}"
                            style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                    </div>
                </div>
            </div>
        `;

        const plateInput = form.querySelector('input[name="plateNumber"]');
        plateInput.addEventListener('input', function () {
            formatPlateNumberInput(this);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
        });

        return form;
    }

    function extractVehicleFormData(form) {
        const formData = new FormData(form);
        const plateNumber = (formData.get('plateNumber') || '').toString().toUpperCase();

        return {
            plateNumber,
            type: (formData.get('type') || '').toString(),
            capacity: Number(formData.get('capacity')),
            status: (formData.get('status') || 'available').toString(),
            lastMaintenance: (formData.get('lastMaintenance') || '') || null,
            nextMaintenance: (formData.get('nextMaintenance') || '') || null,
        };
    }

    function createModal({ title, content, buttons = [], width = '520px' }) {
        const backdrop = document.createElement('div');
        backdrop.className = 'simple-modal-backdrop';
        backdrop.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:2000;padding:1rem;';

        const dialog = document.createElement('div');
        dialog.style.cssText = `background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(15,23,42,0.16);width:min(${width},100%);max-width:${width};padding:1.75rem;display:flex;flex-direction:column;gap:1.5rem;`;

        const header = document.createElement('div');
        header.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:1rem;';
        const titleEl = document.createElement('h3');
        titleEl.textContent = title;
        titleEl.style.cssText = 'margin:0;font-size:1.25rem;font-weight:600;color:#111827;';
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.innerHTML = '&times;';
        closeButton.style.cssText = 'border:none;background:transparent;font-size:1.75rem;line-height:1;color:#6b7280;cursor:pointer;padding:0 0 0.25rem 0;';

        const body = document.createElement('div');
        body.style.cssText = 'max-height:60vh;overflow:auto;';
        body.appendChild(content);

        const footer = document.createElement('div');
        footer.style.cssText = 'display:flex;justify-content:flex-end;gap:0.75rem;';

        function closeModal() {
            backdrop.remove();
        }

        closeButton.addEventListener('click', closeModal);
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) {
                closeModal();
            }
        });

        buttons.forEach((buttonConfig) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = buttonConfig.label;

            const variant = buttonConfig.variant || 'secondary';
            let styles = 'padding:0.6rem 1.25rem;border-radius:6px;font-weight:600;border:none;cursor:pointer;';
            if (variant === 'primary') {
                styles += 'background:#16a34a;color:#fff;';
            } else if (variant === 'danger') {
                styles += 'background:#dc2626;color:#fff;';
            } else {
                styles += 'background:#6b7280;color:#fff;';
            }
            btn.style.cssText = styles;

            btn.addEventListener('click', function () {
                if (typeof buttonConfig.onClick === 'function') {
                    buttonConfig.onClick(closeModal);
                } else {
                    closeModal();
                }
            });

            footer.appendChild(btn);
        });

        header.appendChild(titleEl);
        header.appendChild(closeButton);
        dialog.appendChild(header);
        dialog.appendChild(body);
        dialog.appendChild(footer);
        backdrop.appendChild(dialog);
        document.body.appendChild(backdrop);

        return {
            close: closeModal,
            element: backdrop,
        };
    }

    function formatDateForDisplay(value) {
        return value && value !== '0000-00-00' ? value : '-';
    }

    function formatCapacity(value) {
        const numeric = Number(value || 0);
        return new Intl.NumberFormat().format(numeric);
    }

    function createVehicleRowElement(vehicle) {
        const tr = document.createElement('tr');
        const idValue = vehicle.id;
        const idString = String(idValue);
        const idLiteral = JSON.stringify(idValue);
        tr.setAttribute('data-id', idString);
        tr.innerHTML = `
            <td class="font-medium" data-field="id">${escapeHtml(idString)}</td>
            <td data-field="plateNumber">${escapeHtml(vehicle.plateNumber || '')}</td>
            <td data-field="type">${escapeHtml(vehicle.type || '')}</td>
            <td data-field="capacity">${formatCapacity(vehicle.capacity)}</td>
            <td data-field="status">${renderStatusBadge(vehicle.status)}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-calendar-days" style="color:var(--text-muted);font-size:14px;"></i>
                    <span data-field="lastMaintenance">${escapeHtml(formatDateForDisplay(vehicle.lastMaintenance))}</span>
                </div>
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-calendar-days" style="color:var(--text-muted);font-size:14px;"></i>
                    <span data-field="nextMaintenance">${escapeHtml(formatDateForDisplay(vehicle.nextMaintenance))}</span>
                </div>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="icon-button" onclick="scheduleMaintenance(${idLiteral})" title="Schedule Maintenance">
                        <i class="fa-solid fa-wrench"></i>
                    </button>
                    <button class="icon-button" onclick="viewVehicleDetails(${idLiteral})" title="View Details">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button class="icon-button" onclick="editVehicle(${idLiteral})" title="Edit Vehicle">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                </div>
            </td>
        `;
        return tr;
    }

    function appendVehicleRow(vehicle) {
        const tbody = document.querySelector('.data-table tbody');
        if (!tbody) {
            return;
        }

        const emptyRow = tbody.querySelector('[data-empty]');
        if (emptyRow) {
            emptyRow.remove();
        }

        const existingRow = tbody.querySelector(`tr[data-id="${vehicle.id}"]`);
        if (existingRow) {
            existingRow.replaceWith(createVehicleRowElement(vehicle));
        } else {
            tbody.prepend(createVehicleRowElement(vehicle));
        }
    }

    function updateVehicleRow(vehicle) {
        const row = document.querySelector(`tr[data-id="${vehicle.id}"]`);
        if (!row) {
            appendVehicleRow(vehicle);
            return;
        }

        const plateCell = row.querySelector('[data-field="plateNumber"]');
        if (plateCell) plateCell.textContent = vehicle.plateNumber || '';

        const typeCell = row.querySelector('[data-field="type"]');
        if (typeCell) typeCell.textContent = vehicle.type || '';

        const capacityCell = row.querySelector('[data-field="capacity"]');
        if (capacityCell) capacityCell.textContent = formatCapacity(vehicle.capacity);

        const statusCell = row.querySelector('[data-field="status"]');
        if (statusCell) statusCell.innerHTML = renderStatusBadge(vehicle.status);

        const lastMaintenance = row.querySelector('[data-field="lastMaintenance"]');
        if (lastMaintenance) lastMaintenance.textContent = formatDateForDisplay(vehicle.lastMaintenance);

        const nextMaintenance = row.querySelector('[data-field="nextMaintenance"]');
        if (nextMaintenance) nextMaintenance.textContent = formatDateForDisplay(vehicle.nextMaintenance);
    }

    async function handleVehicleSave({ mode, vehicleId, form, close }) {
        try {
            const payload = extractVehicleFormData(form);

            if (!validatePlateNumber(payload.plateNumber)) {
                showToast('Please use the format ABC-1234 for plate numbers.', 'error');
                return;
            }

            if (!Number.isFinite(payload.capacity) || payload.capacity <= 0) {
                showToast('Capacity must be a positive number.', 'error');
                return;
            }

            const endpoint = mode === 'create' ? VEHICLE_API_BASE : `${VEHICLE_API_BASE}/${vehicleId}`;
            const method = mode === 'create' ? 'POST' : 'PUT';
            const response = await apiRequest(endpoint, { method, body: payload });
            const vehicle = response.vehicle;

            syncVehicleCache(vehicle);

            if (mode === 'create') {
                appendVehicleRow(vehicle);
            } else {
                updateVehicleRow(vehicle);
            }

            if (typeof close === 'function') {
                close();
            }
            showToast(mode === 'create' ? 'Vehicle added successfully.' : 'Vehicle updated successfully.', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function addVehicle() {
        const form = buildVehicleForm();
        createModal({
            title: 'Add New Vehicle',
            content: form,
            buttons: [
                { label: 'Cancel', variant: 'secondary', onClick: (close) => close() },
                {
                    label: 'Save Vehicle',
                    variant: 'primary',
                    onClick: (close) => handleVehicleSave({ mode: 'create', form, close })
                }
            ]
        });
    }

    async function fetchVehicle(vehicleId) {
        const id = Number(vehicleId);
        const response = await apiRequest(`${VEHICLE_API_BASE}/${id}`);
        const vehicle = response.vehicle;
        if (vehicle) {
            syncVehicleCache(vehicle);
        }
        return vehicle;
    }

    async function viewVehicleDetails(vehicleId) {
        try {
            const vehicle = await fetchVehicle(vehicleId);
            if (!vehicle) {
                showToast('Vehicle not found.', 'error');
                return;
            }

            const container = document.createElement('div');
            container.innerHTML = `
                <div style="display:grid;gap:1rem;">
                    <div>
                        <span style="display:block;color:#6b7280;font-size:0.85rem;">Plate Number</span>
                        <strong style="font-size:1.1rem;color:#111827;">${escapeHtml(vehicle.plateNumber || '')}</strong>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                        <div>
                            <span style="display:block;color:#6b7280;font-size:0.85rem;">Type</span>
                            <strong>${escapeHtml(vehicle.type || '-')}</strong>
                        </div>
                        <div>
                            <span style="display:block;color:#6b7280;font-size:0.85rem;">Capacity</span>
                            <strong>${formatCapacity(vehicle.capacity)} kg</strong>
                        </div>
                        <div>
                            <span style="display:block;color:#6b7280;font-size:0.85rem;">Status</span>
                            <span>${renderStatusBadge(vehicle.status)}</span>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                        <div>
                            <span style="display:block;color:#6b7280;font-size:0.85rem;">Last Maintenance</span>
                            <strong>${escapeHtml(formatDateForDisplay(vehicle.lastMaintenance))}</strong>
                        </div>
                        <div>
                            <span style="display:block;color:#6b7280;font-size:0.85rem;">Next Maintenance</span>
                            <strong>${escapeHtml(formatDateForDisplay(vehicle.nextMaintenance))}</strong>
                        </div>
                    </div>
                </div>
            `;

            createModal({
                title: `Vehicle ${escapeHtml(vehicle.plateNumber || vehicle.id)}`,
                content: container,
                buttons: [
                    { label: 'Close', variant: 'secondary', onClick: (close) => close() }
                ],
                width: '480px'
            });
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    async function editVehicle(vehicleId) {
        try {
            const vehicle = await fetchVehicle(vehicleId);
            if (!vehicle) {
                showToast('Vehicle not found.', 'error');
                return;
            }

            const form = buildVehicleForm(vehicle);
            createModal({
                title: `Edit Vehicle ${escapeHtml(vehicle.plateNumber || vehicle.id)}`,
                content: form,
                buttons: [
                    { label: 'Cancel', variant: 'secondary', onClick: (close) => close() },
                    {
                        label: 'Save Changes',
                        variant: 'primary',
                        onClick: (close) => handleVehicleSave({ mode: 'update', vehicleId: vehicle.id, form, close })
                    }
                ]
            });
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function scheduleMaintenance(vehicleId) {
        showToast(`Maintenance scheduling for vehicle ${vehicleId} is coming soon.`, 'info');
    }
</script>