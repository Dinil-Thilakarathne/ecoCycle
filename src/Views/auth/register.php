<?php
/**
 * Simple registration view (UI only)
 * Mirrors the structure and style of the login selection page so it can be used
 * as a starting point for all user types. No backend integration in this file.
 */
$error = $error ?? (session()->getFlash('error') ?? null);
$success = $success ?? (session()->getFlash('success') ?? null);
$old = function ($k, $d = '') {
    return htmlspecialchars(old($k, $d));
};
?>

<section class="main-section auth-login-page">
    <div class="login-content">
        <div class="content-top">
            <h1>Create an account</h1>
            <p>Register to access the platform. Choose your role and complete the form below.</p>
        </div>

        <form class="content-body" method="POST" action="/register" novalidate>
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <?php if ($success): ?>
                <p role="status" aria-live="polite" style="color:var(--success);"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <div class="form-select">
                <label for="role-select">Account role</label><br>
                <select id="role-select" name="role" aria-label="Select user role" style="width:100%;">
                    <option value="customer" selected>Customer — Track recycling requests</option>
                    <option value="collector">Collector — Manage pickups &amp; routes</option>
                    <option value="company">Company — Operations &amp; analytics</option>
                    <option value="admin">Admin — Platform configuration</option>
                </select>
            </div>

            <form-input label="Full name" name="name" placeholder="Your full name" value="<?= $old('name') ?>"
                required></form-input>

            <form-input label="Email" name="email" type="email" placeholder="email@example.com"
                value="<?= $old('email') ?>" required></form-input>

            <form-input label="Password" name="password" type="password" placeholder="Choose a password"
                required></form-input>

            <form-input label="Confirm password" name="password_confirm" type="password" placeholder="Repeat password"
                required></form-input>

            <p id="registerError" role="status" aria-live="polite" style="color:var(--danger);"
                class="<?= $error ? 'visible' : '' ?>">
                <?= $error ? htmlspecialchars($error) : '&nbsp;' ?>
            </p>

            <noscript>
                <div style="display:none;">
                    <input type="text" name="name" value="<?= $old('name') ?>" />
                    <input type="email" name="email" value="<?= $old('email') ?>" />
                    <input type="password" name="password" />
                    <input type="password" name="password_confirm" />
                </div>
            </noscript>

            <div style="display:flex; gap:.5rem; margin-top:var(--space-2); width: 100%; flex-direction:column;">
                <button id="registerSubmit" type="submit" class="btn btn-gradient login-card__action">Create
                    account</button>
                <div class="forget-password">
                    <a href="/login">Already have an account? Sign in</a>
                </div>
            </div>
        </form>

        <div class="content-footer">
            <a href="/" class="btn btn-outline signup-btn">Back to home</a>
        </div>

        <script src="/js/toast.js"></script>
        <script>
            (function () {
                // Keep client-side validations. If validation passes, redirect to /login.
                var form = document.querySelector('form.content-body');
                var registerError = document.getElementById('registerError');

                function showError(msg) {
                    if (!registerError) { alert(msg); return; }
                    registerError.textContent = msg;
                    registerError.style.display = 'block';
                }

                if (!form) return;

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var btn = document.getElementById('registerSubmit');
                    if (btn) btn.disabled = true;
                    if (registerError) registerError.style.display = 'none';

                    // Basic client-side validation
                    var name = form.querySelector('[name="name"]').value.trim();
                    var email = form.querySelector('[name="email"]').value.trim();
                    var pwd = form.querySelector('[name="password"]').value;
                    var pwd2 = form.querySelector('[name="password_confirm"]').value;

                    if (!name || !email || !pwd || !pwd2) {
                        showError('Please fill out all required fields.');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    if (pwd.length < 6) {
                        showError('Password should be at least 6 characters.');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    if (pwd !== pwd2) {
                        showError('Passwords do not match.');
                        if (btn) btn.disabled = false;
                        return;
                    }

                    // All validations passed — submit the form to server
                    if (btn) btn.textContent = 'Creating account...';
                    // Allow normal POST submission
                    form.submit();
                });

                // If server-side success was flashed (redirect from POST), show toast
                <?php if ($success): ?>
                    try {
                        if (typeof __createToast === 'function') __createToast(<?= json_encode($success) ?>, 'success', 4000);
                        else document.addEventListener('DOMContentLoaded', function () { if (typeof __createToast === 'function') __createToast(<?= json_encode($success) ?>, 'success', 4000); });
                    } catch (e) { /* ignore */ }
                <?php endif; ?>
            })();
        </script>
    </div>
</section>