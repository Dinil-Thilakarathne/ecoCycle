<?php
$username = $username ?? 'User';
$reset_url = $reset_url ?? '#';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
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
            background: #ef4444;
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
            background: #ef4444;
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }

        .button:hover {
            background: #dc2626;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .alert {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin-top: 30px;
        }

        .link-box {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">🔐 Reset Your Password</h1>
        </div>
        <div class="content">
            <h2>Hello,
                <?= htmlspecialchars($username) ?>!
            </h2>

            <p>We received a request to reset your password for your ecoCycle account.</p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="<?= htmlspecialchars($reset_url) ?>" class="button">
                    Reset Password
                </a>
            </p>

            <p style="color: #6b7280; font-size: 14px;">
                If the button doesn't work, copy and paste this link into your browser:
            </p>
            <div class="link-box">
                <a href="<?= htmlspecialchars($reset_url) ?>" style="color: #3b82f6;">
                    <?= htmlspecialchars($reset_url) ?>
                </a>
            </div>

            <div class="alert">
                <strong>⏰ This link will expire in 1 hour</strong>
                <p style="margin: 5px 0 0 0;">For security reasons, please reset your password as soon as possible.</p>
            </div>

            <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
                <strong>Didn't request this?</strong><br>
                If you didn't request a password reset, you can safely ignore this email. Your password will remain
                unchanged.
            </p>
        </div>
        <div class="footer">
            <p>©
                <?= date('Y') ?> ecoCycle. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>