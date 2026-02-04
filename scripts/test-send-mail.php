<?php

/**
 * Test Email Sending
 * 
 * This script sends a test email to verify the complete mail pipeline works.
 * Run: php scripts/test-send-mail.php your-email@example.com
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Load config helper
require_once __DIR__ . '/../src/helpers.php';

echo "=== Email Sending Test ===\n\n";

// Get recipient email from command line argument
$to = $argv[1] ?? null;

if (!$to) {
    echo "Usage: php scripts/test-send-mail.php your-email@example.com\n";
    exit(1);
}

// Validate email
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email address: $to\n";
    exit(1);
}

// Check if credentials are set
if (empty(env('SMTP_USER')) || empty(env('SMTP_PASS'))) {
    echo "❌ ERROR: SMTP credentials not configured in .env file\n";
    exit(1);
}

try {
    echo "Sending test email to: $to\n\n";

    // Prepare test data
    $data = [
        'username' => 'Test User',
        'title' => 'Test Email from EcoCycle',
        'message' => 'This is a test email to verify that your mail engine is working correctly!',
        'action_url' => 'http://localhost:8080',
        'action_text' => 'Visit Dashboard',
        'details' => [
            'Test Status' => 'Success',
            'Mail Engine' => 'Custom SMTP',
            'Sent At' => date('Y-m-d H:i:s')
        ]
    ];

    // Send email using the notification template
    $result = sendMail(
        $to,
        'notification',
        $data,
        'Test Email from EcoCycle Mail Engine'
    );

    if ($result) {
        echo "✅ Email sent successfully!\n";
        echo "\nPlease check your inbox at: $to\n";
        echo "Note: The email might be in your spam folder.\n";
    } else {
        echo "❌ Failed to send email\n";
        echo "Check the error logs for more details.\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ Error sending email!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
