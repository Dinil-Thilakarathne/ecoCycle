<?php
/**
 * Test Welcome Email Script
 * 
 * This script sends a test welcome email to verify email functionality.
 * Usage: php scripts/test-welcome-email.php recipient@example.com
 */


// Load environment
$basePath = dirname(__DIR__);
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

// Get recipient from command line or use default
$recipient = $argv[1] ?? null;

if (!$recipient) {
    echo "Usage: php scripts/test-welcome-email.php recipient@example.com\n";
    echo "\nExample:\n";
    echo "  php scripts/test-welcome-email.php friend@gmail.com\n";
    exit(1);
}

// Validate email
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email address: {$recipient}\n";
    exit(1);
}

echo "=== Welcome Email Test ===\n\n";
echo "Sending test welcome email to: {$recipient}\n";
echo "From: " . $_ENV['MAIL_FROM_ADDRESS'] . "\n";
echo "SMTP Host: " . $_ENV['SMTP_HOST'] . "\n\n";

try {
    // Generate a test verification token
    $verificationToken = bin2hex(random_bytes(32));

    // Send welcome email
    $result = sendMail(
        $recipient,
        'welcome',
        [
            'username' => 'Test User',
            'email' => $recipient,
            'role' => 'customer',
            'login_url' => url('/login'),
            'dashboard_url' => url('/dashboard'),
            'verification_url' => url("/verify-email?token={$verificationToken}"),
        ],
        'Welcome to ecoCycle!'
    );

    if ($result) {
        echo "✅ Welcome email sent successfully!\n\n";
        echo "Please check the inbox for: {$recipient}\n";
        echo "Note: Check spam/junk folder if you don't see it in inbox.\n\n";
        echo "The email includes:\n";
        echo "  - Welcome message\n";
        echo "  - Email verification link\n";
        echo "  - Login button\n";
        echo "  - Role-specific information\n";
    } else {
        echo "❌ Failed to send email\n";
        echo "Check error logs for details.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
