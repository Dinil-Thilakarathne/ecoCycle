# 📋 New Device Setup - Quick Reference

## Minimum Requirements

### Required Software

- **Docker Desktop** (includes Docker Compose)
- **Git**

### System Requirements

- **OS**: macOS 10.15+ / Windows 10+ / Linux (Ubuntu 20.04+)
- **RAM**: 4GB minimum, 8GB recommended
- **Disk**: 2GB free space

---

## 🚀 Quick Setup (5 Minutes)

### 1. Install Docker

**Download from**: https://www.docker.com/products/docker-desktop

### 2. Clone & Start

```bash
# Clone repository
git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle

# Copy environment config (already configured for Docker)
cp .env.example .env

# Start everything
docker-compose -f docker-compose.dev.yml up -d
```

### 3. Verify

- Open browser: `http://localhost:8080`
- Test page: `http://localhost:8080/test-db-connection.php`

**Done!** ✅

---

## 📦 What Gets Installed Automatically

When you run `docker-compose up -d`:

- ✅ PostgreSQL 15 database
- ✅ PHP 8.2 with Apache
- ✅ All PHP extensions (pdo_pgsql, etc.)
- ✅ Database schema (12 tables)
- ✅ Seed data (roles, demo users, categories)

---

## 🔑 Default Credentials

### Database (PostgreSQL)

- **Host**: localhost (or `db` inside Docker)
- **Port**: 5432
- **Database**: ecocycle
- **Username**: postgres
- **Password**: root

### Application

- **URL**: http://localhost:8080
- **Port**: 8080

---

## 🎯 Platform-Specific Notes

### macOS

```bash
# Install Docker
brew install --cask docker

# Clone and start
git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle
cp .env.example .env
docker-compose -f docker-compose.dev.yml up -d
```

### Windows

```bash
# 1. Install Docker Desktop from docker.com
# 2. Enable WSL2 if prompted
# 3. Restart computer
# 4. Open PowerShell or Git Bash:

git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle
copy .env.example .env
docker-compose -f docker-compose.dev.yml up -d
```

### Linux (Ubuntu/Debian)

```bash
# Install Docker
sudo apt-get update
sudo apt-get install docker.io docker-compose
sudo systemctl start docker
sudo usermod -aG docker $USER  # Add user to docker group
# Log out and log back in

# Clone and start
git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle
cp .env.example .env
docker-compose -f docker-compose.dev.yml up -d
```

---

## 🛠️ Essential Commands

```bash
# Start containers
docker-compose -f docker-compose.dev.yml up -d

# Stop containers
docker-compose -f docker-compose.dev.yml down

# View logs
docker logs ecocycle-db-1 -f
docker logs ecocycle-app-1 -f

# Connect to database
docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle

# Reset everything (⚠️ deletes all data)
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d

# Check container status
docker-compose -f docker-compose.dev.yml ps
```

---

## 🐛 Common Issues

### Issue: "Port 5432 already in use"

**Solution**: Stop local PostgreSQL

```bash
# macOS
brew services stop postgresql

# Linux
sudo systemctl stop postgresql

# Windows: Stop PostgreSQL service in Services panel
```

### Issue: "Cannot connect to Docker daemon"

**Solution**: Start Docker Desktop application

### Issue: "Permission denied" (Linux)

**Solution**:

```bash
sudo usermod -aG docker $USER
# Log out and log back in
```

### Issue: Database tables not created

**Solution**: Recreate containers with fresh volumes

```bash
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d
docker logs ecocycle-db-1  # Check initialization logs
```

---

## 📚 Documentation

| File                                | Purpose                              |
| ----------------------------------- | ------------------------------------ |
| `SETUP_NEW_DEVICE.md`               | **Complete setup guide** (this file) |
| `README.md`                         | Project overview                     |
| `docs/seeding.md`                   | Database seeding info                |
| `docs/DOCKER_SETUP_WITH_DBEAVER.md` | GUI database client setup            |
| `POSTGRESQL_SEEDS_SUMMARY.md`       | Seed data info                       |

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Docker Desktop is running
- [ ] Containers are up: `docker-compose -f docker-compose.dev.yml ps`
- [ ] Application loads: `http://localhost:8080`
- [ ] Database connects: `http://localhost:8080/test-db-connection.php`
- [ ] Can access database: `docker exec -it ecocycle-db-1 psql -U postgres -d ecocycle`

---

## 🎓 For Team Members

### First Time Setup

1. Install Docker Desktop
2. Clone repository
3. Run `cp .env.example .env`
4. Run `docker-compose -f docker-compose.dev.yml up -d`
5. Open `http://localhost:8080`

### Daily Development

```bash
# Start your work
docker-compose -f docker-compose.dev.yml up -d

# Stop when done
docker-compose -f docker-compose.dev.yml down
```

### If Something Breaks

```bash
# Nuclear option - reset everything
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d
```

---

## 🔗 Database GUI (Optional)

**DBeaver** - Free database client

1. Download: https://dbeaver.io/
2. Connect:
   - Host: `localhost`
   - Port: `5432`
   - Database: `ecocycle`
   - User: `postgres`
   - Password: `root`

---

## 💬 Need Help?

1. Check logs: `docker logs ecocycle-db-1`
2. Read full guide: `SETUP_NEW_DEVICE.md`
3. Check documentation in `docs/` folder
4. Ask team members

---

**Setup complete!** Start developing at `http://localhost:8080` 🚀
