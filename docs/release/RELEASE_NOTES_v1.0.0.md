# HElbaron v1.0.0 — Release Notes (Release Candidate)

HElbaron v1.0.0 is the first production release candidate: a bilingual enterprise Learning
Management System covering catalog, authoring, self-paced learning, commerce, certification,
live sessions, CRM, analytics, and notifications.

## Highlights
- 10 fully-tested backend domains behind a single, versioned REST API (`/api/v1`).
- Real external integrations (Stripe, Mux, S3/CloudFront, Mailgun, Twilio, Firebase) that stay
  on safe fakes locally and switch to real providers by environment only.
- Production-grade operations: health/readiness probes, structured logs with correlation ids,
  Horizon-managed queues with retry + dead-letter, and a complete runbook/DR/monitoring set.

## Upgrade / install
Follow `docs/ops/DEPLOYMENT_GUIDE.md`. Provision secrets out-of-band; run migrations once; roll
web replicas behind the readiness gate.

## Known limitations
- `/admin` (Filament) disabled pending v4 migration.
- Firebase push on FCM legacy HTTP.
- Add FK covering indexes after load testing (see audit §3).

## Validation
Backend Pest suite green (102+ tests). Run the full toolchain per `RELEASE_CHECKLIST.md` before
promoting the candidate to GA.
