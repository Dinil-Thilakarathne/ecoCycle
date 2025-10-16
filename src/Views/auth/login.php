<?php
// Simple login role selection page (no actual auth logic yet)
?>

<section class="main-section auth-login-page">
    <div class="container" style="padding-inline: var(--space-8);">
        <div>
            <header style="text-align:center; margin-bottom: var(--space-16);">
                <h1 class="gradient-text"
                    style="font-size: var(--text-6xl); font-weight: var(--font-weight-bold); line-height:1.1;">
                    Choose Your Portal
                </h1>
                <p style="margin-top: var(--space-4); color: var(--neutral-600); font-size: var(--text-lg);">
                    Select the user type to continue to the login page.
                </p>
            </header>

            <div class="login-role-grid">
                <article class="login-card" data-role="role-select">
                    <div class="login-card__body">
                        <h2 class="login-card__title">Select your role</h2>
                        <p class="login-card__desc">Pick a portal from the dropdown and continue to its login page.</p>

                        <div class="form-select">
                            <label for="role-select" class="sr-only">Choose role</label>
                            <select id="role-select" aria-label="Select user role"
                                style=" width:100%; max-width:360px;">
                                <option value="" selected disabled>-- Choose a role --</option>
                                <option value="/customer">Customer — Track recycling requests & status</option>
                                <option value="/collector">Collector — Manage pickups & routes</option>
                                <option value="/company">Company — Operations & analytics</option>
                                <option value="/admin">Admin — Platform configuration</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: var(--space-8);">
                        <a id="role-continue" class="btn btn-gradient login-card__action" href="#" role="button"
                            aria-disabled="true">Continue</a>
                    </div>
                </article>
            </div>
        </div>

        <script>
            (function () {
                var select = document.getElementById('role-select');
                var btn = document.getElementById('role-continue');

                function updateButton() {
                    var val = select.value;
                    if (val) {
                        btn.removeAttribute('aria-disabled');
                        btn.classList.remove('disabled');
                        btn.href = val;
                    } else {
                        btn.setAttribute('aria-disabled', 'true');
                        btn.classList.add('disabled');
                        btn.href = '#';
                    }
                }

                // update on change
                select.addEventListener('change', updateButton);

                // support keyboard Enter on select: navigate when Enter pressed
                select.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && select.value) {
                        window.location.href = select.value;
                    }
                });

                // initialize
                updateButton();
            })();
        </script>
    </div>
</section>