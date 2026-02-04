<?php
$username = $username ?? 'User';
$verification_url = $verification_url ?? '#';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
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
            background: #f59e0b;
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
            background: #f59e0b;
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }

        .button:hover {
            background: #d97706;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .alert {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
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
            <h1 style="margin: 0; font-size: 28px;">📧 Verify Your Email</h1>
        </div>
        <div class="content">
            <h2>Hello,
                <?= htmlspecialchars($username) ?>!
            </h2>

            <p>Thank you for registering with ecoCycle. To complete your registration and access all features, please
                verify your email address.</p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="<?= htmlspecialchars($verification_url) ?>" class="button">
                    Verify Email Address
                </a>
            </p>

            <p style="color: #6b7280; font-size: 14px;">
                If the button doesn't work, copy and paste this link into your browser:
            </p>
            <div class="link-box">
                <a href="<?= htmlspecialchars($verification_url) ?>" style="color: #3b82f6;">
                    <?= htmlspecialchars($verification_url) ?>
                </a>
            </div>

            <div class="alert">
                <strong>⏰ This link will expire in 24 hours</strong>
                <p style="margin: 5px 0 0 0;">For security reasons, please verify your email as soon as possible.</p>
            </div>

            <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
                If you didn't create an account with ecoCycle, you can safely ignore this email.
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