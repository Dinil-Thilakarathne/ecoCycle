<?php


// Sample data - in real application, this would come from database
$pickup_requests = [
    [
        'id' => 1,
        'type' => 'Bulk Pickup',
        'status' => 'pending',
        'description' => 'Old TV and computer monitor',
        'requested_date' => '2024-01-10',
        'scheduled_date' => '2024-01-16',
        'waste_type' => 'Electronics',
        'weight' => '25kg'
    ],
    [
        'id' => 2,
        'type' => 'Special Pickup',
        'status' => 'confirmed',
        'description' => 'Paint cans and batteries',
        'requested_date' => '2024-01-08',
        'scheduled_date' => '2024-01-15',
        'waste_type' => 'Hazardous',
        'weight' => '5kg'
    ],
    [
        'id' => 3,
        'type' => 'Missed Pickup',
        'status' => 'completed',
        'description' => 'Regular pickup was missed on Jan 8',
        'requested_date' => '2024-01-09',
        'scheduled_date' => '2024-01-12',
        'waste_type' => 'Regular',
        'weight' => '15kg'
    ]
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'new_request':
                // Handle new request
                $new_request = [
                    'id' => count($pickup_requests) + 1,
                    'type' => $_POST['pickup_type'] ?? '',
                    'status' => 'pending',
                    'description' => $_POST['description'] ?? '',
                    'requested_date' => date('Y-m-d'),
                    'scheduled_date' => $_POST['preferred_date'] ?? '',
                    'waste_type' => $_POST['waste_type'] ?? '',
                    'weight' => $_POST['weight'] ?? ''
                ];
                $pickup_requests[] = $new_request;
                $success_message = "Pickup request submitted successfully!";
                break;
                
            case 'edit_request':
                // Handle edit request
                $request_id = $_POST['request_id'] ?? 0;
                foreach ($pickup_requests as &$request) {
                    if ($request['id'] == $request_id) {
                        $request['description'] = $_POST['description'] ?? $request['description'];
                        $request['scheduled_date'] = $_POST['scheduled_date'] ?? $request['scheduled_date'];
                        $request['waste_type'] = $_POST['waste_type'] ?? $request['waste_type'];
                        $request['weight'] = $_POST['weight'] ?? $request['weight'];
                        break;
                    }
                }
                $success_message = "Request updated successfully!";
                break;
                
            case 'cancel_request':
                // Handle cancel request
                $request_id = $_POST['request_id'] ?? 0;
                foreach ($pickup_requests as $key => $request) {
                    if ($request['id'] == $request_id && $request['status'] === 'pending') {
                        unset($pickup_requests[$key]);
                        $success_message = "Request cancelled successfully!";
                        break;
                    }
                }
                break;
        }
    }
}

// Filter requests by status if specified
$filter = $_GET['filter'] ?? 'all';
$filtered_requests = $pickup_requests;

if ($filter !== 'all') {
    $filtered_requests = array_filter($pickup_requests, function($request) use ($filter) {
        return $request['status'] === $filter;
    });
}

function getStatusClass($status) {
    switch ($status) {
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'completed': return 'status-completed';
        default: return 'status-pending';
    }
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>



    <div class="dashboard-page">
        <div class="page-header" style="margin-bottom:2rem;">
            <div class="header-content">
                
                <p><b>Manage special, bulk, and missed pickup requests</b></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showNewRequestForm()">+ New Request</button>
            </div>
        </div>

        <!-- Feature Cards (Stats) -->
        <?php
        $pickupStats = [
            [
                'title' => 'Total Requests',
                'value' => count($pickup_requests),
                'icon' => 'fa-solid fa-truck',
                'subtitle' => 'All time',
            ],
            [
                'title' => 'Pending',
                'value' => count(array_filter($pickup_requests, fn($r) => $r['status'] === 'pending')),
                'icon' => 'fa-solid fa-hourglass-half',
                'subtitle' => 'Awaiting confirmation',
            ],
            [
                'title' => 'Confirmed',
                'value' => count(array_filter($pickup_requests, fn($r) => $r['status'] === 'confirmed')),
                'icon' => 'fa-solid fa-check-circle',
                'subtitle' => 'Scheduled',
            ],
            [
                'title' => 'Completed',
                'value' => count(array_filter($pickup_requests, fn($r) => $r['status'] === 'completed')),
                'icon' => 'fa-solid fa-clipboard-check',
                'subtitle' => 'Finished',
            ],
        ];
        ?>
        <div class="stats-grid" style="margin-bottom:2.5rem;">
            <?php foreach ($pickupStats as $stat): ?>
                <div class="feature-card">
                    <div class="feature-card__header">
                        <h3 class="feature-card__title">
                            <?= htmlspecialchars($stat['title']) ?>
                        </h3>
                        <div class="feature-card__icon">
                            <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                        </div>
                    </div>
                    <p class="feature-card__body">
                        <?= htmlspecialchars($stat['value']) ?>
                    </p>
                    <div class="feature-card__footer">
                        <span class="tag success"><?= htmlspecialchars($stat['subtitle']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert" style="margin-bottom:1.5rem;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons" style="margin-bottom:2rem;">
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline'; ?>">All Requests</a>
            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-outline'; ?>">Pending</a>
            <a href="?filter=confirmed" class="btn <?php echo $filter === 'confirmed' ? 'btn-primary' : 'btn-outline'; ?>">Confirmed</a>
            <a href="?filter=completed" class="btn <?php echo $filter === 'completed' ? 'btn-primary' : 'btn-outline'; ?>">Completed</a>
        </div>

        <div class="table-container" style="overflow-x:auto;">
            <table class="data-table" style="min-width:900px;">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Requested</th>
                        <th>Scheduled</th>
                        <th>Waste Type</th>
                        <th>Weight</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filtered_requests)): ?>
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
                        <?php foreach ($filtered_requests as $request): ?>
                            <tr>
                                <td>#<?php echo $request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['type']); ?></td>
                                <td><?php echo htmlspecialchars($request['description']); ?></td>
                                <td><?php echo formatDate($request['requested_date']); ?></td>
                                <td><?php echo formatDate($request['scheduled_date']); ?></td>
                                <td><?php echo htmlspecialchars($request['waste_type']); ?></td>
                                <td><?php echo htmlspecialchars($request['weight']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($request['status']); ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <button onclick="editRequest(<?php echo $request['id']; ?>)" class="action-btn view">Edit</button>
                                        <button onclick="cancelRequest(<?php echo $request['id']; ?>)" class="action-btn delete">Cancel</button>
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

    <!-- New Request Modal -->

    <div id="newRequestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Pickup Request</h2>
                <span class="close" onclick="hideNewRequestForm()">&times;</span>
            </div>
            <form method="POST" class="request-form">
                <input type="hidden" name="action" value="new_request">
                <div class="form-group">
                    <label for="pickup_type">Pickup Type</label>
                    <select name="pickup_type" id="pickup_type" required>
                        <option value="">Select pickup type</option>
                        <option value="Bulk Pickup">Bulk Pickup</option>
                        <option value="Special Pickup">Special Pickup</option>
                        <option value="Missed Pickup">Missed Pickup</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3" required placeholder="Describe the items for pickup"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="waste_type">Waste Type</label>
                        <select name="waste_type" id="waste_type" required>
                            <option value="">Select type</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Hazardous">Hazardous</option>
                            <option value="Regular">Regular</option>
                            <option value="Recyclable">Recyclable</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="weight">Estimated Weight</label>
                        <input type="text" name="weight" id="weight" placeholder="e.g., 10kg">
                    </div>
                </div>
                <div class="form-group">
                    <label for="preferred_date">Preferred Date</label>
                    <input type="date" name="preferred_date" id="preferred_date" required>
                </div>
                <div class="form-actions" style="display:flex;justify-content:flex-end;gap:1rem;">
                    <button type="button" onclick="hideNewRequestForm()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Request Modal -->

    <div id="editRequestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Request</h2>
                <span class="close" onclick="hideEditRequestForm()">&times;</span>
            </div>
            <form method="POST" class="request-form" id="editForm">
                <input type="hidden" name="action" value="edit_request">
                <input type="hidden" name="request_id" id="edit_request_id">
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea name="description" id="edit_description" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_waste_type">Waste Type</label>
                        <select name="waste_type" id="edit_waste_type" required>
                            <option value="Electronics">Electronics</option>
                            <option value="Hazardous">Hazardous</option>
                            <option value="Regular">Regular</option>
                            <option value="Recyclable">Recyclable</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_weight">Weight</label>
                        <input type="text" name="weight" id="edit_weight">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_scheduled_date">Scheduled Date</label>
                    <input type="date" name="scheduled_date" id="edit_scheduled_date" required>
                </div>
                <div class="form-actions" style="display:flex;justify-content:flex-end;gap:1rem;">
                    <button type="button" onclick="hideEditRequestForm()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showNewRequestForm() {
            document.getElementById('newRequestModal').style.display = 'block';
            // Set minimum date to tomorrow
            document.getElementById('preferred_date').min = new Date(Date.now() + 86400000).toISOString().split('T')[0];
        }

        function hideNewRequestForm() {
            document.getElementById('newRequestModal').style.display = 'none';
        }

        function editRequest(id) {
            // In real application, you would fetch request data via AJAX
            document.getElementById('editRequestModal').style.display = 'block';
            document.getElementById('edit_request_id').value = id;
        }

        function hideEditRequestForm() {
            document.getElementById('editRequestModal').style.display = 'none';
        }

        function cancelRequest(id) {
            if (confirm('Are you sure you want to cancel this request?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_request">
                    <input type="hidden" name="request_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
