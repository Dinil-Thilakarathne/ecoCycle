Hi
<?= htmlspecialchars($customer_name) ?>,

Great news! You've received a new bid on your waste listing.

Bid Amount: Rs.
<?= htmlspecialchars(number_format($bid_amount, 2)) ?> per kg

Bid Details:
- Company:
<?= htmlspecialchars($company_name) ?>

- Waste Type:
<?= htmlspecialchars($waste_type) ?>

- Quantity:
<?= htmlspecialchars($quantity) ?> kg
<?php if (isset($bid_message)): ?>

    - Message:
    <?= htmlspecialchars($bid_message) ?>
<?php endif; ?>


View Bid Details:
<?= htmlspecialchars($view_url) ?>


You can accept or reject this bid from your dashboard. Multiple bids may be received, so review all offers before making
a decision.

If you have any questions, contact us at support@ecocycle.com.

Regards,
The EcoCycle Team
EcoCycle - Digital Waste Management System