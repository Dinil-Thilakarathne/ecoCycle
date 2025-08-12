<?php
// Sample vehicle data (in a real application, this would come from your database/models)
$vehicles = [
    [
        'id' => 'VH001',
        'plateNumber' => 'ECO-001',
        'type' => 'Pickup Truck',
        'capacity' => 2000,
        'status' => 'available',
        'lastMaintenance' => '2024-01-01',
        'nextMaintenance' => '2024-04-01',
    ],
    [
        'id' => 'VH002',
        'plateNumber' => 'ECO-002',
        'type' => 'Van',
        'capacity' => 1500,
        'status' => 'in-use',
        'lastMaintenance' => '2023-12-15',
        'nextMaintenance' => '2024-03-15',
    ],
    [
        'id' => 'VH003',
        'plateNumber' => 'ECO-003',
        'type' => 'Pickup Truck',
        'capacity' => 2000,
        'status' => 'maintenance',
        'lastMaintenance' => '2024-01-10',
        'nextMaintenance' => '2024-04-10',
    ],
    [
        'id' => 'VH004',
        'plateNumber' => 'ECO-004',
        'type' => 'Small Truck',
        'capacity' => 3000,
        'status' => 'available',
        'lastMaintenance' => '2023-11-20',
        'nextMaintenance' => '2024-02-20',
    ],
    [
        'id' => 'VH005',
        'plateNumber' => 'ECO-005',
        'type' => 'Van',
        'capacity' => 1200,
        'status' => 'in-use',
        'lastMaintenance' => '2024-01-05',
        'nextMaintenance' => '2024-04-05',
    ],
];

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
    return $v['status'] === 'available';
}));
$inMaintenanceVehicles = count(array_filter($vehicles, function ($v) {
    return $v['status'] === 'maintenance';
}));
$inUseVehicles = count(array_filter($vehicles, function ($v) {
    return $v['status'] === 'in-use';
}));
?>

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

    <!-- Statistics Cards -->
    <div class="dashboard-grid feature-cards">
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__info">
                    <h3 class="feature-card__title">Total Vehicles</h3>
                </div>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-truck"></i>
                </div>
            </div>
            <div class="feature-card__content">
                <div class="metric-value"><?= $totalVehicles ?></div>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__info">
                    <h3 class="feature-card__title">Available</h3>
                </div>
                <div class="feature-card__icon success">
                    <i class="fa-solid fa-truck"></i>
                </div>
            </div>
            <div class="feature-card__content">
                <div class="metric-value"><?= $availableVehicles ?></div>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__info">
                    <h3 class="feature-card__title">In Use</h3>
                </div>
                <div class="feature-card__icon warning">
                    <i class="fa-solid fa-truck-moving"></i>
                </div>
            </div>
            <div class="feature-card__content">
                <div class="metric-value"><?= $inUseVehicles ?></div>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__info">
                    <h3 class="feature-card__title">In Maintenance</h3>
                </div>
                <div class="feature-card__icon danger">
                    <i class="fa-solid fa-wrench"></i>
                </div>
            </div>
            <div class="feature-card__content">
                <div class="metric-value"><?= $inMaintenanceVehicles ?></div>
            </div>
        </div>
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
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($vehicle['id']) ?></td>
                                <td><?= htmlspecialchars($vehicle['plateNumber']) ?></td>
                                <td><?= htmlspecialchars($vehicle['type']) ?></td>
                                <td><?= number_format($vehicle['capacity']) ?></td>
                                <td><?= getStatusBadge($vehicle['status']) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-calendar-days"
                                            style="color: var(--text-muted); font-size: 14px;"></i>
                                        <span><?= htmlspecialchars($vehicle['lastMaintenance']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-calendar-days"
                                            style="color: var(--text-muted); font-size: 14px;"></i>
                                        <span><?= htmlspecialchars($vehicle['nextMaintenance']) ?></span>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Vehicle management functions
    function addVehicle() {
        console.log('Adding new vehicle');
        alert('Add Vehicle functionality would open a form to register a new vehicle with details like plate number, type, capacity, etc.');

        // In a real application, this would redirect to an add vehicle form or open a modal:
        // window.location.href = '/admin/vehicles/add';
    }

    function scheduleMaintenance(vehicleId) {
        console.log(`Scheduling maintenance for vehicle ${vehicleId}`);
        alert(`Scheduling maintenance for vehicle ${vehicleId}. This would open a maintenance scheduling form with available dates and service types.`);

        // In a real application, you would open a maintenance scheduling modal or form:
        /*
        fetch('/api/vehicles/schedule-maintenance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                vehicleId: vehicleId,
                scheduledDate: selectedDate,
                maintenanceType: selectedType
            })
        });
        */
    }

    function viewVehicleDetails(vehicleId) {
        console.log(`Viewing details for vehicle ${vehicleId}`);
        alert(`Viewing details for vehicle ${vehicleId}. This would show comprehensive vehicle information including maintenance history, current assignments, and performance metrics.`);

        // In a real application, this would redirect to a vehicle details page:
        // window.location.href = `/admin/vehicles/${vehicleId}`;
    }

    function editVehicle(vehicleId) {
        console.log(`Editing vehicle ${vehicleId}`);
        alert(`Editing vehicle ${vehicleId}. This would open an edit form where you can update vehicle information like capacity, type, plate number, etc.`);

        // In a real application, this would redirect to an edit form or open a modal:
        // window.location.href = `/admin/vehicles/${vehicleId}/edit`;
    }

    // Additional functionality for future implementation
    function assignVehicle(vehicleId, collectorId) {
        console.log(`Assigning vehicle ${vehicleId} to collector ${collectorId}`);
        // Implementation for assigning vehicles to collectors
    }

    function markVehicleAvailable(vehicleId) {
        console.log(`Marking vehicle ${vehicleId} as available`);
        // Implementation for updating vehicle status to available
    }

    function generateMaintenanceReport(vehicleId) {
        console.log(`Generating maintenance report for vehicle ${vehicleId}`);
        // Implementation for generating maintenance reports
    }
</script>