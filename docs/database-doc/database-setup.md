## Database setup for collaborators

This document explains how to create the database and schema used by this project, and how to verify the setup on a collaborator's machine or CI environment.

Checklist

- Update `config/database.php` or provide equivalent environment variables for your MySQL instance.
- Install PHP dependencies with Composer.
- Create the physical database (if not present).
- Apply the SQL schema (`database/create_tables.sql`) using the project's setup script.
- (Optional) Run seeders to populate demo/test data.

Prerequisites

- PHP 8+ and Composer
- MySQL / MariaDB (MySQL 5.7+ recommended for JSON column support)
- Project checked out and `vendor/` installed

1. Install dependencies

```bash
cd /path/to/ecoCycle
composer install
```

2. Configure database connection

Edit `config/database.php` to match your local credentials (host, port, database, username, password) or export environment variables used by your deployment.

3. Create the empty database (if required)

Replace `root` and `eco_cycle` with your DB user and project DB name as needed.

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS eco_cycle CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

4. Apply schema (idempotent)

The provided script runs the `database/create_tables.sql` file and uses `CREATE TABLE IF NOT EXISTS` where applicable.

```bash
php scripts/setup_db.php
```

Notes

- The script is idempotent: safe to re-run. If you want to skip it (for CI or special cases), set the env var:

```bash
SKIP_DB_SETUP=1 php scripts/setup_db.php
```

5. Verify tables exist

Quick PHP check using the app's DB wrapper:

```bash
php -r "require 'vendor/autoload.php'; \$db=new Core\\Database(); print_r(\$db->fetchAll('SHOW TABLES'));"
```

Or using MySQL client:

```bash
mysql -u root -p -e "USE eco_cycle; SHOW TABLES;"
```

Troubleshooting

- Permission errors: ensure DB user has CREATE, ALTER, INSERT privileges on the database.
- JSON columns: MySQL older than 5.7 may not support JSON; use TEXT columns instead or upgrade.
- Foreign key errors during schema apply: the setup script executes statements in order; re-running it will complete missing tables. If problems persist, run the SQL file directly with a MySQL client.

Production considerations

- Do NOT run the demo seeds in production unless explicitly intended. Use migrations for production schema changes.
- Prefer a migration tool for long-term schema management (Phinx, Doctrine Migrations, or a small in-repo migrations runner).

Next steps

- See `docs/seeding.md` for seeding options (demo data vs minimal app seed) and automation suggestions.
