<?php
/**
 * Quick Email Test
 * Run this to test if email sending works at all
 */

// Load environment
$basePath = dirname(__DIR__);
require_once $basePath . '/src/Core/Environment.php';
Core\Environment::load($basePath);

// Load helpers
require_once $basePath . '/src/helpers.php';

// Load mail configuration
require_once $basePath . '/src/Core/Config.php';
require_once $basePath . '/src/Core/Mail/SmtpMailer.php';
require_once $basePath . '/src/Core/Mail/Mailer.php';
Core\Config::load($basePath . '/config/mail.php', 'mail');

echo "Quick Email Test\n";
echo "================\n\n";

// Get email from command line
$recipient = $argv[1] ?? null;

if (!$recipient) {
    echo "Usage: php scripts/quick-email-test.php recipient@example.com\n";
    exit(1);
}

echo "Recipient: $recipient\n";
echo "Sending test email...\n\n";

try {
    $token = bin2hex(random_bytes(32));

    $result = sendMail(
        $recipient,
        'welcome',
        [
            'username' => 'Quick Test User',
            'email' => $recipient,
            'role' => 'customer',
            'login_url' => 'http://localhost:8080/login',
            'dashboard_url' => 'http://localhost:8080/dashboard',
            'verification_url' => "http://localhost:8080/verify-email?token={$token}",
        ],
        'Welcome to ecoCycle - Quick Test'
    );

    if ($result === true) {
        echo "✅ SUCCESS! Email sent.\n";
        echo "\nCheck:\n";
        echo "  - Inbox of $recipient\n";
        echo "  - Spam/Junk folder\n";
        echo "  - Wait 1-2 minutes for delivery\n";
    } else {
        echo "❌ FAILED! sendMail() returned: " . var_export($result, true) . "\n";
    }

} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "\nFile: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
