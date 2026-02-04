<?php

/**
 * Test Email Template Rendering
 * 
 * This script tests that email templates can be rendered correctly with sample data.
 * Run: php scripts/test-mail-templates.php
 */

// Bootstrap the application
$basePath = dirname(__DIR__);

// Load environment
require_once $basePath . '/src/Core/Environment.php';
Core\Environment::load($basePath);

// Load helpers
require_once $basePath . '/src/helpers.php';

// Load configuration system
require_once $basePath . '/src/Core/Config.php';

// Load mail classes
require_once $basePath . '/src/Core/Mail/SmtpMailer.php';
require_once $basePath . '/src/Core/Mail/Mailer.php';

// Load ONLY the mail configuration file
Core\Config::load($basePath . '/config/mail.php', 'mail');

echo "=== Email Template Rendering Test ===\n\n";

// Test templates
$templates = [
    'notification' => [
        'username' => 'John Doe',
        'title' => 'Test Notification',
        'message' => 'This is a test notification message.',
        'action_url' => 'http://localhost:8080',
        'action_text' => 'View Details'
    ],
    'pickup-request-created' => [
        'customer_name' => 'Jane Smith',
        'request_id' => '12345',
        'waste_type' => 'Plastic',
        'pickup_address' => '123 Main St, Colombo',
        'pickup_date' => '2026-02-10',
        'status' => 'Pending',
        'view_url' => 'http://localhost:8080/customer/pickup-requests/12345'
    ],
    'pickup-status-updated' => [
        'customer_name' => 'Jane Smith',
        'request_id' => '12345',
        'status' => 'Accepted',
        'status_message' => 'Your pickup request has been accepted by a collector.',
        'collector_name' => 'ABC Waste Collectors',
        'view_url' => 'http://localhost:8080/customer/pickup-requests/12345'
    ],
    'bid-received' => [
        'customer_name' => 'John Doe',
        'bid_amount' => 150.00,
        'company_name' => 'EcoRecycle Ltd',
        'waste_type' => 'Paper',
        'quantity' => 50,
        'bid_message' => 'We are interested in purchasing your waste.',
        'view_url' => 'http://localhost:8080/customer/bids/67890'
    ]
];

$transport = new \Core\Mail\SmtpMailer();
$mailer = new \Core\Mail\Mailer($transport);

$success = 0;
$failed = 0;

foreach ($templates as $templateName => $data) {
    echo "Testing template: $templateName\n";

    try {
        // Use reflection to access private renderTemplate method
        $reflection = new ReflectionClass($mailer);
        $method = $reflection->getMethod('renderTemplate');
        $method->setAccessible(true);

        // Test HTML version
        $html = $method->invoke($mailer, $templateName, $data, 'html');
        if (empty($html)) {
            echo "  ❌ HTML template not found or empty\n";
            $failed++;
            continue;
        }
        echo "  ✅ HTML template rendered (" . strlen($html) . " bytes)\n";

        // Test text version
        $text = $method->invoke($mailer, $templateName, $data, 'text');
        if (empty($text)) {
            echo "  ⚠️  Text template not found (will auto-generate from HTML)\n";
        } else {
            echo "  ✅ Text template rendered (" . strlen($text) . " bytes)\n";
        }

        $success++;

    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        $failed++;
    }

    echo "\n";
}

echo "=== Summary ===\n";
echo "Templates tested: " . count($templates) . "\n";
echo "Successful: $success\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    exit(1);
}
