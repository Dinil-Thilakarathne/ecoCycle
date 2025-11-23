# Docker PostgreSQL Setup Guide with DBeaver

## ✅ Status: Successfully Running!

Your Docker PostgreSQL setup is now operational with DBeaver.

## Current Configuration

### Running Services

```
✔ Container ecocycle-db   - PostgreSQL 15 (Port 5432)
✔ Container ecocycle-app  - PHP 8.2 + Apache (Port 8080)
```

### Connection Details

| Property     | Value                      |
| ------------ | -------------------------- |
| **Host**     | `localhost` or `127.0.0.1` |
| **Port**     | `5432`                     |
| **Database** | `eco_cycle`                |
| **Username** | `ecocycle_user`            |
| **Password** | `ecocycle_dev_password`    |

## Prerequisites

- ✅ Docker Desktop installed and running
- ✅ DBeaver installed
- ✅ Terminal access (zsh)

## Quick Start (Already Completed!)

### Step 1: Start Docker Containers ✅

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/ecoCycle
docker-compose -f docker-compose.dev.yml up -d
```

### Step 2: Verify Containers ✅

```bash
docker-compose -f docker-compose.dev.yml ps
```

Expected output:

```
NAME               STATUS
ecocycle-db        Up (healthy)
ecocycle-app       Up (healthy)
```

### Step 3: Database is Ready ✅

PostgreSQL has been initialized with all tables and seed data.

```bash
docker-compose -f docker-compose.dev.yml logs db
```

Look for:

```
database system is ready to accept connections
```

## Connecting with DBeaver

### Method 1: Direct Connection (Recommended)

1. **Open DBeaver**

2. **Create New Connection**

   - Click: `Database` → `New Database Connection`
   - Or click the plug icon in toolbar

3. **Select PostgreSQL**

   - Choose `PostgreSQL` from the list
   - Click `Next`

4. **Enter Connection Details**

   **Main Tab:**

   ```
   Host:     localhost
   Port:     5432
   Database: eco_cycle
   Username: ecocycle_user
   Password: ecocycle_dev_password
   ```

   📝 **Note:** These credentials are from your `docker-compose.dev.yml` file.

5. **Test Connection**

   - Click `Test Connection` button
   - First time: DBeaver will ask to download PostgreSQL drivers
   - Click `Download` and wait for completion
   - Click `Test Connection` again
   - You should see: ✅ **"Connected"** with PostgreSQL version

6. **Save Connection**
   - Click `Finish`
   - Your connection appears in Database Navigator (left sidebar)
   - Name it "ecoCycle - Dev" for easy identification

### Connection Details Reference

| Setting      | Value                      | Notes                          |
| ------------ | -------------------------- | ------------------------------ |
| **Host**     | `localhost` or `127.0.0.1` | Docker exposes on host machine |
| **Port**     | `5432`                     | Standard PostgreSQL port       |
| **Database** | `eco_cycle`                | Main database name             |
| **Username** | `ecocycle_user`            | Database user (not `postgres`) |
| **Password** | `ecocycle_dev_password`    | Development password           |
| **Schema**   | `public`                   | Default PostgreSQL schema      |

### Alternative: pgAdmin Web Interface

If you prefer a web interface, pgAdmin is also available:

```
URL:      http://localhost:5050
Email:    admin@ecocycle.local
Password: admin123
```

**Note:** We removed pgAdmin from the setup since you're using DBeaver.

## Verifying Your Setup

### 1. Check Tables in DBeaver

Once connected, you should see these tables:

- `roles`
- `users`
- `vehicles`
- `waste_categories`
- `pickup_requests`
- `pickup_request_wastes`
- `bidding_rounds`
- `bids`
- `payments`
- `notifications`
- `system_alerts`
- `analytics_aggregates`

### 2. Run a Test Query

In DBeaver, open SQL Editor and run:

```sql
-- Check if tables exist
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public'
ORDER BY table_name;

-- Check roles data
SELECT * FROM roles;

-- Count users
SELECT COUNT(*) as total_users FROM users;
```

### 3. Test Application Connection

Visit your application:

```
http://localhost:8080
```

## Alternative: Using pgAdmin (Web Interface)

If you prefer a web-based interface:

1. **Open pgAdmin**

   ```
   http://localhost:5050
   ```

2. **Login**

   - Email: `admin@ecocycle.local`
   - Password: `admin123`

3. **Add Server**

   - Right-click `Servers` → `Create` → `Server`

   **General Tab:**

   - Name: `ecoCycle Local`

   **Connection Tab:**

   - Host: `db` (or `ecocycle-db`)
   - Port: `5432`
   - Database: `eco_cycle`
   - Username: `postgres`
   - Password: `secret123`

   - ✅ Save Password

4. **Click Save**

## Common Docker Commands

### Start Containers

```bash
# Start in background
docker-compose -f docker-compose.dev.yml up -d

# Start and view logs
docker-compose -f docker-compose.dev.yml up
```

### Stop Containers

```bash
docker-compose -f docker-compose.dev.yml down
```

### Restart Containers

```bash
# Restart all
docker-compose -f docker-compose.dev.yml restart

# Restart just database
docker-compose -f docker-compose.dev.yml restart db
```

### View Logs

```bash
# All services
docker-compose -f docker-compose.dev.yml logs -f

# Just database
docker-compose -f docker-compose.dev.yml logs -f db

# Just app
docker-compose -f docker-compose.dev.yml logs -f app
```

### Database Operations

#### Connect to PostgreSQL CLI

```bash
docker-compose -f docker-compose.dev.yml exec db psql -U postgres -d eco_cycle
```

Once connected, you can run SQL commands:

```sql
-- List tables
\dt

-- Describe users table
\d users

-- Query data
SELECT * FROM roles;

-- Exit
\q
```

#### Backup Database

```bash
docker-compose -f docker-compose.dev.yml exec -T db pg_dump -U postgres eco_cycle > backup_$(date +%Y%m%d).sql
```

#### Restore Database

```bash
docker-compose -f docker-compose.dev.yml exec -T db psql -U postgres -d eco_cycle < backup_20251023.sql
```

#### Reset Database (Fresh Start)

```bash
# Stop containers and remove volumes
docker-compose -f docker-compose.dev.yml down -v

# Start again (will recreate database)
docker-compose -f docker-compose.dev.yml up -d
```

## Troubleshooting

### Issue: Can't Connect with DBeaver

**Problem:** Connection refused or timeout

**Solutions:**

1. **Check if containers are running:**

   ```bash
   docker-compose -f docker-compose.dev.yml ps
   ```

2. **Check PostgreSQL logs:**

   ```bash
   docker-compose -f docker-compose.dev.yml logs db
   ```

3. **Verify port is accessible:**

   ```bash
   lsof -i :5432
   ```

4. **Try restarting database:**

   ```bash
   docker-compose -f docker-compose.dev.yml restart db
   ```

5. **Check firewall:** Make sure port 5432 isn't blocked

### Issue: Authentication Failed

**Problem:** Password incorrect

**Solution:**

- Verify password in `.env` file matches DBeaver
- Default is: `secret123`
- If changed, restart containers:
  ```bash
  docker-compose -f docker-compose.dev.yml down -v
  docker-compose -f docker-compose.dev.yml up -d
  ```

### Issue: Database Empty (No Tables)

**Problem:** Connected but no tables visible

**Solutions:**

1. **Check init scripts ran:**

   ```bash
   docker-compose -f docker-compose.dev.yml logs db | grep "init"
   ```

2. **Manually run schema:**

   ```bash
   docker-compose -f docker-compose.dev.yml exec -T db psql -U postgres -d eco_cycle < database/postgresql/init/01_create_tables.sql
   ```

3. **Check for errors:**
   ```sql
   -- In DBeaver, run:
   SELECT * FROM pg_stat_activity;
   ```

### Issue: Port Already in Use

**Problem:** Port 5432 already in use

**Solutions:**

1. **Check what's using the port:**

   ```bash
   lsof -i :5432
   ```

2. **Stop local PostgreSQL if running:**

   ```bash
   brew services stop postgresql
   # or
   sudo systemctl stop postgresql
   ```

3. **Or change port in `docker-compose.dev.yml`:**

   ```yaml
   ports:
     - "5433:5432" # Use 5433 on host instead
   ```

   Then connect with DBeaver using port `5433`

### Issue: Permission Denied

**Problem:** Can't access volumes or files

**Solution:**

```bash
# Fix permissions
sudo chmod -R 755 database/
sudo chown -R $USER database/
```

## DBeaver Tips & Tricks

### 1. Entity Relationship Diagram

View your database schema visually:

- Right-click database → `View Diagram`
- Or: Select tables → Right-click → `View Diagram`

### 2. SQL Editor Shortcuts

- `Ctrl/Cmd + Enter` - Execute current statement
- `Ctrl/Cmd + Shift + Enter` - Execute script
- `Ctrl/Cmd + /` - Comment/uncomment
- `Ctrl/Cmd + Space` - Auto-complete

### 3. Data Export

Export query results:

- Run query
- Right-click results → `Export Data`
- Choose format (CSV, JSON, SQL, etc.)

### 4. Compare Databases

- Right-click connection → `Compare` → `Compare With...`
- Useful when syncing schemas between environments

### 5. Multiple Connections

Create separate connections for:

- Local Development (localhost:5432)
- Production (when deployed)
- Backup testing

## Environment Variables Reference

Your `.env` file contains these PostgreSQL settings:

```env
DB_CONNECTION=pgsql
DB_HOST=db                # 'localhost' for DBeaver
DB_PORT=5432
DB_DATABASE=eco_cycle
DB_USERNAME=postgres
DB_PASSWORD=secret123
```

To change credentials:

1. Update `.env` file
2. Restart containers:
   ```bash
   docker-compose -f docker-compose.dev.yml down -v
   docker-compose -f docker-compose.dev.yml up -d
   ```

## Next Steps

1. ✅ Start Docker containers
2. ✅ Connect with DBeaver
3. ✅ Verify tables exist
4. ✅ Test application at http://localhost:8080
5. 📝 Start developing!

## Useful Resources

- [PostgreSQL Documentation](https://www.postgresql.org/docs/15/)
- [DBeaver Documentation](https://dbeaver.com/docs/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Project Migration Guide](./MIGRATION_GUIDE.md)

---

**Quick Reference Card:**

```
🐳 Docker Commands:
   Start:    docker-compose -f docker-compose.dev.yml up -d
   Stop:     docker-compose -f docker-compose.dev.yml down
   Logs:     docker-compose -f docker-compose.dev.yml logs -f db
   Restart:  docker-compose -f docker-compose.dev.yml restart

🗄️ DBeaver Connection:
   Host:     localhost
   Port:     5432
   DB:       eco_cycle
   User:     postgres
   Pass:     secret123

🌐 Access Points:
   App:      http://localhost:8080
   pgAdmin:  http://localhost:5050

📊 PostgreSQL CLI:
   docker-compose -f docker-compose.dev.yml exec db psql -U postgres -d eco_cycle
```

Happy developing! 🚀
