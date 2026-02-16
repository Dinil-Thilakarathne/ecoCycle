Welcome to ecoCycle!

Hello
<?= $username ?>,

An account has been created for you at ecoCycle with the role:
<?= ucfirst($role) ?>.

Your Login Credentials:
Email:
<?= $email ?>
Password:
<?= $password ?>

Please change your password after logging in for the first time.

<?php if (isset($verification_url) && $verification_url): ?>
    Verify your email here:
    <?= $verification_url ?>
<?php endif; ?>

Log in here:
<?= $login_url ?>

If you have any questions, please contact your administrator.