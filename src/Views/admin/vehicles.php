<?php
// Sample vehicle data (in a real application, this would come from your database/models)
$vehicles = [
    [
        'id' => 'VH001',
        'plateNumber' => 'ABC-1234',
        'type' => 'Pickup Truck',
        'capacity' => 2000,
        'status' => 'available',
        'lastMaintenance' => '2024-01-01',
        'nextMaintenance' => '2024-04-01',
    ],
    [
        'id' => 'VH002',
        'plateNumber' => 'XYZ-5678',
        'type' => 'Van',
        'capacity' => 1500,
        'status' => 'in-use',
        'lastMaintenance' => '2023-12-15',
        'nextMaintenance' => '2024-03-15',
    ],
    [
        'id' => 'VH003',
        'plateNumber' => 'DEF-9012',
        'type' => 'Pickup Truck',
        'capacity' => 2000,
        'status' => 'maintenance',
        'lastMaintenance' => '2024-01-10',
        'nextMaintenance' => '2024-04-10',
    ],
    [
        'id' => 'VH004',
        'plateNumber' => 'GHI-3456',
        'type' => 'Small Truck',
        'capacity' => 3000,
        'status' => 'available',
        'lastMaintenance' => '2023-11-20',
        'nextMaintenance' => '2024-02-20',
    ],
    [
        'id' => 'VH005',
        'plateNumber' => 'JKL-7890',
        'type' => 'Van',
        'capacity' => 1200,
        'status' => 'in-use',
        'lastMaintenance' => '2024-01-05',
        'nextMaintenance' => '2024-04-05',
    ],
];

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
    // Vehicle plate number validation
    function validatePlateNumber(plateNumber) {
        // Pattern: XXX-XXXX (3 capital letters - 4 digits)
        const pattern = /^[A-Z]{3}-[0-9]{4}$/;
        return pattern.test(plateNumber);
    }

    // Format plate number as user types
    function formatPlateNumberInput(input) {
        let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');

        if (value.length >= 3) {
            value = value.substring(0, 3) + '-' + value.substring(3, 7);
        }

        input.value = value;

        // Show validation feedback
        const isValid = validatePlateNumber(value);
        if (value.length >= 7) {
            if (isValid) {
                input.style.borderColor = '#16a34a';
                input.style.backgroundColor = '#f0fdf4';
            } else {
                input.style.borderColor = '#dc2626';
                input.style.backgroundColor = '#fef2f2';
            }
        } else {
            input.style.borderColor = '';
            input.style.backgroundColor = '';
        }
    }

    // Vehicle management functions
    function addVehicle() {
        console.log('Adding new vehicle');

        // Create a simple modal for adding vehicle with plate number validation
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        `;

        modal.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
                <h3 style="margin: 0 0 1rem 0; color: #1f2937;">Add New Vehicle</h3>
                <form id="addVehicleForm">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Plate Number:</label>
                        <input type="text" id="plateNumber" name="plateNumber" maxlength="8" 
                               placeholder="ABC-1234" required
                               style="width: 100%; padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 4px; font-family: monospace;"
                               oninput="formatPlateNumberInput(this)">
                        <small style="color: #6b7280; display: block; margin-top: 0.25rem;">
                            Format: 3 capital letters followed by 4 numbers (e.g., ABC-1234)
                        </small>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Vehicle Type:</label>
                        <select name="type" required style="width: 100%; padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 4px;">
                            <option value="">Select Type</option>
                            <option value="Van">Van</option>
                            <option value="Pickup Truck">Pickup Truck</option>
                            <option value="Small Truck">Small Truck</option>
                            <option value="Large Truck">Large Truck</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Capacity (kg):</label>
                        <input type="number" name="capacity" min="500" max="10000" required
                               style="width: 100%; padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 4px;">
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <button type="button" onclick="closeModal()" 
                                style="padding: 0.5rem 1rem; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="submit" 
                                style="padding: 0.5rem 1rem; background: #16a34a; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Add Vehicle
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // Handle form submission
        document.getElementById('addVehicleForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const plateNumber = document.getElementById('plateNumber').value;

            if (!validatePlateNumber(plateNumber)) {
                alert('Please enter a valid plate number in format: XXX-XXXX (3 capital letters followed by 4 numbers)');
                return;
            }

            alert(`Vehicle with plate number ${plateNumber} would be added to the system.`);
            closeModal();
        });

        // Close modal function
        window.closeModal = function () {
            document.body.removeChild(modal);
        };

        // Close on outside click
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
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