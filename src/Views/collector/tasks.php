<?php
$assignedPickups = $assignedPickups ?? [];
$selectedTimeSlot = $selectedTimeSlot ?? 'all';
$selectedStatus = $selectedStatus ?? 'all';
$assignedRequests = array_values($assignedPickups);
$csrfToken = csrf_token();

// Status badge generator
function getStatusBadge($status) {
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
window.__PICKUP_DATA = <?php
$pickupData = array_map(function($pickup) {
    $pickup['wastes'] = $pickup['wastes'] ?? [];
    return $pickup;
}, $assignedRequests);
echo json_encode($pickupData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>;
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
        <h3 class="activity-card__title">
            <i class="fa-solid fa-box" style="margin-right:8px;"></i>My Tasks
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
                                    <button class="icon-button" onclick="viewDetails(this, '<?= htmlspecialchars($r['id'] ?? '') ?>')">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;color:gray;">No tasks assigned.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
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
                <input id="weightInput" type="number" step="0.01" min="0" placeholder="e.g. 12.50" style="padding:0.5rem;border:1px solid #e5e7eb;border-radius:4px;width:100%;box-sizing:border-box;">
            </div>
            <div style="margin-top:0.5rem;">
                <strong>Calculated Price (₹): </strong>
                <span id="calculatedPrice">0.00</span>
            </div>
            <div id="wasteBreakdown" style="margin-top:1rem;font-size:0.95rem;color:#333;"></div>
            <div style="margin-top:0.5rem;">
                <button class="btn btn-secondary" onclick="saveWeight()">Save Weight & Calculate</button>
            </div>
        </div>

        <div style="margin-top: var(--space-8); text-align: right;">
            <button class="btn" onclick="closeDetailModal()">Close</button>
            <button class="btn btn-primary" id="taskActionBtn" onclick="updateTaskStatus()">Start Task</button>
        </div>
    </div>
</div>

<script>
const weightInput = document.getElementById('weightInput');
const calculatedPriceEl = document.getElementById('calculatedPrice');
const breakdownEl = document.getElementById('wasteBreakdown');

function closeDetailModal() {
    const modal = document.getElementById('pickup-detail-modal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}

function normalizeStatusValue(status) {
    const value = (status || '').toString().toLowerCase();
    if (value === 'in_progress' || value === 'in-progress') return 'in progress';
    return value;
}

function getStatusBadge(status) {
    const normalized = normalizeStatusValue(status);
    const map = { 'pending':'tag pending', 'assigned':'tag assigned', 'in progress':'tag inprogress', 'completed':'tag completed' };
    const cls = map[normalized] || 'tag';
    return `<div class="${cls}">${normalized.charAt(0).toUpperCase() + normalized.slice(1)}</div>`;
}

function viewDetails(el, pickupId) {
    const record = window.__PICKUP_DATA.find(r => r.id == pickupId);
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

    if (statusValue === 'assigned') {
        btn.textContent = 'Start Task';
        document.getElementById('weight-entry-row').style.display = '';
        weightInput.value = '';
        calculatedPriceEl.textContent = '0.00';
        breakdownEl.innerHTML = '';
    } else if (statusValue === 'in progress') {
        btn.textContent = 'Mark as Completed';
        document.getElementById('weight-entry-row').style.display = '';
        weightInput.value = record.weight || '';
        updateCalculatedPrice(record.id);
    } else {
        btn.style.display = 'none';
        document.getElementById('weight-entry-row').style.display = 'none';
        breakdownEl.innerHTML = '';
    }

    modal.setAttribute('data-current-id', record.id);
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
}

// Real-time calculation
weightInput.addEventListener('input', () => {
    const modal = document.getElementById('pickup-detail-modal');
    const pickupId = modal.getAttribute('data-current-id');
    updateCalculatedPrice(pickupId);
});

function updateCalculatedPrice(pickupId) {
    const pickup = window.__PICKUP_DATA.find(p => p.id == pickupId);
    if (!pickup || !pickup.wastes || pickup.wastes.length===0) return;

    const totalWeight = parseFloat(weightInput.value);
    if (isNaN(totalWeight)) {
        calculatedPriceEl.textContent = '0.00';
        breakdownEl.innerHTML = '';
        return;
    }

    const sumQty = pickup.wastes.reduce((sum, w)=>sum+parseFloat(w.quantity||0),0)||1;
    let totalPrice = 0;

    const breakdownHTML = pickup.wastes.map(w=>{
        const scaled = parseFloat(w.quantity)*totalWeight/sumQty;
        const amount = scaled*parseFloat(w.price_per_unit);
        totalPrice+=amount;
        return `${w.category_name}: ${scaled.toFixed(2)} ${w.unit} → ₹${amount.toFixed(2)}`;
    }).join('<br>');

    calculatedPriceEl.textContent = totalPrice.toFixed(2);
    breakdownEl.innerHTML = breakdownHTML;
}

// Save weight & calculate to backend
async function saveWeight() {
    const modal = document.getElementById('pickup-detail-modal');
    const pickupId = modal.getAttribute('data-current-id');
    const weightVal = parseFloat(weightInput.value);
    if (isNaN(weightVal)) return alert("Enter a valid weight");

    const pickup = window.__PICKUP_DATA.find(p=>p.id==pickupId);
    const sumQty = pickup.wastes.reduce((sum,w)=>sum+parseFloat(w.quantity||0),0)||1;
    const totalPrice = pickup.wastes.reduce((sum,w)=>sum+parseFloat(w.quantity)*weightVal/sumQty*parseFloat(w.price_per_unit),0);

    // Update local object
    pickup.weight = weightVal;
    pickup.price = totalPrice;

    // Save to backend
    try {
        await fetch(`/api/collector/pickup-requests/${pickupId}/save-weight`, {
            method: 'PUT',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
            body: JSON.stringify({weight:weightVal, price:totalPrice})
        });
        alert("Weight saved and price calculated!");
        updateCalculatedPrice(pickupId);
    } catch(err){ console.error(err); alert("Failed to save weight"); }
}

// Start / complete task
async function updateTaskStatus() {
    const modal = document.getElementById('pickup-detail-modal');
    const pickupId = modal.getAttribute('data-current-id');
    const pickup = window.__PICKUP_DATA.find(p=>p.id==pickupId);
    const btn = document.getElementById('taskActionBtn');

    let nextStatus = '';
    const curr = normalizeStatusValue(pickup.status);
    if(curr==='assigned') nextStatus='in_progress';
    else if(curr==='in progress') nextStatus='completed';
    else return;

    btn.disabled=true; btn.textContent='Updating...';

    const payload={status: nextStatus};
    if(nextStatus==='in_progress' || nextStatus==='completed'){
        payload.weight=parseFloat(weightInput.value);
        payload.price=parseFloat(calculatedPriceEl.textContent);
    }

    try{
        const res=await fetch(`/api/collector/pickup-requests/${pickupId}/status`,{
            method:'PUT',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
            body:JSON.stringify(payload)
        });
        const data=await res.json().catch(()=>({}));
        pickup.status=normalizeStatusValue(data.status||nextStatus);

        document.querySelector(`tr[data-id="${pickupId}"] td:nth-child(6)`).innerHTML=getStatusBadge(pickup.status);
        modal.querySelector('.pd-status').textContent=pickup.status;

        if(pickup.status==='completed'){
            btn.style.display='none';
            document.getElementById('weight-entry-row').style.display='none';
        } else btn.textContent='Mark as Completed';

    }catch(err){
        alert(err.message||"Failed to update task");
        btn.disabled=false; btn.textContent=(nextStatus==='in_progress')?'Mark as Completed':'Start Task';
    }
}
</script>
