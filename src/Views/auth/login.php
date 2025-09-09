<?php
/**
 * Login portal selection view
 * Recreated to use the requested layout and class names.
 */
$error = $error ?? (session()->getFlash('error') ?? null);
$oldLogin = old('login', '');
?>

<section class="main-section auth-login-page">
    <div class="login-content">
        <div class="content-top">
            <h1>Welcome Back!</h1>
            <p>Please signin to access your dashboard.</p>
        </div>
        <form class="content-body" method="POST" action="/login">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <!-- server-side error will be shown inline under the submit button; no separate alert box -->
            <div class="form-select">
                <label for="role-select" class="sr-only">Choose role</label><br>
                <select id="role-select" aria-label="Select user role" style=" width:100%;" required>
                    <option value="" selected disabled>-- Choose a role --</option>
                    <option value="/customer">Customer — Track recycling requests &amp; status</option>
                    <option value="/collector">Collector — Manage pickups &amp; routes</option>
                    <option value="/company">Company — Operations &amp; analytics</option>
                    <option value="/admin">Admin — Platform configuration</option>
                </select>
            </div>
            <!-- login field: uses underlying native input with name="login" so PHP receives it -->
            <form-input label="Email or Username" name="login" placeholder="email@example.com or username"
                value="<?= htmlspecialchars($oldLogin) ?>" <?= $error ? '' : '' /* example of adding attributes conditionally */ ?> required></form-input>

            <!-- password field -->
            <form-input label="Password" name="password" required type="password"
                placeholder="Your password"></form-input>

            <p id="loginError" role="status" aria-live="polite" style="color:var(--danger);"
                class=" <?= $error ? "" : "visible" ?>">
                <?= $error ? htmlspecialchars($error) : '&nbsp;' ?>
            </p>

            <!-- Fallback for no-JS / component load failure: include native inputs so POST always contains fields -->
            <noscript>
                <div style="display:none;">
                    <input type="text" name="login" value="<?= htmlspecialchars($oldLogin) ?>" />
                    <input type="password" name="password" value="" />
                </div>
            </noscript>


            <div style="display:flex; gap:.5rem; margin-top:var(--space-2); width: 100%; flex-direction:column;">
                <button id="loginSubmit" type="submit" class="btn btn-gradient login-card__action">Sign in</button>
                <div class="forget-password">
                    <a href="/forgot-password">Forgot your password?</a>
                </div>
            </div>
        </form>

        <div class="content-footer">
            <a href="/register" class="btn btn-outline signup-btn">Sign up</a>
        </div>

        <script>
            (function () {
                // Ensure core elements exist before operating on them to avoid runtime errors
                var form = document.querySelector('form.content-body');
                var loginError = document.getElementById('loginError');

                // Attach AJAX submit handler first so it's always in place even if other code fails
                if (form) {
                    form.addEventListener('submit', function (e) {
                        try {
                            e.preventDefault();

                            var btnEl = document.getElementById('loginSubmit');
                            if (btnEl) btnEl.disabled = true;
                            // if (loginError) loginError.style.display = 'none';

                            var formData = new FormData(form);

                            fetch(form.action || '/login', {
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
                                    window.location.href = data.redirect || '/';
                                    return;
                                }

                                var msg = (data && data.message) ? data.message : 'Invalid email or password';
                                if (loginError) {
                                    loginError.textContent = msg;
                                    loginError.style.display = 'block';
                                } else {
                                    alert(msg);
                                }
                            }).catch(function (err) {
                                if (loginError) {
                                    loginError.textContent = 'Network error. Please try again.';
                                    loginError.style.display = 'block';
                                }
                            }).finally(function () {
                                if (btnEl) btnEl.disabled = false;
                            });
                        } catch (ex) {
                            // In case of any unexpected error, fall back to default submit
                            console.error('Login submit handler error', ex);
                        }
                    });
                }

                // Role-select button wiring (optional) — guarded to avoid exceptions if the element doesn't exist
                var select = document.getElementById('role-select');
                var roleBtn = document.getElementById('role-continue');

                if (select) {
                    function updateButton() {
                        if (!roleBtn) return;
                        var val = select.value;
                        if (val) {
                            roleBtn.removeAttribute('aria-disabled');
                            roleBtn.classList.remove('disabled');
                            roleBtn.href = val;
                        } else {
                            roleBtn.setAttribute('aria-disabled', 'true');
                            roleBtn.classList.add('disabled');
                            roleBtn.href = '#';
                        }
                    }

                    select.addEventListener('change', updateButton);
                    select.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' && select.value) {
                            window.location.href = select.value;
                        }
                    });

                    // initialize safely
                    try { updateButton(); } catch (e) { /* ignore */ }
                }

                // Wire up inline form demo fill button
                var fillDemo = document.getElementById('fillDemo');
                if (fillDemo) {
                    fillDemo.addEventListener('click', function () {
                        var inlineLogin = document.getElementById('inline_login');
                        var inlinePassword = document.getElementById('inline_password');
                        if (inlineLogin) inlineLogin.value = 'admin@ecocycle.com';
                        if (inlinePassword) inlinePassword.value = 'admin123';
                    });
                }

                // AJAX submit: prevents full page reload on invalid credentials
                var form = document.querySelector('form.content-body');
                var loginError = document.getElementById('loginError');
                // If server-side error was flashed, show it inline
                <?php if ($error): ?>
                    if (loginError) {
                        loginError.textContent = <?= json_encode($error) ?>;
                        loginError.style.display = 'block';
                    }
                <?php endif; ?>
            })();
        </script>
    </div>
</section>