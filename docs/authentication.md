# Authentication & Authorization Guide

This project implements a lightweight authentication + role-based authorization layer.

## Supported Roles

- admin
- customer
- collector
- company

## Login Flow

1. User submits `/login` form with `email` + `password`.
2. `AuthController@login` attempts DB lookup via `Models\User::findByEmail`.
3. If DB not available or user not found, it falls back to demo users defined in `config/auth.php` (`demo_users`).
4. Password check:
   - DB user: `verifyPassword()` (supports bcrypt hash or plain fallback in dev).
   - Demo user: plain string compare (development only).
5. On success, session keys are stored:
   - `user_id`, `user_name`, `user_email`, `user_role`.
6. User is redirected to their dashboard using `dashboard_redirect()` helper.

Demo credentials (development):
| Role | Email | Password |
|------|-------------------------|-------------|
| admin | admin@ecocycle.com | admin123 |
| customer | customer@ecocycle.com | customer123 |
| collector | collector@ecocycle.com | collector123 |
| company | company@ecocycle.com | company123 |

> Replace or remove demo users before production.

## Session Helper Functions

- `auth()` returns current authenticated user array or `null`.
- `dashboard_redirect()` sends user to role-specific dashboard.
- `can_access_dashboard($role)` returns boolean.

## Middleware

### AuthMiddleware

Ensures a user is authenticated or redirects to `/login` (or JSON 401 if API request expects JSON).

### RoleMiddleware

Generic role gate used internally by specialized role-only middlewares.

### Role-Specific Middlewares

Located in `src/Middleware/Roles/`:

- `AdminOnly`
- `CustomerOnly`
- `CollectorOnly`
- `CompanyOnly`
  Each extends `RoleMiddleware` with a preset single allowed role.

## Automatic Route Protection

Dashboard routes are auto-generated in `RouteConfig::registerDashboardRoutes()`. Each dashboard route now receives:

```
AuthMiddleware
<ROLE>Only (e.g., AdminOnly, CustomerOnly, ...)
```

This enforces both authentication and correct role segregation.

## Removing Demo Auth

1. Delete or clear `demo_users` in `config/auth.php`.
2. Ensure database tables `users` + `roles` exist and contain hashed passwords.
3. Update registration logic in `AuthController@register` to create real users.

## Adding a New Role

1. Add new navigation set to `NavigationConfig`.
2. Add mapping in `RouteConfig::roleMiddlewareClass()`.
3. Create new RoleOnly middleware class in `src/Middleware/Roles/` extending `RoleMiddleware`.
4. Add demo user (optional) in `config/auth.php`.

## API Authentication

Use the same session-based auth. For stateless APIs you could issue tokens:

- Add a `api_tokens` table.
- Create middleware checking `Authorization: Bearer <token>`.

## Security Notes

- Demo passwords are plain text; never ship to production.
- Always hash new passwords using `password_hash($plain, PASSWORD_BCRYPT)`.
- Consider CSRF protection on state-changing POST routes (CsrfMiddleware already present).

## Troubleshooting

| Issue                       | Cause                        | Fix                                                                                                            |
| --------------------------- | ---------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Always redirected to /login | Session not started          | Ensure `Application` boots and sessions directory writable.                                                    |
| Wrong dashboard redirect    | Missing or wrong `user_role` | Inspect session contents; ensure role name matches keys in `config/auth.php` and `dashboard_redirect` mapping. |
| 403 after login             | Role middleware mismatch     | Verify role string EXACTLY matches (admin/customer/collector/company).                                         |

## Quick Validation Checklist

- Can log in with each demo credential.
- Visiting another role's URL redirects back to correct dashboard.
- Direct /admin when logged out → /login.
- API request to a protected route with `Accept: application/json` returns JSON 401.

## Next Steps (Optional Enhancements)

- Implement password reset flow.
- Add remember-me token cookies.
- Introduce permission (fine-grained) layer beyond roles.
- Use JWT for external API consumers.
- Add rate limiting per user (ThrottleMiddleware extension).
