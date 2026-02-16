<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>New Bid Received</title>
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

        .bid-box {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin: 16px 0;
            text-align: center;
        }

        .bid-amount {
            font-size: 32px;
            font-weight: 700;
            color: #10b981;
            margin: 8px 0;
        }

        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
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
    <div class="wrap" role="article" aria-label="New Bid Received">
        <div class="header">
            <strong>EcoCycle</strong>
        </div>

        <div class="content">
            <h1>New Bid Received!</h1>

            <p>Hi
                <?= htmlspecialchars($customer_name) ?>,
            </p>

            <p>Great news! You've received a new bid on your waste listing.</p>

            <div class="bid-box">
                <p style="margin: 0; font-size: 14px; color: #6b7280;">Bid Amount</p>
                <div class="bid-amount">Rs.
                    <?= htmlspecialchars(number_format($bid_amount, 2)) ?>
                </div>
                <p style="margin: 0; font-size: 14px; color: #6b7280;">per kg</p>
            </div>

            <div class="info-box">
                <p><strong>Company:</strong>
                    <?= htmlspecialchars($company_name) ?>
                </p>
                <p style="margin-top: 8px;"><strong>Waste Type:</strong>
                    <?= htmlspecialchars($waste_type) ?>
                </p>
                <p style="margin-top: 8px;"><strong>Quantity:</strong>
                    <?= htmlspecialchars($quantity) ?> kg
                </p>
                <?php if (isset($bid_message)): ?>
                    <p style="margin-top: 8px;"><strong>Message:</strong>
                        <?= htmlspecialchars($bid_message) ?>
                    </p>
                <?php endif; ?>
            </div>

            <p style="text-align:center; margin:18px 0;">
                <a href="<?= htmlspecialchars($view_url) ?>" class="button" target="_blank" rel="noopener noreferrer">
                    View Bid Details
                </a>
            </p>

            <hr style="border:none; border-top:1px solid #eef2f7; margin:20px 0;" />

            <p class="muted">You can accept or reject this bid from your dashboard. Multiple bids may be received, so
                review all offers before making a decision.</p>

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