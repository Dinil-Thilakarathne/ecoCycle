Hi
<?= htmlspecialchars($username ?? 'there') ?>,

<?= htmlspecialchars($message ?? '') ?>

<?php if (isset($details) && is_array($details)): ?>

    Details:
    <?php foreach ($details as $key => $value): ?>
        -
        <?= htmlspecialchars($key) ?>:
        <?= htmlspecialchars($value) ?>

    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($action_url)): ?>
    <?= htmlspecialchars($action_text ?? 'View Details') ?>:
    <?= htmlspecialchars($action_url) ?>

<?php endif; ?>

If you have any questions, please contact us at support@ecocycle.com.

Regards,
The EcoCycle Team
EcoCycle - Digital Waste Management System