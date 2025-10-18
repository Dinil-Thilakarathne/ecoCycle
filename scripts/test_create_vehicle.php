<?php

require __DIR__ . '/../vendor/autoload.php';

use Models\Vehicle;

// Quick CLI test to create a vehicle record using the Model.
// Adjust values if needed. This will use the app's database config.

$vehicleModel = new Vehicle();

$data = [
    'plate_number' => 'TST-0001',
    'type' => 'truck',
    'capacity' => 1000,
    'status' => 'available',
    'last_maintenance' => date('Y-m-d', strtotime('-30 days')),
    'next_maintenance' => date('Y-m-d', strtotime('+30 days')),
    'notes' => 'Created from CLI test script'
];

try {
    $created = $vehicleModel->create($data);
    echo "Created vehicle:\n";
    print_r($created);
} catch (\Exception $e) {
    echo "Error creating vehicle: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

