<?php
/**
 * Email Diagnostic Script
 * Tests all aspects of email functionality
 */

echo "=== Email System Diagnostic ===\n\n";

// Step 1: Load environment
echo "1. Loading environment...\n";
$basePath = dirname(__DIR__);
require_once $basePath . '/src/Core/Environment.php';
Core\Environment::load($basePath);

echo "   ✓ Environment loaded\n\n";

// Step 2: Check environment variables
echo "2. Checking SMTP configuration...\n";
$requiredVars = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_ENCRYPTION'];
$allSet = true;

foreach ($requiredVars as $var) {
    $value = $_ENV[$var] ?? getenv($var);
    if ($value) {
        if ($var === 'SMTP_PASS') {
            echo "   ✓ $var: " . str_repeat('*', strlen($value)) . "\n";
        } else {
            echo "   ✓ $var: $value\n";
        }
    } else {
        echo "   ✗ $var: NOT SET\n";
        $allSet = false;
    }
}

if (!$allSet) {
    echo "\n❌ ERROR: Missing required SMTP configuration\n";
    exit(1);
}

echo "\n3. Loading mail classes...\n";
require_once $basePath . '/src/Core/Mail/SmtpMailer.php';
require_once $basePath . '/src/Core/Mail/Mailer.php';
echo "   ✓ Mail classes loaded\n\n";

// Step 4: Load configuration
echo "4. Loading mail configuration...\n";
require_once $basePath . '/src/Core/Config.php';
Core\Config::load($basePath . '/config/mail.php', 'mail');
echo "   ✓ Mail config loaded\n\n";

// Step 5: Load helpers
echo "5. Loading helper functions...\n";
require_once $basePath . '/src/helpers.php';

if (function_exists('sendMail')) {
    echo "   ✓ sendMail() function exists\n";
} else {
    echo "   ✗ sendMail() function NOT FOUND\n";
    exit(1);
}

if (function_exists('generateVerificationToken')) {
    echo "   ✓ generateVerificationToken() function exists\n";
} else {
    echo "   ✗ generateVerificationToken() function NOT FOUND\n";
    exit(1);
}

echo "\n6. Testing SMTP connection...\n";
try {
    $transport = new Core\Mail\SmtpMailer();
    echo "   ✓ SmtpMailer instance created\n";
} catch (Exception $e) {
    echo "   ✗ Failed to create SmtpMailer: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n7. Testing email template...\n";
$templatePath = $basePath . '/resources/emails/welcome.html.php';
if (file_exists($templatePath)) {
    echo "   ✓ Welcome template exists: $templatePath\n";
} else {
    echo "   ✗ Welcome template NOT FOUND: $templatePath\n";
    exit(1);
}

echo "\n8. Sending test email...\n";
$testEmail = $argv[1] ?? null;

if (!$testEmail) {
    echo "\n⚠️  No recipient specified. Usage:\n";
    echo "   php scripts/diagnose-email.php recipient@example.com\n\n";
    echo "All checks passed! Email system is configured correctly.\n";
    exit(0);
}

if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "   ✗ Invalid email address: $testEmail\n";
    exit(1);
}

echo "   Recipient: $testEmail\n";
echo "   Generating test data...\n";

try {
    $token = bin2hex(random_bytes(32));

    $result = sendMail(
        $testEmail,
        'welcome',
        [
            'username' => 'Test User',
            'email' => $testEmail,
            'role' => 'customer',
            'login_url' => 'http://localhost:8080/login',
            'dashboard_url' => 'http://localhost:8080/dashboard',
            'verification_url' => "http://localhost:8080/verify-email?token={$token}",
        ],
        'Welcome to ecoCycle - Test Email'
    );

    if ($result) {
        echo "\n✅ SUCCESS! Email sent to $testEmail\n";
        echo "\nPlease check:\n";
        echo "  1. Inbox of $testEmail\n";
        echo "  2. Spam/Junk folder\n";
        echo "  3. Wait a few minutes for delivery\n";
    } else {
        echo "\n❌ FAILED: sendMail() returned false\n";
        echo "Check error logs for details.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Diagnostic Complete ===\n";
