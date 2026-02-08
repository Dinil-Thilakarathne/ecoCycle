<?php
/**
 * Check Last Registration
 * Shows details of the most recent user registration
 */

$basePath = dirname(__DIR__);
require_once $basePath . '/src/Core/Environment.php';
require_once $basePath . '/src/Core/Database.php';

Core\Environment::load($basePath);

echo "Last Registration Details\n";
echo "=========================\n\n";

try {
    $db = new Core\Database();

    // Get the most recent user
    $user = $db->fetch(
        'SELECT id, name, email, role_id, email_verified, email_verification_token, 
                email_verification_sent_at, created_at 
         FROM users 
         ORDER BY created_at DESC 
         LIMIT 1'
    );

    if (!$user) {
        echo "No users found in database.\n";
        exit(1);
    }

    echo "User ID: " . $user['id'] . "\n";
    echo "Name: " . $user['name'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Role ID: " . $user['role_id'] . "\n";
    echo "Email Verified: " . ($user['email_verified'] ? 'Yes' : 'No') . "\n";
    echo "Verification Token: " . ($user['email_verification_token'] ?? 'NULL') . "\n";
    echo "Verification Email Sent: " . ($user['email_verification_sent_at'] ?? 'NULL') . "\n";
    echo "Created At: " . $user['created_at'] . "\n\n";

    // Check if this is a Gmail self-send issue
    $smtpUser = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER');
    echo "SMTP Sender: " . $smtpUser . "\n";

    if ($user['email'] === $smtpUser) {
        echo "\n⚠️  WARNING: Gmail Self-Send Issue Detected!\n";
        echo "You registered with the same email as your SMTP sender.\n";
        echo "Gmail will NOT deliver emails to yourself.\n\n";
        echo "Solution: Register with a different email address.\n";
    } else {
        echo "\n✓ Email addresses are different (good!)\n";

        if ($user['email_verification_token']) {
            echo "\n✓ Verification token was generated\n";

            if ($user['email_verification_sent_at']) {
                echo "✓ Email send was attempted at: " . $user['email_verification_sent_at'] . "\n";
                echo "\nPossible issues:\n";
                echo "  1. Email is in spam/junk folder\n";
                echo "  2. SMTP credentials are incorrect\n";
                echo "  3. Gmail blocked the email\n";
                echo "  4. Network/firewall issue\n";
            } else {
                echo "✗ Email send timestamp is NULL\n";
                echo "  This means the email was never attempted.\n";
            }
        } else {
            echo "\n✗ No verification token generated\n";
            echo "  This means the email sending code didn't run.\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
