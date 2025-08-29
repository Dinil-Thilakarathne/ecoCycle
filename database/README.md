# Database Schema

This directory contains SQL schema files for setting up the EcoCycle database.

## Files

- `000_core_users_minimal.sql` – Minimal required tables: `roles`, `users`.
- `001_core_users_full.sql` – Extended auth stack: roles, users, profiles, password resets, sessions, activity log, email verifications, plus seed admin.

## Usage

1. Create database (example):

```sql
CREATE DATABASE ecocycle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecocycle;
```

2. Run minimal or full schema:

```bash
# Minimal
mysql -u <user> -p ecocycle < database/schema/000_core_users_minimal.sql

# Full
mysql -u <user> -p ecocycle < database/schema/001_core_users_full.sql
```

3. Replace the placeholder admin password hash before production:

```php
<?php echo password_hash('ChangeThisAdminPass!', PASSWORD_BCRYPT); ?>
```

4. Update your environment variables in `.env` (or `config/database.php`) to match your DB credentials.

## Generating a Password Hash

```php
php -r "echo password_hash('ChangeThisAdminPass!', PASSWORD_BCRYPT), PHP_EOL;"
```

## Optional Tables

If you don't need sessions, activity log, or email verification yet, start with the minimal schema to keep things lean.

## Next Steps

- Add domain tables (e.g. `waste_pickups`, `bids`, `transactions`).
- Create indexes for frequent query patterns.
- Set up a migration system if schema will evolve rapidly.
