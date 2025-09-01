<?php
/**
 * Login view
 * Accepts single 'login' field which can be an email or username, and 'password'.
 */
$error = $error ?? null;
?>

<section class="main-section auth-login-page">
    <div class="container" style="max-width:480px; margin: 4rem auto;">
        <div class="card">
            <header style="text-align:center; margin-bottom: 1.5rem;">
                <h1>Sign in to EcoCycle</h1>
                <p class="muted">Use your email or username to sign in.</p>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/login">
                <div class="form-group">
                    <label for="login">Email or Username</label>
                    <input id="login" name="login" type="text" required maxlength="150"
                        placeholder="email@example.com or username" value="<?= htmlspecialchars(old('login', '')) ?>" />
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required />
                </div>

                <div style="margin-top:1rem;">
                    <button class="btn btn-primary" type="submit">Sign in</button>
                </div>
            </form>

            <!-- Demo quick-fill: keep existing page, but provide optional email/password inputs
                 that copy into the main login form when used. This does NOT replace the page. -->
            <div style="margin-top:1.5rem; border-top:1px solid #eee; padding-top:1rem;">
                <h3 style="margin:0 0 0.5rem 0; font-size:1rem;">Quick demo login</h3>
                <p class="muted" style="margin:0 0 0.75rem 0;">Use these fields to auto-fill the main login form.</p>

                <div class="form-group">
                    <label for="demo_email">Email</label>
                    <input id="demo_email" type="email" value="admin@ecocycle.com" />
                </div>

                <div class="form-group">
                    <label for="demo_password">Password</label>
                    <input id="demo_password" type="text" value="admin123" />
                </div>

                <div style="display:flex; gap:.5rem;">
                    <button id="fillBtn" class="btn" type="button">Fill fields</button>
                    <button id="fillAndSubmitBtn" class="btn btn-primary" type="button">Fill & Sign in</button>
                </div>
            </div>

            <script>
                (function () {
                    const fillBtn = document.getElementById('fillBtn');
                    const fillAndSubmitBtn = document.getElementById('fillAndSubmitBtn');
                    const demoEmail = document.getElementById('demo_email');
                    const demoPassword = document.getElementById('demo_password');
                    const mainLogin = document.getElementById('login');
                    const mainPassword = document.getElementById('password');
                    const mainForm = document.querySelector('form[action="/login"]');

                    fillBtn.addEventListener('click', function () {
                        if (mainLogin) mainLogin.value = demoEmail.value;
                        if (mainPassword) mainPassword.value = demoPassword.value;
                    });

                    fillAndSubmitBtn.addEventListener('click', function () {
                        if (mainLogin) mainLogin.value = demoEmail.value;
                        if (mainPassword) mainPassword.value = demoPassword.value;
                        if (mainForm) mainForm.submit();
                    });
                })();
            </script>
        </div>
    </div>
</section>