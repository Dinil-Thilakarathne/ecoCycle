<?php

require __DIR__ . '/../vendor/autoload.php';

use Models\Role;
use Models\User;

echo "Running DB seed...\n";

$roleModel = new Role();
$userModel = new User();

// Create tables
try {
    echo "Creating roles table... ";
    $roleModel->createTableIfNotExists();
    echo "OK\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}

try {
    echo "Creating users table... ";
    $userModel->createTableIfNotExists();
    echo "OK\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}

// Seed roles
$roles = [
    ['name' => 'admin', 'label' => 'Administrator'],
    ['name' => 'customer', 'label' => 'Customer'],
    ['name' => 'collector', 'label' => 'Collector'],
    ['name' => 'company', 'label' => 'Company']
];

try {
    echo "Seeding roles... ";
    $roleModel->seed($roles);
    echo "OK\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}

// Insert demo users (map role names to ids)
$db = new \Core\Database();
$rows = $db->fetchAll("SELECT id, name FROM roles");
$roleMap = [];
foreach ($rows as $r) {
    $roleMap[$r['name']] = $r['id'];
}

$demoUsers = [
    ['email' => 'admin@ecocycle.com', 'username' => 'admin', 'password' => 'admin123', 'role_id' => $roleMap['admin'] ?? null],
    ['email' => 'customer@ecocycle.com', 'username' => 'customer1', 'password' => 'customer123', 'role_id' => $roleMap['customer'] ?? null],
    ['email' => 'collector@ecocycle.com', 'username' => 'collector1', 'password' => 'collector123', 'role_id' => $roleMap['collector'] ?? null],
    ['email' => 'company@ecocycle.com', 'username' => 'company1', 'password' => 'company123', 'role_id' => $roleMap['company'] ?? null],
];

foreach ($demoUsers as $du) {
    try {
        // check exists
        $exists = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$du['email']]);
        if ($exists) {
            echo "User {$du['email']} exists, skipping\n";
            continue;
        }
        $id = $userModel->createUser($du);
        echo $id ? "Created user {$du['email']} (id={$id})\n" : "Failed to create {$du['email']}\n";
    } catch (Exception $e) {
        echo "ERR creating user {$du['email']}: " . $e->getMessage() . "\n";
    }
}

echo "Seed complete.\n";
