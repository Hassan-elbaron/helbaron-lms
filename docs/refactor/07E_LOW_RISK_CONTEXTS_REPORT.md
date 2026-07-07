# STEP 5E - Low-risk contexts: Learning, Commerce, Analytics -> Contexts (report)

**Scope:** move ONLY `App\Domains\{Learning,Commerce,Analytics}` -> `App\Contexts\{...}`. Catalog/Live/Crm/Authoring/Administration untouched. No schema, behavior, API, or URL change.
**Runner:** `scripts/refactor-5e-low-risk-contexts.ps1` + a one-time AdminPanelProvider discovery-map edit (below).
**Precondition:** C1/C2/C3 (Platform) confirmed green.

## Execution note
Executor can't run composer/artisan here; artisan/test outputs come from your run (PENDING). Run:
```powershell
cd "D:\Claude_Files\Projects\LMS\CoreLMS Implementation\corelms"
powershell -ExecutionPolicy Bypass -File scripts/refactor-5e-low-risk-contexts.ps1
```

## Moved contexts
| From | To | Kind |
|------|----|------|
| `app/Domains/Learning` | `app/Contexts/Learning` | clean 1:1 |
| `app/Domains/Commerce` | `app/Contexts/Commerce` | clean 1:1 |
| `app/Domains/Analytics` | `app/Contexts/Analytics` | clean 1:1 |

Each entire tree moves (Actions, Contracts, Database{Migrations,Factories,Seeders}, Enums, Events, Exceptions, Filament/Resources, Http, Jobs, Listeners, Models, Playback/Gateways/Metrics(as applicable), Policies, Providers, Services, routes). Migration table names unchanged -> **zero schema impact**.

## Namespace changes
- `App\Domains\Learning\*` -> `App\Contexts\Learning\*`
- `App\Domains\Commerce\*` -> `App\Contexts\Commerce\*`
- `App\Domains\Analytics\*` -> `App\Contexts\Analytics\*`
- No `composer.json` change (PSR-4 `App\ => app/`).

## Provider updates
- `bootstrap/providers.php`: the 3 provider FQCNs rewritten (`App\Contexts\Learning\Providers\LearningServiceProvider`, `...Commerce...`, `...Analytics...`).
- **Config path: safe, no fix.** Each provider uses `__DIR__.'/../../../../config/<name>.php'` (4-ups) + `dirname(__DIR__)`. Depth is unchanged (`Domains/X` and `Contexts/X` are both 3 dirs under `app`), so the 4-ups path still resolves to the app root. (Only C1/Shared changed depth and needed a fix.)

## Route updates
- Route files move with their context; `loadRoutesFrom(__DIR__...)` is provider-relative, so **URLs are identical**. `route:list` URI count must be unchanged. (Verify.)

## Policy updates
- `EnrollmentPolicy` (Learning); `ProductPolicy`, `OrderPolicy`, `ContractPolicy` (Commerce); `DashboardDefinitionPolicy`, `ReportDefinitionPolicy`, `ExportJobPolicy` (Analytics) move with their context; registrations in each provider update automatically via the blanket rewrite (model + policy FQCNs both change).

## Event/listener updates
- Learning events (`UserEnrolled`, `LessonCompleted`, `CourseCompleted`, `LessonProgressRecorded`) and listeners (`UpdateLearningSession`) FQCNs rewritten. Commerce events/listeners (`OrderPaid`/`ContractAccepted` -> fulfillment) rewritten. Analytics `MetricEventSubscriber` (subscribes to many domains' events) rewritten - the event imports live inside the subscriber and move with it. Cross-domain listeners in OTHER (unmoved) domains that reference these events are updated by the repo-wide sweep.

## Imports updated
- Every `use App\Domains\{Learning|Commerce|Analytics}\...;` / FQCN across `apps/api/**/*.php` (two literal passes per context; long paths via `\\?\`). This includes cross-references (e.g., Certification listens to Learning's `CourseCompleted`; Commerce grants Learning enrollments; Notifications subscriber references Learning/Commerce events) - all rewritten repo-wide.

## Filament (discovery map - no branches)
Per your instruction ("no new special-case branches"), the `AdminPanelProvider` discovery was **converted from the string-template loop + the earlier Identity/Notifications `if` branches to a single data map** and a uniform loop:
```php
private const RESOURCE_PATHS = [
    'App\\Platform\\Identity\\Filament\\Resources'      => 'Platform/Identity/Filament/Resources',
    'App\\Platform\\Notifications\\Filament\\Resources' => 'Platform/Notifications/Filament/Resources',
    'App\\Contexts\\Learning\\Filament\\Resources'      => 'Contexts/Learning/Filament/Resources',
    'App\\Contexts\\Commerce\\Filament\\Resources'      => 'Contexts/Commerce/Filament/Resources',
    'App\\Contexts\\Analytics\\Filament\\Resources'     => 'Contexts/Analytics/Filament/Resources',
    'App\\Domains\\Catalog\\Filament\\Resources'        => 'Domains/Catalog/Filament/Resources',
    'App\\Domains\\Authoring\\Filament\\Resources'      => 'Domains/Authoring/Filament/Resources',
    'App\\Domains\\Certification\\Filament\\Resources'  => 'Domains/Certification/Filament/Resources',
    'App\\Domains\\Live\\Filament\\Resources'           => 'Domains/Live/Filament/Resources',
    'App\\Domains\\Crm\\Filament\\Resources'            => 'Domains/Crm/Filament/Resources',
];
...
foreach (self::RESOURCE_PATHS as $namespace => $path) {
    $panel->discoverResources(in: app_path($path), for: $namespace);
}
```
This **removes** all `if ($domain === ...)` branches (net -2 branches vs before), adds no new ones, and only updates resource-path references. Future context moves = one map line, never a branch. `navigationGroups([...])` labels untouched.

## Artisan outputs  PENDING (paste from the run)
```
composer dump-autoload       -> (paste)
php artisan optimize:clear    -> (paste)
php artisan config:clear      -> (paste)
php artisan route:list (tail) -> (paste - identical URIs)
```

## Tests  PENDING (paste from the run)
```
php artisan test              -> (paste - same pass count as C3 baseline)
```

## Remaining App\Domains references (for migrated contexts)
- Script asserts **zero** remaining `App\Domains\{Learning,Commerce,Analytics}` (both slash forms). Paste the "OK: no remaining..." line. (References to Catalog/Live/Crm/Authoring/Certification remain by design - those contexts are not part of 5E.)

## Technical debt
- Catalog, Live, Crm, Authoring, Certification still under `App\Domains\*` (intended - later chunks). The `RESOURCE_PATHS` map already lists them at their current `Domains/` paths and will be updated one line at a time as they move.
- Cross-context coupling (Analytics subscribing to concrete domain events, Commerce->Learning) is unchanged by this move - decoupling to event DTOs is a separate roadmap item (audit 04/G3), not part of a pure relocation.

## Risks
| # | Risk | Status |
|---|------|--------|
| R1 | Filament resources for the 3 contexts lost | Covered - map points them at `Contexts/`; verify `/admin` shows Enrollment / Product,Coupon,Order,Contract / Dashboard,Report,Export |
| R2 | Config-path depth break (like Shared) | Not applicable - depth unchanged (verified) |
| R3 | Cross-domain event refs to moved events missed | Covered - repo-wide sweep rewrites all `use` sites |
| R4 | Long-path read / docker stderr noise | Covered - `\\?\` + stderr rendered as plain text (`ForEach-Object { "$_" }`) so no scary red |

## Acceptance criteria
| Criterion | Status |
|-----------|--------|
| Learning works | verify (enroll/progress/player tests) |
| Commerce works | verify (cart/checkout/order tests) |
| Analytics works | verify (kpi/report/export tests) |
| `php artisan test` passes | PENDING |
| `route:list` unchanged | PENDING |
| no `App\Domains` refs remain for migrated contexts | asserted by script |
| no new conditional discovery logic in AdminPanelProvider | met - branches removed, replaced by a data map |
| no business logic changed | met - move + rename only |

## STOP
After you paste `route:list` + `test` + the "no remaining" line, I finalize this to PASS. Catalog/Live/Crm/Authoring/Administration remain for later chunks (each their own gated step).
