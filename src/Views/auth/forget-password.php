<?php
/**
 * Password reset request placeholder UI.
 */
$oldEmail = old('email', '');
$headContent = '<link rel="stylesheet" href="/css/page/forget-password.css">';
$title = $title ?? 'Reset your password';
?>

<section class="main-section auth-forget-page">
    <div class="forget-card" role="presentation">
        <div class="forget-card__header">
            <h1>Reset your password</h1>
            <p>Enter the email linked to your EcoCycle account and we will send you the next steps.</p>
        </div>

        <form id="forgetPasswordForm" class="forget-card__form" action="/forget-password" method="POST" novalidate>
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <form-input label="Email address" name="email" type="email" placeholder="you@example.com"
                value="<?= htmlspecialchars($oldEmail) ?>" required></form-input>

            <p id="forgetPasswordNotice" class="forget-card__notice" role="status" aria-live="polite">
                We are preparing this feature. You will receive instructions soon after it is activated.
            </p>

            <div class="forget-card__actions">
                <button type="submit" class="btn btn-primary">Send reset link</button>
                <a class="btn btn-outline" href="/login">Back to sign in</a>
            </div>
        </form>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('forgetPasswordForm');
        var statusText = document.getElementById('forgetPasswordNotice');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (statusText) {
                statusText.textContent = 'Password reset emails will be available soon. Please check back later.';
            }
            try {
                if (typeof __createToast === 'function') {
                    __createToast('Password reset will be available soon.', 'info', 3000);
                } else {
                    alert('Password reset will be available soon.');
                }
            } catch (err) {
                // ignore toast errors
            }
        });
    });
</script>