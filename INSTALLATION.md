# LMST System Installation Guide

Step-by-step instructions to bring both the Laravel API (`lmst-api`) and Nuxt SPA (`lmst-front`) from a clean clone to a fully running stack with Redis-powered queues, Laravel Reverb broadcasting, and seeded demo data.

---

## 1. Prerequisites

| Component | Recommended Version | Notes |
| --- | --- | --- |
| PHP | 8.3.x | Enable `bcmath`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `sqlite`, `redis` |
| Composer | 2.7+ | Global installation |
| Node.js | 20.x LTS | Matches Nuxt 4 requirements |
| PNPM | 10.x | Respect `packageManager` pin in `lmst-front` |
| Redis | 7.x | Used for cache, queues, and Reverb scaling |
| Database | MySQL 8 / MariaDB 10.6+ / PostgreSQL 14+ | Configure `.env` accordingly |
| Supervisor / systemd | Optional | For long-running queue and Reverb processes |

> **Tip:** macOS/Linux users can rely on Homebrew; Windows users should use WSL2 with Ubuntu 22.04+ for parity.

---

## 2. Backend (`lmst-api`) Setup

1. **Clone & install**
   ```bash
   git clone <repo-url> lmst-api
   cd lmst-api
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure `.env`** (highlighted keys)
   ```env
   APP_NAME="LMST API"
   APP_URL=http://localhost:8000

   # Database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lmst
   DB_USERNAME=root
   DB_PASSWORD=secret

   # Cache / Queue / Session
   CACHE_DRIVER=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   BROADCAST_DRIVER=reverb

   # Redis (shared with Reverb scaling)
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379

   # Sanctum token expiry (minutes)
   SANCTUM_ACCESS_TOKEN_EXPIRY=60
   SANCTUM_REFRESH_TOKEN_EXPIRY=43200

   # Reverb server + app credentials
   REVERB_SERVER_HOST=0.0.0.0
   REVERB_SERVER_PORT=8080
   REVERB_HOST=127.0.0.1
   REVERB_PORT=8080
   REVERB_SCHEME=http
   REVERB_APP_KEY=local-key
   REVERB_APP_SECRET=local-secret
   REVERB_APP_ID=local-app-id
   ```

   - Ensure `APP_URL` matches the domain you expose to the SPA.
   - Update `REVERB_APP_*` to match the keys consumed by the frontend runtime config.

3. **Database & storage**
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```
   - The seeders provision demo admins, teachers, students, and baseline attendance so dashboards render immediately.

4. **Start runtime services**
   ```bash
   # Terminal 1 - Laravel HTTP API
   php artisan serve --host=0.0.0.0 --port=8000

   # Terminal 2 - Queue worker (sync notifications, broadcasts)
   php artisan queue:work --tries=3

   # Terminal 3 - Reverb websocket server
   php artisan reverb:start --host=0.0.0.0 --port=8080
   ```

   - Make sure `redis-server` is running before starting the queue worker or Reverb.
   - For production, supervise these commands via systemd or Supervisor.

5. **Optional tooling**
   ```bash
   # Telescope / Pulse (if enabled)
   php artisan telescope:install && php artisan migrate
   php artisan pulse:install && php artisan migrate
   ```

---

## 3. Frontend (`lmst-front`) Setup

1. **Clone & install**
   ```bash
   git clone <repo-url> lmst-front
   cd lmst-front
   pnpm install --frozen-lockfile
   ```

2. **Environment configuration**
   Create `.env` (or edit the existing one) with at least:
   ```env
   NUXT_PUBLIC_API_BASE=http://localhost:8000/api/v1
   NUXT_PUBLIC_ASSET_BASE=http://localhost:8000
   NUXT_PUBLIC_WS_URL=ws://localhost:8080/app/local-key
   NUXT_PUBLIC_BROADCAST_AUTH_ENDPOINT=http://localhost:8000/api/broadcasting/auth
   NUXT_PUBLIC_REVERB_HOST=127.0.0.1
   NUXT_PUBLIC_REVERB_PORT=8080
   NUXT_PUBLIC_REVERB_SCHEME=http
   NUXT_PUBLIC_REVERB_KEY=local-key
   ```
   - Ensure the `key`, `host`, `port`, and `scheme` mirror the backend Reverb config.

3. **Run the SPA**
   ```bash
   pnpm dev --port 3009
   ```
   - The dashboard, attendance, and student screens will call the API via `NUXT_PUBLIC_API_BASE`.
   - Use `pnpm build && pnpm preview` to simulate production output.

---

## 4. Service Matrix

| Service | Command | Purpose |
| --- | --- | --- |
| Redis | `redis-server` | Cache, queues, Reverb scaling |
| Laravel API | `php artisan serve --host=0.0.0.0 --port=8000` | REST + broadcasting auth endpoints |
| Queue worker | `php artisan queue:work --tries=3` | Notification + broadcast jobs |
| Reverb | `php artisan reverb:start --host=0.0.0.0 --port=8080` | WebSocket transport for Laravel Echo |
| Nuxt dev server | `pnpm dev --port 3009` | SPA during development |

Keep these terminals/supervised processes alive to experience full real-time behavior (notifications, Live updates, etc.).

---

## 5. Verification Checklist

1. Visit `http://localhost:8000/api/health` (if enabled) or hit `/api/v1/dashboard/summary` with a seeded admin token to confirm API connectivity.
2. Load `http://localhost:3009` and sign in using seeded credentials (see `database/seeders` for defaults).
3. Open browser dev tools → Network to verify `/broadcasting/auth` calls succeed (indicates Sanctum + Reverb are in sync).
4. Tail the queue worker terminal; recording attendance via the SPA should push jobs and notifications.

---

## 6. Production Notes

- Replace SQLite/localhost values with managed MySQL/PostgreSQL, Redis Cloud, and load-balanced Reverb servers.
- Configure HTTPS for both API and Reverb (`REVERB_SCHEME=https`, `NUXT_PUBLIC_REVERB_SCHEME=https`).
- Run the new GitHub Actions workflows (`backend-ci.yml`, `frontend-ci.yml`) to ensure pull requests stay green before deployment.
- Document any environment-specific overrides in your infra repo, not inside this project.

With these steps you can consistently bootstrap a fresh environment—from cloning the repositories to serving a fully functional real-time attendance system.
