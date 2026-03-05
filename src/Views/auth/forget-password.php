<?php
/**
 * Password reset request form
 */
$oldEmail = old('email', '');
$error = $error ?? (session()->getFlash('error') ?? null);
$success = $success ?? (session()->getFlash('success') ?? null);
$headContent = '<link rel="stylesheet" href="/css/page/forget-password.css">';
$title = $title ?? 'Reset your password';
?>

<section class="main-section auth-forget-page">
    <div class="forget-card" role="presentation">
        <div class="forget-card__header">
            <h1>Reset your password</h1>
            <p>Enter the email linked to your ecoCycle account and we will send you the next steps.</p>
        </div>

        <form id="forgetPasswordForm" class="forget-card__form" method="POST" novalidate>
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <form-input label="Email address" name="email" type="email" placeholder="you@example.com"
                value="<?= htmlspecialchars($oldEmail) ?>" required></form-input>

            <p id="forgetPasswordError" class="forget-card__notice" role="status" aria-live="polite"
                style="color:var(--danger); display:none;">
                &nbsp;
            </p>

            <p id="forgetPasswordSuccess" class="forget-card__notice" role="status" aria-live="polite"
                style="color:var(--success); display:none;">
                &nbsp;
            </p>

            <div class="forget-card__actions">
                <button id="resetSubmitBtn" type="submit" class="btn btn-primary">Send reset link</button>
                <a class="btn btn-outline" href="/login">Back to sign in</a>
            </div>
        </form>
    </div>
</section>

<script src="/js/toast.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('forgetPasswordForm');
        var errorText = document.getElementById('forgetPasswordError');
        var successText = document.getElementById('forgetPasswordSuccess');
        var submitBtn = document.getElementById('resetSubmitBtn');

        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            // Disable submit button
            if (submitBtn) submitBtn.disabled = true;

            // Hide previous messages
            if (errorText) errorText.style.display = 'none';
            if (successText) successText.style.display = 'none';

            var formData = new FormData(form);

            fetch('/api/auth/send-password-reset-link', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (resp) {
                return resp.json().catch(function () {
                    return { success: false, message: 'Invalid server response' };
                });
            }).then(function (data) {
                if (data && data.success) {
                    // Show success message
                    if (successText) {
                        successText.textContent = data.message || 'Password reset link sent to your email.';
                        successText.style.display = 'block';
                    }

                    // Show toast
                    try {
                        if (typeof __createToast === 'function') {
                            __createToast(data.message || 'Password reset link sent!', 'success', 4000);
                        }
                    } catch (e) {
                        // ignore
                    }

                    // Clear form
                    form.reset();

                    return;
                }

                // Show error message
                var msg = (data && data.message) ? data.message : 'Failed to send reset link';
                if (errorText) {
                    errorText.textContent = msg;
                    errorText.style.display = 'block';
                } else {
                    alert(msg);
                }
            }).catch(function (err) {
                if (errorText) {
                    errorText.textContent = 'Network error. Please try again.';
                    errorText.style.display = 'block';
                }
            }).finally(function () {
                // Re-enable submit button
                if (submitBtn) submitBtn.disabled = false;
            });
        });

        // Show server-side error if present
        <?php if ($error): ?>
            if (errorText) {
                errorText.textContent = <?= json_encode($error) ?>;
                errorText.style.display = 'block';
            }
        <?php endif; ?>

        // Show server-side success if present
        <?php if ($success): ?>
            if (successText) {
                successText.textContent = <?= json_encode($success) ?>;
                successText.style.display = 'block';
            }
            try {
                if (typeof __createToast === 'function') {
                    __createToast(<?= json_encode($success) ?>, 'success', 4000);
                }
            } catch (e) { /* ignore */ }
        <?php endif; ?>
    });
</script>