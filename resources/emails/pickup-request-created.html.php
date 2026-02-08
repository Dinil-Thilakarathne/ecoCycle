<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Pickup Request Created</title>
    <style>
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

        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #374151;
        }

        .info-value {
            color: #6b7280;
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
    <div class="wrap" role="article" aria-label="Pickup Request Created">
        <div class="header">
            <strong>EcoCycle</strong>
        </div>

        <div class="content">
            <h1>Pickup Request Created</h1>

            <p>Hi
                <?= htmlspecialchars($customer_name) ?>,
            </p>

            <p>Your pickup request has been successfully created! Our collectors will review your request and respond
                soon.</p>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Request ID:</span>
                    <span class="info-value">#
                        <?= htmlspecialchars($request_id) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Waste Type:</span>
                    <span class="info-value">
                        <?= htmlspecialchars($waste_type) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pickup Address:</span>
                    <span class="info-value">
                        <?= htmlspecialchars($pickup_address) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Requested Date:</span>
                    <span class="info-value">
                        <?= htmlspecialchars($pickup_date) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <?= htmlspecialchars($status) ?>
                    </span>
                </div>
            </div>

            <p style="text-align:center; margin:18px 0;">
                <a href="<?= htmlspecialchars($view_url) ?>" class="button" target="_blank" rel="noopener noreferrer">
                    View Request Details
                </a>
            </p>

            <hr style="border:none; border-top:1px solid #eef2f7; margin:20px 0;" />

            <p class="muted">You will receive notifications when collectors respond to your request or when the status
                changes.</p>

            <p class="muted">If you have any questions, contact us at
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