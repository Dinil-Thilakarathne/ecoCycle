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

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">My Assigned Pickups & Daily Tasks</h2>
            <p class="page-header__description">Manage your assigned pickups and track progress in real time</p>
        </div>
    </div>

    <!-- Task Table -->
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

    <!-- Modal for Task Details -->
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

            <!-- Weight & Price Entry -->
            <div id="weight-entry-row" style="display:none;margin-top:var(--space-6);">
                <div style="margin-bottom:0.5rem;"><strong>Measured Weight (kg)</strong></div>
                <div>
                    <input id="weightInput" type="number" step="0.01" min="0" placeholder="e.g. 12.50"
                        style="padding:0.5rem;border:1px solid #e5e7eb;border-radius:4px;width:100%;box-sizing:border-box;">
                    <div id="weightError" style="color:#dc2626;margin-top:0.5rem;display:none;font-size:0.95rem;">
                        Please enter a valid weight greater than 0.
                    </div>

                    <!-- Enter Button (styled like Mark as Completed) -->
                    <div style="margin-top:0.5rem;">
                        <button id="enterWeightBtn" class="btn btn-primary" type="button">Enter</button>
                    </div>
                </div>

                <!-- Total Price Display -->
                <div id="totalPriceContainer" style="margin-top:1rem;">
                    <strong>Calculated Price (Rs): </strong>
                    <input id="calculatedPrice" type="text" placeholder="Rs0.00" readonly
                        style="padding:0.5rem; border:1px solid #d1d5db; border-radius:4px; width:150px; font-weight:600; color:#1f2937; text-align:right;">
                </div>

                <!-- Waste Breakdown -->
                <div id="wasteBreakdown" style="margin-top:0.5rem; font-size:0.9rem; color:#555;"></div>
            </div>

            <div style="margin-top: var(--space-8); text-align: right;">
                <button class="btn" onclick="closeDetailModal()">Close</button>
                <button class="btn btn-primary" id="taskActionBtn" onclick="startOrUpdateTask()">Start Task</button>
            </div>
        </div>
    </div>

    <script>
    const weightInput = document.getElementById('weightInput');
    const calculatedPriceEl = document.getElementById('calculatedPrice');
    const wasteBreakdownEl = document.getElementById('wasteBreakdown');
    const weightError = document.getElementById('weightError');

    // --- Close modal
    function closeDetailModal() {
        const modal = document.getElementById('pickup-detail-modal');
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    // --- Normalize status
    function normalizeStatusValue(status) {
        const value = (status || '').toString().toLowerCase();
        if (value === 'in_progress' || value === 'in-progress') return 'in progress';
        return value;
    }

    // --- Status badge HTML
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

    // --- Show modal
    function viewDetails(el, pickupId) {
        const record = (window.__PICKUP_DATA || []).find(r => (r.id || '') == pickupId);
        const modal = document.getElementById('pickup-detail-modal');
        if (!record || !modal) return;

        modal.querySelector('.pd-id').textContent = record.id;
        modal.querySelector('.pd-customer').textContent = record.customerName;
        modal.querySelector('.pd-address').textContent = record.address;
        modal.querySelector('.pd-waste').textContent = (record.wasteCategories || []).join(', ');
        modal.querySelector('.pd-timeslot').textContent = record.timeSlot;
        const statusValue = normalizeStatusValue(record.status);
        modal.querySelector('.pd-status').textContent = statusValue;

        const btn = document.getElementById('taskActionBtn');
        btn.style.display = '';
        btn.disabled = false;

        // hide weight entry initially
        document.getElementById('weight-entry-row').style.display = 'none';
        weightInput.value = '';
        calculatedPriceEl.value = '';
        wasteBreakdownEl.innerHTML = '';

        // set button text
        if (statusValue === 'assigned') {
            btn.textContent = 'Start Task';
        } else if (statusValue === 'in progress') {
            btn.textContent = 'Mark as Completed';
            document.getElementById('weight-entry-row').style.display = '';
            weightInput.value = record.weight || '';
            calculatePriceAndUpdateTable(record.id, record.weight || 0);
        } else {
            btn.style.display = 'none';
        }

        modal.setAttribute('data-current-id', record.id);
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    // --- Real-time price calculation
    function calculatePriceAndUpdateTable(pickupId, enteredWeight) {
        const pickup = (window.__PICKUP_DATA || []).find(p => p.id == pickupId);
        if (!pickup || !pickup.wastes || pickup.wastes.length === 0) return;

        const totalWeight = parseFloat(enteredWeight);
        if (isNaN(totalWeight) || totalWeight <= 0) {
            weightError.style.display = 'block';
            calculatedPriceEl.value = '';
            wasteBreakdownEl.innerHTML = '';
            return;
        } else {
            weightError.style.display = 'none';
        }

        const sumOriginalQty = pickup.wastes.reduce((sum, w) => sum + parseFloat(w.quantity || 0), 0) || 1;
        let totalPrice = 0;
        const breakdownLines = pickup.wastes.map(w => {
            const scaledQty = parseFloat(w.quantity) * totalWeight / sumOriginalQty;
            const amount = scaledQty * parseFloat(w.price_per_unit);
            totalPrice += amount;
            return `${w.category_name}: ${scaledQty.toFixed(2)} ${w.unit} → ₹${amount.toFixed(2)}`;
        });

        calculatedPriceEl.value = `₹${totalPrice.toFixed(2)}`;
        wasteBreakdownEl.innerHTML = breakdownLines.join('<br>');

        const row = document.querySelector(`tr[data-id="${pickupId}"]`);
        if (row) {
            const wasteCell = row.querySelectorAll('td')[3];
            wasteCell.innerHTML = breakdownLines.join('<br>');
        }

        pickup.calculatedWeight = totalWeight;
        pickup.calculatedPrice = totalPrice;
        pickup.calculatedBreakdown = breakdownLines;
    }

    // --- Real-time input listener
    weightInput.addEventListener('input', () => {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        calculatePriceAndUpdateTable(pickupId, weightInput.value);
    });

    // --- Enter Weight Button click (save to database)
    document.getElementById('enterWeightBtn').addEventListener('click', async () => {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        const idx = (window.__PICKUP_DATA || []).findIndex(r => r.id == pickupId);
        if (idx === -1) return;

        const pickup = window.__PICKUP_DATA[idx];
        const weightVal = parseFloat(weightInput.value);
        if (isNaN(weightVal) || weightVal <= 0) {
            weightError.style.display = 'block';
            return;
        }

        calculatePriceAndUpdateTable(pickupId, weightVal);

        try {
            const payloadBody = {
                weight: weightVal,
                price: pickup.calculatedPrice
            };

            const response = await fetch(`/api/collector/pickup-requests/${encodeURIComponent(pickupId)}/weight`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payloadBody)
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Failed to save weight.');

            pickup.weight = weightVal;
            pickup.price = pickup.calculatedPrice;

            alert('Weight saved successfully!');
        } catch (err) {
            alert(err.message || 'Unable to save weight.');
        }
    });
    /*
    const enterBtn = document.getElementById('enterWeightBtn');

    enterBtn.addEventListener('click', async () => {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        const weight = parseFloat(weightInput.value);
        const price = parseFloat(calculatedPriceEl.textContent.replace('₹',''));

        if (!weight || weight <= 0) {
            weightError.style.display = 'block';
            return;
        }

        try {
            enterBtn.disabled = true;
            enterBtn.textContent = 'Saving...';

            const res = await fetch(`/api/collector/pickup-requests/${encodeURIComponent(pickupId)}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ weight, price, status: 'completed' })
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to save weight');

            // Update modal and table
            modal.querySelector('.pd-status').textContent = 'completed';
            const row = document.querySelector(`tr[data-id="${pickupId}"]`);
            if (row) {
                row.querySelectorAll('td')[5].innerHTML = getStatusBadge('completed');
                row.querySelectorAll('td')[3].innerHTML = wasteBreakdownEl.innerHTML;
            }

            enterBtn.style.display = 'none';
            alert('Weight and price saved successfully!');
        } catch (err) {
            alert(err.message);
            enterBtn.disabled = false;
            enterBtn.textContent = 'Enter';
        }
    });
    */
    // --- Enter Weight Button (save weight + amounts in DB, show in modal only)
    enterBtn.addEventListener('click', async () => {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        const weight = parseFloat(weightInput.value);

        if (!weight || weight <= 0) {
            weightError.style.display = 'block';
            return;
        } else {
            weightError.style.display = 'none';
        }

        try {
            enterBtn.disabled = true;
            enterBtn.textContent = 'Calculating...';

            // --- Send weight to server
            const response = await fetch('/collector/save-weight', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ pickup_id: pickupId, weight })
            });

            const payload = await response.json();
            if (!payload.success) throw new Error(payload.error || 'Failed to calculate');

            // --- Show returned total + breakdown in modal
            const data = payload.data;
            calculatedPriceEl.value = `₹${data.total.toFixed(2)}`;
            wasteBreakdownEl.innerHTML = data.breakdown.map(b =>
                `${b.category_name}: ${b.quantity.toFixed(2)} → ₹${b.amount.toFixed(2)}`
            ).join('<br>');

            // --- Save values locally for later "Mark as Completed"
            const pickup = window.__PICKUP_DATA.find(p => p.id === pickupId);
            if (pickup) {
                pickup.calculatedWeight = weight;
                pickup.calculatedPrice = data.total;
                pickup.calculatedBreakdown = data.breakdown;
            }

            alert('Weight saved and price calculated successfully!');

        } catch (err) {
            alert(err.message);
        } finally {
            enterBtn.disabled = false;
            enterBtn.textContent = 'Enter';
        }
    });

    // --- Start or Update Task
    function startOrUpdateTask() {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        const idx = (window.__PICKUP_DATA || []).findIndex(r => r.id == pickupId);
        if (idx === -1) return;

        const pickup = window.__PICKUP_DATA[idx];
        const btn = document.getElementById('taskActionBtn');
        const status = normalizeStatusValue(pickup.status);

        if (status === 'assigned') {
            pickup.status = 'in progress';
            modal.querySelector('.pd-status').textContent = 'in progress';
            btn.textContent = 'Mark as Completed';
            document.getElementById('weight-entry-row').style.display = '';
            return;
        }

        updateTaskStatus();
    }

    // --- Update status to completed
    async function updateTaskStatus() {
        const modal = document.getElementById('pickup-detail-modal');
        const pickupId = modal.getAttribute('data-current-id');
        const idx = (window.__PICKUP_DATA || []).findIndex(r => r.id == pickupId);
        if (idx === -1) return;

        const pickup = window.__PICKUP_DATA[idx];
        const btn = document.getElementById('taskActionBtn');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Updating...';

        const weightVal = pickup.calculatedWeight;
        const priceVal = pickup.calculatedPrice;

        if (!weightVal || weightVal <= 0) {
            weightError.style.display = '';
            btn.textContent = originalText;
            btn.disabled = false;
            return;
        }

        try {
            const payloadBody = {
                status: 'completed',
                weight: weightVal,
                price: priceVal
            };

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
            if (!response.ok) throw new Error(payload.message || 'Failed to update task status.');

            const updated = payload.data || {};
            pickup.status = normalizeStatusValue(updated.status || 'completed');
            pickup.weight = weightVal;
            pickup.price = priceVal;

            modal.querySelector('.pd-status').textContent = pickup.status;

            const row = document.querySelector(`tr[data-id="${pickupId}"]`);
            if (row) {
                const statusCell = row.querySelectorAll('td')[5];
                if (statusCell) statusCell.innerHTML = getStatusBadge(pickup.status);
            }

            btn.style.display = 'none';
        } catch (err) {
            alert(err.message || 'Unable to update task status.');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    async function calculatePriceAndUpdateTable(pickupId, enteredWeight) {
        if (!enteredWeight || enteredWeight <= 0) {
            weightError.style.display = 'block';
            calculatedPriceEl.value = '';
            wasteBreakdownEl.innerHTML = '';
            return;
        } else {
            weightError.style.display = 'none';
        }

        try {
            const response = await fetch('/collector/calculate-price', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ pickup_id: pickupId, weight: parseFloat(enteredWeight) })
            });

            const payload = await response.json();
            if (!payload.success) throw new Error(payload.error || 'Failed to calculate price');

            const data = payload.data;
            calculatedPriceEl.value = `₹${data.total.toFixed(2)}`;

            // show breakdown
            const breakdownLines = data.breakdown.map(b => 
                `${b.category_name}: ${b.quantity} ${b.unit} → ₹${b.amount.toFixed(2)}`
            );
            wasteBreakdownEl.innerHTML = breakdownLines.join('<br>');

            // Update table row
            const row = document.querySelector(`tr[data-id="${pickupId}"]`);
            if (row) {
                row.querySelectorAll('td')[3].innerHTML = breakdownLines.join('<br>');
            }

        } catch (err) {
            console.error(err);
            calculatedPriceEl.value = '';
            wasteBreakdownEl.innerHTML = '';
        }
    }

    </script>
