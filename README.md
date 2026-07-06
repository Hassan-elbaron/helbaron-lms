# HElbaron — v1.0.0-rc.1 (Release Candidate)

A bilingual (AR/EN) enterprise Learning Management System. Laravel 12 modular-monolith API +
custom Next.js 15 frontend. All 10 domains implemented, tested, and hardened for production.

## Architecture

| Layer       | Technology                                       |
|-------------|--------------------------------------------------|
| Backend API | Laravel 12 (PHP 8.3), REST `/api/v1` only        |
| Admin       | Filament v4 (`/admin`) — *pending v4 resource pass* |
| Frontend    | Next.js 15 · React 19 · TypeScript · Tailwind 4  |
| Database    | PostgreSQL 16                                     |
| Cache/Queue | Redis 7 + Laravel Horizon                        |
| Storage     | AWS S3 + CloudFront (signed delivery)            |
| Video       | Mux (signed playback)                            |
| Auth        | Laravel Sanctum (token-only) + MFA               |
| RBAC        | spatie/laravel-permission                        |

## Domains
Identity · Catalog · Authoring · Learning · Commerce · Certification · Live · CRM · Analytics ·
Notifications — each with REST API, policies, Filament resources, factories, seeders, Pest tests.

## Repository layout
```
helbaron/
  apps/
    api/   Laravel 12 backend (Dockerfile = prod, Dockerfile.dev = local)
    web/   Next.js 15 frontend
  docs/
    ops/       Deployment, Operations, Runbook, Monitoring, Incident, Rollback, DR, Secrets
    audits/    PRODUCTION_AUDIT.md
    release/   Release notes + checklist
  infra/       nginx + php ini/opcache
  docker-compose.yml        local dev stack
  docker-compose.prod.yml   production stack (api/nginx/horizon/scheduler/postgres/redis)
  CHANGELOG.md · VERSION
```

## Quick start (local)
```bash
docker compose up -d --build
docker compose exec api php artisan migrate:fresh --seed --force
docker compose exec api php artisan test        # Pest suite
# API:  http://localhost:8000/api/v1/health
```
Frontend:
```bash
cd apps/web && cp .env.example .env.local && npm install && npm run dev   # http://localhost:3000
```

## Production
See `docs/ops/DEPLOYMENT_GUIDE.md` and `docs/release/RELEASE_CHECKLIST.md`. Providers stay on
fakes locally and switch to real vendors by environment only. Secrets are injected at runtime
(`docs/ops/SECRETS.md`) — never committed.

## Health
- Liveness: `GET /up`, `GET /api/v1/health/live`
- Readiness: `GET /api/v1/health/ready` (Postgres + Redis)

## Status
Backend green on the Pest suite. Known follow-ups: Filament v3→v4 migration for `/admin`,
Firebase FCM v1, and FK covering indexes after load testing (see `docs/audits/PRODUCTION_AUDIT.md`).
