# HElbaron LMS — Software Architecture Review (04)

**Repository:** local working copy (`apps/api` Laravel 12, `apps/web` Next.js 15).
**Scope:** Software architecture & engineering quality ONLY. No UX/UI/product/business-logic review.
**Assumes:** Reviews 01–03 exist; not repeated.
**Method:** Structural inspection + quantitative sweeps: per-domain folder taxonomy, `bootstrap/providers.php`, cross-domain namespace-import counts, file LOC distribution, Contracts/interface counts, event/listener/job counts, read-cache usage, OpenAPI presence, frontend `lib/` + API client, monorepo manifests.
**Benchmark bar:** Vercel/Turborepo, Linear, Notion, Supabase, Cal.com, Payload, Medusa, Laravel, Next.js Enterprise.

---

## Executive Summary

HElbaron's backend is a **well-executed modular monolith with real DDD discipline**. All 10 domains share an identical, predictable folder taxonomy (Actions, Contracts, Database, Enums, Events, Exceptions, Filament, Http, Jobs, Listeners, Models, Policies, Providers, Services, routes, openapi). Files are **small and single-responsibility** — the largest PHP file in the entire codebase is 152 LOC (`StripeGateway`), and average file size is ~31 LOC. There are **no god classes, no god services, and no god components**. The domain dependency graph is **acyclic**, with `Identity` as a dependency-free kernel. External integrations use a clean **ports-and-adapters (hexagonal)** pattern — payment gateway, video playback, PDF generation, meeting/calendar/reminder providers, and notification channels are all defined as `Contracts` with `Manager` selectors and Fake implementations for testing. This is above the bar for most commercial LMS backends.

The gaps are in **integration architecture and tooling maturity**, not in domain design:

1. **It is not actually a monorepo.** There is no root workspace manager (no pnpm workspaces, Turborepo, or Nx), no shared packages, and no unified task graph. `apps/api` and `apps/web` are two independent projects that happen to share a folder. Cal.com/Vercel-class repos use Turborepo + shared packages for exactly this shape.
2. **The API contract is not enforced end-to-end.** Ten per-domain **OpenAPI specs exist but are unused for codegen** — the frontend hand-maintains `src/types/api.ts`. This guarantees eventual drift between server Resources and client types.
3. **Two contexts leak across boundaries.** `Analytics` and `Notifications` each import concrete classes from **6 other domains** instead of consuming published events/DTOs — the highest coupling in the system and a bounded-context smell for otherwise event-driven consumers (the platform has 46 domain events).
4. **Cross-context calls depend on implementations, not interfaces.** Within a domain, layering is clean; *between* domains there is no published "context API" (facade/interface), so DIP isn't enforced at the boundary.
5. **Scalability read-path is unoptimized.** Despite Redis being wired for cache/queue/session, **only one file in all domains uses read caching** (`Cache::remember`). Hot reads (catalog, course listings) hit the DB directly.

Minor debt: an **unused `Shared\Contracts\Repository`** abstraction (0 implementations), **auth token stored in `localStorage`** (XSS exposure vs httpOnly/Sanctum-stateful), and a single 893-LOC i18n dictionary file that will not scale.

**Bottom line:** the *domain* architecture is genuinely strong (8/10); the *integration + tooling* architecture is mid-maturity (4–6/10). Nothing here requires re-architecting — it requires adding a workspace layer, a generated contract, published context events, and a caching seam.

---

## Overall Architecture Score

**7.2 / 10** — "excellent modular monolith, mid-maturity integration & tooling."

| Category | Score | One-line justification |
|----------|-------|------------------------|
| Backend architecture | 8.5 | Consistent DDD, tiny files, acyclic graph, hexagonal ports |
| Frontend architecture | 7.0 | Clean per-domain `lib/`, typed client; localStorage token |
| API architecture | 6.5 | Standard envelope + OpenAPI, but no enforced contract/codegen |
| Monorepo | 4.0 | No workspace manager, no shared packages, no task graph |
| Domain architecture / boundaries | 7.5 | Strong contexts; 2 leaky consumers, no context facades |
| State management | 7.5 | TanStack Query + typed hooks per domain |
| Authentication | 6.5 | Sanctum + MFA + OTP; token in localStorage |
| Authorization | 7.0 | Policies + Spatie; coarse roles (see 01) |
| Infrastructure | 6.5 | Redis/Horizon/S3/Mux wired; caching underused |
| Performance | 5.5 | No read-cache strategy; N+1 risk unverified |
| Maintainability | 7.5 | Predictable structure, small files, tests present |
| Scalability | 6.5 | Event-driven core, but read path + monolith seams |

---

## Frontend Architecture

**Score: 7.0/10.**

- **Strengths:** `src/lib/` mirrors backend domains (`auth, catalog, commerce, crm, learning, org, student, analytics`), each with `api.ts` + `hooks.ts` — clean feature-sliced organization. Single typed `apiFetch<T>` wrapper (`lib/api/client.ts`) centralizes envelope unwrap, bearer injection, and `ApiRequestError` with `correlationId`. Data fetching standardized on TanStack Query. No god components (largest page ~190 LOC; largest file is i18n data).
- **Issues:**

| # | Sev | Issue | Evidence | Recommendation |
|---|-----|-------|----------|----------------|
| FE-1 | High | Client types hand-maintained; no OpenAPI codegen | `types/api.ts` + 10 unused specs; no codegen dep in `web/package.json` | Generate types/client from OpenAPI (openapi-typescript/orval) |
| FE-2 | Med | Auth token in `localStorage` (`helbaron.token`) | `lib/api/client.ts` | Move to httpOnly cookie / Sanctum stateful SPA |
| FE-3 | Med | i18n in one 893-LOC `dictionaries.ts` | file LOC | Split per-domain namespaces, lazy-load |
| FE-4 | Low | No shared error/DTO package between apps | monorepo shape | Introduce `packages/contracts` (ties to MR-1/API-1) |

---

## Backend Architecture

**Score: 8.5/10 — the strongest layer.**

- **Strengths:** Uniform per-domain taxonomy across all 10 domains; layered flow Http → Actions/Services → Models with domain Events/Listeners for side effects (46 events, 15 listeners, 2 queued jobs). Ports & adapters for every external concern (`Payments/Gateways` + `GatewayManager`, `Playback/Providers` + `PlaybackTokenManager`, `Pdf/Providers`, Live `Meeting/Calendar/Reminder` managers, Notifications `Channels/Providers`) with Fake implementations. `Identity` depends on nothing (correct kernel). Providers registered in a documented, ordered `bootstrap/providers.php`.
- **Issues:**

| # | Sev | Issue | Evidence | Recommendation |
|---|-----|-------|----------|----------------|
| BE-1 | Med | Unused `Shared\Contracts\Repository` | 0 implementations found | Remove (YAGNI) or adopt where a persistence seam is genuinely needed |
| BE-2 | Med | Interface coverage uneven | Contracts: Live 3, Notifications 4, but Authoring/Crm/Identity 0 | Fine where internal; add context-facade interfaces for cross-domain calls (DA-1) |
| BE-3 | Low | Actions vs Services responsibility overlap in places | both present in most domains | Document the rule (Action = 1 use case orchestration; Service = reusable domain logic) |

---

## API Architecture

**Score: 6.5/10.**

- **Strengths:** REST, version-prefixed (`/api/v1`), per-domain route files, standard success/error envelope (`Shared/Support/ApiResponse`, `ApiResponse.php` 110 LOC), correlation IDs, `AssignCorrelationId` middleware, and **10 OpenAPI specs**.
- **Issues:**

| # | Sev | Issue | Evidence | Recommendation |
|---|-----|-------|----------|----------------|
| API-1 | High | OpenAPI specs not enforced/generated | 10 specs, no codegen, hand-written client types | Make OpenAPI the source of truth: generate TS client + run contract tests in CI |
| API-2 | Med | No documented pagination/filtering/sorting standard surfaced as a shared contract | list endpoints per domain | Define a shared list-query contract (page/limit/sort/filter) + `PaginatedCollection` already exists — standardize usage |
| API-3 | Low | Versioning strategy single (`v1`) with no deprecation policy | routes | Document version/deprecation policy before v2 |

---

## Monorepo Architecture

**Score: 4.0/10 — biggest tooling gap.**

- **Evidence:** root has **no** `pnpm-workspace.yaml`, `turbo.json`, `nx.json`, or root `package.json`. `apps/api` + `apps/web` are independent.
- **Consequences:** no shared packages (types, config, ESLint/TS presets), no cached/parallel task graph, duplicated tooling config, and the FE↔BE contract can't be shared as code.
- **Recommendation (MR-1):** Adopt Turborepo (or pnpm workspaces + Turbo). Add `packages/`: `contracts` (generated API types + shared DTOs), `config` (tsconfig/eslint), optionally `ui` later. Wire `turbo run build/lint/test`.

---

## Domain Architecture & Module Boundaries

**Score: 7.5/10.**

Measured dependency directions (concrete namespace imports):

```
Identity      → (none)                 [kernel — correct]
Catalog       → Identity
Authoring     → Catalog, Identity
Learning      → Authoring, Catalog, Identity
Commerce      → Catalog, Identity, Learning
Certification → Catalog, Identity, Learning
Live          → Catalog, Identity
Crm           → Identity
Analytics     → Certification, Commerce, Crm, Identity, Learning, Live   [6 — leaky]
Notifications → Certification, Commerce, Crm, Identity, Learning, Live    [6 — leaky]
```

- **Strengths:** Acyclic (a DAG); `Identity` is a clean shared kernel; reference domains (`Catalog`) depended on sanely.
- **Issues:**

| # | Sev | Issue | Evidence | Recommendation |
|---|-----|-------|----------|----------------|
| DA-1 | High | `Analytics` & `Notifications` import 6 concrete domains each | coupling sweep | Consume via published domain **events/DTOs** only; remove concrete-model imports |
| DA-2 | Med | Cross-context calls bind to concrete classes, not interfaces | no context facades | Publish a per-context public API (interface/facade in `Contracts`) that other contexts depend on |
| DA-3 | Low | No enforcement preventing new cross-context coupling | manual | Add a dependency-direction lint (Deptrac ruleset) in CI |

---

## State Management

**Score: 7.5/10.**

- TanStack Query for server state (per-domain `hooks.ts`), React context for auth (`auth-context.tsx`) and i18n. Optimistic cache for user (from 02 work). Clear separation of server vs UI state.
- Issues: (SM-1, Low) query keys/staleTime conventions not centralized → risk of inconsistent caching/invalidations. Recommendation: a `queryKeys` factory + shared defaults in `query-client.ts`.

---

## Authentication Architecture

**Score: 6.5/10.**

- Sanctum bearer + MFA (`MfaService` 146 LOC) + email/phone OTP (`OtpService` 109 LOC), device tracking, admin MFA middleware (`EnforceAdminMfa`). Solid feature set.
- Issues: (AUTH-1, Med–High) token in `localStorage` → XSS-exfiltratable. Recommendation: Sanctum **stateful SPA cookies** (httpOnly, SameSite) or httpOnly refresh-token pattern; keep bearer only for non-browser clients.

---

## Authorization Architecture

**Score: 7.0/10.**

- Laravel Policies per resource + Spatie roles/permissions; `canAccessPanel()` gate for Filament. Well-structured.
- Issues: (AUTHZ-1, Med) coarse role model (only `super_admin/admin/instructor/student`) forces multiple product roles onto `admin` — this is the same root cause flagged in 01 but has an **architecture** dimension: policies can't express org/support/finance scopes. Recommendation: add roles + policy abilities; consider policy-level team/tenant scoping.

---

## Infrastructure

**Score: 6.5/10.**

- Redis (cache/queue/session) + Horizon, S3 + CloudFront (signed URLs via `CloudFrontUrlSigner`), Mux playback, Docker dev + prod compose, health/readiness endpoints, JSON logging + correlation processor. Good production surface.
- Issues: (INF-1, Med) DI is Laravel-container-idiomatic but manager bindings are spread across providers — verify all Contracts are bound once and swappable via config. (INF-2, Low) config is env-driven and validated (`ValidateEnvironment` command) — good; document required env matrix per environment.

---

## Performance

**Score: 5.5/10.**

| # | Sev | Issue | Evidence | Recommendation |
|---|-----|-------|----------|----------------|
| PERF-1 | High | Almost no read caching | 1 file uses `Cache::remember` across all domains | Add a caching seam for hot reads (catalog, course detail, KPIs) with tag-based invalidation on domain events |
| PERF-2 | Med | N+1 risk unverified on list resources | eager-load patterns not audited here | Add query-count assertions in feature tests; enforce eager loading in list services |
| PERF-3 | Low | No CDN/edge caching policy for public catalog JSON | — | Cache-Control + stale-while-revalidate on public GETs |

---

## Maintainability

**Score: 7.5/10.** Predictable structure, tiny files, tests present (Pest feature+unit; Vitest FE), Pint/PHPStan configs, per-domain READMEs. Main detractors: no shared contract package (drift), no dependency-direction enforcement, i18n monofile.

## Scalability

**Score: 6.5/10.** Event-driven core (46 events) and queue/Horizon give good async headroom; the modular monolith can be split along the already-clean domain seams later. Blockers to scale: read-cache absence (PERF-1), cross-context coupling in Analytics/Notifications (DA-1) which would complicate extraction, and single-node assumptions in dev config.

---

## Technical Debt (register)

| ID | Sev | Debt | Evidence |
|----|-----|------|----------|
| TD-1 | High | No monorepo tooling / shared packages | no root workspace manifest |
| TD-2 | High | Unenforced API contract (specs unused) | 10 OpenAPI, no codegen |
| TD-3 | High | Leaky Analytics/Notifications boundaries | 6-domain coupling |
| TD-4 | Med | Unused Repository abstraction | 0 implementations |
| TD-5 | Med | localStorage auth token | client.ts |
| TD-6 | Med | No read caching seam | 1 usage |
| TD-7 | Low | i18n 893-LOC monofile | dictionaries.ts |
| TD-8 | Low | No dependency-direction lint | CI |

## Dead Code

| Item | Evidence | Action |
|------|----------|--------|
| `Shared\Contracts\Repository` | 0 implementers | Remove or adopt |
| `components/catalog/public-header.tsx` (FE) | unused (per 02/03) | Remove |
| Committed runtime artifacts (certificates PDFs, compiled views) | already gitignored this session | Ensure removed from tree |
| `(dashboard)` route group (FE) | dead layout (per 01/02) | Remove/merge |

## Code Duplication

| Area | Evidence | Recommendation |
|------|----------|----------------|
| FE/BE type definitions | `types/api.ts` duplicates server Resources | Generate from OpenAPI (API-1) |
| Per-domain `api.ts` boilerplate | repeated CRUD wrappers | Generate from OpenAPI or a small factory |
| Manager/Provider selection boilerplate | repeated across integration domains | Extract a shared `Shared/Support/ProviderManager` base (partially exists) |
| Tooling config (tsconfig/eslint) not shared | two apps | `packages/config` (MR-1) |

Note: duplication is **low** overall — files are small and DRY within domains; the duplication that exists is cross-app (contracts/config), solved by the monorepo + codegen fixes.

---

## High Priority Fixes (ordered)

- **P0-1 (TD-1/MR-1):** Introduce Turborepo + pnpm workspaces + `packages/contracts` & `packages/config`.
- **P0-2 (TD-2/API-1/FE-1):** Make OpenAPI the source of truth; generate the TS client into `packages/contracts`; add CI contract test.
- **P0-3 (TD-3/DA-1):** Decouple `Analytics` & `Notifications` — consume published events/DTOs, delete concrete cross-domain imports.
- **P1-1 (TD-6/PERF-1):** Add a read-cache seam with event-driven invalidation for catalog/course/KPIs.
- **P1-2 (DA-2/DA-3):** Publish per-context facade interfaces; add Deptrac dependency-direction rules in CI.
- **P1-3 (TD-5/AUTH-1):** Move auth to httpOnly cookie / Sanctum stateful.
- **P2-1 (TD-4):** Remove unused Repository contract (or adopt intentionally).
- **P2-2 (TD-7/FE-3):** Split i18n dictionaries per domain with lazy loading.

---

## AI Implementation Prompts

**AIP-1 — Turborepo + shared packages (TD-1)**
> At the repo root, add pnpm workspaces + Turborepo. Create root `package.json` with `workspaces: ["apps/*","packages/*"]`, a `turbo.json` with `build/lint/test/typecheck` pipelines, and `packages/config` holding shared `tsconfig.base.json` and ESLint config consumed by `apps/web`. Do not change app runtime code; only wire tooling. Verify `turbo run typecheck` runs the web app.

**AIP-2 — OpenAPI-driven contract package (TD-2/API-1)**
> Create `packages/contracts`. Add a script that concatenates/validates the 10 domain OpenAPI YAMLs into one spec and runs `openapi-typescript` to emit typed models + a thin fetch client. Replace hand-written `apps/web/src/types/api.ts` imports with the generated types. Add a CI job that fails if generated types are out of date. Do not alter backend endpoints.

**AIP-3 — Decouple Analytics & Notifications (TD-3/DA-1)**
> In `apps/api`, refactor `App\Domains\Analytics` and `App\Domains\Notifications` so they no longer `use App\Domains\{Commerce,Crm,Learning,Live,Certification}\...` concrete classes. Instead, define DTO payloads on the existing domain Events these consumers subscribe to, and have subscribers read only the event DTO. Move any shared identifiers to `App\Shared`. Confirm via grep that Analytics/Notifications import zero other domain namespaces except `Identity` (if unavoidable) and `Shared`.

**AIP-4 — Read-cache seam with event invalidation (PERF-1)**
> Add a `App\Shared\Support\CacheedRead` helper (tag-based `Cache::tags(...)->remember(...)`). Apply it to hot read services: catalog course listing, course detail, and analytics KPI reads. Wire cache invalidation to the relevant domain events (e.g., `CoursePublished`, `CourseUnpublished`) via listeners that flush the matching tags. Add tests asserting a cache hit avoids a second DB query.

**AIP-5 — Context facades + dependency lint (DA-2/DA-3)**
> For each domain that is called by others (Catalog, Learning, Identity), define a public facade interface in its `Contracts` (e.g., `CatalogContext`) exposing only the methods other contexts need, bind it in the provider, and refactor cross-domain callers to depend on the interface. Add Deptrac with a ruleset encoding the allowed dependency directions from this review; add `deptrac` to CI.

**AIP-6 — Auth cookie migration (AUTH-1)**
> Migrate the SPA auth from `localStorage` bearer to Sanctum stateful cookies: enable `EnsureFrontendRequestsAreStateful`, configure `SANCTUM_STATEFUL_DOMAINS`/CORS credentials, update `apps/web/src/lib/api/client.ts` to send `credentials: "include"` and drop `helbaron.token`. Keep bearer support only for non-browser clients. Verify login/refresh/logout flows.

**AIP-7 — Remove dead abstractions (TD-4, dead code)**
> Delete `App\Shared\Contracts\Repository` (0 implementers) unless a persistence seam is being adopted. Delete unused `apps/web/src/components/catalog/public-header.tsx` and the empty `(dashboard)` route group. Run typecheck/tests to confirm no references remain.

**AIP-8 — i18n split (FE-3)**
> Split `apps/web/src/lib/i18n/dictionaries.ts` into per-domain dictionary files (`dictionaries/{common,catalog,commerce,...}.ts`) and lazy-load the namespace a route needs. Keep the `useI18n().t` API unchanged.

---

## Acceptance Criteria

- AC1 (TD-1): Root `turbo run build/lint/test/typecheck` executes both apps; `packages/config` is consumed by `apps/web`.
- AC2 (TD-2): `apps/web` imports API types only from `packages/contracts` (generated); CI fails on spec/type drift; `types/api.ts` hand-written models removed.
- AC3 (TD-3): A namespace-import grep shows `Analytics` and `Notifications` reference no concrete sibling-domain classes (only Shared/Identity + event DTOs).
- AC4 (DA-3): Deptrac runs in CI and enforces the documented dependency directions; a violating import fails the build.
- AC5 (PERF-1): Catalog listing/detail and KPI reads are cache-backed with event-driven invalidation; tests prove cache hits reduce DB queries.
- AC6 (AUTH-1): The web app authenticates via httpOnly cookies; no auth token is present in `localStorage`.
- AC7 (BE-1/TD-4): No unused framework abstractions remain (`Repository` contract removed or implemented).
- AC8 (dead code): `public-header.tsx`, the `(dashboard)` group, and committed runtime artifacts are absent from the tree.
- AC9 (DA-2): Cross-domain calls go through published context interfaces, not concrete classes (spot-check Learning→Catalog, Commerce→Learning).
- AC10 (traceability): Every issue (FE/BE/API/DA/PERF/AUTH/TD IDs) maps to a fix and a criterion here.

---

### Appendix — Evidence index
- Structure: `app/Domains/*/` (16-folder taxonomy, verified on Catalog); `bootstrap/providers.php`.
- Sizes: 828 PHP files / ~25.7k LOC (avg ~31 LOC); largest `StripeGateway.php` 152, `MfaService.php` 146, `CheckoutAction.php` 120. Largest FE file `dictionaries.ts` 893 (data).
- Coupling: per-domain other-domain imports (Analytics 6, Notifications 6, others ≤3, Identity 0).
- Interfaces: Contracts counts (Notifications 4, Live 3, Analytics/Catalog 2, others 0–1); `Shared\Contracts\Repository` implementers = 0.
- Events/async: 46 events, 15 listeners, 2 jobs; read-cache usages = 1.
- Contract: 10 OpenAPI specs; no FE codegen dependency.
- Monorepo: no root workspace/turbo/pnpm/nx manifest.
- FE: `lib/{domain}/{api,hooks}`, `lib/api/client.ts` typed envelope wrapper, token in `localStorage`.
