<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>
        <?= htmlspecialchars($subject ?? 'Notification') ?>
    </title>
    <style>
        /* Minimal, email-safe CSS */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f7fb;
            color: #111;
        }

        .wrap {
            max-width: 600px;
            margin: 32px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(16, 24, 40, 0.06);
        }

        .header {
            padding: 20px 24px;
            background: #10b981;
            color: #fff;
        }

        .content {
            padding: 24px;
        }

        h1 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
        }

        p {
            margin: 0 0 12px 0;
            line-height: 1.45;
        }

        .button {
            display: inline-block;
            padding: 12px 20px;
            background: #10b981;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
        }

        .footer {
            padding: 16px 24px;
            font-size: 12px;
            color: #6b7280;
            background: #fbfdff;
        }

        a {
            color: inherit;
        }

        @media (max-width:420px) {
            .wrap {
                margin: 16px;
            }

            .content {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="wrap" role="article" aria-label="Email notification">
        <div class="header">
            <strong>EcoCycle</strong>
        </div>

        <div class="content">
            <h1>
                <?= htmlspecialchars($title ?? 'Notification') ?>
            </h1>

            <p>Hi
                <?= htmlspecialchars($username ?? 'there') ?>,
            </p>

            <p>
                <?= htmlspecialchars($message ?? '') ?>
            </p>

            <?php if (isset($action_url) && isset($action_text)): ?>
                <p style="text-align:center; margin:18px 0;">
                    <a href="<?= htmlspecialchars($action_url) ?>" class="button" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars($action_text) ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php if (isset($details) && is_array($details)): ?>
                <hr style="border:none; border-top:1px solid #eef2f7; margin:20px 0;" />
                <p><strong>Details:</strong></p>
                <ul style="margin: 8px 0; padding-left: 20px;">
                    <?php foreach ($details as $key => $value): ?>
                        <li>
                            <?= htmlspecialchars($key) ?>:
                            <?= htmlspecialchars($value) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <hr style="border:none; border-top:1px solid #eef2f7; margin:20px 0;" />

            <p class="muted">If you have any questions, please contact us at
                <a href="mailto:support@ecocycle.com">support@ecocycle.com</a>.
            </p>
        </div>

        <div class="footer">
            <div>EcoCycle - Digital Waste Management System</div>
            <div style="margin-top:6px;">©
                <?= date('Y') ?> EcoCycle. All rights reserved.
            </div>
        </div>
    </div>
</body>

</html>