# HElbaron v1.0.0 — Release Checklist

## Code quality (run in CI + locally)
- [ ] `docker compose exec api php artisan test` (Pest) — all green
- [ ] `vendor/bin/pint --test` (style)
- [ ] `vendor/bin/phpstan analyse` (Larastan — add `phpstan.neon` at desired level)
- [ ] `vendor/bin/rector process --dry-run` (if Rector adopted)
- [ ] Web: `npm run typecheck && npm test` ; `npm run build`
- [ ] Web e2e: `npx playwright test` (once specs exist)
- [ ] `composer audit` and `npm audit` — no unresolved criticals

## Configuration
- [ ] `.env.production` rendered from secret store; `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `SESSION_SECURE_COOKIE=true`, `CORS_ALLOWED_ORIGINS` set (no `*`), `APP_TRUSTED_HOSTS` set
- [ ] `LOG_CHANNEL=json`; provider `*_PROVIDER` values set for prod
- [ ] S3 bucket private + CloudFront OAC; export lifecycle rule

## Data & infra
- [ ] `php artisan migrate --force`; `migrate:status` clean
- [ ] Postgres backups/PITR verified; Redis AOF on; DR drill within 90 days
- [ ] Horizon running; `viewHorizon` gate defined

## Verification
- [ ] `/up`, `/api/v1/health/ready` = 200
- [ ] Smoke path: register → verify → login → catalog → checkout(webhook) → enrol → complete →
      certificate verify
- [ ] Rollback tag identified

## Sign-off
- [ ] Eng ✅  [ ] Security ✅  [ ] Ops ✅  → tag `v1.0.0`
