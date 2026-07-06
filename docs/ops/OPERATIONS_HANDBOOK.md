# HElbaron — Operations Handbook

## Services
| Role | Command | Notes |
|---|---|---|
| web | `php-fpm` behind nginx | stateless; scale horizontally |
| horizon | `php artisan horizon` | 3 supervisors: default / notifications / exports |
| scheduler | `schedule:run` every 60s | single instance |

## Configuration (env-driven, no code changes)
- Security headers/CSP/HSTS: `config/security.php` (`SECURITY_*`).
- Sessions/cookies: `config/session.php` (`SESSION_SECURE_COOKIE=true` in prod).
- CORS allow-list: `CORS_ALLOWED_ORIGINS` (comma-separated; never `*`).
- Trusted proxies/hosts: `TRUSTED_PROXIES`, `APP_TRUSTED_HOSTS`.
- Providers by env: `COMMERCE_PAYMENT_PROVIDER`, `LEARNING_PLAYBACK_PROVIDER`,
  `NOTIFICATIONS_{MAIL,SMS,PUSH}_PROVIDER` (default `fake`).

## Recommended production toggles
- `AppServiceProvider::boot()`: `Model::preventLazyLoading(! app()->isProduction())`.
- Horizon dashboard: define a `viewHorizon` gate (deny by default) before exposing `/horizon`.
- Cache config/routes/events on every deploy; clear on rollback.

## Storage
- S3 bucket: private, TLS-only bucket policy; block public access.
- CloudFront: Origin Access Control; signed URLs only (`CLOUDFRONT_*`).
- Lifecycle: expire `exports/*` after 7–30 days; certificates retained per policy.

## Routine tasks
- Rotate Sanctum tokens / provider secrets per the security calendar.
- Review `failed_jobs` weekly; replay with `php artisan queue:retry`.
- Review Horizon metrics (throughput, wait, runtime) after traffic changes.

## Rate limiting
Named limiters live in `IdentityServiceProvider` (`identity-login` keyed by email+IP,
`identity-register`, `identity-password`, `identity-otp-verify`). Tune the `perMinute` values
via a config PR if abuse patterns change.
