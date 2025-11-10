# PostgreSQL Migration Guide

This document outlines the migration process from MySQL to PostgreSQL for the ecoCycle project.

## Migration Overview

The ecoCycle project has been updated to use PostgreSQL as the primary database system instead of MySQL. This migration brings several benefits:

- Better concurrency handling
- More advanced data types
- ACID compliance
- Enhanced reliability
- Improved security features
- OS agnostic deployment via Docker

## Key Changes

1. **Database Driver**: Changed from `mysqli` to PDO with PostgreSQL driver (`pgsql`)
2. **Schema Changes**: Converted MySQL schema to PostgreSQL syntax
3. **Docker Configuration**: Updated to use PostgreSQL container instead of MariaDB
4. **Connection Parameters**: Modified default connection parameters (port, username)

## Using the PostgreSQL Setup

### Local Development

For local development without Docker:

1. Install PostgreSQL on your development machine
2. Create a database named `eco_cycle`
3. Update your `.env` file:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=eco_cycle
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```
4. Import the schema:
   ```bash
   psql -U postgres -d eco_cycle < database/postgresql/init/01_create_tables.sql
   ```

### Docker Development

For development using Docker:

1. Ensure Docker and Docker Compose are installed
2. Run:
   ```bash
   docker-compose up -d
   ```
3. The database will be automatically set up using the init scripts

### Connection Testing

To test your PostgreSQL connection:

```php
$db = new \Core\Database();
$result = $db->ping();
if ($result['ok']) {
    echo "Connected successfully!";
} else {
    echo "Connection failed: " . $result['error'];
}
```

## Syntax Differences to Be Aware Of

When writing SQL queries, note these differences between MySQL and PostgreSQL:

1. **String Concatenation**:

   - MySQL: `CONCAT(first_name, ' ', last_name)`
   - PostgreSQL: `first_name || ' ' || last_name`

2. **Case Sensitivity**:

   - MySQL: Case-insensitive by default
   - PostgreSQL: Case-sensitive by default

3. **Limit with Offset**:

   - MySQL: `LIMIT 10 OFFSET 20`
   - PostgreSQL: `LIMIT 10 OFFSET 20` (same, but also supports `OFFSET 20 LIMIT 10`)

4. **Date Functions**:

   - MySQL: `NOW()`, `DATE_FORMAT(date, '%Y-%m-%d')`
   - PostgreSQL: `CURRENT_TIMESTAMP`, `TO_CHAR(date, 'YYYY-MM-DD')`

5. **Boolean Values**:

   - MySQL: Uses `TINYINT(1)` with 0/1
   - PostgreSQL: Has a native `BOOLEAN` type with true/false

6. **Database Dumps**:
   - MySQL: `mysqldump -u username -p database_name > dump.sql`
   - PostgreSQL: `pg_dump -U username database_name > dump.sql`

## Troubleshooting

Common issues and solutions:

1. **Connection Refused**:

   - Verify PostgreSQL is running
   - Check port (5432 is default)
   - Ensure your IP is allowed in `pg_hba.conf`

2. **Authentication Failed**:

   - Verify username and password
   - Check PostgreSQL authentication method

3. **SQL Syntax Errors**:

   - Look for MySQL-specific syntax in your queries
   - Review case sensitivity in table/column names

4. **Missing Extensions**:
   - Ensure `pdo_pgsql` PHP extension is installed
   - Some features might require specific PostgreSQL extensions
