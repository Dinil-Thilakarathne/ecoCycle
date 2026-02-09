Hi
<?= htmlspecialchars($customer_name) ?>,

The status of your pickup request #
<?= htmlspecialchars($request_id) ?> has been updated.

New Status:
<?= htmlspecialchars($status) ?>

<?php if (isset($status_message)): ?>
    Message:
    <?= htmlspecialchars($status_message) ?>

<?php endif; ?>
<?php if (isset($collector_name)): ?>
    Collector:
    <?= htmlspecialchars($collector_name) ?>

<?php endif; ?>

View Request Details:
<?= htmlspecialchars($view_url) ?>


If you have any questions, contact us at support@ecocycle.com.

Regards,
The EcoCycle Team
EcoCycle - Digital Waste Management System