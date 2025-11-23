# PostgreSQL Seed Data Documentation

## Overview

This document explains how to populate demo and minimal data into the PostgreSQL database for development.

---

## PostgreSQL Seed Files (NEW - Use These!)

Located in `database/postgresql/init/`:

### 1. `03_seed_dummy_data.sql` ✅

**Purpose**: Main seed file with comprehensive demo data  
**Contains**: 5 waste categories, 5 vehicles, 4 customers, 4 companies, 4 collectors, 6 payments, 5 bidding rounds, 4 pickup requests

**When to use**: First-time setup, development environment

### 2. `04_seed_collector_tasks_demo.sql` ✅

**Purpose**: Focused seed for testing collector dashboard  
**Contains**: 1 demo collector, 3 customers, 3 pickup requests in different states

**When to use**: Testing collector UI/features

### 3. `05_seed_company_dashboard_demo.sql` ✅

**Purpose**: Focused seed for testing company dashboard  
**Contains**: 3 companies, 4 waste categories with colors, 6 bidding rounds, 12 bids, 5 payments

**When to use**: Testing company bidding/dashboard UI

---

## Automatic Seeding with Docker (Recommended)

When you start Docker containers, PostgreSQL automatically runs all `.sql` files in `database/postgresql/init/`:

```bash
docker-compose -f docker-compose.dev.yml up -d
```

**Note**: Init scripts only run on **first container creation**. To re-seed:

```bash
docker-compose -f docker-compose.dev.yml down -v  # Remove volumes
docker-compose -f docker-compose.dev.yml up -d    # Recreate with fresh data
```

---

## OBSOLETE MySQL Seed Files (Do NOT Use!)

These files in `database/seeds/` are **NO LONGER COMPATIBLE** with PostgreSQL:

- ❌ `database/seeds/seed_dummy_data.sql` - Use `database/postgresql/init/03_seed_dummy_data.sql` instead
- ❌ `database/seeds/seed_collector_tasks_demo.sql` - Use `database/postgresql/init/04_seed_collector_tasks_demo.sql` instead
- ❌ `database/seeds/seed_company_dashboard_demo.sql` - Use `database/postgresql/init/05_seed_company_dashboard_demo.sql` instead

---

## PHP Seed Scripts Status

### ⚠️ `scripts/seed_db.php`

**Status**: NEEDS UPDATING - Currently runs MySQL seed file  
**Recommendation**: Use Docker automatic seeding instead

### ⚠️ `scripts/seed.php`

**Status**: May work with PostgreSQL if it uses PDO  
**Purpose**: Lightweight seeder - creates roles and demo users via model classes  
**Note**: Should be tested with PostgreSQL

### ⚠️ `scripts/seed_collector_tasks_demo.php` & `scripts/seed_company_dashboard_demo.php`

**Status**: OBSOLETE - Use PostgreSQL seed files directly instead

---

## Verification After Seeding

Check that data was loaded successfully:

```bash
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle -c "
SELECT 'users' as table_name, COUNT(*) FROM users
UNION ALL SELECT 'vehicles', COUNT(*) FROM vehicles
UNION ALL SELECT 'waste_categories', COUNT(*) FROM waste_categories
UNION ALL SELECT 'pickup_requests', COUNT(*) FROM pickup_requests
UNION ALL SELECT 'bidding_rounds', COUNT(*) FROM bidding_rounds
UNION ALL SELECT 'payments', COUNT(*) FROM payments;"
```

**Expected counts** (if all 3 seed files run):

- users: 14+
- vehicles: 7
- waste_categories: 5
- pickup_requests: 7+
- bidding_rounds: 11
- payments: 11

Or visit: `http://localhost:8080/test-db-connection.php`

---

## Manual Seeding (Advanced)

To run a specific seed file manually:

```bash
# Run a single seed file
docker exec -i ecocycle-db-1 psql -U postgres -d ecocycle < database/postgresql/init/03_seed_dummy_data.sql

# Or connect interactively
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle
\i /docker-entrypoint-initdb.d/03_seed_dummy_data.sql
```

---

## Key MySQL to PostgreSQL Changes

| Feature          | MySQL                              | PostgreSQL                               |
| ---------------- | ---------------------------------- | ---------------------------------------- |
| Insert or ignore | `INSERT IGNORE`                    | `INSERT ... ON CONFLICT DO NOTHING`      |
| Upsert           | `ON DUPLICATE KEY UPDATE`          | `ON CONFLICT ... DO UPDATE`              |
| Current time     | `NOW()`                            | `CURRENT_TIMESTAMP`                      |
| Date arithmetic  | `DATE_ADD(NOW(), INTERVAL 2 HOUR)` | `CURRENT_TIMESTAMP + INTERVAL '2 hours'` |
| JSON objects     | `JSON_OBJECT('key', 'value')`      | `jsonb_build_object('key', 'value')`     |
| JSON extract     | `JSON_EXTRACT(col, '$.key')`       | `col::json->>'key'`                      |
| Auto increment   | `AUTO_INCREMENT`                   | `SERIAL`                                 |

---

## Troubleshooting

### Seed files not running?

Init scripts only run on first database creation. Reset:

```bash
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d
```

### Duplicate key errors?

Seed files use `ON CONFLICT` clauses for idempotency. Check for ID conflicts in tables with string IDs (`payments`, `bidding_rounds`, `pickup_requests`).

### Syntax errors?

Make sure you're using PostgreSQL seed files from `database/postgresql/init/`, not MySQL files from `database/seeds/`.

---

## File Cleanup Recommendations

**Can be archived** (backup first):

```bash
mkdir -p database/seeds_mysql_backup
mv database/seeds/*.sql database/seeds_mysql_backup/
```

**Obsolete files**:

- `database/seeds/seed_dummy_data.sql` (MySQL)
- `database/seeds/seed_collector_tasks_demo.sql` (MySQL)
- `database/seeds/seed_company_dashboard_demo.sql` (MySQL)
- `scripts/seed_collector_tasks_demo.php`
- `scripts/seed_company_dashboard_demo.php`
- `scripts/seed_db.php` (needs updating for PostgreSQL)

---

## Security Notes

- Never seed sensitive or production data in development repos
- Keep demo data non-sensitive (fake names, emails, phone numbers)
- Don't commit real bank account numbers or personal information
- Demo passwords should be simple (e.g., "password") and documented

---

## Support

For issues:

- Check logs: `docker logs ecocycle-db-1`
- Test connection: `http://localhost:8080/test-db-connection.php`
- Use DBeaver: See `docs/DOCKER_SETUP_WITH_DBEAVER.md`
