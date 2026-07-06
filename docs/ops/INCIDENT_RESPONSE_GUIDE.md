# HElbaron — Incident Response Guide

## Severity
| Sev | Definition | Response |
|---|---|---|
| SEV1 | Full outage / data loss risk / security breach | Page on-call immediately; incident channel |
| SEV2 | Major degradation (checkout, login, playback down) | Page on-call; 15-min updates |
| SEV3 | Partial/feature degradation, workaround exists | Business hours |

## Flow
1. **Detect** — alert or report; open an incident with a correlation window.
2. **Triage** — classify severity; assign an Incident Commander.
3. **Mitigate** — stop the bleeding (rollback, scale, disable a failing provider via env,
   `horizon:pause`). Prefer mitigation over root-cause during the incident.
4. **Communicate** — status updates at the cadence above.
5. **Resolve & verify** — readiness green, error rate normal.
6. **Postmortem** — blameless; within 3 business days; track action items.

## Provider isolation
Payment/video/mail/sms/push are behind managers and fail closed. To shed a failing vendor:
flip `*_PROVIDER=fake` (or a backup) and redeploy config — no code change, no business impact.

## Security incident
- Rotate exposed secrets immediately (see `SECRETS`), revoke Sanctum tokens
  (`php artisan sanctum:prune-expired` / targeted deletes), invalidate sessions.
- Preserve logs (correlation ids) before rotation; notify per policy.

## Contacts
Maintain an on-call rota + escalation path in the ops directory (out of repo).
