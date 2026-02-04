<?php
$username = $username ?? 'User';
$role = $role ?? 'customer';
$login_url = $login_url ?? url('/login');
$dashboard_url = $dashboard_url ?? url('/dashboard');
$verification_url = $verification_url ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ecoCycle</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }

        .button {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: 600;
        }

        .button:hover {
            background: #059669;
        }

        .button-warning {
            background: #f59e0b;
        }

        .button-warning:hover {
            background: #d97706;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }

        .info-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">Welcome to ecoCycle! 🌱</h1>
        </div>
        <div class="content">
            <h2>Hello,
                <?= htmlspecialchars($username) ?>!
            </h2>

            <p>Thank you for joining ecoCycle, your partner in sustainable waste management and recycling.</p>

            <p>Your account has been successfully created with the role: <span class="badge">
                    <?= ucfirst(htmlspecialchars($role)) ?>
                </span></p>

            <?php if ($verification_url): ?>
                <div class="alert">
                    <strong>⚠️ Please verify your email address</strong>
                    <p style="margin: 10px 0 0 0;">Click the button below to verify your email and unlock all features.</p>
                </div>

                <p style="text-align: center;">
                    <a href="<?= htmlspecialchars($verification_url) ?>" class="button button-warning">
                        Verify Email Address
                    </a>
                </p>
            <?php endif; ?>

            <p style="text-align: center; margin-top: 30px;">
                <a href="<?= htmlspecialchars($login_url) ?>" class="button">
                    Log In to Your Account
                </a>
            </p>

            <div class="info-box">
                <h3 style="margin-top: 0;">What's Next?</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php if ($role === 'customer'): ?>
                        <li>Schedule your first waste pickup</li>
                        <li>Track your recycling impact</li>
                        <li>Earn rewards for sustainable practices</li>
                    <?php elseif ($role === 'collector'): ?>
                        <li>View assigned pickup routes</li>
                        <li>Update pickup statuses</li>
                        <li>Manage your collection schedule</li>
                    <?php elseif ($role === 'company'): ?>
                        <li>Browse available waste lots</li>
                        <li>Place bids on recycling materials</li>
                        <li>Track your procurement</li>
                    <?php endif; ?>
                </ul>
            </div>

            <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
                If you have any questions, feel free to reach out to our support team.
            </p>
        </div>
        <div class="footer">
            <p>©
                <?= date('Y') ?> ecoCycle. All rights reserved.
            </p>
            <p>Building a sustainable future, one pickup at a time.</p>
        </div>
    </div>
</body>

</html>