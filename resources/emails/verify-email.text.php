<?php
$username = $username ?? 'User';
$verification_url = $verification_url ?? '#';
?>
Verify Your Email Address

Hello,
<?= $username ?>!

Thank you for registering with ecoCycle. Please verify your email address by clicking the link below:

<?= $verification_url ?>

This link will expire in 24 hours.

If you didn't create an account with ecoCycle, you can safely ignore this email.

---
©
<?= date('Y') ?> ecoCycle. All rights reserved.