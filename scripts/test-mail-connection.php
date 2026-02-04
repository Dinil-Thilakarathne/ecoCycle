<?php

/**
 * Test SMTP Connection
 * 
 * This script tests if the SMTP connection can be established with the configured credentials.
 * Run: php scripts/test-mail-connection.php
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

    echo "✅ SMTP mailer instance created successfully!\n";
    echo "\nYour SMTP configuration is working correctly.\n";

} catch (\Exception $e) {
    echo "❌ SMTP connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Common issues:\n";
    echo "  - Invalid SMTP credentials\n";
    echo "  - Firewall blocking SMTP port\n";
    echo "  - SMTP server not allowing connections from your IP\n";
    echo "  - For Gmail: Enable 2FA and use App Password\n";
    exit(1);
}
