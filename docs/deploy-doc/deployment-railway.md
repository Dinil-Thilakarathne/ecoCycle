# Deploying ecoCycle on Railway (Docker + Managed MySQL)

This guide shows how to deploy the existing Docker-based app to Railway using:

- Your existing `Dockerfile` (Apache + PHP)
- A Railway MySQL service (managed DB)

Railway provides HTTPS automatically. You do NOT need `Caddy` or `docker-compose` here. Each service runs separately.

---

## 1. Prerequisites

| Item            | Notes                                 |
| --------------- | ------------------------------------- |
| Railway account | https://railway.app/ (GitHub sign-in) |
| GitHub repo     | Push your code (public or private)    |
| Dockerfile      | Already present in project root       |

---

## 2. High-Level Architecture

| Service                | Purpose                                       |
| ---------------------- | --------------------------------------------- |
| Web (Docker)           | Runs Apache/PHP serving `public/`             |
| MySQL (Railway plugin) | Managed database (host, port, creds provided) |

No need for `db` container or `caddy` in this environment.

---

## 3. Prepare Repository

1. Ensure `Dockerfile` is at the root (already).
2. Remove any Railway‑irrelevant overrides at deploy time (Railway ignores `docker-compose.yml`).
3. (Optional) Add a lightweight health endpoint: `public/health.php` (added by this guide) responding `OK`.

---

## 4. Create Project on Railway

1. New Project → Deploy from GitHub → select repository.
2. Railway detects the `Dockerfile`; choose it as a service (Web).
3. When prompted for service port, keep default (Railway maps to container port 80). No change to Apache needed.

---

## 5. Add MySQL Service

1. In project → New → Database → MySQL.
2. After provisioning, open the MySQL service → Variables tab.
3. Copy values: `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.

---

## 6. Configure Environment Variables (Web Service)

In the Web service → Variables, add (map Railway vars to app env):

| App Variable     | Value (from MySQL service) |
| ---------------- | -------------------------- |
| APP_ENV          | production                 |
| APP_DEBUG        | false                      |
| DB_HOST          | ${MYSQLHOST}               |
| DB_PORT          | ${MYSQLPORT}               |
| DB_DATABASE      | ${MYSQLDATABASE}           |
| DB_USERNAME      | ${MYSQLUSER}               |
| DB_PASSWORD      | ${MYSQLPASSWORD}           |
| SESSION_NAME     | ECOSESSID                  |
| SESSION_LIFETIME | 120                        |

Railway automatically restarts the container when variables change (redeploy if needed).

---

## 7. First Deployment

1. Trigger deploy (Railway auto builds on initial import).
2. View build logs; expect composer vendor directory already baked-in (from Docker multi-stage).
3. Once deployed, open the generated Railway domain (e.g., `https://ecocycle.up.railway.app`).
4. Test health: `https://ecocycle.up.railway.app/health` → should return `OK`.

---

## 8. Import Existing Data

Option A (Railway Shell):

1. Click MySQL service → Connect → Launch Shell.
2. Use browser upload/import (if offered) or paste SQL commands directly.

Option B (Local MySQL client):

```bash
mysql -h MYSQLHOST -u MYSQLUSER -p -P MYSQLPORT MYSQLDATABASE < dump.sql
```

---

## 9. Updating Code

Push to the tracked branch → Railway rebuilds automatically (CI style). For manual redeploy: Web service → Deploy → Redeploy.

If you change PHP dependencies (`composer.json`), Railway rebuild handles it automatically (Docker rebuild).

---

## 10. Logging & Debugging

| Need                 | Where                                            |
| -------------------- | ------------------------------------------------ |
| App logs             | Web service → Logs                               |
| Build errors         | Deploy tab → Build logs                          |
| DB connection issues | Verify env mapping; test via Railway MySQL shell |

Temporarily enable debug (NOT recommended long-term): set `APP_DEBUG=true`, redeploy, then revert.

---

## 11. Security Notes

| Item       | Action                                                 |
| ---------- | ------------------------------------------------------ |
| Debug mode | Keep off (`APP_DEBUG=false`)                           |
| DB creds   | Managed by Railway; rotate if leaked                   |
| Sessions   | Stored on ephemeral FS; consider Redis for scale later |
| HTTPS      | Provided automatically by Railway edge                 |

---

## 12. Optional: Custom Domain

1. Project → Settings → Domains → Add Custom Domain.
2. Create CNAME pointing to Railway domain (or A record if instructed).
3. Wait for verification + auto-SSL.

---

## 13. Removing Local-Only Code (Optional Cleanup)

For a lean production branch you can remove:

- `docker-compose.yml` / `docker-compose.prod.yml`
- `Caddyfile`
  They are not required on Railway (unless you keep them for alternative deployment targets).

---

## 14. Health Checks

Railway pings your service; ensure non-200 errors are visible. The added `public/health.php` returns 200 quickly.

---

## 15. Troubleshooting Quick Table

| Symptom                  | Cause             | Fix                                                      |
| ------------------------ | ----------------- | -------------------------------------------------------- |
| 502 from Railway         | Container crashed | Check Web service logs; verify DB vars                   |
| Connection refused to DB | Wrong host/port   | Use provided `MYSQLHOST` & `MYSQLPORT`                   |
| Blank page               | PHP error hidden  | Temporarily set `APP_DEBUG=true` then revert             |
| Rebuild uses cache       | Force new build   | Redeploy with Clear Cache (GUI) or change a Docker layer |

---

Maintained: 2025-08-25
