<?php

/**
 * Test SMTP Connection
 * 
 * This script tests if the SMTP connection can be established with the configured credentials.
 * Run: php scripts/test-mail-connection.php
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

echo "=== SMTP Connection Test ===\n\n";

// Display configuration
echo "Configuration:\n";
echo "  Host: " . env('SMTP_HOST', 'not set') . "\n";
echo "  Port: " . env('SMTP_PORT', 'not set') . "\n";
echo "  User: " . env('SMTP_USER', 'not set') . "\n";
echo "  Encryption: " . env('SMTP_ENCRYPTION', 'not set') . "\n\n";

// Check if credentials are set
if (empty(env('SMTP_USER')) || empty(env('SMTP_PASS'))) {
    echo "❌ ERROR: SMTP credentials not configured in .env file\n";
    echo "Please set SMTP_USER and SMTP_PASS in your .env file\n";
    exit(1);
}

try {
    echo "Testing SMTP connection...\n";

    // Create SMTP mailer instance
    $smtp = new \Core\Mail\SmtpMailer();

    echo "✅ SMTP connection successful!\n";
    echo "\nYour SMTP configuration is working correctly.\n";

} catch (\Exception $e) {
    echo "❌ SMTP connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Common issues:\n";
    echo "  - Invalid SMTP credentials\n";
    echo "  - Firewall blocking SMTP port\n";
    echo "  - SMTP server not allowing connections from your IP\n";
    echo "  - For Gmail: Enable 'Less secure app access' or use App Password\n";
    exit(1);
}
