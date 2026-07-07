# Administration, Filament & Enterprise Integration Blueprint (Phase 5 — Final)

> Architecture phase only. No code, no file moves, no namespace changes, no API changes, no database changes.
> Capstone of the redesign series: `01_CATALOG` · `02_CRM_ORGANIZATION` · `03_INSTRUCTOR_AUTHORING` · `04_LEARNING` · **`05_ADMINISTRATION_ENTERPRISE`**.

---

# Executive Summary

This is the enterprise-architecture capstone. It defines the **Administration** bounded context, the role of **Filament** as an administration UI (never a business-logic owner), and the **integration architecture** binding all bounded contexts and platform capabilities into one coherent, scalable, multi-tenant platform.

The platform is a **Laravel 12 modular monolith** organized as bounded contexts, fronted by a **custom Next.js** experience and a **Filament** operator console, on **PostgreSQL + Redis/Horizon + S3/CloudFront + Mux**, REST-only. Today the code is organized as `App\Domains\*` (Catalog, Authoring, Certification, Crm, Live), `App\Contexts\*` (Learning, Commerce, Analytics), and `App\Platform\*` (Shared, Identity, Notifications), with a single Filament panel at `/admin` whose resource discovery is a data map (no conditional branches). Media, AI, Search, and Integration exist today as *embedded capabilities* (Mux/CloudFront playback, provider managers, DB search); this blueprint formalizes them as **Platform capabilities** with explicit ports.

**Administration** is introduced as a distinct context that owns *operating the platform* — settings, feature flags, audit center, tenant provisioning, system health, jobs/queues/scheduler, secrets, providers, backups — and **nothing about learning content, commerce, or CRM**. It is the operator's cockpit, not a super-domain: it configures and observes the others through the same ports and events everyone else uses.

The governing principles, enforced throughout:

1. **One writer per fact.** Every datum has exactly one owning context; others hold references.
2. **Contexts integrate by events + ports, never by reaching into each other's tables.**
3. **Filament is UI. Business logic lives in domain Actions/Services.**
4. **Administration operates the platform; it does not own domain data.**
5. **Multi-tenant isolation, capability-based entitlement, and auditability are cross-cutting, not per-context afterthoughts.**

---

# Administration Boundary

Administration is a **new bounded context** (`App\Platform\Administration\*` in the target layout — not yet built; this is its blueprint). It sits in the Platform layer beside Identity, Notifications, and Shared, because it is infrastructure-facing, not domain-facing.

## Administration owns ONLY

Platform Administration · System Settings · Feature Flags · Global Configuration · Audit Center · Permissions (registry) · Role Templates · Impersonation · System Health · Maintenance Mode · Background Jobs · Queues · Scheduler · Logs · Monitoring · Alerts · Platform Integrations · Secrets · API Keys · Environment Profiles · License Management · Platform Branding · Multi-brand Management · Platform Themes · Tenant Provisioning · Tenant Management · Usage Limits · Billing Configuration · Storage Configuration · Media Providers · AI Providers · Email Providers · SMS Providers · SSO Providers · Backups · Restore · Platform Migration.

## Administration NEVER owns

Courses/Lessons (Catalog/Authoring) · Enrollments/Progress/Grades (Learning) · Orders/Payments (Commerce) · Leads/Deals (CRM) · Certificates (Certification) · User identities & auth (Identity) · Notification delivery (Notifications) · the *content* of any domain. It **configures** these; it does not **contain** them.

## The critical distinction (Administration vs Identity vs Organization)

| Concern | Owner | Why |
|--------|-------|-----|
| Who a user is; authentication; user roles/permissions **assignment** | **Identity** | identity is the security kernel |
| A **tenant** (customer org) as a billable, isolatable unit; its lifecycle & limits | **Administration** (provisioning) + **Organization** (business relationship) | Admin provisions the tenant *envelope*; Organization owns the *relationship* |
| Permission **catalog / role templates** (what permissions exist) | **Administration** (registry) | the definition of the RBAC vocabulary is a platform config |
| Permission **grants** (this user has this role) | **Identity** | enforcement is Identity's |
| Feature availability per tenant (**capability**) | **Administration** (config) → enforced by each context | operational entitlement is platform config |

Administration owns the **registry and configuration**; Identity owns **enforcement of user auth**; each domain owns **its own data**. Administration never bypasses a domain to mutate its data — it flips flags, sets limits, and reads health.

## Administration modules

| Module | Owns | Talks to |
|--------|------|----------|
| **Settings & Config** | System settings, global config, environment profiles | all contexts read via `ConfigPort` |
| **Feature Flags & Capabilities** | flag definitions, per-tenant capability grants | every context checks `CapabilityPort` |
| **Audit Center** | aggregated audit log, retention, search, export | consumes `*Audited` events from all contexts |
| **Tenant Lifecycle** | provisioning, suspension, limits, usage metering | Organization (relationship), Commerce (billing config) |
| **Providers & Integrations** | Media/AI/Email/SMS/SSO provider registration, secrets, API keys | Platform capability ports |
| **Ops Console** | jobs, queues, scheduler, health, maintenance mode, alerts | Horizon, scheduler, health endpoints |
| **Branding & White-label** | multi-brand, themes, platform branding | Filament panels, Next.js theming |
| **Backups & DR** | backup schedules, restore, platform migration | storage, DB |
| **Licensing** | license management, entitlement ceilings | Capabilities |

Administration is **thin on data, rich on configuration and observation**. Its own tables are limited to: settings, feature_flags, capability_grants, audit_index, tenants, tenant_limits, provider_configs (secrets encrypted/externalized), api_keys, license, brands, backup_runs.

---

# Filament Architecture

**Filament is the operator UI only. It NEVER owns business logic.** Every Filament resource reads existing models and defers every mutation to a domain **Action/Service**. This is already the discipline in the live `AdminPanelProvider` (resources auto-discovered from each context's `Filament/Resources`, business logic in domain Actions). This section formalizes and extends it.

## Layered rule

```
Filament Resource/Page/Action  ──calls──▶  Domain Action/Service  ──▶  Domain Aggregate
        (presentation)                       (business logic)          (invariants)
        NEVER writes to a model directly for a business operation
```

A Filament `Action` may `->action(fn () => app(SomeDomainAction::class)->execute(...))` but must not encode business rules, cross-aggregate writes, or invariants inline. Tables/forms are projections and input adapters; policies delegate to domain policies.

## Components

| Element | Design |
|---------|--------|
| **Panels** | multi-panel: `admin` (platform operators, exists), `instructor` (authoring/teaching), `org` (tenant admins) — each a `PanelProvider`, each with its own guard, brand, and discovery map |
| **Resources** | one per aggregate root, discovered per context (`Contexts/*/Filament/Resources`, `Domains/*/...`, `Platform/*/...`) via the existing **data-map** (`RESOURCE_PATHS`) — no conditional branches |
| **Pages** | custom pages for dashboards, ops console, audit center, settings — read-only or delegating to Actions |
| **Widgets** | KPI/stat widgets read **read models** (never live cross-context joins); e.g., `PlatformOverview` reads projections |
| **Navigation** | grouped by context (`navigationGroups([...])`), order fixed in the panel; labels are presentation-only |
| **Clusters** | group related resources (e.g., "Providers", "Tenants", "Ops") within a panel |
| **Forms** | input adapters → validated → passed to Actions; no business branching in form state |
| **Tables** | query builders over owning-context models, scoped by tenant + policy |
| **Actions** | thin wrappers over domain Actions; bulk actions batch domain Actions |
| **Policies** | Filament authorization delegates to the same domain policies used by the API |
| **Authorization** | `canAccessPanel()` (active + role) + `EnforceAdminMfa` middleware (exists); per-resource via policies + `filament-shield` |
| **Discovery** | data-map driven; adding a context = one map line, never a branch (per Phase-5E decision) |
| **Configuration** | panel config in provider; brand/theme from Administration branding config |

## Plugin strategy

Filament plugins are **UI extensions only** (e.g., shield for authorization, log viewer, media picker). A plugin may never introduce a business-logic sink. Third-party/marketplace plugins are sandboxed to presentation + calling published context APIs/Actions.

## Multi-panel strategy

| Panel | Guard | Audience | Discovery scope |
|-------|-------|----------|-----------------|
| `admin` | web (super_admin/admin) + MFA | platform operators (HElbaron) | all contexts' resources |
| `instructor` | web (instructor role) | teachers | Instructor + Authoring resources, tenant-scoped |
| `org` | web (org admin) | tenant administrators | Organization + tenant-scoped Learning/Commerce reads |

Each panel is an independent `PanelProvider` with its own `RESOURCE_PATHS` map, brand, and policy set. Tenant scoping is applied globally per panel (global scopes + policy), so a panel physically cannot render another tenant's rows.

## White-label support

Branding (name, logo, colors, theme, domain) is **Administration-owned config** consumed by Filament panels and the Next.js app. A brand resolves from the request host/tenant; the panel's `brandName`/`brandLogo`/theme are set from the resolved brand. Multiple brands share one codebase; no fork. Emails/certificates/notifications also resolve brand from the same source.

---

# Platform Integration Blueprint

Sixteen participants: ten **bounded contexts** (Catalog, Authoring, Learning, Instructor, Commerce, CRM, Organization, Certification, Analytics, Administration) plus **Identity** and **Notifications**, and four **Platform capabilities** (Media, AI, Search, Integration). Instructor, Organization, Media, AI, Search, and Integration are **target** separations formalized in the redesigns; today Instructor lives inside Catalog/Identity, Organization inside CRM, and Media/AI/Search/Integration are embedded capabilities.

Legend: **Owned** = single source of truth · **Consumed** = read via port/ref · **Ports** = outbound interfaces this context depends on · **Adapters** = inbound implementations it provides.

### Catalog
- **Owned:** Course, Category, taxonomy, course lifecycle/publish state, SEO fields.
- **Consumed:** publish-readiness (from Authoring), pricing ref (Commerce), instructor ref (Instructor).
- **Published events:** CoursePublished, CourseVersionPublished, CourseArchived, CategoryChanged.
- **Consumed events:** ContentApproved (Authoring), CoursePricingChanged (Commerce), CoursePublishGuard result.
- **Public APIs:** catalog browse/search, course detail. **Internal APIs:** `CoursePublishGuard` inbound port.
- **Ports:** PublishReadinessPort (Authoring). **Adapters:** CurriculumReadPort impl (serves Learning).
- **Read models:** CatalogSearchIndex, CourseCard. **Caching:** version-keyed course/curriculum.
- **Forbidden deps:** Learning, Commerce internals, Media bytes.

### Authoring
- **Owned:** curriculum/sections/lessons, lesson **versions**, assessment **definitions**, question banks, content workflows, AI generation, asset **refs**.
- **Consumed:** teaching authority (Instructor/Catalog), media asset lifecycle (Media Platform), publish decision (Catalog).
- **Published events:** LessonPublished, ContentApproved, AssessmentPublished, ContentVersionPublished.
- **Consumed events:** MediaAssetReady/Failed (Media), TeachingAuthorityChanged (Instructor).
- **Public APIs:** authoring admin API. **Internal APIs:** `TeachingAuthority` consumer, `AssessmentDefinitionPort` provider.
- **Ports:** MediaPort, AIProvider, TeachingAuthority. **Adapters:** implements Catalog's CoursePublishGuard, AssessmentDefinitionPort (serves Learning).
- **Read models:** ContentValidationView. **Caching:** version-keyed definitions.
- **Forbidden deps:** Learning attempts, Commerce, Media bytes (refs only).

### Learning
- **Owned:** enrollments, sessions, progress, attempts, submissions, grades, paths execution, mastery, gamification, engagement, attendance, LRS, offline/sync, certificate **references**.
- **Consumed:** curriculum (Catalog/Authoring), assessment definitions (Authoring), playback (Media), entitlement (Commerce/Catalog), certificate issuance (Certification).
- **Published events:** EnrollmentCreated, LessonCompleted, CourseCompleted, AssessmentPassed, BadgeUnlocked, StreakUpdated, OfflineSyncCompleted, … (full list in Event Map).
- **Consumed events:** EnrollmentGranted (Commerce), CoursePublished/CourseVersionPublished (Catalog), CertificateIssued (Certification), LiveSessionAttended (Live).
- **Public APIs:** learner API (enroll, learn, progress, attempts, dashboard, sync). **Internal APIs:** none exposed inbound (pure consumer/emitter).
- **Ports:** CurriculumReadPort, AssessmentDefinitionPort, PlaybackPort, EntitlementPort, CertificationPort, AdaptivePolicyPort, AIProvider, LrsExportPort.
- **Read models:** StudentDashboard, Gradebook, Leaderboard, SkillMatrix, LearningAnalyticsProjection. **Caching:** version-keyed curriculum + rebuildable projections.
- **Forbidden deps:** Authoring/Catalog **models** for logic, Media bytes, Commerce internals.

### Instructor (target context)
- **Owned:** instructor profile (ref to Identity user), teaching assignments, schedules, teaching revenue refs, reviews, availability, teams, brand.
- **Consumed:** identity (Identity), course refs (Catalog), earnings (Commerce), teaching analytics (Learning/Analytics).
- **Published events:** TeachingAuthorityChanged, InstructorAssigned, InstructorAvailabilityChanged.
- **Public APIs:** instructor panel/API. **Ports:** provides `TeachingAuthority` (consumed by Authoring/Catalog).
- **Forbidden deps:** lesson content (owns zero lesson data), Learning attempts.

### Commerce
- **Owned:** products, pricing, coupons, cart, orders, payments, contracts, fulfillment, refunds, billing.
- **Consumed:** course refs (Catalog), user (Identity), tenant billing config (Administration), payment gateway (Integration/Payment provider).
- **Published events:** OrderPaid, EnrollmentGranted, ContractAccepted, RefundIssued, CoursePricingChanged.
- **Consumed events:** CourseArchived (Catalog), tenant limit changes (Administration).
- **Public APIs:** cart/checkout/orders. **Ports:** PaymentGateway, EntitlementPort provider. **Adapters:** grants entitlement → Learning.
- **Forbidden deps:** Learning progress, content bytes.

### CRM
- **Owned:** leads, contacts, deals, sales timeline, notes/tasks/tags, consulting delivery.
- **Consumed:** organization ref (Organization), user (Identity), commerce order refs.
- **Published events:** LeadCreated, DealWon/Lost, ConsultingScheduled.
- **Forbidden deps:** Learning, Authoring internals, tenant provisioning.

### Organization (target context, split from CRM)
- **Owned:** tenant business entity, org membership, org roles, org lifecycle, capabilities (business-side), org audit trail, consulting **requests**.
- **Consumed:** tenant envelope + limits (Administration), users (Identity), learning rollups (Analytics).
- **Published events:** OrganizationCreated, MemberInvited, OrgCapabilityChanged, ConsultingRequested.
- **Forbidden deps:** Learning internals, Commerce internals (refs only).

### Certification
- **Owned:** certificate templates, requirements, issuance, credential lifecycle, verification.
- **Consumed:** course/exam completion (Learning), competency attainment (Learning), branding (Administration).
- **Published events:** CertificateIssued, CredentialExpiring, CredentialRevoked.
- **Consumed events:** CourseCompleted, AssessmentPassed, CompetencyAttained (Learning).
- **Ports:** PdfGenerator. **Adapters:** provides CertificationPort (Learning stores refs).
- **Forbidden deps:** Learning tables, content bytes.

### Analytics
- **Owned:** cross-domain BI, dashboards, reports, exports, metric catalog.
- **Consumed:** **published projections** from every context (esp. Learning's LearningAnalyticsProjection), Commerce, CRM.
- **Published events:** ReportGenerated, ExportCompleted.
- **Consumed events:** all contexts' domain events → metric projections.
- **Ports:** MetricProvider, ExportWriter. **Read models:** every BI projection.
- **Forbidden deps:** writing any domain's data; it is **read-only** across contexts.
- **Target correction:** consumes Learning's **published analytics projection** rather than subscribing to each concrete domain event (removes current coupling where `MetricEventSubscriber` binds to many domains' concrete events).

### Administration
- **Owned:** (see Administration Boundary) settings, flags, capabilities, audit index, tenants, providers, secrets, ops, branding, backups, license.
- **Consumed:** health signals + audit events from all; usage metrics (Analytics).
- **Published events:** FeatureFlagChanged, CapabilityGranted/Revoked, TenantProvisioned/Suspended, MaintenanceModeChanged, ProviderConfigured, BackupCompleted.
- **Consumed events:** `*Audited` from all contexts; queue/job failures.
- **Ports:** ConfigPort, CapabilityPort, SecretsPort (provides to all); consumes health/ops.
- **Forbidden deps:** mutating any domain's business data directly.

### Identity (Platform)
- **Owned:** users, credentials, sessions, devices, MFA, roles/permission **grants**, OTP.
- **Consumed:** role templates/permission registry (Administration), SSO provider config (Administration).
- **Published events:** UserRegistered, UserVerified, RoleAssigned, LoggedIn/Out, ImpersonationStarted/Ended.
- **Ports:** SsoProvider (via Administration config). **Forbidden deps:** domain business data.

### Notifications (Platform)
- **Owned:** notification records, templates, channels, delivery, workflow engine, preferences.
- **Consumed:** trigger events from all contexts; channel provider config (Administration); branding.
- **Published events:** NotificationSent/Failed/Bounced.
- **Consumed events:** LessonCompleted, CourseCompleted, CertificateIssued, BadgeUnlocked, OrderPaid, StreakAtRisk, LiveSessionStartingSoon, …
- **Ports:** MailProvider, SmsProvider, PushProvider (config from Administration).
- **Forbidden deps:** deciding *when* to notify (that's the domains) — it decides *how/where* only.

### Media Platform (capability → formalize)
- **Owned:** media bytes, upload, transcode (Mux), storage (S3), delivery (CloudFront), caption/AD/sign-language tracks, offline licenses.
- **Consumed:** provider config/secrets (Administration).
- **Published events:** MediaAssetUploaded, MediaAssetReady, MediaAssetFailed.
- **Ports:** provides **PlaybackPort** (Learning) + **MediaPort** (Authoring uploads/refs). **Adapters:** Mux/CloudFront/S3.
- **Rule:** contexts own **asset refs**; Media owns bytes. (Today Learning embeds playback providers — target: move behind PlaybackPort.)

### AI Platform (capability → formalize)
- **Owned:** AI provider abstraction, model routing, safety/moderation, prompt/response logging, cost governance.
- **Consumed:** provider config/secrets (Administration).
- **Ports:** provides **AIProvider** (Authoring generation, Learning adaptive/tutoring, Analytics narratives). Human-in-the-loop enforced by consumers.
- **Rule:** AI **suggests**; humans decide; no AI output reaches learners/grades without human approval.

### Search Platform (capability → formalize)
- **Owned:** search indexing/query abstraction (Postgres FTS today; pluggable to OpenSearch/Meilisearch).
- **Ports:** provides **SearchPort** per index owner (Catalog owns catalog index, Learning owns notes/history index). **Rule:** each context owns **its** index; Search provides the engine, not the ownership.

### Integration Platform (capability → formalize)
- **Owned:** outbound webhooks, inbound webhooks, external API connectors, outbox, idempotency store, retry/DLQ.
- **Consumed:** provider config/secrets (Administration).
- **Published events:** WebhookDelivered/Failed. **Ports:** provides **WebhookPort**, **OutboxPort**, external connector adapters.
- **Rule:** all third-party I/O crosses here (payment gateways, calendar, SSO callbacks), giving one place for security, retry, and audit.

---

# Dependency Matrix

Communication is **event-first**; synchronous calls are limited to **ports** (interfaces) and are never direct table access. "ACL" = anti-corruption layer (translates a foreign model into this context's language).

| Context | Allowed (sync via port) | Allowed (async events) | Forbidden | ACL needed |
|---------|-------------------------|------------------------|-----------|------------|
| **Catalog** | Authoring (PublishReadiness), Commerce (pricing ref) | consumes Authoring/Commerce events | Learning, Media bytes | on Authoring readiness |
| **Authoring** | Media (MediaPort), AI (AIProvider), Instructor (TeachingAuthority) | Media, Instructor events | Learning attempts, Commerce | on Media refs |
| **Learning** | Catalog/Authoring (CurriculumReadPort, AssessmentDefinitionPort), Media (PlaybackPort), Commerce (EntitlementPort), Certification (CertificationPort), AI, Adaptive | consumes Commerce/Catalog/Certification/Live | Authoring/Catalog **models**, Media bytes, Commerce internals | **yes** on curriculum + entitlement |
| **Instructor** | Identity, Catalog refs, Commerce earnings ref | consumes Learning/Analytics events | lesson content, Learning attempts | on identity |
| **Commerce** | Catalog (course ref), Payment (Integration), Administration (billing config) | consumes Catalog events; emits grants | Learning progress, content bytes | on payment gateway |
| **CRM** | Organization (org ref), Identity | consumes Commerce/Org events | Learning, Authoring | on org |
| **Organization** | Administration (tenant envelope), Identity, Analytics (rollups) | consumes Admin/Analytics events | Learning/Commerce internals | on tenant |
| **Certification** | Learning (completion signals via events), Media (PDF), Administration (brand) | consumes Learning events | Learning tables, content bytes | on completion evidence |
| **Analytics** | — (read-only projections) | consumes **all** published projections | writing any domain data | on every source projection |
| **Administration** | provides ConfigPort/CapabilityPort/SecretsPort to all | consumes `*Audited`, ops/health | mutating domain business data | — |
| **Identity** | Administration (role templates, SSO config) | emits auth events | domain business data | — |
| **Notifications** | Administration (channel config), branding | consumes trigger events from all | deciding *when* to notify | on each trigger payload |
| **Media Platform** | Administration (provider config) | emits asset lifecycle | domain business logic | — |
| **AI Platform** | Administration (provider config) | — | autonomous action on learner data | — |
| **Search Platform** | index owners (SearchPort) | reindex on owner events | owning any domain's data | — |
| **Integration Platform** | Administration (secrets) | emits delivery events; drives outbox | domain business logic | on every external contract |

**Global forbidden dependencies (Deptrac-enforced):**
- No context depends on another context's `Models\*` for **business logic** (references/ACLs only).
- No context writes another context's tables.
- Platform capabilities (Media/AI/Search/Integration) never depend on domain contexts.
- Analytics is strictly read-only across contexts.
- Notifications never originates business decisions.
- Filament never contains business logic.

**Communication patterns summary:** synchronous **only** through ports (in-process interface calls, swappable); asynchronous **default** via domain events (queued); reads across contexts **only** through read models/projections or a published port; external I/O **only** through Integration Platform.

---

# Event Map

All events are **DTOs** (never Eloquent models cross a boundary). Delivery is via Laravel events on the **queue** (Redis/Horizon) unless marked sync. Ordering is **per-aggregate** (not global). Idempotency is by a stable event id (+ consumer dedupe). Retention is in the **outbox/LRS**; versioning is additive (`v1`, `v2` payload schemas, never breaking). DLQ handling routes exhausted retries to a dead-letter store surfaced in the Administration Ops Console.

| Event | Publisher | Key subscribers | Payload owner | Delivery | Retry | Ordering | Idempotency | Retention | Versioning | DLQ |
|-------|-----------|-----------------|---------------|----------|-------|----------|-------------|-----------|------------|-----|
| UserRegistered | Identity | Notifications, Analytics, Organization | Identity | async | 3× backoff | per-user | eventId | 12 mo | additive | Ops DLQ |
| RoleAssigned | Identity | Admin audit, Analytics | Identity | async | 3× | per-user | eventId | 24 mo | additive | Ops DLQ |
| ImpersonationStarted/Ended | Identity | Audit Center | Identity | sync+audit | n/a | per-session | eventId | 24 mo (security) | additive | alert |
| CoursePublished | Catalog | Learning, Search, Analytics, Notifications | Catalog | async | 5× | per-course | eventId | 24 mo | additive | Ops DLQ |
| CourseVersionPublished | Catalog | Learning (re-pin), Search | Catalog | async | 5× | per-course | eventId | 24 mo | additive | Ops DLQ |
| ContentApproved | Authoring | Catalog (publish guard) | Authoring | async | 5× | per-course | eventId | 24 mo | additive | Ops DLQ |
| LessonPublished / AssessmentPublished | Authoring | Learning (cache invalidate), Search | Authoring | async | 5× | per-lesson | eventId | 24 mo | additive | Ops DLQ |
| MediaAssetReady / Failed | Media | Authoring, Learning | Media | async | 8× | per-asset | eventId | 12 mo | additive | alert+DLQ |
| EnrollmentGranted | Commerce | Learning | Commerce | async (guaranteed) | 8× | per-order | eventId | 24 mo | additive | alert |
| OrderPaid | Commerce | Learning (grant), Certification drip, Analytics, Notifications | Commerce | async (guaranteed) | 8× | per-order | eventId (+gateway id) | 7 yr (financial) | additive | alert |
| RefundIssued | Commerce | Learning (revoke?), Analytics, Notifications | Commerce | async | 8× | per-order | eventId | 7 yr | additive | alert |
| EnrollmentCreated | Learning | Analytics, Notifications | Learning | async | 3× | per-enrollment | eventId | 24 mo | additive | Ops DLQ |
| LessonStarted/Completed | Learning | Gamification, Dashboard proj, Analytics, Notifications | Learning | async | 3× | per-enrollment | eventId (+clientMutationId) | 24 mo hot | additive | Ops DLQ |
| ProgressUpdated | Learning | Dashboard proj, Session checkpoint, Analytics | Learning | async | 3× | per-enrollment | clientMutationId | 24 mo | additive | Ops DLQ |
| CourseCompleted | Learning | **Certification** (issue), Analytics, Notifications, Commerce | Learning | async (guaranteed) | 8× | per-enrollment | eventId | 24 mo | additive | alert |
| AssessmentSubmitted/Graded | Learning | Certification, Mastery, Gamification, Analytics | Learning | async | 5× | per-attempt | attemptId | life-of-item | additive | Ops DLQ |
| AssessmentPassed/Failed | Learning | Certification, Mastery, Gamification | Learning | async | 5× | per-attempt | attemptId | 24 mo | additive | Ops DLQ |
| CompetencyAttained | Learning | Certification, Organization, Analytics | Learning | async | 5× | per-learner | eventId | 24 mo | additive | Ops DLQ |
| BadgeUnlocked/LevelUp/StreakUpdated | Learning (Gamification) | Notifications, Dashboard | Learning | async | 3× | per-learner | eventId | 24 mo | additive | Ops DLQ |
| OfflineSyncCompleted / SyncConflictDetected | Learning | Dashboard, Analytics, (Ops on conflict) | Learning | async | 5× | per-device | clientMutationId | 12 mo | additive | Ops DLQ |
| CertificateIssued | Certification | Learning (store ref), Notifications, Analytics | Certification | async (guaranteed) | 8× | per-credential | eventId | life-of-credential | additive | alert |
| CredentialExpiring | Certification | Learning (recert prompt), Notifications | Certification | async | 5× | per-credential | eventId | life | additive | Ops DLQ |
| LiveSessionStartingSoon / Attended | Live | Notifications, Learning (attendance) | Live | async | 5× | per-session | eventId | 12 mo | additive | Ops DLQ |
| LeadCreated / DealWon/Lost | CRM | Analytics, Notifications | CRM | async | 3× | per-record | eventId | 24 mo | additive | Ops DLQ |
| OrganizationCreated / MemberInvited / OrgCapabilityChanged | Organization | Admin, Identity, Notifications | Organization | async | 5× | per-org | eventId | 24 mo | additive | Ops DLQ |
| TenantProvisioned/Suspended | Administration | Organization, Commerce, all (capability) | Administration | async (guaranteed) | 8× | per-tenant | eventId | life | additive | alert |
| FeatureFlagChanged / CapabilityGranted/Revoked | Administration | all contexts | Administration | async | 5× | per-flag | eventId | 24 mo | additive | Ops DLQ |
| MaintenanceModeChanged | Administration | all | Administration | sync broadcast | n/a | global | eventId | 12 mo | additive | alert |
| ProviderConfigured / BackupCompleted | Administration | Ops, capability ports | Administration | async | 5× | per-provider | eventId | 12 mo | additive | alert |
| NotificationSent/Failed/Bounced | Notifications | Analytics, Admin (deliverability) | Notifications | async | 5× | per-notification | eventId | 12 mo | additive | Ops DLQ |
| WebhookDelivered/Failed | Integration | Admin Ops, source context | Integration | async | 10× | per-endpoint | eventId | 12 mo | additive | DLQ+alert |
| `*Audited` (all contexts) | every context | **Audit Center** | each publisher | async | 5× | per-entity | eventId | per policy (24 mo–7 yr) | additive | Ops DLQ |

**Guaranteed-delivery events** (money, entitlement, credentials, tenant lifecycle) use the **transactional outbox** pattern (write event + state in one DB tx; a relay publishes) so they can never be lost; ordinary telemetry uses at-least-once queue delivery with consumer idempotency.

---

# Security Architecture

| Layer | Design |
|-------|--------|
| **Authentication** | Sanctum (SPA/token) for API + Next.js; Filament session guard + MFA. **Target hardening (from audit):** move web tokens out of localStorage to httpOnly cookies to close the XSS→token-theft chain. |
| **Authorization** | policy-per-aggregate, shared between API and Filament; deny-by-default. |
| **RBAC** | roles + permission grants owned by **Identity**; permission **registry & role templates** owned by **Administration**; enforced via policies + `filament-shield`. |
| **ABAC** | attribute checks layered on RBAC (owner-of-record, same-tenant, resource-state) evaluated in policies. |
| **Capability-based access** | per-tenant **capabilities** (Administration) gate whole features (e.g., "assessments", "AI authoring") — checked via `CapabilityPort` before RBAC. Capability = *is this feature enabled for this tenant*; permission = *is this user allowed*; flag = *is this operationally on*. |
| **Organization isolation** | every tenant-scoped query filtered by `organization_id` via a **global scope + policy**, never manual `where` (closes the manual-tenant-scoping risk TEN-1). Cross-tenant access impossible by construction. |
| **Tenant isolation** | shared-DB, row-level tenancy today (org_id scoping) with a path to schema/DB-per-tenant for enterprise plans (see Deployment). Provisioning owned by Administration. |
| **Secrets** | never in code/repo; env + external secret store (target: Vault/SSM); provider secrets encrypted at rest, referenced by Administration `provider_configs`. `.env*` gitignored (already hardened). |
| **Encryption** | TLS in transit; at-rest for DB + S3; app-level encryption for PII fields and provider secrets; crypto-shred keys for GDPR erasure (per Learning LRS). |
| **Audit** | every state-changing action emits `*Audited`; Audit Center aggregates, retains per policy, and is queryable/exportable. Impersonation and role changes are high-retention security events. |
| **Impersonation** | Administration-initiated, Identity-enforced, **always audited**, time-boxed, banner-flagged, never for financial actions. |
| **API security** | Sanctum + rate limits + per-route policies; media returns signed expiring tokens (raw storage ids never leak); idempotency keys on mutating endpoints. |
| **Webhook security** | signature verification (HMAC) on inbound (e.g., Stripe), idempotent processing (`firstOrCreate(event_id)`), all via Integration Platform; **gateway calls never inside a DB transaction** (fixes audit finding). |
| **Rate limiting** | centralized named limiters (per-route, per-user, per-tenant, per-IP); stricter on auth/OTP/checkout; 429 with retry-after. |

---

# Operational Architecture

| Concern | Design |
|---------|--------|
| **Queues** | Redis-backed, multiple named queues (default, notifications, media, analytics, webhooks, outbox-relay) with priorities; guaranteed events on durable queues. |
| **Workers** | Horizon-managed; per-queue worker pools; autoscaled by depth; isolated failure domains (media processing can't starve notifications). |
| **Scheduler** | Laravel scheduler for decay recompute, streak rollover, projection rebuilds, backups, expiry sweeps, digest sends; single-run locks for HA. |
| **Caching** | Redis; **version-keyed** content/curriculum caches (immutable per version); rebuildable read-model caches; short TTL for volatile views; cache tags per context for targeted invalidation. |
| **Redis** | cache + queue + locks + rate-limit counters + Horizon; separate logical DBs per concern; eviction policy tuned per use. |
| **Search** | Search Platform port; Postgres FTS today (per-context indexes), pluggable to OpenSearch/Meilisearch for scale; reindex on owner events. |
| **Media processing** | Media Platform: upload → Mux transcode → CloudFront delivery; async, event-driven (MediaAssetReady); retries + DLQ + alert. |
| **Notifications** | workflow engine + channel providers (mail/SMS/push) behind ports; preference-aware; deliverability tracked. |
| **Observability** | structured JSON logs with correlation ids; metrics (RED/USE); distributed tracing (target: OpenTelemetry) across HTTP→queue→external. |
| **Metrics** | app + business metrics; Administration Ops Console surfaces queue depth, failure rates, job latency, DLQ size. |
| **Tracing** | correlation id propagated request→event→job→webhook; trace ids in logs. |
| **Logging** | JSON, centralized, per-context channel, PII-aware; retention per policy. |
| **Health checks** | `/health`, `/readiness`, `/liveness` (exist) — DB, Redis, queue, storage, providers; used by orchestrator + Ops Console. |
| **Backups** | scheduled DB + object-storage backups (Administration-owned schedule), tested restores, offsite copies, PITR. |
| **Disaster recovery** | documented RPO/RTO; outbox + backups enable replay; multi-AZ target; runbooks in Ops Console; DR drills scheduled. |

---

# Deployment Architecture

| Environment | Purpose | Shape |
|-------------|---------|-------|
| **Development** | local | Docker Compose (api, web, postgres, redis, horizon, mailpit) — exists |
| **Testing/CI** | automated gates | ephemeral containers; full test suite + static analysis + Deptrac + security scan |
| **Staging** | pre-prod mirror | prod-like, seeded, feature-flag parity, smoke + e2e |
| **Production** | live | containerized, horizontally scaled, multi-AZ |

| Concern | Design |
|---------|--------|
| **Horizontal scaling** | stateless API + web behind a load balancer; scale by CPU/RPS; workers scale by queue depth; sticky-free (session in Redis). |
| **Database strategy** | managed Postgres, primary + read replicas; reads routed to replicas for heavy queries/analytics; connection pooling (PgBouncer); row-level tenancy now, schema/DB-per-tenant option for enterprise. |
| **Queue scaling** | Horizon pools per queue; autoscale workers; backpressure via depth alarms; DLQ isolation. |
| **CDN** | CloudFront for media + static assets; signed URLs for protected media; edge caching for public catalog. |
| **Object storage** | S3 (media, exports, backups, certificates); lifecycle policies (hot→cold→archive); per-tenant prefixes. |
| **Containers** | prod Dockerfile (exists); immutable images; 12-factor config; health probes. |
| **Kubernetes readiness** | stateless services, config via env/secrets, health/readiness endpoints, horizontal pod autoscaling, jobs as CronJobs, Horizon as a deployment — the app is K8s-ready without redesign. |
| **Blue/Green deployment** | two prod stacks; migrate (expand/contract) → deploy green → smoke → switch LB → keep blue warm for rollback. |
| **Rollback** | expand-and-contract migrations (never destructive in one step) + previous image + blue stack → instant revert (rollback scripts exist). |

---

# Enterprise Roadmap

Each phase is independently shippable and gated by exit criteria. Phases layer capability; nothing later breaks earlier ownership.

### Phase A — Platform Foundation
- **Objectives:** solidify Platform layer (Shared, Identity, Administration, Media/AI/Search/Integration ports), tenancy isolation, capabilities, audit center, ops console.
- **Deliverables:** Administration context; capability/flag system; global tenant scoping; ports for Media/AI/Search/Integration; outbox + DLQ; observability baseline; secrets externalized; httpOnly-cookie auth.
- **Dependencies:** none (foundation).
- **Risks:** retrofitting tenant scoping onto existing queries; secret migration.
- **Exit criteria:** every tenant-scoped query filtered by global scope; capabilities enforced; audit center live; guaranteed-delivery events on outbox; security audit findings (token storage, tenant scoping, webhook-in-tx) closed.

### Phase B — Core LMS
- **Objectives:** the learning redesign in production — Catalog/Authoring/Learning with ports, versioning, progress engine off the write path, LRS.
- **Deliverables:** CurriculumReadPort/PlaybackPort/AssessmentDefinitionPort; content versioning; projector-based progress; assessment execution; LRS + xAPI export.
- **Dependencies:** Phase A (ports, capabilities, media).
- **Risks:** data migration of progress to projections; behavior parity.
- **Exit criteria:** Learning reads content only via ports (Deptrac green); completion is projector-derived; attempts pin versions; dashboards rebuildable from LRS.

### Phase C — Commerce
- **Objectives:** robust monetization — pricing, checkout, contracts, refunds, entitlement grants, billing config per tenant.
- **Deliverables:** EntitlementPort; guaranteed OrderPaid→EnrollmentGranted; refund→revoke policy; tenant billing config; gateway calls outside DB tx.
- **Dependencies:** Phase A (tenant billing), Phase B (entitlement → learning).
- **Risks:** payment idempotency; refund/entitlement consistency.
- **Exit criteria:** paid enrollment is exactly-once; webhook idempotent + verified; financial events retained per policy.

### Phase D — Enterprise
- **Objectives:** multi-tenant enterprise features — Organization context, org roles/capabilities, SSO, white-label, usage limits, org analytics, consulting.
- **Deliverables:** Organization split from CRM; SSO providers; multi-brand panels; usage metering + limits; org dashboards; instructor context split.
- **Dependencies:** Phases A–C.
- **Risks:** CRM↔Organization split migration; SSO edge cases; brand resolution.
- **Exit criteria:** tenant provisioning self-serve; SSO live; white-label per brand; org isolation verified; instructor context owns zero lesson data.
- 
### Phase E — AI
- **Objectives:** AI Platform in production — authoring generation, adaptive learning, tutoring signals, AI analytics — all human-in-the-loop.
- **Deliverables:** AIProvider port + safety/moderation; AdaptivePolicyPort (rules→ML); AI content behind approval gate; adaptive recommendations (advisory); AI analytics narratives.
- **Dependencies:** Phases A–B (LRS/evidence), D (capabilities to gate AI per tenant).
- **Risks:** safety/hallucination; cost governance; over-automation.
- **Exit criteria:** no AI output reaches learners/grades without human approval; AI gated by capability; every AI decision auditable + overridable.

### Phase F — Marketplace
- **Objectives:** open the platform — instructor marketplace, third-party content, plugins, revenue sharing, partner instructors.
- **Deliverables:** marketplace listing/discovery; revenue-share (Commerce); external/partner instructor model; sandboxed Filament/UI plugins; content licensing (copy/fork per Composition model).
- **Dependencies:** Phases C (revenue), D (org/instructor), E (quality/AI review).
- **Risks:** content quality/trust; payout complexity; plugin security.
- **Exit criteria:** external instructors onboard safely; revenue share correct; plugins sandboxed to presentation + published APIs.

### Phase G — Global Expansion
- **Objectives:** scale globally — i18n/l10n, data residency, regional providers, compliance (GDPR/regional), multi-region deployment.
- **Deliverables:** full localization (content + UI + RTL, already partly present); per-region storage/media/data residency; regional payment/SMS providers; compliance tooling (residency, erasure, consent); multi-region active-active or active-passive.
- **Dependencies:** all prior phases.
- **Risks:** data residency complexity; regional compliance; latency.
- **Exit criteria:** data residency honored per tenant/region; localized end-to-end; multi-region DR proven.

---

# Architecture Decision Records

Concise ADRs for the major decisions across Phases 1–5. Each: Context · Decision · Alternatives · Consequences · Trade-offs · Future Evolution.

### ADR-01 — Modular monolith over microservices
- **Context:** one team, evolving domain, need velocity + strong consistency.
- **Decision:** Laravel 12 modular monolith with bounded contexts (`Domains`/`Contexts`/`Platform`), integrating by events + ports.
- **Alternatives:** microservices; single unstructured app.
- **Consequences:** one deploy, in-process ports, easy transactions; module boundaries enforced by Deptrac not network.
- **Trade-offs:** discipline (not network) enforces boundaries; scaling is per-process not per-service.
- **Future:** any context can be extracted to a service later because it already talks via events/ports.

### ADR-02 — Bounded contexts with single-writer ownership
- **Context:** avoid the "everything reaches into everything" LMS trap.
- **Decision:** each fact has exactly one owning context; others hold refs; cross-context reads via read models/ports.
- **Alternatives:** shared models; direct cross-domain queries.
- **Consequences:** clear ownership, safe evolution, testability.
- **Trade-offs:** more ports/ACLs; some duplication of reference data.
- **Future:** enables per-context scaling, extraction, and independent schemas.

### ADR-03 — Event-driven integration (events as DTOs)
- **Context:** decouple producers from consumers.
- **Decision:** domain events are DTOs on the queue; no Eloquent crosses a boundary; guaranteed events via transactional outbox.
- **Alternatives:** synchronous cross-calls; passing models in events.
- **Consequences:** resilience, replay, auditability; loose coupling.
- **Trade-offs:** eventual consistency; need idempotency + DLQ.
- **Future:** outbox → external broker (Kafka/SQS) if extraction happens.

### ADR-04 — Filament as UI only
- **Context:** admin console must not fork business rules.
- **Decision:** Filament resources/pages/actions delegate to domain Actions/Services; zero business logic in the panel; discovery via data-map (no branches).
- **Alternatives:** logic-in-resources; separate bespoke admin app.
- **Consequences:** one source of business truth; panel is replaceable.
- **Trade-offs:** thin-wrapper boilerplate.
- **Future:** multi-panel (admin/instructor/org), white-label, sandboxed plugins.

### ADR-05 — Administration as a Platform context (not a super-domain)
- **Context:** platform operation vs domain data must not blur.
- **Decision:** Administration owns config/flags/capabilities/tenants/providers/ops/audit; never owns domain business data; operates others via ports/flags/events.
- **Alternatives:** god-admin module; scatter admin concerns into each domain.
- **Consequences:** clean operator cockpit; domains stay authoritative.
- **Trade-offs:** more ports (ConfigPort/CapabilityPort/SecretsPort).
- **Future:** self-serve tenant provisioning, licensing, multi-brand.

### ADR-06 — Capability vs Permission vs Feature Flag
- **Context:** three different "can this happen" questions were conflated.
- **Decision:** **Capability** = tenant feature entitlement (Administration); **Permission** = user authorization (Identity); **Flag** = operational toggle (Administration). Checked capability→permission at each entry point.
- **Alternatives:** one flag system for all.
- **Consequences:** correct multi-tenant gating; clear layering.
- **Trade-offs:** three concepts to teach.
- **Future:** licensing tiers map to capability bundles.

### ADR-07 — Row-level multi-tenancy via global scope
- **Context:** manual `where org_id` is error-prone (audit TEN-1).
- **Decision:** enforce tenant isolation with a global scope + policy on every tenant-scoped model; no manual scoping.
- **Alternatives:** manual scoping; schema/DB-per-tenant from day one.
- **Consequences:** isolation by construction; simpler queries.
- **Trade-offs:** shared DB blast radius; noisy-neighbor risk.
- **Future:** schema/DB-per-tenant option for enterprise (Deployment).

### ADR-08 — Media Platform owns bytes; contexts own refs
- **Context:** playback providers were embedded in Learning.
- **Decision:** Media Platform owns upload/transcode/delivery behind PlaybackPort/MediaPort; contexts store asset refs and request signed tokens.
- **Alternatives:** each context handles its own media.
- **Consequences:** one place for signing/security/cost; no raw storage ids leak.
- **Trade-offs:** an extra port + migration of embedded providers.
- **Future:** per-region media, DRM, offline licenses.

### ADR-09 — Content versioning with copy-on-write + version pinning
- **Context:** editing published content must not corrupt learner history.
- **Decision:** lessons/assessments are versioned; references pin a version; attempts record the version they ran against; majors never auto-propagate.
- **Alternatives:** mutate in place; snapshot everything on read.
- **Consequences:** stable grades/history; safe re-publishing.
- **Trade-offs:** version storage + re-pin workflow.
- **Future:** editions, branching, collaborative merge.

### ADR-10 — Progress derived by projector, off the write path
- **Context:** synchronous full-course recompute per progress write doesn't scale.
- **Decision:** progress writes emit events; rollups computed by projectors against a version-keyed curriculum snapshot; read models serve reads.
- **Alternatives:** synchronous recompute (current); compute-on-read.
- **Consequences:** fast writes; rebuildable views; scalable.
- **Trade-offs:** eventual consistency of rollups.
- **Future:** streaming projections, real-time dashboards.

### ADR-11 — Authoring owns definitions; Learning owns attempts
- **Context:** assessments blur content vs execution.
- **Decision:** Authoring defines quizzes/assignments/exams; Learning owns every attempt/grade; attempt pins definition version.
- **Alternatives:** one context owns both.
- **Consequences:** clean split; grade stability; independent evolution.
- **Trade-offs:** AssessmentDefinitionPort + coordination.
- **Future:** adaptive/randomized delivery, proctoring.

### ADR-12 — Certification issues credentials; Learning stores references
- **Context:** who owns the credential.
- **Decision:** Learning proves completion/mastery via events; Certification decides + issues; Learning stores CertificateReference.
- **Alternatives:** Learning issues certificates.
- **Consequences:** single credential authority; verifiable.
- **Trade-offs:** cross-context event coordination.
- **Future:** verifiable credentials / blockchain attestation.

### ADR-13 — LRS + xAPI/SCORM/cmi5 as Learning's ledger
- **Context:** need auditable, replayable, standards-compatible learning history.
- **Decision:** append-only experience-event ledger is source of truth; xAPI mapping; SCORM/cmi5 normalized into events; GDPR erasure via crypto-shred.
- **Alternatives:** mutable current-state only.
- **Consequences:** audit, replay, interoperability, rebuildable projections.
- **Trade-offs:** ledger storage; erasure design.
- **Future:** external LRS export, analytics/AI on the stream.

### ADR-14 — AI is human-in-the-loop behind a port
- **Context:** AI must help, not act autonomously on learners/grades.
- **Decision:** AI Platform behind AIProvider/AdaptivePolicyPort; every AI output is a suggestion requiring human approval; gated by tenant capability; all decisions audited.
- **Alternatives:** autonomous AI actions.
- **Consequences:** safety, trust, auditability.
- **Trade-offs:** slower automation; human bottleneck by design.
- **Future:** rules→ML swap behind same port; richer tutoring.

### ADR-15 — Offline-first with idempotent, mergeable writes
- **Context:** mobile/offline + multi-device are first-class.
- **Decision:** every learner-state write carries clientMutationId; version vectors; per-aggregate deterministic merge; completion never regresses; exams online-only.
- **Alternatives:** online-only; last-write-wins everywhere.
- **Consequences:** durable offline UX; safe multi-device sync.
- **Trade-offs:** merge complexity; conflict quarantine.
- **Future:** CRDTs, real-time collaboration/presence.

### ADR-16 — Integration Platform as the single external I/O boundary
- **Context:** external calls (payments, calendar, SSO, webhooks) need one security/retry/audit point.
- **Decision:** all third-party I/O flows through Integration Platform (outbox, idempotency, retry, DLQ, signature verification); gateway calls never inside DB transactions.
- **Alternatives:** each context calls externals directly.
- **Consequences:** one place for resilience + security; fixes webhook-in-tx risk.
- **Trade-offs:** an extra hop/abstraction.
- **Future:** connector marketplace, API gateway.

### ADR-17 — REST-only, versioned, Sanctum-authenticated API
- **Context:** stable client contract for Next.js + integrations.
- **Decision:** REST under `/api/v1`, Sanctum auth, policy-enforced, idempotency keys on mutations; media via signed tokens.
- **Alternatives:** GraphQL; RPC.
- **Consequences:** simple, cacheable, well-understood.
- **Trade-offs:** over/under-fetching vs GraphQL.
- **Future:** `/v2` additive; optional GraphQL gateway for partners.

### ADR-18 — Analytics is read-only; consumes published projections
- **Context:** BI must not couple to every domain's internals.
- **Decision:** Analytics consumes contexts' **published projections** (esp. Learning's LearningAnalyticsProjection), never writes domain data, never binds to concrete internal events.
- **Alternatives:** subscribe to every concrete domain event (current coupling).
- **Consequences:** decoupled BI; stable contracts.
- **Trade-offs:** contexts must publish analytics projections.
- **Future:** data warehouse/lakehouse export, xAPI stream.

---

# Migration Strategy (no code in this phase)

Sequenced, additive, reversible. Aligns with the backend refactor chunks (Platform extraction done for Shared/Identity/Notifications; Learning/Commerce/Analytics moved to `Contexts`; Catalog/Authoring/Certification/Live/Crm remain under `Domains` pending chunks).

1. **Formalize Platform capabilities as ports first** (Media/AI/Search/Integration) over existing embedded implementations — no behavior change, coupling severed (mirrors the Learning port strategy).
2. **Introduce Administration context** (Platform layer) owning settings/flags/capabilities/audit-index/tenants/providers — start by *reading* existing config; add capability checks at entry points behind a default-on flag.
3. **Enforce tenant isolation** via global scope on tenant-scoped models (retrofit), verified by tests; remove manual scoping incrementally.
4. **Adopt transactional outbox** for guaranteed events (money/entitlement/credentials/tenant) without changing event contracts.
5. **Split Organization from CRM** and **Instructor from Catalog/Identity** per redesigns 02/03 (folder+namespace move like prior chunks; no schema/API change).
6. **Move embedded media providers out of Learning** behind PlaybackPort (per redesign 04).
7. **Repoint Analytics** to consume published projections instead of concrete domain events.
8. **Multi-panel Filament + white-label** last (additive panels; existing `admin` panel unchanged).

Every step preserves current APIs, DB schema, and the data-map Filament discovery (adding a context = one map line, never a branch).

---

# Final Architecture Principles

1. **One writer per fact.** Single-owner contexts; everyone else holds references.
2. **Integrate by events and ports; never by tables.** No context reads another's models for business logic (Deptrac-enforced).
3. **Definitions vs execution vs credentials are different owners.** Authoring defines, Learning executes, Certification credentials.
4. **Bytes belong to Media; refs belong to contexts.**
5. **AI suggests; humans decide.** Nothing AI-authored reaches learners or grades without human approval.
6. **Everything auditable and replayable.** Append-only ledgers + outbox make any state reconstructable.
7. **Multi-tenant isolation by construction,** not by remembering to add a `where`.
8. **Capability (tenant) → Permission (user) → Flag (operational)** are distinct and layered.
9. **Filament and the API share one source of business truth** — the domain Actions/Services.
10. **Administration operates the platform; it never owns the domains.**
11. **Additive, reversible evolution:** versioning, expand-and-contract migrations, no breaking changes.
12. **External I/O crosses exactly one boundary** (Integration Platform) for security, retry, and audit.

---

# Acceptance Criteria

1. **Administration owns only** platform operation (config, flags, capabilities, audit, tenants, providers, ops, branding, backups, license) and **no** domain business data; grep/Deptrac shows no Administration write into a domain's tables.
2. **Every "Administration owns" item** maps to a module/table/port in this blueprint.
3. **Filament contains zero business logic:** every resource/page/action delegates to a domain Action/Service; discovery is data-map driven (no conditional branches); multi-panel + white-label defined.
4. **All 16 participants** have Owned/Consumed/Events/APIs/Ports/Adapters/Read-models/Caching/Ownership/Forbidden-deps specified.
5. **The dependency matrix** enumerates allowed/forbidden deps, communication pattern, and ACL needs for every context; global forbidden rules are Deptrac-enforceable.
6. **The event map** lists every event with publisher, subscribers, payload owner, delivery, retry, ordering, idempotency, retention, versioning, and DLQ; guaranteed events use the outbox.
7. **Security architecture** covers authN/authZ, RBAC/ABAC/capabilities, tenant/org isolation, secrets, encryption, audit, impersonation, API/webhook security, rate limiting — and closes the known audit findings (token storage, manual tenant scoping, webhook-in-transaction).
8. **Operational architecture** specifies queues, workers, scheduler, caching/Redis, search, media, notifications, observability (metrics/tracing/logging), health checks, backups, and DR (RPO/RTO).
9. **Deployment architecture** covers dev/test/staging/prod, horizontal scaling, DB strategy (replicas/pooling/tenancy), queue scaling, CDN, object storage, containers, K8s readiness, blue/green, and rollback (expand-and-contract).
10. **The enterprise roadmap** defines Phases A–G each with objectives, deliverables, dependencies, risks, and exit criteria; phases are additive and never violate ownership.
11. **ADRs (18)** capture every major Phase 1–5 decision with context, decision, alternatives, consequences, trade-offs, and future evolution.
12. **No code, no schema, no API, no namespace change** was made in this phase; every recommendation is additive, reversible, and consistent with redesigns 01–04 and the executed backend refactor chunks.
