# CRM & Organization Domain Redesign (Phase 2) — Architecture Only

**Role:** Principal Domain Architect. **Type:** documentation only — no code, no moves, no namespace/API/DB changes.
**Grounding:** current single `Crm` domain as built (21 models, shared polymorphic timeline concerns, consulting request→project→session, seats/members, pipelines/leads) verified in audits 04/05 and refactor 07A §1.4A.
**Thesis:** Today one `Crm` module conflates two fundamentally different things: **who our tenants are and how they self-organize** (Organization) and **how we sell to and nurture relationships** (CRM). They must become **two independent bounded contexts** that never import each other's models and interact only through **published contracts and domain events**.

---

## Executive Summary

The pivotal distinction the current design blurs: an **Organization is a real customer tenant** operating on the LMS (its members log in, consume seats, learn), whereas a **CRM Account/Company is a sales record** (a prospect or customer relationship the sales team manages — it may exist long before, or entirely without, ever becoming a tenant). Collapsing both into `Crm` created shared tables, a shared polymorphic timeline used by both org and sales entities, and consulting logic split awkwardly across "request" and "delivery."

The redesign creates:
- **Organization** — the multi-tenant backbone: organizations (and their hierarchy), departments/teams/branches/business-units, members/roles/invitations, seats/licenses, org settings/branding/domains/SSO, org-scoped billing references, audit trail, API keys, and org integrations. Its consistency guarantee is **tenant isolation** (the fix demanded in audit 09/TEN-1).
- **CRM** — the sales & success system: accounts/companies, leads/contacts, pipeline/stages/deals/opportunities, activities/notes/tasks/tags timeline, consulting delivery (projects/sessions), lead scoring, automations, dashboards.

They meet only at the seams: when a **deal is won**, CRM emits `DealWon`; Organization may **provision a tenant** in response. When an **organization is created/renamed/archived**, Organization emits events; CRM may **link an Account** to it. Neither reads the other's Eloquent models. The shared timeline concern is assigned to **CRM only**; Organization gets its own **audit trail** (distinct concern, distinct table).

Outcome: clean tenant isolation, a sales system that can scale into AI scoring / conversation intelligence independently, and a migration that matches the already-planned backend chunk C11 (`Crm` → `Organization` + `Crm`).

---

## Current Problems

1. **Two contexts in one module.** `Crm` holds tenant-management entities (Organization, OrganizationMember, Department, Team, SeatPool, SeatAssignment, BillingProfile) **and** sales entities (Company, Contact, Lead, Pipeline, Stage, Opportunity) in one namespace, one provider, one Filament group.
2. **Duplicate/ambiguous "organization" concept.** `Organization` (a tenant) and `Company` (a CRM account) both model "a business," with no explicit relationship or ownership rule → confusion about which is the source of truth for a customer.
3. **Wrong ownership of the timeline.** `HasActivities/HasNotes/HasTags/HasTasks` (polymorphic) are attached to **both** org entities and sales entities. This couples Organization to CRM's timeline and blocks independent evolution (audit 07A/R3).
4. **Consulting split across the seam.** `ConsultingRequest` (an org asking for help) lives beside `ConsultingProject`/`ConsultingSession` (sales/CS delivery) with no boundary — request is an Organization concern, delivery is a CRM concern.
5. **Cross-context coupling / direct model deps.** Anything needing "the customer" imports `Crm\Models\Organization` or `Company` directly; there is no `OrganizationContext`/`CrmContext` contract.
6. **Hidden dependency: seats vs billing vs entitlement.** Seats (Organization) are purchased (Commerce) and consumed by learners (Learning), but the chain is implicit; billing lives as `BillingProfile` inside CRM.
7. **Tenant isolation is manual.** Org-scoped data has no enforced scope (audit 09/TEN-1) — a first-class Organization context makes tenancy a boundary invariant rather than a scattered `where`.
8. **Future scalability blocked.** Multi-tenant SaaS, holding companies (parent/child orgs), white-label, resellers, mergers/splits, and CRM AI (scoring, conversation intelligence, email/calendar sync) cannot be added cleanly while the two concerns share tables and namespaces.

---

## Organization Boundary

**Mission:** Be the authoritative multi-tenant backbone — model **who the customer is as an operating tenant** and everything about how that tenant self-organizes, provisions access, and is governed. Guarantee **tenant isolation** by construction.

**Organization OWNS:** Organizations · Departments · Teams · Branches · Business Units · Members · Seats · Licenses · Organization Roles · Organization Invitations · Organization Learning (assignments/tracks scoped to the org) · Organization Policies · Organization Settings · Organization Branding · Organization Domains · Organization SSO (config) · Organization Reports · Organization Billing **References** · Organization Audit Trail · Organization API Keys · Organization Integrations.

**Organization does NOT own:** sales pipeline/leads/deals (CRM); money/pricing/invoices (Commerce owns; Organization holds *references*); user identity/authentication (Identity owns; Organization holds membership + SSO *config*, Identity enforces); course definitions (Catalog); enrollment/progress (Learning consumes org entitlements); consulting *delivery* (CRM).

---

## CRM Boundary

**Mission:** Be the sales & customer-success system — model **how we acquire, nurture, and grow relationships**, independent of whether the relationship is a tenant yet.

**CRM OWNS:** Accounts · Companies · Leads · Contacts · Sales Pipeline · Deals · Activities · Notes · Tasks · Opportunities · Consulting Projects (delivery) · Consulting Sessions · Sales Teams · Customer Success · Relationship Timeline · Email Timeline · Meeting Timeline · Call Logs · Follow-ups · Lead Scoring · CRM Automations · CRM Dashboards.

**CRM does NOT own:** tenants/members/seats/licenses (Organization); money (Commerce); identity (Identity); learning delivery (Learning). CRM may **reference** an Organization by id (a won account that became a tenant) but never reads Organization internals.

---

## Context Ownership

| Concern | Owner | Cross-context interaction |
|--------|-------|---------------------------|
| Tenant + hierarchy + members + seats | **Organization** | emits lifecycle events |
| Sales pipeline / leads / deals | **CRM** | emits sales events |
| "The customer as a business record" (prospect/account) | **CRM** (`Account`) | links to Organization by id when won |
| "The customer as an operating tenant" | **Organization** (`Organization`) | provisioned on `DealWon` (optional) |
| Consulting **request** (org asks) | **Organization** | `ConsultingRequested` → CRM creates project |
| Consulting **delivery** (project/session) | **CRM** | `ConsultingProjectCompleted` → Organization/Analytics |
| Timeline (activities/notes/tasks/tags) | **CRM** | Organization does NOT share it |
| Audit trail | **Organization** (its own) | distinct from CRM timeline |
| Money / invoices / pricing | **Commerce** | Organization holds `billingRef`; CRM holds `dealValue` (informational) |
| Seats purchase | **Commerce → Organization** | `OrderPaid(seats)` → Organization credits SeatPool |
| Entitlement consumption | **Learning** | reads Organization seat/license contract |
| Identity / SSO enforcement | **Identity** | Organization owns SSO *config*, Identity authenticates |

---

## Entities

### Organization context
- **Organization** *(aggregate root)* — id, `slug`, `name`, `type` (business|enterprise|partner|reseller|white_label|government|education|holding), `status`, `parentOrganizationId?`, settings, branding ref, primary domain, `billingRef?`.
- **OrganizationMember** — org↔user membership (userId, orgRole, status, invitedBy).
- **Department**, **Team**, **Branch**, **BusinessUnit** — org sub-structure (each with parent scope).
- **Invitation** — pending member invite (email, role, token, expiry).
- **SeatPool** — capacity of a plan/license (`total`, `used`, source `licenseId`).
- **SeatAssignment** — a seat consumed by a member (memberId, poolId, assignedAt).
- **License** — an entitlement grant (plan, quantity, validity, source `billingRef`).
- **OrganizationRole** — org-scoped role definition (custom roles per tenant).
- **OrganizationDomain** — verified domain for auto-join/SSO.
- **SsoConnection** — SAML/OIDC config (metadata, mappings) — enforced by Identity.
- **ApiKey** — org-scoped API credential (hashed).
- **OrganizationIntegration** — configured connector (type, config ref, status).
- **OrganizationPolicy** / **OrganizationSetting** — governance + preferences.
- **OrganizationAuditEntry** — immutable audit record (actor, action, before/after).
- **OrganizationLearningAssignment** — org-mandated course/track for members (references Catalog/Learning by id).

### CRM context
- **Account** *(aggregate root)* — the customer record (company). `linkedOrganizationId?` when won/provisioned.
- **Company** — synonym/parent of Account (or Account is the aggregate over Company + Contacts). id, name, domain, size, industry.
- **Contact** — person at an account (name, email, phone, role).
- **Pipeline**, **Stage** — sales process definition.
- **Lead** *(aggregate root)* — inbound prospect (source, status, score, ownerId).
- **Opportunity / Deal** *(aggregate root)* — qualified sales opportunity (value, stage, closeDate, status won|lost|open).
- **ConsultingProject**, **ConsultingSession** — CS/consulting **delivery**.
- **Activity**, **Note**, **Task**, **Tag** — polymorphic **relationship timeline** (CRM-only).
- **CallLog**, **EmailThread**, **Meeting** — channel timelines (future sync).
- **FollowUp** — scheduled next action.
- **LeadScore** — computed scoring snapshot.
- **Automation / AutomationRule** — CRM workflow triggers.

---

## Aggregates

| Context | Aggregate | Root | Key invariants | Consistency boundary |
|---------|-----------|------|----------------|----------------------|
| Organization | **Organization** | Organization | tenant isolation; unique slug; hierarchy acyclic; archived blocks new members | org + settings/branding/domain refs |
| Organization | **Membership** | OrganizationMember | one active membership per (org,user); role valid; invite→join transition | membership + its seat assignment |
| Organization | **SeatPool/License** | SeatPool | used ≤ total; assignment references active member; license validity | pool + its assignments |
| CRM | **Account** | Account | unique per domain (soft); linkedOrganizationId set-once | account + companies + contacts |
| CRM | **Lead** | Lead | status transitions (new→qualified→converted/lost); one conversion | lead + its timeline entries |
| CRM | **Deal/Opportunity** | Opportunity | stage transitions legal; won/lost terminal; value ≥ 0 | opportunity + timeline |
| CRM | **ConsultingProject** | ConsultingProject | sessions belong to a project; SLA rules | project + sessions |

Cross-aggregate + cross-context references are **by id only**.

---

## Value Objects

**Organization:** `OrganizationType`, `OrganizationStatus`, `MemberRole`, `MemberStatus`, `SeatCount(total,used)`, `LicensePlan`, `DomainName(verified)`, `BillingRef` (opaque Commerce reference), `OrgBranding(logoAssetId,theme)`, `HierarchyPath` (for parent/child), `TenantId`.
**CRM:** `LeadStatus`, `LeadScore(value,band)`, `PipelineType`, `StageRef`, `OpportunityStatus`, `DealValue(Money)` *(informational; not authoritative money)*, `ActivityType`, `TaskStatus`, `ConsultingProjectStatus`, `ConsultingSessionStatus`, `HealthScore(band)`, `ContactChannel`.

---

## Commands

### Organization
`CreateOrganization` · `RenameOrganization` · `ArchiveOrganization` · `SetOrganizationType` · `SetParentOrganization` (holding) · `MergeOrganizations` · `SplitOrganization` · `InviteMember` · `AcceptInvitation` · `RemoveMember` · `ChangeMemberRole` · `CreateDepartment/Team/Branch/BusinessUnit` · `AssignSeat` · `ReleaseSeat` · `GrantLicense` · `RevokeLicense` · `VerifyDomain` · `ConfigureSso` · `IssueApiKey` · `RevokeApiKey` · `ConfigureIntegration` · `UpdateOrganizationSettings/Branding/Policy` · `RequestConsulting`.

### CRM
`CreateLead` · `QualifyLead` · `ConvertLead` (→ Account + Opportunity) · `MoveLeadStage` · `ScoreLead` · `CreateAccount` · `LinkAccountToOrganization` · `CreateContact` · `CreateOpportunity/Deal` · `MoveDealStage` · `WinDeal` · `LoseDeal` · `LogActivity` · `AddNote` · `CreateTask` · `CompleteTask` · `ScheduleFollowUp` · `CreateConsultingProject` · `ScheduleConsultingSession` · `CompleteConsultingProject` · `RunAutomation` · `RecordCall/Email/Meeting`.

Each command → application service → transaction → event(s), gated by policy + permission.

---

## Queries

**Organization:** `GetOrganization(id)` · `GetOrgHierarchy(rootId)` · `GetMembers(orgId, filters)` · `GetSeatUsage(orgId)` · `GetLicenses(orgId)` · `GetPendingInvitations(orgId)` · `GetOrgAuditTrail(orgId)` · `GetOrgLearningAssignments(orgId)` · `GetOrgReports(orgId)`.
**CRM:** `GetPipeline(pipelineId)` · `GetLeads(filters, ownerId)` · `GetLead(id)` · `GetAccount(id)` · `GetOpportunities(filters)` · `GetTimeline(entityRef)` · `GetTasksDueToday(ownerId)` · `GetConsultingProjects(filters)` · `GetForecast(period)` · `GetCrmDashboard(userId)`.

Queries return **read models**, never Eloquent aggregates.

---

## Events

### Organization events
`OrganizationCreated` · `OrganizationRenamed` · `OrganizationArchived` · `OrganizationTypeChanged` · `OrganizationParentSet` · `OrganizationsMerged` · `OrganizationSplit` · `MemberInvited` · `MemberJoined` · `MemberRemoved` · `MemberRoleChanged` · `SeatAssigned` · `SeatReleased` · `LicenseGranted` · `LicenseRevoked` · `DomainVerified` · `SsoConfigured` · `ConsultingRequested` · `OrgIntegrationConfigured` · `OrgSettingsChanged`.

### CRM events
`LeadCreated` · `LeadQualified` · `LeadConverted` · `LeadStageMoved` · `LeadScored` · `AccountCreated` · `AccountLinkedToOrganization` · `DealCreated` · `DealStageMoved` · `DealWon` · `DealLost` · `OpportunityCreated` · `ConsultingProjectCreated` · `ConsultingProjectCompleted` · `ActivityLogged` · `NoteAdded` · `TaskCreated` · `TaskCompleted` · `FollowUpScheduled` · `CustomerHealthChanged` · `CallLogged` · `EmailSynced` · `MeetingScheduled`.

All events carry **DTOs (ids + primitives)** — never Eloquent models — so Analytics/Notifications/Marketing consume without importing either context.

---

## Read Models

**Organization:** `OrgSummaryCard` (name, type, member count, seat usage) · `MemberRow` · `SeatUsagePanel` · `InvitationRow` · `OrgHierarchyTree` · `LicensePanel` · `OrgAuditRow` · `OrgLearningProgress` (composed from Learning by id).
**CRM:** `LeadRow` · `PipelineBoard` (stages × deals) · `AccountCard` (+ linkedOrganizationId) · `OpportunityRow` · `TimelineFeed` · `TaskRow` · `ForecastSummary` · `ConsultingProjectRow` · `HealthScoreCard` · `CrmDashboardTiles`.

Maintained by projectors on each context's own events; the cacheable surface.

---

## Services

**Organization:** `OrganizationLifecycleService` · `MembershipService` (invite/join/remove/role) · `SeatService` (assign/release, capacity invariants — *exists*) · `LicensingService` · `DomainVerificationService` · `SsoConfigService` · `HierarchyService` (parent/child, merge/split) · `OrgAuditService` · `OrgReportingService`.
**CRM:** `LeadService` (+ scoring hook) · `PipelineService` · `DealService` · `AccountService` (+ `LinkToOrganization`) · `TimelineService` (activities/notes/tasks — *exists*) · `ConsultingDeliveryService` (project/session, SLA — from *ConsultingSlaService*) · `CrmSearchService` (*exists*) · `AutomationEngine` · `ForecastService`.

---

## Repository Interfaces

**Organization ports:** `OrganizationRepository`, `MembershipRepository`, `SeatPoolRepository`, `LicenseRepository`, `OrgReadRepository`, plus outbound `BillingReference` (Commerce), `IdentityDirectory` (Identity for users), `SsoEnforcer` (Identity).
**CRM ports:** `LeadRepository`, `AccountRepository`, `OpportunityRepository`, `PipelineRepository`, `ConsultingRepository`, `CrmReadRepository`, plus outbound `OrganizationDirectory` (contract to look up an org by id when linking an Account — read-only, no internals).

Neither context depends on the other's concrete classes — only on these interfaces + events.

---

## API Ownership

- **Organization API:** `/api/v1/organizations*`, `/members`, `/seats`, `/licenses`, `/invitations`, `/domains`, `/sso`, `/api-keys`, `/org-reports`, `/consulting-requests` (org-raised). URLs preserved where they exist today (org detail/invite/consulting-request).
- **CRM API:** `/api/v1/crm/leads*`, `/accounts` (renamed from `/crm/organizations` per refactor 02), `/pipeline`, `/deals`, `/opportunities`, `/consulting` (delivery), `/timeline`, `/tasks`.
- **OpenAPI ownership:** two specs — `organization.yaml` and `crm.yaml` — split from today's `crm.yaml`. No shared schema; a linked-org reference is an id, not an embedded Organization schema.

---

## Filament Ownership

- **Organization panel resources:** `OrganizationResource`, `MemberResource`, `SeatResource`, `LicenseResource`, `InvitationResource`, `ConsultingRequestResource` (org-raised).
- **CRM panel resources:** `LeadResource`, `AccountResource`, `PipelineResource`, `OpportunityResource`, `ConsultingProjectResource`, `TaskResource`.
- Discovery via the **data-map** in `AdminPanelProvider` (introduced in refactor 5E) — add two map lines (`Contexts/Organization/...`, `Contexts/Crm/...`), **no conditional branches**. `navigationGroups` gets distinct "Organization" and "CRM" groups.

---

## Search Strategy

- **Organization search:** members (by name/email), organizations (admin), scoped by tenant; DB indexes on `organization_members(organization_id,user_id)`, `organizations(slug)`, domain.
- **CRM search:** leads/accounts/contacts/deals via `CrmSearchService`; full-text on names/emails/company; future external index fed by CRM events.
- Search is always **tenant/owner-scoped** (Organization by tenant, CRM by sales-team/owner visibility).

## Cache Strategy

- Read models cached per context with event-driven invalidation: `org:{id}:*` flushed on Organization events; `crm:pipeline:{id}`, `crm:account:{id}` flushed on CRM events.
- Seat-usage and pipeline-board are hot read models — cached with short TTL + event bust.
- Tenant-scoped cache keys prevent cross-org leakage.

## Reporting Strategy

- **Organization Reports:** seat utilization, member activity, org learning progress (composed from Learning by id), license consumption — owned by Organization, surfaced to org admins; heavy aggregates delegated to **Analytics** via events.
- **CRM Dashboards:** pipeline value, win rate, forecast, activity volume, lead-source ROI, CS health — owned by CRM; cross-cutting BI delegated to **Analytics**.
- Both feed **Analytics** through events (DTOs); Analytics owns cross-context BI, neither context queries the other for reporting.

## Integration Strategy

Both contexts reach externals via **outbox + adapters** (never direct dependency), consumers idempotent by event id (consistent with the webhook pattern, audit 05):
- **Organization:** SSO/IdP (via Identity), SCIM provisioning, domain DNS verification, billing sync (Commerce), enterprise API keys/webhooks, ERP (seat/license), directory sync.
- **CRM:** email sync (IMAP/Graph), calendar sync, WhatsApp/telephony (call logs), marketing automation, conversation-intelligence (transcription), AI scoring/recommendation, external CRM import/export.
Each integration: **Owner** (adapter context), **Direction**, **Transport** (event/async), **Contract** (versioned DTO), **Failure** (outbox retry), **Idempotency** (event id / natural key), **Future** (swap provider behind contract).

---

## Dependency Rules

- **Organization must NOT know CRM internals; CRM must NOT know Organization internals.** No `use App\...\Crm\Models\*` inside Organization and vice-versa.
- Interaction **only** via: (a) published **contracts** (`OrganizationDirectory` read port, `CrmContext`), (b) **domain events**.
- Both depend on **Platform (Identity/Shared)** via interfaces; both may reference **Commerce**/**Learning** via contracts (ids), never models.
- **Forbidden:** shared polymorphic timeline across contexts (timeline is CRM-only; Organization has its own audit trail); events carrying Eloquent models; either context writing the other's tables.
- Enforced by **Deptrac** rules once split (audit 04/DA-3).

---

## Event Flow (subscribers per event)

```
CRM: DealWon ─────────────┬─> Organization: (optional) provision tenant / link (LinkAccountToOrganization prerequisite)
                          ├─> Commerce: create billing/contract
                          ├─> Analytics: revenue/win metrics [DTO]
                          └─> Notifications: notify AE / CS [DTO]

CRM: LeadConverted ───────┬─> CRM: create Account + Opportunity
                          └─> Analytics: conversion funnel [DTO]

Org: OrganizationCreated ─┬─> CRM: create/link Account (AccountLinkedToOrganization)
                          ├─> Identity: seed org admin membership context
                          ├─> Analytics: new-tenant metric [DTO]
                          └─> Notifications: welcome / onboarding [DTO]

Org: MemberInvited/Joined/Removed ─> Identity (access), Learning (entitlement recalculation), Analytics, Notifications
Org: SeatAssigned/Released ────────> Learning (entitlement), Commerce (usage), Analytics
Org: ConsultingRequested ──────────> CRM: create ConsultingProject
CRM: ConsultingProjectCompleted ───> Organization (close request), Analytics, Notifications
CRM: ActivityLogged / TaskCompleted / CustomerHealthChanged ─> Analytics, Notifications (CS alerts)
Org: OrganizationsMerged / OrganizationSplit ─> CRM (reconcile linked accounts), Learning (re-scope entitlements), Analytics
Commerce: OrderPaid(seats) ────────> Organization: credit SeatPool (LicenseGranted)
```

Every subscriber consumes a **DTO**; no subscriber imports the emitter's models.

---

## Future Evolution

Additive-only, behind contracts/events:
- **Multi-tenant SaaS:** Organization is the tenant root; `TenantId` on all org-owned rows; global scope enforces isolation (audit 09/TEN-1) — the default from day one, not bolted on.
- **Enterprise customers:** `type=enterprise` + custom `OrganizationRole`s + SSO + domains + higher seat/license tiers; all facets, no new required fields.
- **Partner organizations / Resellers / White-label:** `type=partner|reseller|white_label` + `OrgBranding` (logo/theme/domain) + a `managedByOrganizationId` for resellers who administer sub-tenants; white-label serves under the org's own domain.
- **Government / Education organizations:** `type=government|education` + region/compliance policies (data residency), procurement/PO billing refs — policy facets on the Organization.
- **Holding companies / Parent-child:** `parentOrganizationId` + `HierarchyPath`; rollup reports aggregate children; access can cascade or isolate per policy.
- **Organization mergers:** `MergeOrganizations(source, target)` → moves members/seats/assignments under target (idempotent, audited), emits `OrganizationsMerged`; CRM reconciles linked accounts; Learning re-scopes entitlements. History preserved (source archived, not deleted).
- **Organization split:** `SplitOrganization(source, spec)` → carve members/units into a new org; emits `OrganizationSplit`; consumers reconcile.

## CRM Future Evolution (additive)
- **AI lead scoring:** `ScoreLead` becomes pluggable — a `LeadScoringProvider` port; scores are `LeadScore` VOs; model swap behind the port; `LeadScored` events feed pipeline prioritization.
- **Predictive pipeline / forecasting:** `ForecastService` gains a `PredictionProvider` port consuming CRM events + history; outputs weighted/predicted forecasts as read models.
- **Conversation intelligence:** `Meeting`/`CallLog` gain transcript refs (Media Platform) + a `ConversationInsightProvider` port; insights attach to the timeline.
- **Email / Calendar synchronization:** `EmailThread`/`Meeting` fed by sync adapters (IMAP/Graph/Google) via outbox; two-way sync behind a `MailboxSync`/`CalendarSync` port; idempotent by external message id.
- **WhatsApp / telephony:** `CallLog`/message timeline via channel adapters; owned by an integration adapter, CRM stores the timeline.
- **Sales & Customer-success automation:** `AutomationEngine` with triggers on CRM events (no-activity-N-days, health-drop, stage-stall) → tasks/follow-ups/notifications; rules are data, not code.

---

## Organization Templates

**Definition.** An `OrganizationTemplate` is a **versioned blueprint** used **only at provisioning** to seed a new Organization with a starting structure, roles, policies, and enabled features. A template is **not** an entity that owns runtime data — once applied it produces real Organization runtime entities (departments, roles, flag/capability grants) that then evolve independently. Editing a template **never** retroactively mutates existing organizations; existing orgs keep the snapshot they were seeded from (`seededFromTemplate = {key, version}` on the Organization, for audit only).

**Relationship to `OrganizationType`.** This refines Phase 2: `OrganizationType` is now purely a **template selector / classification label** — it selects which blueprint to apply and is retained for reporting/segmentation. It carries **no runtime behavior**; behavior comes from the Capability Set + Feature Flags (see below). (No prior decision is removed — `OrganizationType` remains a value object; its *authority over behavior* is transferred to capabilities.)

**Blueprint shape (every template defines):** default hierarchy · departments · teams · organization roles · learning policies · branding defaults · onboarding flow · reporting defaults · integrations enabled · feature flags (initial) · capability set (initial) · default permissions.

| Template | Default hierarchy | Departments (seed) | Teams (seed) | Org roles (seed) | Learning policy | Branding defaults | Onboarding flow | Reporting defaults | Integrations enabled | Feature flags ON | Default permissions |
|----------|-------------------|--------------------|--------------|-------------------|-----------------|-------------------|-----------------|--------------------|-----------------------|-------------------|---------------------|
| **University** | Org → Faculties → Departments → Programs | Admissions, Faculties, Registrar | Cohorts per program | Registrar, Dean, Faculty Admin, Instructor, Student | Mandatory tracks per program; term-based | Institution logo/colors | SSO-first, bulk roster import | Program completion, cohort progress | SSO, SIS/ERP, SCORM | Learning, Certificates, SCORM, SSO, Analytics, API | Faculty Admin manages own faculty |
| **School** | Org → Grades → Classes | Administration, Grades | Class groups | Principal, Teacher, Parent, Student | Grade-locked curricula; guardian access | School branding | Guardian invites, class rosters | Attendance, grade progress | SSO, Google Classroom | Learning, Certificates, SSO | Teacher manages own class |
| **Enterprise** | Org → Business Units → Departments → Teams | HR, L&D, IT | Functional teams | Org Admin, L&D Manager, Manager, Learner | Compliance/mandatory training + deadlines | Corporate brand | SSO/SCIM auto-provision | Compliance status, seat utilization | SSO, SCIM, HRIS, API, Webhooks | Learning, Analytics, SSO, API, Certificates, Integrations | Manager sees own team |
| **Government** | Org → Agencies → Units | Agency Admin, Compliance | Unit teams | Agency Admin, Officer, Auditor | Data-residency + audit-heavy; role-gated | Gov brand + accessibility | Procurement/PO onboarding, SSO | Compliance + audit reports | SSO, ERP (PO), Audit export | Learning, Certificates, SSO, Analytics | Auditor read-all (scoped) |
| **Training Center** | Org → Programs → Batches | Operations, Instructors, Sales | Batch cohorts | Center Admin, Coordinator, Instructor, Trainee | Scheduled cohorts + attendance | Center brand | Batch creation, trainer assign | Batch/attendance/revenue | Payments, Calendar | Learning, Live, Commerce, Certificates | Coordinator manages batches |
| **Partner** | Org → Teams | Delivery, Sales | Delivery teams | Partner Admin, Delivery, Sales | Co-branded delivery | Co-brand (dual logo) | Partner agreement + brand setup | Delivery + attribution | CRM link, Webhooks | Learning, CRM link, Analytics, API | Partner Admin scoped to partner |
| **Reseller** | Reseller → Managed sub-orgs (parent/child) | Sales, Support | Account teams | Reseller Admin, Account Manager | Per-sub-org policy | Reseller brand + sub-brands | Sub-tenant provisioning wizard | Rollup across managed orgs | Billing, Provisioning API | White Label, Marketplace, API, Analytics | Account Mgr manages assigned sub-orgs |
| **White Label** | Org → Teams (own domain) | Product, Support | — | Owner Admin, Staff | Own catalog/policy | Full custom brand + own domain + SSO | Domain verification, brand upload | Own dashboards | Custom domain, SSO, API | White Label, Learning, Commerce, API, Analytics | Owner controls everything within tenant |

**Rules:** templates are **customizable** (an org admin, per permission, may add/remove departments/roles/flags after seeding); templates are **versioned** (`template@vN`); templates **never own runtime data** (blueprints only); applying a template is a provisioning-time command (`ProvisionOrganization(templateKey, overrides)`); overrides are captured so provisioning is reproducible/auditable.

---

## Organization Lifecycle

An Organization moves through six states. Transitions are **commands** gated by permission, emit **events**, and gate **operations**.

```
                +---------------+
                | Provisioning  |  (seeding from template; not yet usable)
                +-------+-------+
                        | provisioned
              +---------v---------+        +-------------+
              |       Trial       |------->|   Active    |
              | (time-boxed)      | convert| (paid/live) |
              +----+---------+----+        +--+-------+--+
        trial-expire|         |suspend         |suspend |reactivate
                    |         v                 v        ^
                    |   +-----+------------------+-----+ |
                    +-->|          Suspended            |-+
                        +---------------+---------------+
                                        | archive
                                        v
                               +--------+--------+
                               |    Archived     | (read-only history)
                               +--------+--------+
                                        | delete (hard, after retention)
                                        v
                               +--------+--------+
                               |    Deleted      | (purged / anonymized)
                               +-----------------+
```

Alternate happy path: `Provisioning -> Active` directly (enterprise contract, no trial). `Suspended -> Active` (reactivate). `Archived -> Active` is **not** allowed directly (must be explicitly `Restore`d to `Suspended` first, then reactivated).

| Transition | Allowed source | Target | Required permission | Emitted event | Blocked operations in target | Rollback |
|-----------|----------------|--------|---------------------|---------------|------------------------------|----------|
| Provision | (none) | Provisioning | system / platform admin | `OrganizationProvisioning` | everything except seeding | delete provisioning record (no side effects yet) |
| Activate (from provisioning) | Provisioning | Active | platform admin / contract | `OrganizationActivated` | none | -> Suspended |
| Start trial | Provisioning | Trial | platform admin / self-serve signup | `OrganizationTrialStarted` | billing-required features gated | -> Suspended/Deleted |
| Convert trial | Trial | Active | org admin + payment (Commerce) | `OrganizationActivated` | none | -> Trial (rare) |
| Trial expire | Trial | Suspended | system (scheduler) | `OrganizationSuspended(reason=trial_expired)` | logins limited to admin; learning read-only | reactivate on payment |
| Suspend | Trial, Active | Suspended | platform admin / billing failure | `OrganizationSuspended` | new enrollments, new members, API writes blocked; existing progress read-only | reactivate |
| Reactivate | Suspended | Active | platform admin / payment cleared | `OrganizationReactivated` | none | re-suspend |
| Archive | Suspended, Active | Archived | platform admin | `OrganizationArchived` | all writes blocked; read-only history | restore -> Suspended |
| Restore | Archived | Suspended | platform admin | `OrganizationRestored` | still suspended until reactivated | re-archive |
| Delete | Archived (after retention window) | Deleted | platform admin (2-person / MFA) | `OrganizationDeleted` | irreversible; data purged/anonymized | **none** (backup restore only, ops procedure) |

**Rollback strategy:** every transition except **Delete** is reversible via its inverse command; Delete is terminal and only recoverable via backup/restore (ops runbook), never via the domain. All transitions write an `OrganizationAuditEntry` (actor, from, to, reason). Seat/license state is preserved through Suspend/Archive and only released on Delete.

---

## Tenant Feature Flags

**Layering (reconciles with the Capability Model below):** a feature is usable only if **`capability granted` AND `flag enabled` AND `rollout includes org`**. **Capabilities** = the *entitlement/authority* (coarse, tied to plan/license/manual grant). **Feature Flags** = the *operational on/off* (can be disabled even when capable; supports gradual rollout, kill-switch). Flags never grant capability; they gate an already-granted capability.

| Feature | Owner (context) | Default | Inheritance | Overrides | Rollout | Backward compat | Evaluation | Caching |
|---------|-----------------|---------|-------------|-----------|---------|-----------------|-----------|---------|
| Learning | Learning | ON | parent→child | org admin (per capability) | GA | on = today's behavior | `capable(Learning) && flag && rollout` | per-tenant, event-bust |
| Commerce | Commerce | template-dependent | inherit | org admin | staged | off = no storefront (no break) | as above | per-tenant |
| Live | Live | OFF | inherit | org admin | staged | additive | " | per-tenant |
| CRM | CRM | OFF (internal orgs) | n/a (platform-side) | platform | GA | additive | platform flag | global |
| Analytics | Analytics | ON | inherit | org admin | GA | on = current | " | per-tenant |
| AI | Recommendation/AI adapters | OFF | inherit | org admin + capability | canary→staged | additive, opt-in | `capable(AI) && flag && rollout%` | per-tenant, short TTL |
| Certificates | Certification | ON | inherit | org admin | GA | on = current | " | per-tenant |
| SCORM | Instructor/Media Platform | OFF | inherit | org admin | staged | additive | " | per-tenant |
| API | Organization | template-dependent | inherit | org admin | GA | additive | `capable(API) && flag` | per-tenant |
| White Label | Organization/Marketing | OFF | reseller→sub inherit | reseller/owner | contract | additive | `capable(WhiteLabel) && flag` | per-tenant + domain |
| Marketplace | Catalog/Commerce | OFF | inherit | platform + org | staged | additive | " | per-tenant |
| Integrations | Organization | template-dependent | inherit | org admin | staged | additive | per-integration flag | per-tenant |

**Evaluation strategy:** a single `FeatureFlagEvaluator` resolves `isEnabled(orgId, feature)` = capability check → org override → parent inheritance → template default → global default, then intersect with rollout cohort. **Inheritance:** child orgs inherit the parent's flags unless explicitly overridden (holding/reseller). **Rollout:** percentage/cohort/canary with a kill-switch that bypasses cache. **Caching:** resolved flags cached per tenant (`org:{id}:flags`) with event-driven invalidation on `OrgSettingsChanged`/`LicenseGranted`/`CapabilityChanged`; AI/experimental flags use short TTL. **Backward compatibility:** every new flag defaults to the value that preserves current behavior; removing a feature = flag OFF, never a code path deletion.

---

## CRM Relationship Graph

Model relationships as a **typed, directed graph** over CRM nodes (Account, Contact) and buying/ecosystem roles. A `RelationshipEdge` is a value object: `{ fromRef, toRef, type, direction, strength, since, until?, ownerId, visibility }`. Nodes remain owned by their aggregates (Account/Contact); edges are CRM-owned graph facts (not a shared concern with Organization).

| Relationship / Role | Direction | Strength | Lifecycle | Ownership | Visibility | Searchability | Analytics usage | Future AI usage |
|---------------------|-----------|----------|-----------|-----------|------------|---------------|-----------------|-----------------|
| **Account ↔ Contact** (works-at) | account→contact | n/a | contact tenure | CRM Account | account team | yes (by name/email) | contacts per account | entity resolution |
| **Decision Maker** | contact→deal | high | per deal | CRM (deal role) | deal team | yes | win-rate by DM engagement | next-best-action |
| **Influencer** | contact→deal | medium | per deal | CRM | deal team | yes | influence mapping | stakeholder scoring |
| **Champion** | contact→deal | high | evolves (identified→lost) | CRM | deal team | yes | champion presence vs win-rate | champion risk alerts |
| **Technical Buyer** | contact→deal | medium-high | per deal | CRM | deal team | yes | tech-eval stage timing | fit prediction |
| **Economic Buyer** | contact→deal | critical | per deal | CRM | deal team | yes | approval cycle time | budget/authority inference |
| **Partner** | account↔account | medium | ongoing | CRM (may reference Organization if partner is a tenant) | sales + partner mgr | yes | partner-sourced pipeline | partner recommendation |
| **Supplier** | account→account | low-med | ongoing | CRM | account team | limited | supplier dependency | risk mapping |
| **Competitor** | deal↔competitor | contextual | per deal | CRM | sales | yes | win/loss vs competitor | competitive-move alerts |
| **Consultant** | contact/account→deal | medium | engagement window | CRM (links to consulting delivery) | deal + CS | yes | consulting attach rate | delivery-risk scoring |
| **Investor** | account→account | low | ongoing | CRM | leadership | restricted | funding signals | intent/timing signals |

Rules: **direction** and **strength** are explicit on the edge; **lifecycle** transitions (e.g., champion identified→confirmed→departed) emit `RelationshipChanged`; **ownership** is CRM (edges never leak to Organization); **visibility** follows sales-team/owner scoping; **searchability** indexes node attributes + edge type; **analytics** consumes edge events as DTOs; **future AI** (scoring, conversation intelligence, next-best-action) reads the graph via the read side + a `RelationshipGraphProvider` port — the graph store can later become a dedicated graph/vector DB behind that port without changing CRM.

---

## Organization Capability Model

**Principle (refinement):** **`OrganizationType` becomes only a template selector; actual runtime functionality is granted by a Capability Set.** Two identically-typed orgs can have different capabilities; a template merely provides the *initial* capability set.

**CapabilitySet** = the set of granted `Capability`s for an org. Each `Capability` is an independent, named, versioned grant:

`CanSellCourses · CanIssueCertificates · CanHostLiveEvents · CanCreateOrganizations · CanManageBrands · CanUseMarketplace · CanUseAI · CanAccessAnalytics · CanUseSCORM · CanUseSSO · CanManageDomains · CanUseAPI · CanCreateCustomRoles · CanCreateCustomReports · CanUseAutomation`.

| Property | Design |
|----------|--------|
| **Independent** | each capability is orthogonal; granting one never implies another (explicit composition) |
| **Assignable** | granted via `GrantCapability(orgId, capability, source)` where source ∈ {template, plan/license (Commerce), manual (platform admin), inherited} |
| **Inheritable** | child orgs inherit parent capabilities unless revoked; reseller may grant a subset to managed sub-orgs (never more than the reseller holds) |
| **Auditable** | every grant/revoke writes an `OrganizationAuditEntry` (who, when, source, capability@version) and emits `CapabilityGranted`/`CapabilityRevoked` |
| **Versioned** | capabilities are semver'd (`CanUseAI@v2` may add scope); an org pins the granted version; upgrades are explicit, additive-safe |
| **Future-proof** | new capabilities are additive; unknown capability = denied by default; no capability is ever implicitly assumed |

**Grant source precedence:** manual (platform) > license/plan (Commerce) > template (provisioning) > inherited (parent). Revoking a license revokes its capabilities unless a manual grant overrides.

**Evaluation:** `can(orgId, Capability)` = capability present in the resolved set (with inheritance) AND not expired. Feature Flags (above) gate the *enabled* state on top of `can(...)`. A feature is usable ⇔ `can(capability) && flagEnabled && rolloutIncludes(org)`.

**Relationship to permissions:** Capabilities are **tenant-level entitlements** ("this org may issue certificates"); **permissions** (Phase 2) are **user-level authorizations within an org** ("this member may manage members"). A user action requires **both** the org capability and the user permission.

**Consumers of capability events:** Commerce (`CanSellCourses`/`CanUseMarketplace` gate storefront), Certification (`CanIssueCertificates`), Live (`CanHostLiveEvents`), Analytics (`CanAccessAnalytics`), AI adapters (`CanUseAI`), Identity (`CanUseSSO`/`CanManageDomains`), Organization itself (`CanCreateOrganizations`/`CanCreateCustomRoles`/`CanManageBrands`) — all via `CapabilityGranted`/`CapabilityRevoked` DTO events; none read Organization internals.

---

## Multi-tenancy Strategy

- **Tenant = Organization.** Every org-owned entity carries `organizationId`/`TenantId`; a **global scope** (trait `BelongsToOrganization`) constrains all queries by default with an explicit admin/system escape hatch — this operationalizes the audit 09/TEN-1 fix as a boundary invariant.
- **CRM is cross-tenant** by nature (sales sees many prospects) but scoped by **sales-team/owner visibility**, not by tenant; a CRM Account links to at most one Organization when won.
- **Isolation guarantees:** no cross-org read without explicit system context; tenant-scoped cache keys; per-tenant API keys/SSO/domains; tenant-scoped audit trail.
- **Hierarchy:** parent orgs may be granted rollup/visibility over children via policy; default is isolation.
- **Data residency (gov/edu):** region policy on Organization can pin storage/processing region (future, via Organization policy + infra routing).

---

## Migration Strategy (no code in this phase)

Matches backend refactor **chunk C11** (`Crm` → `Organization` + `Crm`), executed as its own gated step (same mechanism as 5E):
1. **Physically split** `App\Domains\Crm` into `App\Contexts\Organization` (Organization, OrganizationMember, Department, Team, TeamMember, SeatPool, SeatAssignment, BillingProfile, ConsultingRequest + their services/policies/events) and `App\Contexts\Crm` (Company/Account, Contact, Pipeline, Stage, Lead, Opportunity, ConsultingProject/Session, Activity/Note/Task/Tag, timeline). **Tables unchanged** (rename namespaces/folders only) → zero schema impact.
2. **Introduce a new `OrganizationServiceProvider`**; move org policies/routes/registrations out of `CrmServiceProvider` (bootstrap/providers.php gains one provider).
3. **Assign the timeline concerns to CRM**; introduce `OrganizationAuditEntry` for Organization (new, additive) — do not share the polymorphic concern.
4. **Publish contracts** `OrganizationDirectory` and `CrmContext`; migrate any cross-references to ids via these contracts.
5. **Wire the seam events** (`DealWon`→provision, `OrganizationCreated`→link Account, `ConsultingRequested`→create project, `OrderPaid(seats)`→credit pool) as event subscribers — additive, no behavior change to existing flows.
6. **Filament:** add two map lines to `RESOURCE_PATHS` (Organization, Crm); split navigation groups. No branches.
7. **Deptrac** rules enforcing "Organization ⊥ CRM."
8. Add the **global tenant scope** (audit 09/TEN-1) as part of the Organization extraction.

Each step: independently shippable; verified by `php artisan test` + `route:list` (URIs unchanged) + `/admin` resources visible; no schema, URL, or business-logic change.

---

## Acceptance Criteria

- **AC1 (two contexts):** `App\Contexts\Organization` and `App\Contexts\Crm` exist; no file references the other's models (Deptrac green).
- **AC2 (tenant vs account):** Organization (tenant) and CRM Account (sales record) are distinct aggregates; an Account references an Organization by id only when linked/won.
- **AC3 (timeline ownership):** activities/notes/tasks/tags belong to CRM only; Organization uses its own `OrganizationAuditEntry`.
- **AC4 (consulting split):** `ConsultingRequest` is Organization-owned; `ConsultingProject`/`Session` are CRM-owned; they communicate via events.
- **AC5 (events are DTOs):** every Organization/CRM event carries ids/primitives; Analytics/Notifications consume without importing either context.
- **AC6 (contracts only):** cross-context interaction is via `OrganizationDirectory`/`CrmContext` + events; grep shows no cross-context model imports.
- **AC7 (tenant isolation):** all Organization-owned queries are tenant-scoped by default; cross-org access is denied without explicit system context (audit 09/TEN-1 satisfied; isolation tests pass).
- **AC8 (seats chain):** `OrderPaid(seats)` → Organization credits SeatPool → Learning consumes entitlement; the chain is event-driven, no direct coupling.
- **AC9 (Filament):** Organization and CRM resources are discovered via the data map (two lines added); no conditional discovery branches added.
- **AC10 (money boundary):** Organization holds `billingRef` only; CRM's `dealValue` is informational; Commerce owns all authoritative money.
- **AC11 (multi-tenant future):** parent/child, merge, split, white-label, reseller are expressible as facets/commands without changing existing required inputs.
- **AC12 (no behavior/URL/DB change on migration):** `route:list` URIs identical, `php artisan test` green, `/admin` resources visible, schema untouched at every step.
- **AC13 (traceability):** every current `Crm` artifact (21 models, services, events, Filament, policies) maps to a target entity/command/event/read-model in Organization or CRM here.
