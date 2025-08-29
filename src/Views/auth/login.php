<?php
// Simple login role selection page (no actual auth logic yet)
?>

<section class="main-section auth-login-page">
    <div class="container" style="padding-inline: var(--space-8);">
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
            <article class="login-card" data-role="customer">
                <div class="login-card__body">
                    <h2 class="login-card__title">Customer</h2>
                    <p class="login-card__desc">Track recycling requests & status.</p>
                </div>
                <a class="btn btn-gradient login-card__action" href="/customer">Login as Customer</a>
            </article>

            <article class="login-card" data-role="collector">
                <div class="login-card__body">
                    <h2 class="login-card__title">Collector</h2>
                    <p class="login-card__desc">Manage assigned pickups & routes.</p>
                </div>
                <a class="btn btn-gradient login-card__action" href="/collector">Login as Collector</a>
            </article>

            <article class="login-card" data-role="company">
                <div class="login-card__body">
                    <h2 class="login-card__title">Company</h2>
                    <p class="login-card__desc">Oversee operations & analytics.</p>
                </div>
                <a class="btn btn-gradient login-card__action" href="/company">Login as Company</a>
            </article>

            <article class="login-card" data-role="admin">
                <div class="login-card__body">
                    <h2 class="login-card__title">Admin</h2>
                    <p class="login-card__desc">Platform configuration & users.</p>
                </div>
                <a class="btn btn-gradient login-card__action" href="/admin">Login as Admin</a>
            </article>
        </div>
    </div>
</section>