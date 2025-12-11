# MySQL to PostgreSQL Migration Guide for ecoCycle

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Step-by-Step Migration Process](#step-by-step-migration-process)
4. [Configuration Changes](#configuration-changes)
5. [Testing the Migration](#testing-the-migration)
6. [Troubleshooting](#troubleshooting)
7. [Rollback Plan](#rollback-plan)

## Overview

This guide walks you through migrating the ecoCycle application from MySQL/MariaDB to PostgreSQL using Docker. This ensures consistent development environments across all team members regardless of their operating system.

### Why PostgreSQL with Docker?

- **Consistent environments**: Same database setup on Windows, macOS, and Linux
- **No OS-specific issues**: Docker containers run identically on all platforms
- **Easy onboarding**: New team members can start with one command
- **Better database features**: PostgreSQL offers superior data types and concurrency
- **Production-ready**: Configuration works in both development and production

### What Changed?

- Database engine: MariaDB/MySQL → PostgreSQL 15
- Database driver: `mysqli` → PDO with `pgsql` driver
- Connection port: 3306 → 5432
- Default user: `root` → `postgres`
- Schema syntax: MySQL → PostgreSQL compatible

## Prerequisites

Before starting the migration, ensure you have:

1. **Docker Desktop** installed and running

   - Download from: https://www.docker.com/products/docker-desktop/
   - Minimum version: Docker 20.10+ and Docker Compose 2.0+

2. **Backup your current database**

   ```bash
   mysqldump -u root -p eco_cycle > backup_mysql_$(date +%Y%m%d).sql
   ```

3. **Stop any running XAMPP/MySQL services**

   ```bash
   # macOS/Linux
   sudo /Applications/XAMPP/xamppfiles/xampp stop mysql

   # Or stop via XAMPP control panel
   ```

4. **Pull the latest code changes**
   ```bash
   git pull origin feat/crud-operations
   ```

## Step-by-Step Migration Process

### Step 1: Environment Configuration

1. **Copy the environment example file:**

   ```bash
   cp .env.example .env
   ```

2. **Edit your `.env` file:**

   ```bash
   nano .env
   # or use your preferred editor
   ```

3. **Update database settings in `.env`:**

   ```env
   # Database Configuration
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=eco_cycle
   DB_USERNAME=postgres
   DB_PASSWORD=your_secure_password_here
   ```

   **Important Notes:**

   - Change `DB_PASSWORD` to a strong password
   - For Docker, `DB_HOST` will be `db` (set automatically in docker-compose.yml)
   - For local PostgreSQL, use `127.0.0.1`

### Step 2: Start Docker Services

1. **Build and start the containers:**

   ```bash
   docker-compose up -d --build
   ```

2. **Verify containers are running:**

   ```bash
   docker-compose ps
   ```

   You should see three services:

   - `ecocycle-app-1` (PHP application)
   - `ecocycle-db-1` (PostgreSQL database)
   - `ecocycle-caddy-1` (Web server)

3. **Check database logs:**

   ```bash
   docker-compose logs db
   ```

   Look for: `database system is ready to accept connections`

### Step 3: Verify Database Schema

1. **Connect to PostgreSQL container:**

   ```bash
   docker-compose exec db psql -U postgres -d eco_cycle
   ```

2. **Verify tables were created:**

   ```sql
   \dt
   ```

   You should see all tables: `roles`, `users`, `vehicles`, `waste_categories`, etc.

3. **Check roles seed data:**

   ```sql
   SELECT * FROM roles;
   ```

   Should show: admin, manager, collector, company, customer

4. **Exit PostgreSQL:**
   ```sql
   \q
   ```

### Step 4: Update Application Configuration

1. **Verify Database.php supports PostgreSQL:**

   The `src/Core/Database.php` file has been updated to support both MySQL and PostgreSQL drivers. No changes needed unless you've customized it.

2. **Check config/database.php:**

   The PostgreSQL configuration is already present in the connections array:

   ```php
   'pgsql' => [
       'driver' => 'pgsql',
       'host' => env('DB_HOST', '127.0.0.1'),
       'port' => env('DB_PORT', '5432'),
       // ... other settings
   ]
   ```

### Step 5: Test the Application

1. **Access the application:**

   ```
   http://localhost
   ```

2. **Test database connectivity:**

   Create a test file `public/test_db.php`:

   ```php
   <?php
   require_once __DIR__ . '/../vendor/autoload.php';
   require_once __DIR__ . '/../src/helpers.php';

   use Core\Database;

   $result = Database::ping('pgsql');

   if ($result['ok']) {
       echo "✅ PostgreSQL connection successful!";
   } else {
       echo "❌ Connection failed: " . $result['error'];
   }
   ```

   Visit: `http://localhost/test_db.php`

3. **Test basic operations:**
   - Register a new user
   - Login
   - Create a pickup request
   - Verify data is saved

## Configuration Changes

### Docker Compose (docker-compose.yml)

**Before (MySQL/MariaDB):**

```yaml
db:
  image: mariadb:11
  environment:
    MYSQL_DATABASE: eco_cycle
    MYSQL_USER: root
    MYSQL_PASSWORD: changeMeStrong
```

**After (PostgreSQL):**

```yaml
db:
  image: postgres:15-alpine
  environment:
    POSTGRES_DB: eco_cycle
    POSTGRES_USER: postgres
    POSTGRES_PASSWORD: changeMeStrong
```

### Dockerfile Changes

Added PostgreSQL PDO extension:

```dockerfile
RUN apt-get install -y libpq-dev \
  && docker-php-ext-install pdo_pgsql
```

### Schema Differences

| MySQL Syntax            | PostgreSQL Syntax         |
| ----------------------- | ------------------------- |
| `INT AUTO_INCREMENT`    | `SERIAL`                  |
| `TINYINT(1)`            | `BOOLEAN`                 |
| `DATETIME`              | `TIMESTAMP`               |
| `JSON`                  | `JSONB` (optimized)       |
| `ENUM('a','b')`         | `CREATE TYPE ... AS ENUM` |
| Backticks `` `table` `` | No backticks needed       |

## Testing the Migration

### Unit Tests

Run the application's test suite:

```bash
docker-compose exec app php vendor/bin/phpunit
```

### Manual Testing Checklist

- [ ] User registration works
- [ ] User login/logout works
- [ ] Password reset functionality
- [ ] Create pickup request
- [ ] View pickup requests
- [ ] Create bidding round
- [ ] Place bids
- [ ] View notifications
- [ ] Payment processing
- [ ] Dashboard statistics
- [ ] Analytics charts
- [ ] File uploads (profile images)

### Performance Testing

1. **Check query performance:**

   ```bash
   docker-compose exec db psql -U postgres -d eco_cycle
   ```

   ```sql
   -- Enable query timing
   \timing on

   -- Test a complex query
   SELECT u.*, r.name as role_name
   FROM users u
   LEFT JOIN roles r ON u.role_id = r.id
   LIMIT 100;
   ```

2. **Monitor container resources:**
   ```bash
   docker stats
   ```

## Troubleshooting

### Issue: Cannot connect to database

**Symptoms:**

```
SQLSTATE[08006] Connection refused
```

**Solutions:**

1. Check if database container is running:

   ```bash
   docker-compose ps
   ```

2. Restart database container:

   ```bash
   docker-compose restart db
   ```

3. Check database logs:
   ```bash
   docker-compose logs db
   ```

### Issue: Authentication failed

**Symptoms:**

```
FATAL: password authentication failed for user "postgres"
```

**Solutions:**

1. Verify `.env` file has correct password
2. Recreate database container with fresh volume:
   ```bash
   docker-compose down -v
   docker-compose up -d
   ```

### Issue: Tables not created

**Symptoms:**

- Empty database
- `relation "users" does not exist`

**Solutions:**

1. Check init scripts are mounted:

   ```bash
   docker-compose exec db ls -la /docker-entrypoint-initdb.d/
   ```

2. Manually run schema:
   ```bash
   docker-compose exec -T db psql -U postgres -d eco_cycle < database/postgresql/init/01_create_tables.sql
   ```

### Issue: SQL syntax errors

**Symptoms:**

```
ERROR: syntax error at or near "AUTO_INCREMENT"
```

**Solutions:**

- This means MySQL syntax is being used in PostgreSQL
- Review the query and convert to PostgreSQL syntax
- Check `docs/postgres-migration.md` for syntax differences

### Issue: PDO extension not found

**Symptoms:**

```
could not find driver
```

**Solutions:**

1. Rebuild Docker image:

   ```bash
   docker-compose down
   docker-compose build --no-cache
   docker-compose up -d
   ```

2. Verify extension is installed:
   ```bash
   docker-compose exec app php -m | grep pdo_pgsql
   ```

### Issue: Port 5432 already in use

**Symptoms:**

```
Error: bind: address already in use
```

**Solutions:**

1. Check what's using the port:

   ```bash
   lsof -i :5432
   ```

2. Stop local PostgreSQL:

   ```bash
   # macOS
   brew services stop postgresql

   # Linux
   sudo systemctl stop postgresql
   ```

## Rollback Plan

If you need to revert to MySQL:

### Quick Rollback

1. **Stop Docker containers:**

   ```bash
   docker-compose down
   ```

2. **Checkout previous commit:**

   ```bash
   git stash
   git checkout <previous-commit-hash>
   ```

3. **Restore MySQL backup:**

   ```bash
   mysql -u root -p eco_cycle < backup_mysql_YYYYMMDD.sql
   ```

4. **Update `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### Keep Both Databases

You can run both MySQL and PostgreSQL simultaneously for testing:

1. **Keep local MySQL running on port 3306**
2. **Run PostgreSQL in Docker on port 5432**
3. **Switch between them using `DB_CONNECTION` in `.env`:**

   ```env
   # For MySQL
   DB_CONNECTION=mysql

   # For PostgreSQL
   DB_CONNECTION=pgsql
   ```

## Additional Resources

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP PDO PostgreSQL](https://www.php.net/manual/en/ref.pdo-pgsql.php)
- [Migration Syntax Guide](./postgres-migration.md)

## Team Communication

After completing migration:

1. **Notify team members** to pull latest changes
2. **Share this migration guide**
3. **Schedule a sync** to help team members migrate
4. **Update project README** with new setup instructions

## Support

If you encounter issues not covered in this guide:

1. Check existing GitHub issues
2. Review Docker logs: `docker-compose logs`
3. Ask in team chat with error details
4. Create a new issue with:
   - OS and Docker version
   - Error messages
   - Steps to reproduce

---

**Last Updated:** October 23, 2025  
**Migration Version:** 1.0  
**Target PostgreSQL Version:** 15  
**Tested On:** Docker Desktop 4.x, macOS/Windows/Linux
