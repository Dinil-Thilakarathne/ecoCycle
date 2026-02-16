<?php
$username = $username ?? 'User';
$role = $role ?? 'customer';
$login_url = $login_url ?? url('/login');
$verification_url = $verification_url ?? null;
?>
Welcome to ecoCycle,
<?= $username ?>!

Thank you for joining ecoCycle, your partner in sustainable waste management.

Your account has been successfully created with the role:
<?= ucfirst($role) ?>

<?php if ($verification_url): ?>
    IMPORTANT: Please verify your email address
    Click here to verify:
    <?= $verification_url ?>

<?php endif; ?>
Log in to your account:
<?= $login_url ?>

What's Next?
<?php if ($role === 'customer'): ?>
    - Schedule your first waste pickup
    - Track your recycling impact
    - Earn rewards for sustainable practices
<?php elseif ($role === 'collector'): ?>
    - View assigned pickup routes
    - Update pickup statuses
    - Manage your collection schedule
<?php elseif ($role === 'company'): ?>
    - Browse available waste lots
    - Place bids on recycling materials
    - Track your procurement
<?php endif; ?>

If you have any questions, feel free to reach out to our support team.

---
©
<?= date('Y') ?> ecoCycle. All rights reserved.
Building a sustainable future, one pickup at a time.