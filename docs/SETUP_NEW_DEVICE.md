# 🚀 EcoCycle - New Device Setup Guide

## Quick Setup (All Operating Systems)

This guide helps you set up the EcoCycle project on a **new device** with **any operating system** (macOS, Windows, Linux).

---

## 📋 Prerequisites

### Required Software

| Software                | Version | Purpose                          | Download                                                     |
| ----------------------- | ------- | -------------------------------- | ------------------------------------------------------------ |
| **Docker Desktop**      | Latest  | Run PostgreSQL database & app    | [docker.com](https://www.docker.com/products/docker-desktop) |
| **Git**                 | 2.x+    | Clone repository                 | [git-scm.com](https://git-scm.com/downloads)                 |
| **PHP** (Optional)      | 8.2+    | Local development without Docker | [php.net](https://www.php.net/downloads)                     |
| **Composer** (Optional) | 2.x+    | PHP dependency management        | [getcomposer.org](https://getcomposer.org/)                  |

### System Requirements

- **RAM**: 4GB minimum (8GB recommended)
- **Disk Space**: 2GB free space
- **OS**:
  - macOS 10.15+
  - Windows 10/11 (with WSL2 for Docker)
  - Linux (Ubuntu 20.04+, Debian, Fedora, etc.)

---

## 🎯 Method 1: Docker Setup (Recommended)

**✅ Best for**: All users, works consistently across all operating systems

### Step 1: Install Docker Desktop

**macOS:**

```bash
# Download and install from docker.com
# Or use Homebrew:
brew install --cask docker
```

**Windows:**

```bash
# Download Docker Desktop from docker.com
# Enable WSL2 if prompted
# Restart computer after installation
```

**Linux:**

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install docker.io docker-compose

# Fedora
sudo dnf install docker docker-compose

# Start Docker
sudo systemctl start docker
sudo systemctl enable docker
```

### Step 2: Verify Docker Installation

```bash
# Check Docker is running
docker --version
docker-compose --version

# Should show versions like:
# Docker version 24.x.x
# docker-compose version 2.x.x
```

### Step 3: Clone the Repository

```bash
# Clone the project
git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle

# Or if you already have it:
cd /path/to/ecoCycle
```

### Step 4: Configure Environment

```bash
# Copy the example environment file
cp .env.example .env

# The default .env.example is already configured for Docker!
# You usually don't need to change anything
```

**Default Docker configuration** (already in `.env.example`):

```env
DB_CONNECTION=pgsql
DB_HOST=db                    # Docker service name
DB_PORT=5432
DB_DATABASE=ecocycle          # ⚠️ Note: Update if your .env.example shows eco_cycle
DB_USERNAME=postgres
DB_PASSWORD=root
```

### Step 5: Start Docker Containers

```bash
# Start all services (database + application)
docker-compose -f docker-compose.dev.yml up -d

# Check containers are running
docker-compose -f docker-compose.dev.yml ps

# You should see:
# ecocycle-db-1   postgres:15-alpine   Up
# ecocycle-app-1  ...                  Up
```

**What happens automatically:**

- ✅ PostgreSQL database is created
- ✅ Database schema is initialized (tables created)
- ✅ Seed data is loaded (demo users, categories, etc.)
- ✅ PHP dependencies are installed
- ✅ Web server is configured

### Step 6: Verify Setup

```bash
# Check database is ready
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle -c "SELECT COUNT(*) FROM users;"

# Should return a count (e.g., 5 if roles are seeded)
```

**Test in browser:**

- Application: `http://localhost:8080`
- Test connection: `http://localhost:8080/test-db-connection.php`

### Step 7: View Logs (If Issues)

```bash
# View all logs
docker-compose -f docker-compose.dev.yml logs -f

# View database logs only
docker logs ecocycle-db-1 -f

# View app logs only
docker logs ecocycle-app-1 -f
```

---

## 🔧 Method 2: Local Development (Without Docker)

**⚠️ Note**: Requires manual PostgreSQL installation. Docker is recommended.

### Step 1: Install PostgreSQL

**macOS:**

```bash
# Using Homebrew
brew install postgresql@15
brew services start postgresql@15
```

**Windows:**

```bash
# Download installer from postgresql.org
# Or use Chocolatey:
choco install postgresql
```

**Linux:**

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib

# Start PostgreSQL
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

### Step 2: Install PHP 8.2+

**macOS:**

```bash
brew install php@8.2
brew install composer
```

**Windows:**

```bash
# Download from php.net
# Or use XAMPP/WAMP
# Install Composer from getcomposer.org
```

**Linux:**

```bash
sudo apt-get install php8.2 php8.2-pgsql php8.2-mbstring php8.2-xml composer
```

### Step 3: Create Database

```bash
# Connect to PostgreSQL
psql -U postgres

# In psql:
CREATE DATABASE ecocycle;
\q
```

### Step 4: Clone & Configure

```bash
# Clone repository
git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle

# Copy environment file
cp .env.example .env

# Edit .env for local setup
nano .env  # or use your preferred editor
```

**Update these values in `.env`:**

```env
DB_HOST=localhost              # Change from 'db' to 'localhost'
DB_PORT=5432
DB_DATABASE=ecocycle
DB_USERNAME=postgres
DB_PASSWORD=your_password      # Your PostgreSQL password
```

### Step 5: Install Dependencies

```bash
composer install
```

### Step 6: Initialize Database

```bash
# Apply schema
psql -U postgres -d ecocycle < database/postgresql/init/01_create_tables.sql

# Apply seeds (optional)
psql -U postgres -d ecocycle < database/postgresql/init/03_seed_dummy_data.sql
```

### Step 7: Start Development Server

```bash
# Using PHP built-in server
php -S localhost:8080 -t public

# Or using Composer script
composer serve
```

**Access**: `http://localhost:8080`

---

## 🗂️ Project Structure Overview

```
ecoCycle/
├── .env                        # Your environment config (create from .env.example)
├── docker-compose.dev.yml      # Docker configuration
├── composer.json               # PHP dependencies
├── public/
│   └── index.php              # Application entry point
├── src/
│   ├── Core/                  # Framework core
│   ├── Controllers/           # Application logic
│   ├── Models/                # Database models
│   └── Views/                 # UI templates
├── database/
│   └── postgresql/
│       └── init/              # Database setup scripts
│           ├── 01_create_tables.sql
│           ├── 03_seed_dummy_data.sql
│           └── ...
├── storage/                   # Logs and cache
└── docs/                      # Documentation
```

---

## ✅ Post-Setup Verification Checklist

After setup, verify everything works:

### 1. Check Docker Containers (Docker Setup)

```bash
docker-compose -f docker-compose.dev.yml ps
# Both containers should show "Up"
```

### 2. Check Database Connection

```bash
# Docker:
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle -c "\dt"

# Local:
psql -U postgres -d ecocycle -c "\dt"

# Should list 12 tables
```

### 3. Test Application

- Visit: `http://localhost:8080`
- Visit: `http://localhost:8080/test-db-connection.php`
- Should see "✅ Database connected successfully"

### 4. Check Seed Data

```bash
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle -c "SELECT * FROM roles;"

# Should show 5 roles: admin, manager, collector, company, customer
```

---

## 🔄 Common Commands

### Docker Commands

```bash
# Start containers
docker-compose -f docker-compose.dev.yml up -d

# Stop containers
docker-compose -f docker-compose.dev.yml down

# Restart containers
docker-compose -f docker-compose.dev.yml restart

# View logs
docker-compose -f docker-compose.dev.yml logs -f

# Connect to database
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle

# Reset everything (⚠️ deletes all data)
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d
```

### Database Commands

```bash
# View all tables
\dt

# Describe a table
\d users

# Run a query
SELECT * FROM users LIMIT 5;

# Exit psql
\q
```

---

## 🐛 Troubleshooting

### Docker not starting

**Problem**: "Cannot connect to Docker daemon"
**Solution**:

```bash
# macOS/Windows: Make sure Docker Desktop is running
# Linux: Start Docker service
sudo systemctl start docker
```

### Port already in use

**Problem**: "Port 5432 or 8080 is already allocated"
**Solution**:

```bash
# Stop conflicting services
# macOS:
brew services stop postgresql

# Linux:
sudo systemctl stop postgresql

# Or change ports in docker-compose.dev.yml
```

### Database init scripts not running

**Problem**: Tables not created
**Solution**:

```bash
# Remove volumes and recreate
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d

# Check logs
docker logs ecocycle-db-1
```

### Permission denied (Linux)

**Problem**: Docker permission errors
**Solution**:

```bash
# Add user to docker group
sudo usermod -aG docker $USER
# Log out and log back in
```

### Can't connect to database

**Problem**: "Connection refused" or "Authentication failed"
**Solution**:

```bash
# Check .env file has correct settings:
# Docker: DB_HOST=db
# Local: DB_HOST=localhost

# Verify password matches:
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle
# If fails, check DB_PASSWORD in .env
```

---

## 🔗 Database GUI Tools (Optional)

### DBeaver (Recommended)

**Free, cross-platform database client**

1. Download: [dbeaver.io](https://dbeaver.io/)
2. Install and open DBeaver
3. Create new connection:
   - **Type**: PostgreSQL
   - **Host**: localhost
   - **Port**: 5432
   - **Database**: ecocycle
   - **Username**: postgres
   - **Password**: root

See detailed guide: `docs/DOCKER_SETUP_WITH_DBEAVER.md`

### Other Options

- **pgAdmin** - Web-based PostgreSQL admin
- **TablePlus** - Modern database GUI (macOS/Windows)
- **DataGrip** - JetBrains IDE (paid)

---

## 📚 Additional Documentation

| Document                                                          | Description                   |
| ----------------------------------------------------------------- | ----------------------------- |
| [README.md](README.md)                                            | Project overview and features |
| [MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md)                     | MySQL to PostgreSQL migration |
| [seeding.md](docs/seeding.md)                                     | Database seeding guide        |
| [DOCKER_SETUP_WITH_DBEAVER.md](docs/DOCKER_SETUP_WITH_DBEAVER.md) | DBeaver connection setup      |
| [postgres-quick-reference.md](docs/postgres-quick-reference.md)   | PostgreSQL commands           |

---

## 🎓 Quick Start Summary

**For absolute beginners:**

1. Install Docker Desktop → [docker.com/get-started](https://www.docker.com/get-started)
2. Clone project: `git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git`
3. Navigate: `cd ecoCycle`
4. Copy config: `cp .env.example .env`
5. Start: `docker-compose -f docker-compose.dev.yml up -d`
6. Open: `http://localhost:8080`

**Done!** 🎉

---

## 💡 Tips for Team Members

### Windows Users

- Use **WSL2** for better Docker performance
- Use **Git Bash** or **PowerShell** for commands
- Install **Windows Terminal** for better CLI experience

### macOS Users

- Ensure Docker Desktop has enough resources (Settings → Resources → Advanced)
- Use **iTerm2** or native Terminal
- Give Docker access to your project folder

### Linux Users

- You may need `sudo` for Docker commands (or add user to docker group)
- Make sure Docker service is running: `sudo systemctl status docker`

### All Users

- Keep Docker Desktop updated
- Don't commit `.env` file (it's in `.gitignore`)
- Use `docker-compose down -v` to completely reset the database

---

## 📞 Support

If you encounter issues:

1. **Check logs**: `docker logs ecocycle-db-1` and `docker logs ecocycle-app-1`
2. **Read error messages** carefully
3. **Search documentation** in `docs/` folder
4. **Ask team members** or create an issue

---

## ✅ Setup Complete!

Once you see the application at `http://localhost:8080`, you're ready to start developing! 🚀

**Next steps:**

- Explore the application
- Read the framework documentation
- Check out existing features
- Start coding!
