# ecoCycle Docker Deployment Guide

This guide shows how to run the project locally and deploy it using Docker.

## 1. Prerequisites

- Docker Desktop (macOS/Windows) or Docker Engine (Linux)
- (Optional) Docker Compose v2 (bundled with recent Docker Desktop)
- A clone of this repository

## 2. Files Added

| File                 | Purpose                                                                 |
| -------------------- | ----------------------------------------------------------------------- |
| `Dockerfile`         | Builds production-ready Apache + PHP 8.2 image with required extensions |
| `docker-compose.yml` | Orchestrates app, MariaDB, (optional) phpMyAdmin                        |
| `.dockerignore`      | Reduces image build context size                                        |
| `.env.example`       | Example environment variables (copy to `.env`)                          |

## 3. Quick Start (Local Dev)

```bash
cp .env.example .env            # Adjust values as needed
docker compose up -d --build
open http://localhost:8080
```

phpMyAdmin (only if you enabled the dev profile):

```bash
docker compose --profile dev up -d
open http://localhost:8090
```

## 4. Directory / Web Root Structure

Apache serves from `public/` only. Application code lives under `src/` and is never directly web-accessible except through routed entry points (`public/index.php`).

## 5. Environment Variables

Modify `.env` (NOT committed):

| Key         | Description                              |
| ----------- | ---------------------------------------- |
| APP_ENV     | `local` / `production`                   |
| APP_DEBUG   | `true` / `false` (disable in production) |
| DB_HOST     | Always `db` inside docker network        |
| DB_DATABASE | Database name (default: ecocycle)        |
| DB_USERNAME | DB user                                  |
| DB_PASSWORD | DB password                              |

The custom bootstrap in `public/index.php` loads key/value pairs from `.env` into `$_ENV`.

## 6. Common Commands

```bash
# Start (build if needed)
docker compose up -d --build

# View logs
docker compose logs -f app

# Rebuild after changing composer.json
docker compose build app --no-cache

# Execute a one-off command (e.g., PHP version)
docker compose exec app php -v

# Stop
docker compose down

# Stop & remove volumes (DB data will be lost!)
docker compose down -v
```

## 7. Database Import / Export

```bash
# Import SQL dump into the running DB
docker compose cp dump.sql db:/dump.sql
docker compose exec db sh -c "mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < /dump.sql"

# Export (inside container)
docker compose exec db sh -c "mysqldump -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > /dump.sql"
docker compose cp db:/dump.sql ./dump.sql
```

## 8. Production Image Build

For production you usually:

1. Copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false`.
2. Build image without dev profiles / bind mounts:
   ```bash
   docker build -t ecocycle:prod .
   ```
3. Run with only necessary services:
   ```bash
   docker run -d --name ecocycle -p 80:80 --env-file .env ecocycle:prod
   ```
4. Put behind a reverse proxy (Caddy, Traefik, or Nginx) for HTTPS / TLS termination.

### Sample Traefik labels (if you add Traefik)

Add to the `app` service in a production-only compose file:

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.http.routers.ecocycle.rule=Host(`example.com`)"
  - "traefik.http.routers.ecocycle.entrypoints=websecure"
  - "traefik.http.routers.ecocycle.tls.certresolver=letsencrypt"
```

## 9. Security & Hardening Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong DB credentials (do not keep defaults)
- [ ] Restrict SSH access to server (fail2ban, firewall)
- [ ] Enforce HTTPS & HSTS at proxy layer
- [ ] Regular database backups (cron + `mysqldump`)
- [ ] Keep images up to date (`docker compose pull && docker compose up -d`)
- [ ] Review access logs periodically

## 10. Updating Dependencies

When `composer.json` changes:

```bash
docker compose exec app composer install --no-dev --prefer-dist
docker compose exec app composer dump-autoload -o
```

Then (optional) rebuild image to bake vendor into image layers for production performance.

## 11. Troubleshooting

| Issue                        | Cause                                   | Fix                                                      |
| ---------------------------- | --------------------------------------- | -------------------------------------------------------- |
| 500 error after build        | Missing extension / config              | Check container logs: `docker compose logs app`          |
| Cannot connect to DB         | Wrong credentials / container not ready | Ensure `db` healthy, wait or adjust depends_on           |
| Changes in code not showing  | Browser cache / opcache                 | Disable aggressive caching locally, or restart container |
| phpMyAdmin 200 but no tables | Empty DB                                | Import your local dump                                   |

## 12. Next Steps

- Add real authentication middleware before exposing admin dashboard
- Add automated CI build (GitHub Actions) to push image to a registry
- Create a `docker-compose.prod.yml` without bind mounts & phpMyAdmin

---

Maintained: 2025-08-25
