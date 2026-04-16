<?php
$username = $username ?? 'User';
$email = $email ?? '';
$password = $password ?? '';
$role = $role ?? 'customer';
$login_url = $login_url ?? url('/login');
$verification_url = $verification_url ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Created - ecoCycle</title>
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

        .credentials-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
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
            <p>An account has been created for you at ecoCycle with the role: <strong>
                    <?= ucfirst(htmlspecialchars($role)) ?>
                </strong>.</p>

            <div class="credentials-box">
                <h3 style="margin-top: 0;">Your Login Credentials</h3>
                <p style="margin: 5px 0;"><strong>Email:</strong>
                    <?= htmlspecialchars($email) ?>
                </p>
                <p style="margin: 5px 0;"><strong>Password:</strong>
                    <?= htmlspecialchars($password) ?>
                </p>
                <p style="font-size: 0.9em; color: #666; margin-top: 10px;">Please change your password after logging in
                    for the first time.</p>
            </div>

            <?php if ($verification_url): ?>
                <p style="text-align: center; margin: 20px 0;">
                    <a href="<?= htmlspecialchars($verification_url) ?>" class="button"
                        style="background-color: #f59e0b;">Verify Email Address</a>
                </p>
            <?php endif; ?>

            <p style="text-align: center;">
                <a href="<?= htmlspecialchars($login_url) ?>" class="button">Log In to Dashboard</a>
            </p>

            <p>If you have any questions, please contact your administrator.</p>
        </div>
        <div class="footer">
            <p>©
                <?= date('Y') ?> ecoCycle. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>