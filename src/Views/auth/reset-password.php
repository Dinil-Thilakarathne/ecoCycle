<?php
/**
 * Password Reset View
 */
$error = $error ?? (session()->getFlash('error') ?? null);
$success = $success ?? (session()->getFlash('success') ?? null);
$token = $token ?? '';

$headContent = '<link rel="stylesheet" href="/css/page/login.css">';
?>

<section class="main-section auth-login-page">
    <div class="login-content">
        <div class="content-top">
            <h1>Reset Your Password</h1>
            <p>Enter your new password below.</p>
        </div>
        <form class="content-body" id="resetPasswordForm" method="POST" action="/api/auth/reset-password">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <form-input label="New Password" name="password" required type="password" placeholder="Enter new password"
                minlength="8"></form-input>

            <form-input label="Confirm Password" name="password_confirmation" required type="password"
                placeholder="Confirm new password" minlength="8"></form-input>

            <p id="resetError" role="status" aria-live="polite" style="color:var(--danger);" class="<?= $error ? "" : "visible" ?>">
                <?= $error ? htmlspecialchars($error) : '&nbsp;' ?>
            </p>

            <p id="resetSuccess" role="status" aria-live="polite" style="color:var(--success); display:none;">
                &nbsp;
            </p>

            <div style="display:flex; gap:.5rem; margin-top:var(--space-2); width: 100%; flex-direction:column;">
                <button id="resetSubmit" type="submit" class="btn btn-gradient login-card__action">Reset
                    Password</button>
                <div class="forget-password">
                    <a href="/login">Back to Login</a>
                </div>
            </div>
        </form>

        <script src="/js/toast.js"></script>
        <script>
            (function () {
                var form = document.getElementById('resetPasswordForm');
                var resetError = document.getElementById('resetError');
                var resetSuccess = document.getElementById('resetSuccess');

                if (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();

                        var btnEl = document.getElementById('resetSubmit');
                        if (btnEl) btnEl.disabled = true;
                        if (resetError) resetError.style.display = 'none';
                        if (resetSuccess) resetSuccess.style.display = 'none';

                        var formData = new FormData(form);

                        fetch('/api/auth/reset-password', {
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
                                if (resetSuccess) {
                                    resetSuccess.textContent = data.message || 'Password reset successfully!';
                                    resetSuccess.style.display = 'block';
                                }

                                try {
                                    if (typeof __createToast === 'function') {
                                        __createToast(data.message || 'Password reset successfully!', 'success', 2000);
                                    }
                                } catch (e) {
                                    // ignore
                                }

                                // Redirect to login after 2 seconds
                                setTimeout(function () {
                                    window.location.href = '/login';
                                }, 2000);
                                return;
                            }

                            var msg = (data && data.message) ? data.message : 'Failed to reset password';
                            if (resetError) {
                                resetError.textContent = msg;
                                resetError.style.display = 'block';
                            } else {
                                alert(msg);
                            }
                        }).catch(function (err) {
                            if (resetError) {
                                resetError.textContent = 'Network error. Please try again.';
                                resetError.style.display = 'block';
                            }
                        }).finally(function () {
                            if (btnEl) btnEl.disabled = false;
                        });
                    });
                }

                // Show server-side error if present
                <?php if ($error): ?>
                        if (resetError) {
                        resetError.textContent = <?= json_encode($error) ?>;
                        resetError.style.display = 'block';
                    }
                <?php endif; ?>
            })();
        </script>
    </div>
</section>