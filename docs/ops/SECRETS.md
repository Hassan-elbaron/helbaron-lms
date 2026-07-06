# HElbaron — Secrets Management

## Principles
- No secrets in git. `.env.example` ships **keys only** (empty values).
- Inject at runtime via the platform secret store (AWS Secrets Manager / SSM / Vault / K8s
  Secrets). `apps/api/.env.production` is rendered from the store at deploy time, never committed.
- Provider adapters are the only code that reads secrets (`config/services.php`); Actions,
  Controllers, and resources never touch them.

## Inventory
`APP_KEY`, DB/Redis creds, `STRIPE_SECRET` + `STRIPE_WEBHOOK_SECRET`, `MUX_SIGNING_KEY(+_ID)`,
`CLOUDFRONT_PRIVATE_KEY(+KEY_PAIR_ID)`, `AWS_*`, `MAILGUN_SECRET`, `TWILIO_AUTH_TOKEN`,
`FIREBASE_SERVER_KEY`.

## Rotation
- Rotate on a schedule and on any suspected exposure.
- Webhook secrets: rotate with a dual-accept window if the provider supports it.
- After rotation: redeploy config, invalidate caches, verify webhooks + a provider smoke test.
