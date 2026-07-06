# HElbaron — Monitoring Guide

## Logs
- `LOG_CHANNEL=json` → structured JSON to stdout; ship via the container runtime.
- Every record carries `service`, `env`, and `correlation_id` (Monolog `CorrelationProcessor`).

## Golden signals
- **Latency**: nginx/LB p50/p95/p99 per route group.
- **Traffic**: requests/min by endpoint; auth attempts (watch `identity-login` limiter hits).
- **Errors**: 4xx vs 5xx rate; error-envelope `code` distribution.
- **Saturation**: php-fpm busy workers, Redis memory, Postgres connections, Horizon wait time.

## Queue metrics (Horizon)
- Throughput and runtime per supervisor (default/notifications/exports).
- `failed_jobs` count and age; dead-lettered notification deliveries (status `Dead`).

## Alerts (suggested thresholds)
- Readiness probe failing > 1 min → page.
- 5xx rate > 2% for 5 min → page.
- Horizon wait > 60s or supervisor down → page.
- Redis memory > 80% (policy `noeviction`) → warn.
- Postgres connections > 80% of max → warn.

## Tracing hooks
Correlation id is injected at the edge; propagate it to downstream providers via headers to
stitch traces. Add an OpenTelemetry exporter as a Monolog/HTTP middleware when APM is adopted.
