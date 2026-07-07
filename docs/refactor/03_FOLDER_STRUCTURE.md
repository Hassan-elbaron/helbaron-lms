# HElbaron LMS — Folder & Package Structure (Refactor 03)

**Type:** Documentation only. Target folder/package layout for the 8 bounded contexts + Platform. No code moves executed here.
**Constraints honored:** keep Laravel DDD, keep Next.js App Router, no schema change, minimize churn, reduce coupling, keep multi-tenancy in mind.
**Companion:** Refactor 01 (Domain Map), Refactor 02 (Routes).

---

## 1. Current Folders (as-built)

### Backend (`apps/api`)
```
app/
  Domains/{Identity,Catalog,Authoring,Learning,Commerce,Certification,
           Live,Crm,Analytics,Notifications}/
      Actions/ Contracts/ Database/{Migrations,Factories,Seeders}/
      Enums/ Events/ Exceptions/ Filament/Resources/ Http/{Controllers,Requests,Resources}/
      Jobs/ Listeners/ Models/ Policies/ Providers/ Services/ routes/ openapi/
  Shared/{Actions,Contracts,DTOs,Enums,Exceptions,Helpers,Policies,Providers,
          Resources,Services,Support,Traits,ValueObjects}/
  Filament/Widgets/  Http/{Controllers,Middleware}/  Logging/  Providers/
```
Flat: 10 domains as peers; Identity/Notifications sit beside product domains; `Crm` holds both Organization and CRM concerns.

### Frontend (`apps/web`)
```
src/
  app/(marketing)/(public)/(auth)/(onboarding)/(student)/(org)/(crm)/(analytics)/(dashboard)/
      settings/theme/  layout.tsx providers.tsx globals.css
  components/{ui,layout,landing,marketing,catalog,commerce,crm,org,student,analytics,learning,states}/
  lib/{api,auth,i18n,catalog,commerce,crm,org,student,analytics,learning,theme}/
  config/{nav,theme,site,demo,page-heroes}/  hooks/  types/
```
No monorepo tooling at root (no workspace/turbo/shared packages).

### Problems (folder-level)
- Identity/Notifications not distinguished from product domains (misplaced → Platform).
- `Crm` mixes two contexts.
- Frontend `(public)` group mixes Marketing + Commerce + Learning; `(student)` mixes Learning + Account; dead `(dashboard)`; misplaced `settings/theme`.
- No shared **contracts** package → hand-written API types (drift).
- `components/` and `lib/` are per-feature but not aligned 1:1 to bounded contexts (e.g., `catalog` vs `marketing`).

---

## 2. Proposed Root — Real Monorepo

```
corelms/
  apps/
    api/            # Laravel 12 — backend contexts + Administration (Filament)
    web/            # Next.js 15 — 8 product web contexts
  packages/
    contracts/      # generated API client + shared DTO/enums (from OpenAPI) — the anti-drift layer
    config/         # shared tsconfig, eslint, prettier, tailwind preset
    ui/             # (optional, later) shared design-system primitives + tokens
  docs/
  infra/  scripts/  .github/
  turbo.json  pnpm-workspace.yaml  package.json
```
Rationale: eliminates FE↔BE type drift (audit 04), enables cached/parallel task graph, shared tooling. `contracts` is the **published-language** package every web context imports.

---

## 3. Proposed Backend (`apps/api`) — Contexts over Domains

Keep the DDD module taxonomy; **regroup** modules under explicit context/platform folders and **split Crm**. This is a namespace/move refactor — **no schema change**.

```
app/
  Contexts/
    Marketing/                 # read-only projections + public API of catalog/commerce
      ReadModels/  Http/{Controllers,Resources}/  Providers/  routes/
    Instructor/                # write-side of teaching  (adopts Authoring + own-course slice)
      Authoring/  (moved) …    # Sections, Lessons, Media, Curriculum, PublishGuard
      Catalog/                 # course *write* (create/update/publish) — instructor/admin owned
      Live/                    # session scheduling/management (instructor side)
      Actions/ Services/ Http/ Filament/ Providers/ routes/ …
    Learning/                  # enrollment, progress, playback, learner cert view, session join
      Learning/ Certification/(learner view) Live/(join)  …
    Commerce/                  # products, cart, coupons, checkout, orders, contracts, payments
    Organization/              # (SPLIT from Crm) orgs, members, seats, seat pools, org billing
    Crm/                       # (SPLIT from Crm) leads, pipelines, opportunities, consulting, timeline
    Analytics/                 # metrics, snapshots, reports, dashboards, exports (event-DTO fed)
    Administration/            # Filament panel wiring + Content models (Homepage/Landing/Brand/Seo)
  Platform/
    Identity/                  # users, auth, roles/permissions, MFA, OTP, devices, audit  (kernel)
    Notifications/             # templates, channels, delivery, automation (supporting)
    Shared/                    # base Action/Service/Resource/Exception, ApiResponse, VOs, helpers
  Http/{Middleware}/  Logging/  Providers/
```

Notes:
- **Catalog** is not a top-level peer anymore: its **write** side lives in Instructor/Administration; its **read** projection is exposed by Marketing/Learning. (Physically the model can stay in one module exposed via a contract; the folder move signals ownership.)
- **Certification** and **Live** are *supporting capabilities* referenced by more than one context; place their code under the **primary** owner (Learning) and expose instructor/admin operations via contracts — avoid duplicating.
- **Crm split** is the only structural split: move `Organizations/Members/Seats/SeatPools/BillingProfile` → `Contexts/Organization`; keep `Leads/Pipelines/Stages/Opportunities/Consulting*/Activities/Notes/Tasks/Tags` → `Contexts/Crm`. Tables unchanged; namespaces/models relocated.
- Each context keeps the familiar internal taxonomy (`Actions/ Services/ Http/ Models/ Events/ Listeners/ Policies/ Providers/ routes/ openapi/`).
- **Contracts folder per context** (`Contexts/<X>/Contracts/<X>Context.php`) publishes the only surface other contexts may call — enforced by **Deptrac** rules.

### Backend module classification
| Type | Modules |
|------|---------|
| **Feature (product) modules** | Marketing, Instructor, Learning, Commerce, Organization, Crm, Analytics, Administration |
| **Shared kernel** | Platform/Identity, Platform/Shared |
| **Supporting/generic** | Platform/Notifications, Certification, Live (capabilities) |
| **Infrastructure** | Http/Middleware, Logging, Providers, payment/media/pdf/channel adapters (live inside their owning context's `*/Providers` + `*/Contracts`) |

---

## 4. Proposed Frontend (`apps/web`) — Context Feature Modules

### 4.1 App Router groups (1 group ⇄ 1 context; see Refactor 02)
```
src/app/
  (marketing)/            # landing, catalog(read), service, legal
    (auth)/               # login/register/forgot/reset/verify/mfa
  (learning)/             # dashboard, my-learning, continue, /learn/*, certificates
  (account)/              # /account/profile,/settings,/notifications
  (commerce)/             # cart, checkout, orders, contracts
  (instructor)/           # /teach/*                                   [NEW]
  (organization)/         # /org/*
  (crm)/                  # /crm/*
  (analytics)/            # /analytics,/reports,/dashboards
  layout.tsx providers.tsx globals.css sitemap.ts robots.ts not-found.tsx
```
Each group carries its own `layout.tsx` (auth/role guard) + `loading.tsx` + `error.tsx`.

### 4.2 Feature modules — align `features/` to contexts
Replace the ad-hoc `components/*` + `lib/*` split with a **feature-module** layout so each context owns its UI + data together:

```
src/
  features/
    marketing/{components,api,hooks,server}/     # catalog read, hero, service, legal
    learning/{components,api,hooks,server}/      # player, curriculum, progress, certificates
    account/{components,api,hooks}/              # profile, settings, notification center
    commerce/{components,api,hooks}/             # cart, checkout, orders, contracts
    instructor/{components,api,hooks}/           # teach dashboard, authoring editor, roster, earnings
    organization/{components,api,hooks}/         # members, seats, org consulting
    crm/{components,api,hooks}/                  # leads, pipeline, accounts, consulting delivery
    analytics/{components,api,hooks}/            # kpis, reports, dashboards, exports
  shared/
    ui/                # design-system primitives (button, card, input, table, dialog … + tokens)
    layout/            # app shell, sidebar, topbar, breadcrumbs, user-menu
    api/               # apiFetch client, query-client, queryKeys factory
    auth/              # session/role hooks, guards (thin; middleware is primary)
    i18n/              # split per-context dictionaries + provider
    lib/               # format, utils
    config/            # nav (single source), site
  types/               # re-export from packages/contracts (generated); no hand-written API types
```

### 4.3 Frontend module classification
| Type | Modules |
|------|---------|
| **Feature modules** | features/{marketing,learning,account,commerce,instructor,organization,crm,analytics} |
| **Shared modules** | shared/{ui,layout,api,auth,i18n,lib,config} |
| **Infrastructure** | app/ route groups + `middleware.ts` (auth/redirects), `next.config.ts` (headers/CSP/redirects), providers |
| **Generated (published language)** | `packages/contracts` → imported by every feature `api/` |

Moves that resolve prior findings: `components/catalog` → `features/marketing`; `components/student` split into `features/learning` + `features/account`; `components/{crm,org,analytics,commerce,learning}` → matching `features/*`; `components/ui` + `layout` → `shared/*`; delete `components/catalog/public-header.tsx`; retire `config/theme` public surface into Administration content.

---

## 5. Shared vs Feature vs Infrastructure (rules)

- **Shared** = used by ≥2 contexts and context-agnostic (design system, api client, auth hooks, i18n runtime, base backend classes, Identity kernel). Lives in `shared/*` (web) / `Platform/*` (api) / `packages/*` (cross-app).
- **Feature** = belongs to exactly one context; may depend on Shared and on other contexts **only through published contracts/events**. Never imports another feature's internals.
- **Infrastructure** = framework wiring (route groups, middleware, providers, adapters, CI). Adapters (Stripe/Mux/CloudFront/PDF/mail/SMS/push) live inside their owning context's `Providers` + `Contracts` (ports & adapters), not in a global bucket.

Enforcement: **Deptrac** (backend) + **ESLint import boundaries** (frontend) encode "feature → shared/contracts only; no feature→feature; no platform→feature." This is the mechanical guarantee that "nothing belongs to two contexts."

---

## 6. Migration Difficulty & Sequencing (folder work only)

| Move | Difficulty | Risk | Notes |
|------|-----------|------|-------|
| Add `packages/{contracts,config}` + Turborepo | M | Low | Tooling only; unblocks type-sync |
| Frontend `components/*`+`lib/*` → `features/*`+`shared/*` | M | Low | Mechanical; update imports; behavior unchanged |
| Route groups `(public)/(student)/(dashboard)` → context groups | M | Low–Med | Redirects per Refactor 02; URLs mostly stable |
| Backend `Domains/*` → `Contexts/*` + `Platform/*` | L | Med | Namespace moves; update providers/autoload; no schema change |
| **Crm → Organization + Crm split** | L | Med | Move models/services by concern; tables unchanged; add events between them |
| Delete dead (`(dashboard)`, `public-header`, public `settings/theme`) | S | Low | Pure removal |
| Add Deptrac + ESLint boundary rules | S | Low | Guardrails; do last so violations surface |

**Recommended order:** packages/tooling → frontend feature-module move → route-group re-own (+redirects) → backend context regroup → Crm split → boundary linters. Each step is independently shippable and reversible; no business logic or schema is touched.

---

## 7. What This Achieves

- **One owner per route/page/feature** (App Router groups + `features/*` + backend `Contexts/*` are 1:1 with the 8 contexts).
- **Coupling reduced:** Catalog demoted to read model; Analytics/Notifications behind event DTOs; cross-context via `Contracts` only, enforced by linters.
- **Complexity reduced:** `(public)` dumping ground dissolved; 3 dead/misplaced modules removed; 10 flat domains → 8 contexts + 3 clearly-labeled platform modules.
- **Multi-tenancy ready:** Organization is now a first-class boundary, making seat/tenant scoping (audit 09) a context concern rather than scattered `where` clauses.
- **Frameworks preserved:** still Laravel DDD, still Next.js App Router — this is re-composition, not a rewrite; no schema migration required.
