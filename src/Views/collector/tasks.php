<!--<div class="header">
  <div>
    <h1>Daily Tasks</h1>
    <div class="sub-header">5 tasks for today <span class="status-tag">Active</span></div>
  </div>
  <div class="search-filter">
    <input type="text" class="search-box" placeholder="Search tasks, customers, or locations...">
    <select class="filter">
      <option>All Tasks</option>
    </select>
  </div>
</div>


<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">Mike Wilson</div>
      <div class="task-address">789 Elm Road, Downtown</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Metal</div>
    <div class="detail-box">Weight: 8kg</div>
    <div class="detail-box">Time: 02:00 PM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 763975639 
    <i class="fa-solid fa-location-dot"></i> 2.3 km 
    <i class="fa-solid fa-clock"></i> 20 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Large metal items, need assistance
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('789 Elm Road, Downtown')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>


<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">Emma Davis</div>
      <div class="task-address">321 Maple Street, Uptown</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Glass</div>
    <div class="detail-box">Weight: 12kg</div>
    <div class="detail-box">Time: 11:30 AM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 719845236 
    <i class="fa-solid fa-location-dot"></i> 3.1 km 
    <i class="fa-solid fa-clock"></i> 25 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Fragile items, handle with care
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('321 Maple Street, Uptown')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>


<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">John Smith</div>
      <div class="task-address">123 Oak Street, Central</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Plastic</div>
    <div class="detail-box">Weight: 15kg</div>
    <div class="detail-box">Time: 09:00 AM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 701234567 
    <i class="fa-solid fa-location-dot"></i> 1.8 km 
    <i class="fa-solid fa-clock"></i> 15 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Multiple plastic bags
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('123 Oak Street, Central')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>


<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">Sophia Brown</div>
      <div class="task-address">456 Pine Avenue, Westside</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Paper</div>
    <div class="detail-box">Weight: 20kg</div>
    <div class="detail-box">Time: 01:15 PM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 753214698 
    <i class="fa-solid fa-location-dot"></i> 4.5 km 
    <i class="fa-solid fa-clock"></i> 35 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Heavy bundle, may require vehicle space
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('456 Pine Avenue, Westside')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>


<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">David Lee</div>
      <div class="task-address">654 Cedar Lane, Eastend</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: E-Waste</div>
    <div class="detail-box">Weight: 6kg</div>
    <div class="detail-box">Time: 04:45 PM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 768523741 
    <i class="fa-solid fa-location-dot"></i> 5.2 km 
    <i class="fa-solid fa-clock"></i> 40 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Old electronic parts
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('654 Cedar Lane, Eastend')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>

<script>
  function startTask(url) {
    alert("Your journey has started! 🚀");
    window.location.href = url;
  }

  function navigateToMap(address) {
    const encodedAddress = encodeURIComponent(address);
    const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodedAddress}`;
    window.open(mapsUrl, '_blank');
  }
</script>

</body>
</html>-->
<?php
// If no dummy data found, provide fallback example data
if (empty($pickupRequests)) {
    $pickupRequests = [
        [
            'id' => 'PK001',
            'customerName' => 'Ramesh Perera',
            'address' => 'No. 45, Temple Road, Kandy',
            'wasteCategories' => ['Plastic', 'Paper'],
            'timeSlot' => '08:00 AM - 10:00 AM',
            'status' => 'assigned',
            'collectorId' => 'C001',
        ],
        [
            'id' => 'PK002',
            'customerName' => 'Anjali Silva',
            'address' => '22, Palm Grove, Colombo 03',
            'wasteCategories' => ['Glass', 'Organic'],
            'timeSlot' => '10:00 AM - 12:00 PM',
            'status' => 'in progress',
            'collectorId' => 'C001',
        ],
        [
            'id' => 'PK003',
            'customerName' => 'Nuwan Jayasuriya',
            'address' => '15, Green Street, Galle',
            'wasteCategories' => ['Metal', 'E-Waste'],
            'timeSlot' => '12:00 PM - 02:00 PM',
            'status' => 'completed',
            'collectorId' => 'C001',
        ],
        [
            'id' => 'PK004',
            'customerName' => 'Kavindi Fernando',
            'address' => '78, Lotus Avenue, Matara',
            'wasteCategories' => ['Plastic', 'Organic'],
            'timeSlot' => '02:00 PM - 04:00 PM',
            'status' => 'assigned',
            'collectorId' => 'C001',
        ],
    ];
}



// Centralized dummy data
$dummy = require base_path('config/dummy.php');
$pickupRequests = $dummy['pickup_requests'];
$collectors = $dummy['collectors'];
$timeSlots = $dummy['time_slots'];

// Assume collector ID is retrieved from session (e.g., after login)
$currentCollectorId = $_SESSION['collector_id'] ?? 'C001'; // demo default

// Filter only the pickups assigned to this collector
$assignedRequests = array_filter($pickupRequests, function ($r) use ($currentCollectorId) {
    return isset($r['collectorId']) && $r['collectorId'] === $currentCollectorId;
});

// Optional: Filter by time slot
$selectedTimeSlot = $_GET['time_slot'] ?? 'all';
if ($selectedTimeSlot !== 'all') {
    $assignedRequests = array_filter($assignedRequests, function ($r) use ($selectedTimeSlot) {
        return $r['timeSlot'] === $selectedTimeSlot;
    });
}
?>

<script>
    // Store assigned pickups for this collector
    window.__PICKUP_DATA = <?php echo json_encode(array_values($assignedRequests), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>

<!-- Pickup Detail Modal -->
<div id="pickup-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close">&times;</button>
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
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}

// Show details
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

    // Update button text depending on current status
    const btn = document.getElementById('taskActionBtn');
    if (record.status === 'assigned') {
        btn.textContent = 'Start Task';
        btn.style.display = '';
    } else if (record.status === 'in progress') {
        btn.textContent = 'Mark as Completed';
        btn.style.display = '';
    } else {
        btn.style.display = 'none'; // hide if completed
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

        // Update in table
        const row = document.querySelector(`tr[data-id="${pickupId}"]`);
        if (row) {
            const statusCell = row.querySelectorAll('td')[5];
            if (statusCell) statusCell.innerHTML = getStatusBadge(next);
        }

        // Update modal
        modal.querySelector('.pd-status').textContent = next;

        // Update button label or hide when done
        const btn = document.getElementById('taskActionBtn');
        if (next === 'in progress') btn.textContent = 'Mark as Completed';
        else if (next === 'completed') btn.style.display = 'none';
    }
}
</script>

<?php
function getStatusBadge($status)
{
    switch (strtolower($status)) {
        case 'pending': return '<div class="tag pending">Pending</div>';
        case 'assigned': return '<div class="tag assigned">Assigned</div>';
        case 'in progress': return '<div class="tag inprogress">In Progress</div>';
        case 'completed': return '<div class="tag completed">Completed</div>';
        default: return '<div class="tag">'.htmlspecialchars($status).'</div>';
    }
}
?>

<div class="page-header">
    <div class="page-header__content">
        <h2 class="page-header__title">My Assigned Pickups</h2>
        <p class="page-header__description">Tasks assigned to you</p>
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

<div class="activity-card">
    <div class="activity-card__header">
        <h3 class="activity-card__title">
            <i class="fa-solid fa-box" style="margin-right:8px;"></i>
            My Tasks
        </h3>
        <p class="activity-card__description">
            <?= count($assignedRequests) ?> assigned pickups
        </p>
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
                    <?php foreach ($assignedRequests as $r): ?>
                        <tr data-id="<?= htmlspecialchars($r['id']) ?>">
                            <td><?= htmlspecialchars($r['id']) ?></td>
                            <td><?= htmlspecialchars($r['customerName']) ?></td>
                            <td><?= htmlspecialchars($r['address']) ?></td>
                            <td><?= htmlspecialchars(implode(', ', $r['wasteCategories'])) ?></td>
                            <td><?= htmlspecialchars($r['timeSlot']) ?></td>
                            <td><?= getStatusBadge($r['status']) ?></td>
                            <td>
                                <button class="icon-button" onclick="viewDetails(this, '<?= $r['id'] ?>')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($assignedRequests)): ?>
                        <tr><td colspan="7" style="text-align:center;color:gray;">No tasks assigned.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterByTimeSlot() {
    const select = document.getElementById('timeSlotFilter');
    const slot = select.value;
    const url = new URL(window.location);
    if (slot === 'all') url.searchParams.delete('time_slot');
    else url.searchParams.set('time_slot', slot);
    window.location.href = url.toString();
}
</script>
