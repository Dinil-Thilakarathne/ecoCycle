<?php
// Centralized dummy data
$dummy = require base_path('config/dummy.php');
$pickupRequests = $dummy['pickup_requests'];
$collectors = $dummy['collectors'];
$timeSlots = $dummy['time_slots'];

// Get selected time slot from URL parameter
$selectedTimeSlot = $_GET['time_slot'] ?? 'all';

// Filter requests by time slot if selected
$filteredRequests = $pickupRequests;
if ($selectedTimeSlot !== 'all') {
    $filteredRequests = array_filter($pickupRequests, function ($request) use ($selectedTimeSlot) {
        return $request['timeSlot'] === $selectedTimeSlot;
    });
}

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
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($request['id']) ?></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-user"></i>
                                        <?= htmlspecialchars($request['customerName']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-map-marker-alt"></i>
                                        <span class="cell-truncate"><?= htmlspecialchars($request['address']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="badge-group">
                                        <?php foreach ($request['wasteCategories'] as $category): ?>
                                            <div class="tag secondary"><?= htmlspecialchars($category) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-clock"></i>
                                        <?= htmlspecialchars($request['timeSlot']) ?>
                                    </div>
                                </td>
                                <td><?= getStatusBadge($request['status']) ?></td>
                                <td>
                                    <?php if ($request['collectorName']): ?>
                                        <?= htmlspecialchars($request['collectorName']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--neutral-500);">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <div class="form-select">
                                            <select onchange="assignCollector('<?= $request['id'] ?>', this.value)"
                                                style="width: 140px;">
                                                <option value="">Assign Collector</option>
                                                <?php foreach ($collectors as $collector): ?>
                                                    <option value="<?= htmlspecialchars($collector['id']) ?>">
                                                        <?= htmlspecialchars($collector['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm" onclick="viewDetails('<?= $request['id'] ?>')">
                                            View Details
                                        </button>
                                    <?php endif; ?>
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

    function viewDetails(pickupId) {
        // In a real application, you would navigate to a detail page or open a modal
        console.log(`Viewing details for pickup ${pickupId}`);
        alert(`Viewing details for pickup ${pickupId}. In a real application, this would show detailed information.`);

        // You could redirect to a details page:
        // window.location.href = `/admin/pickup-requests/${pickupId}`;
    }
</script>