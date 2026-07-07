# STEP 5D - Chunk C3: `App\Domains\Notifications` -> `App\Platform\Notifications` (report)

**Scope:** ONLY Notifications -> Platform/Notifications. No other domain touched. No schema, no behavior, no URL change.
**Runner:** `scripts/refactor-c3-platform-notifications.ps1` (host move + long-path-safe blanket rewrite + Filament discovery patch + docker verify).
**Precondition:** run ONLY after C2 (Identity) is confirmed green.

## Execution note
Executor can't run composer/artisan here; artisan/test outputs come from your run (below, PENDING). Run:
```powershell
cd "D:\Claude_Files\Projects\LMS\CoreLMS Implementation\corelms"
powershell -ExecutionPolicy Bypass -File scripts/refactor-c3-platform-notifications.ps1
```

## Files moved
- `apps/api/app/Domains/Notifications/` -> `apps/api/app/Platform/Notifications/` (entire tree: Actions, Channels{ChannelManager,ProviderManager,InApp,WhatsApp,Fake/*,Providers/*}, Contracts{NotificationChannel,Providers/*}, Data(RenderedMessage), Database{Migrations,Factories,Seeders}, Enums, Events{NotificationDelivered,NotificationDeadLettered}, Exceptions, Filament/Resources{Notification,NotificationTemplate,AutomationRule}, Http{Controllers,Requests,Resources}, Jobs(DeliverNotificationJob), Listeners(NotificationEventSubscriber), Models{Notification,NotificationTemplate,NotificationDelivery,NotificationPreference,AutomationRule,AutomationAction,ScheduledAutomation,UserNotificationSetting}, Policies(NotificationPolicy), Providers(NotificationsServiceProvider), Services{NotificationDispatcher,TemplateRenderer,PreferenceService,DigestService,RateLimiterService,WorkflowEngine}, routes(notifications.php)).
- Migration files move with the domain; **table names unchanged -> zero schema impact**.

## Namespaces changed
- `namespace App\Domains\Notifications\...;` -> `namespace App\Platform\Notifications\...;` in every moved file. No `composer.json` change (PSR-4 `App\ => app/`).

## Providers updated
- `bootstrap/providers.php`: `App\Domains\Notifications\Providers\NotificationsServiceProvider` -> `App\Platform\Notifications\Providers\NotificationsServiceProvider`.
- **Config path: safe, no fix.** `NotificationsServiceProvider` uses `__DIR__.'/../../../../config/notifications.php'` (4-ups) and `dirname(__DIR__)`. Depth is unchanged (`Domains/Notifications` and `Platform/Notifications` are both 3 dirs under `app`), so the 4-ups path still resolves to the app root. (Contrast: Shared changed depth in C1 and needed a `base_path()` fix.)

## Listeners updated
- `NotificationEventSubscriber` (registered via `Event::subscribe(...)` in `NotificationsServiceProvider`) FQCN rewritten. It subscribes to other domains' events (Learning/Commerce/Crm/Live/Certification/Identity) - those event imports live inside the subscriber and move with it (rewritten). Registration unchanged in behavior.

## Events updated
- `NotificationDelivered`, `NotificationDeadLettered` FQCNs rewritten; any dispatch/listener references across the repo updated by the blanket sweep.

## Notification channels updated
- Channel/provider classes (Email/SMS/Push/InApp/WhatsApp + Fake + real providers Mailgun/Twilio/Firebase) FQCNs rewritten; `ChannelManager`/`ProviderManager` driver bindings resolve to the new namespace.

## Queue updates
- `DeliverNotificationJob` FQCN rewritten. **Queued-payload note:** any job already serialized in Redis before the move references the old class and would fail to unserialize. Mitigation: run on a drained queue (dev has none pending) or clear/replay; production would need a queue drain during deploy. In dev/test this is a non-issue.

## Imports updated
- Every `use App\Domains\Notifications\...;` / FQCN across `apps/api/**/*.php` rewritten (two literal passes: `App\\Domains\\Notifications` for config strings, `App\Domains\Notifications` for code; long paths via `\\?\`).

## Filament updates (non-blanket fix)
- The `AdminPanelProvider` discovery loop is string-built, so the blanket rewrite doesn't redirect it. Script step C3.3 injects a Notifications branch (alongside the Identity branch added in C2):
  ```php
  if ($domain === 'Notifications') {
      $panel->discoverResources(
          in: app_path('Platform/Notifications/Filament/Resources'),
          for: 'App\Platform\Notifications\Filament\Resources',
      );
      continue;
  }
  ```
  `navigationGroups(['Notifications', ...])` labels untouched.

## Artisan outputs  PENDING (paste from the run)
```
composer dump-autoload      -> (paste)
php artisan optimize:clear   -> (paste)
php artisan config:clear     -> (paste)
php artisan route:list (tail)-> (paste - identical URIs to before)
```

## Tests  PENDING (paste from the run)
```
php artisan test             -> (paste - same pass count as C2 baseline)
```

## Remaining references
- Script asserts **zero** remaining `App\Domains\Notifications` (both slash forms). Paste the "OK: no remaining..." line.

## Risks
| # | Risk | Status |
|---|------|--------|
| R1 | Filament Notifications resources lost (string-built discovery) | Covered - Notifications branch injected (C3.3). If "anchor not found" prints, patch `AdminPanelProvider` manually. |
| R2 | Config-path depth break (like Shared/C1) | Not applicable - depth unchanged, 4-ups still correct (verified) |
| R3 | Stale serialized queue jobs reference old class | Dev/test: none pending. Prod: drain queue on deploy. |
| R4 | Event subscriber not re-registered | Covered - registered in provider (rewritten), not auto-discovered |
| R5 | Long-path read / docker stderr | Covered - `\\?\` + `ErrorActionPreference=Continue` |

## Acceptance criteria
| Criterion | Status |
|-----------|--------|
| notifications still dispatch | verify (tests covering NotificationDispatcher) |
| queued notifications resolve | verify (DeliverNotificationJob tests) |
| listeners resolve | verify (event->notification tests) |
| `php artisan test` passes | PENDING (expected: same as baseline) |
| `route:list` passes | PENDING |
| no `App\Domains\Notifications` references remain | asserted by script (paste to confirm) |

## STOP
C3 only. **Do not proceed to Learning (C4).** After you paste `route:list` + `test` + the "no remaining" line, I finalize this to PASS and wait for approval before C4.
