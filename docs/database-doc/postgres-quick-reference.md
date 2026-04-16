# PostgreSQL Quick Reference for ecoCycle Developers

## Quick Start Commands

### Docker Operations

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Rebuild after code changes
docker-compose up -d --build

# View logs
docker-compose logs -f

# View database logs only
docker-compose logs -f db

# Restart specific service
docker-compose restart app
docker-compose restart db
```

### Database Access

```bash
# Connect to PostgreSQL CLI
docker-compose exec db psql -U postgres -d eco_cycle

# Run SQL file
docker-compose exec -T db psql -U postgres -d eco_cycle < your_file.sql

# Create database backup
docker-compose exec -T db pg_dump -U postgres eco_cycle > backup.sql

# Restore database backup
docker-compose exec -T db psql -U postgres -d eco_cycle < backup.sql
```

## PostgreSQL CLI Commands

Once connected via `psql`:

```sql
-- List all databases
\l

-- Connect to database
\c eco_cycle

-- List all tables
\dt

-- Describe table structure
\d users
\d+ users  -- detailed info

-- List all indexes
\di

-- List all views
\dv

-- Show table sizes
\dt+

-- Execute SQL from file
\i /path/to/file.sql

-- Toggle timing
\timing on

-- Exit psql
\q
```

## Common SQL Syntax Differences

### String Operations

```sql
-- MySQL
CONCAT(first_name, ' ', last_name)

-- PostgreSQL
first_name || ' ' || last_name
```

### Date/Time Functions

```sql
-- MySQL
NOW()
DATE_FORMAT(created_at, '%Y-%m-%d')
CURDATE()

-- PostgreSQL
CURRENT_TIMESTAMP
TO_CHAR(created_at, 'YYYY-MM-DD')
CURRENT_DATE
```

### Auto Increment

```sql
-- MySQL
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY
);

-- PostgreSQL
CREATE TABLE users (
  id SERIAL PRIMARY KEY
);
```

### Boolean Type

```sql
-- MySQL
is_active TINYINT(1) DEFAULT 0

-- PostgreSQL
is_active BOOLEAN DEFAULT false
```

### Limit with Offset

```sql
-- Both work the same
SELECT * FROM users LIMIT 10 OFFSET 20;
```

### Case Sensitivity

```sql
-- MySQL (case-insensitive by default)
SELECT * FROM users WHERE email = 'USER@EXAMPLE.COM';

-- PostgreSQL (case-sensitive, use ILIKE for case-insensitive)
SELECT * FROM users WHERE email ILIKE 'user@example.com';
```

### Insert with Conflict Handling

```sql
-- MySQL
INSERT INTO users (email, name) VALUES ('test@test.com', 'Test')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- PostgreSQL
INSERT INTO users (email, name) VALUES ('test@test.com', 'Test')
ON CONFLICT (email) DO UPDATE SET name = EXCLUDED.name;
```

## PHP PDO with PostgreSQL

### Basic Connection

```php
use Core\Database;

// Using the wrapper
$db = new Database('pgsql');

// Test connection
$result = Database::ping('pgsql');
if ($result['ok']) {
    echo "Connected!";
}
```

### Parameterized Queries

```php
// Named parameters (works with both MySQL and PostgreSQL)
$sql = "SELECT * FROM users WHERE email = :email AND status = :status";
$params = [':email' => $email, ':status' => 'active'];
$users = $db->fetchAll($sql, $params);

// Positional parameters
$sql = "SELECT * FROM users WHERE id = ?";
$user = $db->fetch($sql, [$userId]);
```

### Insert and Get ID

```php
$sql = "INSERT INTO users (name, email) VALUES (:name, :email)";
$db->query($sql, [':name' => $name, ':email' => $email]);
$newId = $db->lastInsertId();
```

### Working with JSONB

```php
// Insert JSON data
$sql = "INSERT INTO users (metadata) VALUES (:metadata)";
$db->query($sql, [':metadata' => json_encode(['key' => 'value'])]);

// Query JSON field
$sql = "SELECT * FROM users WHERE metadata->>'key' = :value";
$users = $db->fetchAll($sql, [':value' => 'value']);
```

### Transactions

```php
$pdo = $db->pdo();
try {
    $pdo->beginTransaction();

    // Multiple operations
    $db->query("INSERT INTO ...", []);
    $db->query("UPDATE ...", []);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

## Performance Tips

### Indexes

```sql
-- Create index
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);

-- Check if query uses index
EXPLAIN SELECT * FROM users WHERE email = 'test@test.com';

-- Create partial index
CREATE INDEX idx_active_users ON users(status) WHERE status = 'active';
```

### Query Optimization

```sql
-- Analyze query performance
EXPLAIN ANALYZE SELECT * FROM users
JOIN roles ON users.role_id = roles.id
WHERE users.status = 'active';

-- Update statistics
ANALYZE users;
ANALYZE;  -- all tables
```

## Useful Queries

### Count Records

```sql
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM users WHERE type = 'customer';
```

### Find Duplicate Emails

```sql
SELECT email, COUNT(*)
FROM users
GROUP BY email
HAVING COUNT(*) > 1;
```

### List Tables with Row Counts

```sql
SELECT
    schemaname,
    tablename,
    n_live_tup as row_count
FROM pg_stat_user_tables
ORDER BY n_live_tup DESC;
```

### Check Database Size

```sql
SELECT
    pg_size_pretty(pg_database_size('eco_cycle')) as database_size;
```

### Active Connections

```sql
SELECT
    pid,
    usename,
    application_name,
    client_addr,
    state
FROM pg_stat_activity
WHERE datname = 'eco_cycle';
```

### Recently Modified Tables

```sql
SELECT
    schemaname,
    tablename,
    last_vacuum,
    last_autovacuum,
    last_analyze
FROM pg_stat_user_tables
ORDER BY last_autovacuum DESC;
```

## Environment Variables Reference

```env
# PostgreSQL Configuration
DB_CONNECTION=pgsql          # Database driver
DB_HOST=127.0.0.1           # localhost or 'db' for Docker
DB_PORT=5432                # PostgreSQL default port
DB_DATABASE=eco_cycle       # Database name
DB_USERNAME=postgres        # Database user
DB_PASSWORD=your_password   # Database password
```

## Common Error Messages

| Error                             | Meaning               | Solution                           |
| --------------------------------- | --------------------- | ---------------------------------- |
| `SQLSTATE[08006]`                 | Connection refused    | Check if PostgreSQL is running     |
| `SQLSTATE[08001]`                 | Cannot connect        | Verify host and port               |
| `SQLSTATE[28P01]`                 | Authentication failed | Check username/password            |
| `relation "table" does not exist` | Table not found       | Check table name (case-sensitive)  |
| `column "col" does not exist`     | Column not found      | Check column name (case-sensitive) |
| `syntax error at or near`         | SQL syntax error      | Review PostgreSQL syntax           |

## Testing Database Connection

Create `test_connection.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/helpers.php';

use Core\Database;

echo "Testing PostgreSQL connection...\n";

try {
    $db = new Database('pgsql');
    echo "✅ Connection successful!\n";

    // Test query
    $result = $db->fetch("SELECT COUNT(*) as count FROM users");
    echo "✅ Query successful! User count: " . $result['count'] . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

## Helpful Resources

- [PostgreSQL Official Docs](https://www.postgresql.org/docs/15/)
- [PHP PDO PostgreSQL](https://www.php.net/manual/en/ref.pdo-pgsql.php)
- [PostgreSQL vs MySQL](https://www.postgresql.org/about/)
- [Docker PostgreSQL Image](https://hub.docker.com/_/postgres)

---

**Pro Tip:** Keep this reference handy while developing. Bookmark it in your browser!
