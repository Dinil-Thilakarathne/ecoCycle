# Production Deployment (Internet)

This guide covers deploying ecoCycle publicly on a VPS / cloud instance with Docker, HTTPS, and a hardened configuration.

## 1. Overview

We use:

- `Dockerfile` (builds the application image)
- `docker-compose.yml` (base services for local)
- `docker-compose.prod.yml` (production overrides: no bind mounts, adds Caddy proxy)
- `Caddyfile` (automatic Let\'s Encrypt TLS and reverse proxy)

## 2. Prerequisites

| Item                | Notes                                                    |
| ------------------- | -------------------------------------------------------- |
| Domain name         | Point A record to server public IP before enabling HTTPS |
| VPS (Ubuntu 22.04+) | 1 vCPU / 1GB RAM minimum (swap recommended)              |
| Docker & Compose    | Install from official docs                               |
| Firewall open       | Allow TCP 80 & 443 (and 22 for SSH)                      |

### Install Docker (Ubuntu quick script)

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
newgrp docker
```

## 3. DNS Setup

Create A record(s):

| Host | Type | Value (IP)                  |
| ---- | ---- | --------------------------- |
| @    | A    | <your_server_ip>            |
| www  | A    | <your_server_ip> (optional) |

Wait for propagation (dig or nslookup until it resolves).

## 4. Prepare Environment

Clone repository on server:

```bash
git clone https://github.com/Dinil-Thilakarathne/ecoCycle.git
cd ecoCycle
cp .env.example .env
```

Edit `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
DB_PASSWORD=StrongGeneratedPass
DB_ROOT_PASSWORD=AnotherStrongRootPass
APP_DOMAIN=example.com
SSL_EMAIL=admin@example.com
```

## 5. Build & Start (Production)

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml build
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Check services:

```bash
docker compose ps
docker compose logs -f caddy
```

First run may take ~1–2 minutes to obtain certificates.

## 6. Zero-Downtime Updates

```bash
git pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml build app
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d app
docker image prune -f   # optional cleanup
```

## 7. Database Backups

Automate daily dump (cron):

```bash
docker compose exec db sh -c "mysqldump -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE" > backups/backup-$(date +%F).sql
```

Keep `backups/` outside the repo or secure it.

## 8. Security Hardening

| Item              | Status/Notes                         |
| ----------------- | ------------------------------------ |
| APP_DEBUG=false   | Ensures no stack traces leaked       |
| Strong DB creds   | Replace defaults in env              |
| HTTPS             | Provided automatically by Caddy      |
| HSTS headers      | Enabled in `Caddyfile`               |
| Remove phpMyAdmin | Not included in prod override        |
| Restrict SSH      | Use key auth, disable password login |
| Logs review       | `docker compose logs --since=1h`     |

Optional: enable swap (1–2GB) for small VPS to prevent OOM.

## 9. Disaster Recovery

Steps to rebuild:

1. Provision new server & install Docker.
2. Restore repo (git clone) and `.env`.
3. Restore DB: `docker compose exec db sh -c "mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < /restore.sql"`.
4. Start services.

## 10. Monitoring Basics

| Check            | Command                                              |
| ---------------- | ---------------------------------------------------- |
| App health       | `curl -I https://example.com/`                       |
| Container health | `docker ps --format 'table {{.Names}}\t{{.Status}}'` |
| Caddy logs       | `docker compose logs -f caddy`                       |
| App logs         | `docker compose logs -f app`                         |

## 11. Rollback

Tag working image before updating:

```bash
docker tag ecocycle-app:latest ecocycle-app:stable-$(date +%F)
```

Revert:

```bash
docker run -d --name ecocycle-rollback -p 80:80 ecocycle-app:stable-2025-08-25
```

## 12. Optional: Push Image to Registry

```bash
docker tag ecocycle-app:latest youruser/ecocycle:latest
docker push youruser/ecocycle:latest
```

On server, pull & up:

```bash
docker pull youruser/ecocycle:latest
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

---

Maintained: 2025-08-25
