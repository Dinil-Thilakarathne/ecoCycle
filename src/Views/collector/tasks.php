<?php
$assignedPickups = $assignedPickups ?? [];
$selectedTimeSlot = $selectedTimeSlot ?? 'all';
$selectedStatus = $selectedStatus ?? 'all';
$assignedRequests = array_values($assignedPickups);
$csrfToken = csrf_token();

function getStatusBadge($status) {
    $status = strtolower($status);
    $class = '';
    switch ($status) {
        case 'pending': $class='pending'; break;
        case 'assigned': $class='assigned'; break;
        case 'in progress': $class='inprogress'; break;
        case 'completed': $class='completed'; break;
    }
    return "<div class='tag $class'>" . ucfirst($status) . "</div>";
}
?>

<script>
window.__PICKUP_DATA = <?= json_encode(array_map(function($p){ $p['wastes']=$p['wastes']??[]; return $p; },$assignedRequests), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- Page Header -->
<div class="page-header">
    <h2>My Assigned Pickups & Daily Tasks</h2>
    <p>Manage your assigned pickups and track progress in real time</p>
</div>

<!-- Task Table -->
<div class="activity-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th><th>Customer</th><th>Address</th><th>Waste</th><th>Time Slot</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($assignedRequests)): foreach($assignedRequests as $r): ?>
            <tr data-id="<?= $r['id'] ?>">
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['customerName']??'Unknown') ?></td>
                <td><?= htmlspecialchars($r['address']??'N/A') ?></td>
                <td><?= htmlspecialchars(implode(',',$r['wasteCategories']??[])) ?></td>
                <td><?= htmlspecialchars($r['timeSlot']??'') ?></td>
                <td><?= getStatusBadge($r['status']??($r['statusRaw']??'')) ?></td>
                <td>
                    <button class="icon-button" onclick="viewDetails(this,'<?= $r['id'] ?>')"><i class="fa-solid fa-eye"></i></button>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" style="text-align:center;color:gray;">No tasks assigned.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="pickup-detail-modal" class="user-modal">
    <div class="user-modal__dialog">
        <button class="close" onclick="closeDetailModal()">&times;</button>
        <h3>Pickup Task Details</h3>
        <div class="user-modal__grid">
            <div><strong>Request ID</strong></div><div class="pd-id"></div>
            <div><strong>Customer</strong></div><div class="pd-customer"></div>
            <div><strong>Address</strong></div><div class="pd-address"></div>
            <div><strong>Waste Categories</strong></div><div class="pd-waste"></div>
            <div><strong>Time Slot</strong></div><div class="pd-timeslot"></div>
            <div><strong>Status</strong></div><div class="pd-status"></div>
        </div>

        <!-- Weight Entry -->
        <div id="weight-entry-row" style="display:none;margin-top:1rem;">
            <div><strong>Measured Weight (kg)</strong></div>
            <input id="weightInput" type="number" step="0.01" min="0" placeholder="e.g. 12.5">
            <div style="margin-top:0.5rem;">
                <strong>Total Price (₹): </strong><span id="calculatedPrice">0.00</span>
            </div>
            <div id="wasteBreakdown" style="margin-top:0.5rem;"></div>
            <div style="margin-top:0.5rem;">
                <button class="btn btn-secondary" onclick="previewWeight()">Preview Weight & Price</button>
                <button class="btn btn-success" onclick="enterWeight()">Enter Weight</button>
            </div>
        </div>

        <div style="margin-top:1rem;text-align:right;">
            <button class="btn" onclick="closeDetailModal()">Close</button>
            <button class="btn btn-primary" id="taskActionBtn" onclick="startOrCompleteTask()">Start Task</button>
        </div>
    </div>
</div>

<script>
const weightInput = document.getElementById('weightInput');
const calculatedPriceEl = document.getElementById('calculatedPrice');
const breakdownEl = document.getElementById('wasteBreakdown');

function closeDetailModal(){
    const modal=document.getElementById('pickup-detail-modal');
    modal.classList.remove('open');
}

function normalizeStatusValue(status){
    if(!status) return '';
    const s = status.toString().toLowerCase();
    return (s==='in_progress'||s==='in-progress')?'in progress':s;
}

function getStatusBadge(status){
    const normalized=normalizeStatusValue(status);
    const map={'pending':'tag pending','assigned':'tag assigned','in progress':'tag inprogress','completed':'tag completed'};
    return `<div class="${map[normalized]||'tag'}">${normalized.charAt(0).toUpperCase()+normalized.slice(1)}</div>`;
}

function viewDetails(el,pickupId){
    const record=window.__PICKUP_DATA.find(r=>r.id==pickupId);
    const modal=document.getElementById('pickup-detail-modal');
    if(!record||!modal) return;

    modal.querySelector('.pd-id').textContent=record.id;
    modal.querySelector('.pd-customer').textContent=record.customerName;
    modal.querySelector('.pd-address').textContent=record.address;
    modal.querySelector('.pd-waste').textContent=(record.wasteCategories||[]).join(', ');
    modal.querySelector('.pd-timeslot').textContent=record.timeSlot;
    modal.querySelector('.pd-status').textContent=normalizeStatusValue(record.status);

    const btn=document.getElementById('taskActionBtn');
    const weightRow=document.getElementById('weight-entry-row');
    weightRow.style.display='none';
    weightInput.value='';
    calculatedPriceEl.textContent='0.00';
    breakdownEl.innerHTML='';

    if(normalizeStatusValue(record.status)==='assigned'){
        btn.textContent='Start Task';
        btn.style.display='';
        btn.disabled=false;
    } else if(normalizeStatusValue(record.status)==='in progress'){
        btn.textContent='Mark as Completed';
        btn.style.display='';
        btn.disabled=false;
        weightRow.style.display='';
        weightInput.value=record.weight||'';
        updateCalculatedPrice(record.id);
    } else{
        btn.style.display='none';
    }

    modal.setAttribute('data-current-id',record.id);
    modal.classList.add('open');
}

weightInput.addEventListener('input',()=>{
    const modal=document.getElementById('pickup-detail-modal');
    const pickupId=modal.getAttribute('data-current-id');
    updateCalculatedPrice(pickupId);
});

function updateCalculatedPrice(pickupId){
    const pickup=window.__PICKUP_DATA.find(p=>p.id==pickupId);
    if(!pickup||!pickup.wastes||pickup.wastes.length===0) return;

    const totalWeight=parseFloat(weightInput.value);
    if(isNaN(totalWeight)){
        calculatedPriceEl.textContent='0.00';
        breakdownEl.innerHTML='';
        return;
    }

    const sumQty=pickup.wastes.reduce((sum,w)=>sum+parseFloat(w.quantity||0),0)||1;
    let totalPrice=0;

    const html=pickup.wastes.map(w=>{
        const scaled=parseFloat(w.quantity)*totalWeight/sumQty;
        const amount=scaled*parseFloat(w.price_per_unit);
        totalPrice+=amount;
        return `${w.category_name}: ${scaled.toFixed(2)} ${w.unit} → ₹${amount.toFixed(2)}`;
    }).join('<br>');

    calculatedPriceEl.textContent=totalPrice.toFixed(2);
    breakdownEl.innerHTML=html;
}

function previewWeight(){
    const modal=document.getElementById('pickup-detail-modal');
    const pickupId=modal.getAttribute('data-current-id');
    updateCalculatedPrice(pickupId);
}

async function enterWeight(){
    const modal=document.getElementById('pickup-detail-modal');
    const pickupId=modal.getAttribute('data-current-id');
    const weight=parseFloat(weightInput.value);
    if(isNaN(weight)||weight<=0){ alert('Enter valid weight'); return; }

    const btn=document.getElementById('taskActionBtn');
    btn.disabled=true;

    try{
        const res=await fetch(`/api/collector/pickup-requests/${pickupId}/enter-weight`,{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
            body:JSON.stringify({weight})
        });
        const data=await res.json();
        if(data.breakdown){
            calculatedPriceEl.textContent=data.totalPrice.toFixed(2);
            breakdownEl.innerHTML=data.breakdown.map(b=>`${b.category_name}: ${b.quantity} ${b.unit} → ₹${b.amount}`).join('<br>');
            alert('Weight saved successfully');
        }
    } catch(err){ alert(err.message||'Failed to save weight'); }
    btn.disabled=false;
}

async function enterWeight() {
    const modal = document.getElementById('pickup-detail-modal');
    const pickupId = modal.getAttribute('data-current-id');
    const weight = parseFloat(weightInput.value);
    const btn = document.getElementById('taskActionBtn');

    if (isNaN(weight) || weight <= 0) {
        alert("Please enter a valid weight greater than 0");
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
        const res = await fetch(`/api/collector/pickup-requests/${pickupId}/weight`, {
            method: 'PUT',
            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ weight })
        });
        const data = await res.json();

        if (!res.ok) throw new Error(data.message || "Failed to save weight");

        // Update modal with calculated DB values
        calculatedPriceEl.textContent = data.totalPrice.toFixed(2);
        breakdownEl.innerHTML = data.breakdown.map(w => 
            `${w.category_name}: ${w.quantity} ${w.unit} → ₹${w.amount.toFixed(2)}`
        ).join('<br>');

        // Update weight in pickup data
        const pickup = window.__PICKUP_DATA.find(p=>p.id==pickupId);
        if(pickup){
            pickup.weight = data.totalWeight;
            pickup.price = data.totalPrice;
            pickup.wastes = data.breakdown;
        }

        btn.disabled = false;
        btn.textContent = 'Mark as Completed';
    } catch(err) {
        alert(err.message || "Failed to save weight");
        btn.disabled = false;
        btn.textContent = 'Mark as Completed';
    }

async function startOrCompleteTask(){
    const modal=document.getElementById('pickup-detail-modal');
    const pickupId=modal.getAttribute('data-current-id');
    const pickup=window.__PICKUP_DATA.find(p=>p.id==pickupId);
    const btn=document.getElementById('taskActionBtn');

    let nextStatus='';
    const curr=normalizeStatusValue(pickup.status);

    if(curr==='assigned') nextStatus='in_progress';
    else if(curr==='in progress') nextStatus='completed';
    else return;

    btn.disabled=true;
    btn.textContent='Updating...';

    const payload={status:nextStatus};
    if(nextStatus==='completed'){
        payload.weight=parseFloat(weightInput.value);
    }

    try{
        const res=await fetch(`/api/collector/pickup-requests/${pickupId}/status`,{
            method:'PUT',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
            body:JSON.stringify(payload)
        });
        const data=await res.json();
        pickup.status=normalizeStatusValue(data.data?.status||nextStatus);
        document.querySelector(`tr[data-id="${pickupId}"] td:nth-child(6)`).innerHTML=getStatusBadge(pickup.status);
        modal.querySelector('.pd-status').textContent=pickup.status;

        if(pickup.status==='in progress'){
            document.getElementById('weight-entry-row').style.display='';
            btn.textContent='Mark as Completed';
            btn.disabled=false;
        } else if(pickup.status==='completed'){
            btn.style.display='none';
            document.getElementById('weight-entry-row').style.display='none';
        }
    }catch(err){
        alert(err.message||'Failed to update status');
        btn.disabled=false;
        btn.textContent=(nextStatus==='in_progress')?'Mark as Completed':'Start Task';
    }
}
</script>
