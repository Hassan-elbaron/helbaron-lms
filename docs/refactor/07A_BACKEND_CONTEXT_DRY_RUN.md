# STEP 5A — Backend Context Refactor: Dry Run (planning only)

**Type:** Documentation only. **No file moves. No namespace changes. No code changes.** This is the exhaustive map + risk analysis + safe migration order that the actual STEP 5 chunks will follow, so each chunk is mechanical and independently verifiable (`php artisan test` green after each).
**Source of truth:** `docs/refactor/01_DOMAIN_MAP.md`, `03_FOLDER_STRUCTURE.md`.
**Grounding:** current backend structure verified across audits 04/05 (10 flat domains under `app/Domains/*`, `app/Shared`, providers in `bootstrap/providers.php`, Filament in `App\Providers\AdminPanelProvider`, PSR‑4 `"App\\": "app/"`).

---

## 0. Key enabling fact (why this is safe with the existing autoloader)

`composer.json` uses the default Laravel PSR‑4 map: **`"App\\": "app/"`**. Therefore a class named `App\Contexts\Learning\Models\Enrollment` autoloads from `app/Contexts/Learning/Models/Enrollment.php` **with no composer.json change** — as long as the folder path and the namespace are renamed *consistently*. `composer dump-autoload` is still required (to refresh the classmap/optimized autoload), but the PSR‑4 root mapping does **not** change.

**Consequence:** every move below is "(a) move folder, (b) rewrite `namespace` line, (c) rewrite every `use App\Domains\X` / `use App\Shared` reference." The only places that need *logic* edits (not blanket string replace) are: `bootstrap/providers.php`, `AdminPanelProvider` (Filament discovery paths built from strings), and any config/string that references a class path.

---

## 1. Full Namespace Map

### 1.1 Platform (shared kernel + supporting)

| Current namespace / folder | Target namespace / folder | Kind |
|----------------------------|---------------------------|------|
| `App\Shared\*` — `app/Shared` | `App\Platform\Shared\*` — `app/Platform/Shared` | blanket rename |
| `App\Domains\Identity\*` — `app/Domains/Identity` | `App\Platform\Identity\*` — `app/Platform/Identity` | blanket rename |
| `App\Domains\Notifications\*` — `app/Domains/Notifications` | `App\Platform\Notifications\*` — `app/Platform/Notifications` | blanket rename |

### 1.2 Contexts — clean 1:1 moves (blanket rename)

| Current | Target | Kind |
|---------|--------|------|
| `App\Domains\Learning\*` | `App\Contexts\Learning\*` | blanket |
| `App\Domains\Commerce\*` | `App\Contexts\Commerce\*` | blanket |
| `App\Domains\Analytics\*` | `App\Contexts\Analytics\*` | blanket |
| `App\Domains\Authoring\*` | `App\Contexts\Instructor\Authoring\*` | blanket (nested under Instructor) |

### 1.3 Contexts — items NOT explicitly listed in the STEP 5 brief (decisions required — see §7)

| Current | Proposed target | Why / flag |
|---------|-----------------|-----------|
| `App\Domains\Certification\*` | `App\Contexts\Learning\Certification\*` | Domain map = supporting capability owned by Learning (learner view) + Admin (templates). **Not named in brief → confirm.** |
| `App\Domains\Catalog\*` | **Split** (see §1.4) | Brief moves only "Catalog *write* logic" to Instructor; read is Marketing, admin is Administration. Full split is high‑risk. **Recommend: defer the split — keep `App\Contexts\Catalog\*` intact as a supporting/published‑language module in chunk 1, split later.** |
| `App\Domains\Live\*` | **Split** (see §1.4) | Brief moves "Instructor‑owned Live logic" to Instructor; learner *join* stays in Learning. **Recommend: defer — keep `App\Contexts\Live\*` intact first, split later.** |

### 1.4 Hard splits (do NOT blanket‑rename; per‑file classification)

**A) `App\Domains\Crm` → `App\Contexts\Organization` + `App\Contexts\Crm`**

Current `Crm/Models` (21) → destination:

| Model / concern | → Context |
|-----------------|-----------|
| Organization, OrganizationMember, Department, Team, TeamMember (join) | **Organization** |
| SeatPool, SeatAssignment | **Organization** |
| BillingProfile | **Organization** |
| ConsultingRequest | **Organization** (org raises the request) |
| Company, Contact | **Crm** (accounts) |
| Pipeline, Stage, Lead, Opportunity | **Crm** |
| ConsultingProject, ConsultingSession | **Crm** (consulting *delivery*) |
| CrmActivity, CrmNote, CrmTask, CrmTag | **Crm** (timeline) |
| Concerns: HasActivities, HasNotes, HasTags, HasTasks | **Crm** (but referenced by Organization models too — see risk R4) |

Migrations (21 tables) stay byte‑for‑byte (schema unchanged); only their **namespace of the migration class is anonymous** (Laravel migrations are anonymous classes) so **no namespace edit needed for migration files** — they move only if we relocate `Database/Migrations` folders, which we should to keep the DDD layout. Table names unchanged → zero schema impact.

**B) `App\Domains\Catalog` → (deferred) split** into Marketing (read: `CourseController`, public resources, `CourseSearchService`, `RelatedCoursesService`), Instructor (write: `Create/Update/Publish/Archive CourseAction`, `Category` write), Administration (Filament CourseResource/CategoryResource). **Recommendation: chunk this LAST, or keep Catalog whole as `App\Contexts\Catalog` supporting module.**

**C) `App\Domains\Live` → (deferred) split** into Learning (join/attendance) and Instructor (scheduling/management). **Recommendation: keep whole as `App\Contexts\Live` first.**

### 1.5 Administration

| Current | Target |
|---------|--------|
| `App\Providers\AdminPanelProvider` | `App\Contexts\Administration\Providers\AdminPanelProvider` (or keep in `App\Providers` — see risk R6) |
| `App\Filament\Widgets\PlatformOverview` | `App\Contexts\Administration\Filament\Widgets\PlatformOverview` |
| Each domain's `Filament/Resources/*` | **stays inside its owning context** (discovered by the panel); Administration only owns the *panel wiring* + cross‑cutting content resources (future Homepage/Landing/Brand/Seo). |

---

## 2. Provider Registration Map (`bootstrap/providers.php`)

Current (13 providers, in order):
```
AppServiceProvider, SharedServiceProvider,
IdentityServiceProvider, CatalogServiceProvider, AuthoringServiceProvider,
LearningServiceProvider, CommerceServiceProvider, CertificationServiceProvider,
LiveServiceProvider, CrmServiceProvider, AnalyticsServiceProvider,
NotificationsServiceProvider, AdminPanelProvider
```

Target imports (namespace changes only; order preserved, Crm becomes two):
| Provider | New FQCN |
|----------|----------|
| SharedServiceProvider | `App\Platform\Shared\Providers\SharedServiceProvider` |
| IdentityServiceProvider | `App\Platform\Identity\Providers\IdentityServiceProvider` |
| NotificationsServiceProvider | `App\Platform\Notifications\Providers\NotificationsServiceProvider` |
| CatalogServiceProvider | `App\Contexts\Catalog\Providers\CatalogServiceProvider` (if kept whole) |
| AuthoringServiceProvider | `App\Contexts\Instructor\Authoring\Providers\AuthoringServiceProvider` |
| LearningServiceProvider | `App\Contexts\Learning\Providers\LearningServiceProvider` |
| CommerceServiceProvider | `App\Contexts\Commerce\Providers\CommerceServiceProvider` |
| CertificationServiceProvider | `App\Contexts\Learning\Certification\Providers\CertificationServiceProvider` |
| LiveServiceProvider | `App\Contexts\Live\Providers\LiveServiceProvider` |
| **CrmServiceProvider → split** | `App\Contexts\Crm\Providers\CrmServiceProvider` **+ new** `App\Contexts\Organization\Providers\OrganizationServiceProvider` |
| AnalyticsServiceProvider | `App\Contexts\Analytics\Providers\AnalyticsServiceProvider` |
| AdminPanelProvider | `App\Contexts\Administration\Providers\AdminPanelProvider` |

**Action:** rewrite the `use` imports + array in `bootstrap/providers.php`. Adding the new `OrganizationServiceProvider` requires extracting the org bindings/routes/policies from the current `CrmServiceProvider`.

---

## 3. Route Registration Map

Each domain provider registers its own route file (pattern: `loadRoutesFrom(__DIR__.'/../routes/<domain>.php')` or from the provider's `boot()`), producing the **same URLs** (`/api/v1/...`). Route **files move with their context**; the `loadRoutesFrom` path is relative (`__DIR__`) so it keeps working after the folder move — **no path edit needed** as long as the routes folder moves with the provider.

| Route file (current) | Moves to | URL impact |
|----------------------|----------|-----------|
| `Domains/Identity/routes/{auth,devices,profile}.php` | `Platform/Identity/routes/*` | none |
| `Domains/Notifications/routes/notifications.php` | `Platform/Notifications/routes/*` | none |
| `Domains/{Learning,Commerce,Analytics}/routes/*` | `Contexts/{Learning,Commerce,Analytics}/routes/*` | none |
| `Domains/Authoring/routes/authoring_admin.php` | `Contexts/Instructor/Authoring/routes/*` | none |
| `Domains/Catalog/routes/catalog.php` | `Contexts/Catalog/routes/*` | none |
| `Domains/Live/routes/live.php` | `Contexts/Live/routes/*` | none |
| `Domains/Crm/routes/crm.php` | **split** across `Contexts/Crm/routes/crm.php` + `Contexts/Organization/routes/organization.php` | none (same URLs; only which provider loads them changes) |
| `Domains/Certification/routes/certification.php` | `Contexts/Learning/Certification/routes/*` | none |

**Verify with:** `php artisan route:list` — the count and URIs must be **identical** before/after each chunk.

---

## 4. Policy Map

Policies are registered per domain provider (Gate::policy / `$policies` in each provider's `boot()`), mapping `Model::class => Policy::class`. After moving, **both the model FQCN and policy FQCN change**, so each registration line updates automatically via the blanket `use` rewrite. 20 policies:

| Policy | Owning context after move |
|--------|---------------------------|
| UserPolicy, DevicePolicy | Platform/Identity |
| NotificationPolicy | Platform/Notifications |
| CoursePolicy, CategoryPolicy | Catalog (or split later) |
| LessonPolicy, SectionPolicy | Instructor/Authoring |
| EnrollmentPolicy | Learning |
| CertificatePolicy, BadgePolicy | Learning/Certification |
| ProductPolicy, OrderPolicy, ContractPolicy | Commerce |
| LiveSessionPolicy | Live |
| **LeadPolicy** | Crm |
| **OrganizationPolicy** | **Organization** (moves with the split) |
| ConsultingRequestPolicy | **Organization** (request) — confirm vs Crm |
| DashboardDefinitionPolicy, ReportDefinitionPolicy, ExportJobPolicy | Analytics |

**Action:** the `OrganizationPolicy` (+ `ConsultingRequestPolicy`) registration must move from `CrmServiceProvider` to the new `OrganizationServiceProvider`.

---

## 5. Filament Resource Map (the highest‑risk area)

`AdminPanelProvider` currently discovers resources with a **string‑built path + namespace** per domain:
```php
private const DOMAINS = ['Identity','Catalog','Authoring','Learning','Commerce',
                         'Certification','Live','Crm','Analytics','Notifications'];
$panel->discoverResources(
  in:  app_path("Domains/{$domain}/Filament/Resources"),
  for: "App\\Domains\\{$domain}\\Filament\\Resources",
);
```
A blanket `App\Domains\X` string replace **will NOT fix this** — the path `"Domains/{$domain}"` and namespace template `"App\\Domains\\{$domain}"` are constructed at runtime. **This is the #1 thing a naive migration breaks (admin panel silently shows no resources).**

**Required rewrite:** replace the single loop with explicit per‑context discovery, e.g.:
```php
$discover = [
  ['Platform/Identity/Filament/Resources',      'App\\Platform\\Identity\\Filament\\Resources'],
  ['Platform/Notifications/Filament/Resources',  'App\\Platform\\Notifications\\Filament\\Resources'],
  ['Contexts/Catalog/Filament/Resources',        'App\\Contexts\\Catalog\\Filament\\Resources'],
  ['Contexts/Instructor/Authoring/Filament/Resources','App\\Contexts\\Instructor\\Authoring\\Filament\\Resources'],
  ['Contexts/Learning/Filament/Resources',       'App\\Contexts\\Learning\\Filament\\Resources'],
  ['Contexts/Learning/Certification/Filament/Resources','App\\Contexts\\Learning\\Certification\\Filament\\Resources'],
  ['Contexts/Commerce/Filament/Resources',       'App\\Contexts\\Commerce\\Filament\\Resources'],
  ['Contexts/Live/Filament/Resources',           'App\\Contexts\\Live\\Filament\\Resources'],
  ['Contexts/Crm/Filament/Resources',            'App\\Contexts\\Crm\\Filament\\Resources'],
  ['Contexts/Organization/Filament/Resources',   'App\\Contexts\\Organization\\Filament\\Resources'],
  ['Contexts/Analytics/Filament/Resources',      'App\\Contexts\\Analytics\\Filament\\Resources'],
];
foreach ($discover as [$path,$ns]) { $panel->discoverResources(in: app_path($path), for: $ns); }
```
Also the panel `->widgets([PlatformOverview::class])` import + the `->authMiddleware([EnforceAdminMfa::class])` import (EnforceAdminMfa currently `App\Domains\Identity\Http\Middleware\EnforceAdminMfa` → `App\Platform\Identity\...`) must update.

**24 resources** (paths change only; class bodies unchanged): Identity(UserResource), Catalog(Course, Category), Authoring(Lesson, Section), Learning(Enrollment), Certification(Certificate, CertificateTemplate, Badge), Commerce(Product, Coupon, Order, ContractTemplate), Live(LiveCourse, LiveSession), **Crm→ Lead + Organization(→Organization ctx) + ConsultingRequest**, Analytics(Dashboard, ReportDefinition, ExportJob), Notifications(Notification, NotificationTemplate, AutomationRule).

**Verify with:** load `/admin` and confirm every resource appears; `php artisan about` / no discovery warnings.

---

## 6. Test Namespace Map

`apps/api/tests` (51 Feature + 19 Unit). Tests reference domain classes via `use App\Domains\...` / `use App\Shared\...`. All covered by the **blanket `use` rewrite** across `tests/**`. Test *class* namespaces are `Tests\...` (unchanged — `"Tests\\": "tests/"` autoload untouched). Feature test folders (e.g., `tests/Feature/Crm/*`) may be renamed for clarity (`tests/Feature/Organization/*`) but this is cosmetic; **keep names to minimize churn** in chunk 1.

**Verify with:** `php artisan test` — same pass count before/after each chunk.

---

## 7. Risk List

| # | Risk | Severity | Mitigation |
|---|------|----------|-----------|
| R1 | **Filament discovery path/namespace is string‑built** → blanket replace misses it → admin shows no resources | 🔴 High | Rewrite discovery loop explicitly (§5); verify `/admin` after the Filament chunk |
| R2 | **Boot‑fatal autoload errors** — one missed `use App\Domains\X` → class‑not‑found at boot → whole API + admin down | 🔴 High | Chunk‑by‑chunk; run `php artisan config:clear && php artisan route:list && php artisan test` after each chunk; keep a **compatibility `class_alias` shim** for one release if needed |
| R3 | **Crm split** — `OrganizationServiceProvider` extraction; polymorphic timeline (Activities/Notes/Tasks/Tags) referenced by BOTH Organization and Crm models → cross‑context coupling | 🟠 Med‑High | Keep the `Has*` concerns + timeline models in **Crm**, expose to Organization via a published contract; do the split as its OWN chunk, last |
| R4 | **Catalog / Live splits** are as hard as Crm | 🟠 Med | **Defer** — keep Catalog & Live whole (`App\Contexts\Catalog`, `App\Contexts\Live`) in the first pass; split in a later, separate effort |
| R5 | **Config files** referencing class strings (e.g., `config/*.php` provider/model FQCNs, `config/admin.php`) | 🟡 Med | Grep `App\\Domains` / `App\\Shared` in `config/**` and rewrite |
| R6 | **AdminPanelProvider move** — moving it out of `App\Providers` changes `bootstrap/providers.php` + any reference | 🟡 Med | Optionally **keep AdminPanelProvider in `App\Providers`** (only update the discovery paths) to reduce churn; move to Administration later |
| R7 | **Migrations** are anonymous classes; if any migration references a domain **model/enum** class (e.g., seeding enum values) it breaks | 🟡 Med | Grep migrations for `App\\Domains` / `App\\Shared`; most don't reference app classes |
| R8 | **Seeders/Factories** reference model FQCNs + `DatabaseSeeder` calls domain seeders | 🟡 Med | Covered by blanket `use` rewrite; verify `db:seed` in a scratch DB |
| R9 | **Event/Listener discovery** — if listeners are auto‑discovered by namespace, moved namespaces must be re‑registered | 🟡 Med | HElbaron registers events in providers (not auto‑discovery) → covered by provider updates; verify events fire in tests |
| R10 | **Windows/Docker path‑case + `[` brackets** (none in backend paths) | 🟢 Low | Backend paths have no brackets; but reuse the STEP‑2 lesson: use literal `git mv`/`Move-Item -LiteralPath` |

---

## 8. Exact Chunk Order (safe migration sequence)

Each chunk = move + rename + rewrite refs + **run `php artisan config:clear && php artisan route:list && php artisan test`** → must be green before the next chunk. Commit after each green chunk.

| Chunk | Scope | Why this order | Verify |
|-------|-------|----------------|--------|
| **C1 — Platform/Shared** | `App\Shared` → `App\Platform\Shared` | Leaf kernel, most‑referenced; get the base right first | test green |
| **C2 — Platform/Identity** | `App\Domains\Identity` → `App\Platform\Identity` (+ EnforceAdminMfa ref in AdminPanelProvider) | Kernel; everything depends on it | test + `/admin` login |
| **C3 — Platform/Notifications** | `App\Domains\Notifications` → `App\Platform\Notifications` | Supporting sink | test green |
| **C4 — Contexts/Learning** | `App\Domains\Learning` → `App\Contexts\Learning` | Clean 1:1 | route:list + test |
| **C5 — Contexts/Learning/Certification** | `App\Domains\Certification` → `App\Contexts\Learning\Certification` | Supporting under Learning (confirm §7) | test |
| **C6 — Contexts/Commerce** | clean 1:1 | independent | test |
| **C7 — Contexts/Analytics** | clean 1:1 (event‑DTO consumer) | independent sink | test |
| **C8 — Contexts/Instructor/Authoring** | `App\Domains\Authoring` → `App\Contexts\Instructor\Authoring` | clean 1:1, nested | test |
| **C9 — Contexts/Catalog (whole)** | `App\Domains\Catalog` → `App\Contexts\Catalog` (NO split yet) | defer split; keep whole | test + public catalog routes |
| **C10 — Contexts/Live (whole)** | `App\Domains\Live` → `App\Contexts\Live` (NO split yet) | defer split | test |
| **C11 — Crm split** | `App\Domains\Crm` → `App\Contexts\Crm` + `App\Contexts\Organization` (+ new OrganizationServiceProvider, policy move) | hardest; do on a fully‑green base | route:list identical + test + `/admin` Org & Lead resources |
| **C12 — Filament discovery rewrite** | rewrite `AdminPanelProvider` discovery loop to explicit per‑context paths (§5); optionally move provider to Administration | after all contexts exist | load `/admin`, every resource visible |
| **C13 — Cleanup** | remove empty `app/Domains`, `app/Shared`; grep‑assert zero `App\\Domains` / `App\\Shared` (except documented shims); `composer dump-autoload -o` | final | full `php artisan test` + `route:list` + `/admin` |

**Deferred to a later effort (not chunk 1):** the Catalog write/read/admin split and the Live instructor/learner split (both flagged high‑risk; keeping them whole preserves correctness now).

---

## 9. Commands run per chunk (verification protocol)

```bash
composer dump-autoload
php artisan config:clear
php artisan optimize:clear
php artisan route:list         # URI count must be unchanged
php artisan test               # pass count must be unchanged
# after Filament chunk: open /admin and confirm all resources render
```

## 10. Acceptance for STEP 5A (this document)

- [x] Full namespace map (Platform + Contexts + both splits) — §1
- [x] Provider registration map — §2
- [x] Route registration map — §3
- [x] Policy map — §4
- [x] Filament resource map (incl. the string‑built discovery hazard) — §5
- [x] Test namespace map — §6
- [x] Risk list — §7
- [x] Exact safe chunk order — §8
- [x] No code changes, no file moves, no namespace changes (planning only) ✅

**Recommended next action:** execute **C1 (Platform/Shared)** only, then run the §9 verification and paste results. When green, proceed to C2. This keeps every step reversible and any breakage localized to a single chunk.
