# EcoCycle Windows Setup Guide

This guide walks through setting up the EcoCycle project on a fresh Windows machine, including environment preparation, database initialization, authentication defaults, and demo data seeding.

## Prerequisites

- **PHP 8.0+** (install via [Scoop](https://scoop.sh/), [Chocolatey](https://chocolatey.org/), or XAMPP/WAMP)
- **Composer** (PHP dependency manager)
- **Git**
- **MySQL 5.7+ / MariaDB** (ships with XAMPP/WAMP)
- Ensure `php`, `composer`, and `mysql` commands are available in your terminal

```powershell
php -v
composer -V
mysql --version
```

## Project Checkout & Dependencies

```powershell
git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle
composer install
```

Copy the environment template and update values to match your local database:

```powershell
copy .env.example .env
```

Edit `.env` (or `config/database.php`) with your Windows MySQL credentials:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eco_cycle
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Database Setup

1. **Create the database schema (once):**

   ```powershell
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS eco_cycle CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
   ```

2. **Apply tables using the project script (idempotent):**

   ```powershell
   php scripts/setup_db.php
   ```

3. **Verify tables exist** (pick your preferred check):
   ```powershell
   mysql -u root -p -e "USE eco_cycle; SHOW TABLES;"
   ```
   ```powershell
   php -r "require 'vendor/autoload.php'; $db=new Core\\Database(); print_r($db->fetchAll('SHOW TABLES'));"
   ```

> Set `SKIP_DB_SETUP=1` if you need to skip schema creation (CI or scripted flows).

## Authentication Defaults

Development logins fall back to demo users defined in `config/auth.php`:

| Role      | Email                  | Password     |
| --------- | ---------------------- | ------------ |
| admin     | admin@ecocycle.com     | admin123     |
| customer  | customer@ecocycle.com  | customer123  |
| collector | collector@ecocycle.com | collector123 |
| company   | company@ecocycle.com   | company123   |

To switch to real users, remove the `demo_users` block and ensure the `users` and `roles` tables contain hashed credentials.

## Data Seeding Options

Run seeds from the project root:

- **Minimal roles + sample users (idempotent):**

  ```powershell
  php scripts/seed.php
  ```

- **Full demo dataset (vehicles, bids, pickups, payments, etc.):**
  ```powershell
  php scripts/seed_db.php
  ```

Notes:

- `seed.php` safely skips existing entries.
- `seed_db.php` executes `database/seeds/seed_dummy_data.sql`; repeated runs may emit duplicate key warnings but remain safe.
- Skip seeding by setting the environment variable before running the script:
  ```powershell
  set SKIP_DB_SEED=1
  php scripts/seed_db.php
  ```

## Running the Application

Start a development server from the project root:

```powershell
php -S localhost:8000 -t public
```

Alternatively configure Apache/Nginx (XAMPP/WAMP) to point the virtual host document root at `public/index.php`.

Visit `http://localhost:8000` and log in with one of the demo credentials to confirm dashboards, role routing, and database connectivity.

## Recommended Follow-Up

1. Configure HTTPS or a virtual host in your Windows stack.
2. Replace demo authentication with real registration and hashed passwords before production.
3. Script these setup steps (PowerShell or batch) to streamline future onboarding.

## References

- `docs/database-setup.md`
- `docs/seeding.md`
- `docs/authentication.md`
