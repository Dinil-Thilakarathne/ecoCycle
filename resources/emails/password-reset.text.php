<?php
$username = $username ?? 'User';
$reset_url = $reset_url ?? '#';
?>
Reset Your Password

Hello,
<?= $username ?>!

We received a request to reset your password for your ecoCycle account.

Click here to reset your password:
<?= $reset_url ?>

This link will expire in 1 hour.

If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.

---
©
<?= date('Y') ?> ecoCycle. All rights reserved.