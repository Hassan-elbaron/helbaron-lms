# HElbaron LMS — Domain Map & Bounded-Context Redesign (Refactor 01)

**Role:** Principal Domain Architect / Product Refactoring Lead.
**Type:** Structural refactoring — **documentation only**. No code, no UI redesign, no business-logic change, no schema change (unless explicitly noted as unavoidable).
**Grounding:** Based on the full-project inspection captured in audits 01–10 (10 backend domains, 46 web routes, 24 Filament resources, 9 route groups).
**Goal:** Collapse the current sprawl into **8 product bounded contexts** + an explicit **shared/platform layer**, so every route, page, and feature has exactly **one** owner.

---

## 1. Method & A Necessary Clarification

The brief asks for **exactly 8 bounded contexts**: Marketing Website, Learning, Commerce, Instructor, Organization, CRM, Analytics, Administration.

Two of the current domains — **Identity** (auth, users, roles, MFA, OTP, devices) and **Notifications** (delivery of email/SMS/push/in-app) — are **cross-cutting infrastructure**, not product experiences. In DDD terms they are a **Shared Kernel** (Identity) and a **Supporting/Generic Subdomain** (Notifications). Forcing "login" or "send email" to live inside "Marketing" or "Learning" would recreate the exact feature-leakage this refactor removes.

**Decision:** the 8 contexts are the **product-facing** bounded contexts. Identity, Notifications, and Shared form a **Platform layer** beneath them that every context may depend on through published contracts. This keeps the "one owner per route/page/feature" rule intact for all *product* surfaces while being architecturally honest. Every user-facing **route/page** is assigned to exactly one of the 8; platform code is owned by the Platform layer and consumed via interfaces.

---

## 2. Current Domain Map (as-built)

### 2.1 Backend domains (`apps/api/app/Domains/*`)

| Domain | Responsibility (today) | Consumers |
|--------|------------------------|-----------|
| Identity | Auth, users, profiles, roles/permissions, MFA, OTP, devices, audit | ALL |
| Catalog | Courses, categories, levels, languages, tags, trainers (public read + admin write) | Marketing, Instructor, Admin, Learning |
| Authoring | Sections, lessons, media, curriculum, publish guard | Instructor, Admin |
| Learning | Enrollment, progress, bookmarks, notes, playback tokens | Learning |
| Commerce | Products, prices, cart, coupons, checkout, orders, invoices, contracts, payments | Commerce, Instructor(earnings), Admin |
| Certification | Certificate templates, issuance, badges, verification | Learning, Admin |
| Live | Live courses/sessions, registration, attendance, reminders | Learning, Instructor, Admin |
| Crm | Organizations, members, seats, leads, pipelines, consulting | Organization, CRM, Admin |
| Analytics | Metrics, snapshots, reports, dashboards, exports | Analytics, Admin |
| Notifications | Templates, channels, delivery, automation rules | ALL (supporting) |

### 2.2 Frontend route groups (`apps/web/src/app/*`)

`(marketing)` · `(public)` · `(auth)` · `(onboarding)` · `(student)` · `(org)` · `(crm)` · `(analytics)` · `(dashboard)` [dead] · `settings/theme` [misplaced].

### 2.3 Structural problems identified

| Problem | Evidence | Type |
|---------|----------|------|
| **Feature leakage — Catalog serves 4 contexts** | public browse + admin write + instructor's own courses + learning references | overlapping responsibility |
| **`(public)` is a dumping ground** | mixes marketing (courses/categories/trainers/service pages/legal) with authenticated commerce (cart/checkout/orders/contracts) and learning (learn/lessons) | unnecessary route group / mixed contexts |
| **`(dashboard)` dead group** | layout with no page | dead module |
| **`settings/theme` misplaced** | internal brand tool exposed in public nav | misplaced module |
| **`public-header.tsx` orphan** | defined, never imported | dead module |
| **Instructor has no home** | Authoring/Live instructor features have no frontend context | orphan responsibility |
| **Organization vs CRM entangled in one `Crm` domain** | org/seats/members + leads/pipeline/consulting in a single domain | modules that should split |
| **Identity/Notifications treated as peers of product domains** | flat `Domains/*` | misplaced (should be platform) |
| **Authoring is admin-API-only** | no owning product context | orphan responsibility → Instructor |
| **`(student)` conflates learning + account** | dashboard/my-learning/certificates (learning) + profile/notifications (account) | mild overlap |

---

## 3. Proposed Domain Map — 8 Bounded Contexts + Platform

```
┌───────────────────────────── PRODUCT BOUNDED CONTEXTS ─────────────────────────────┐
│ 1 Marketing Website   2 Learning     3 Commerce     4 Instructor                    │
│ 5 Organization        6 CRM          7 Analytics    8 Administration                │
└──────────────────────────────────────────────────────────────────────────────────┘
        depend (via published contracts) on ↓
┌───────────────────────────── PLATFORM (shared/supporting) ─────────────────────────┐
│ Identity/Access (kernel)   Notifications (supporting)   Shared (base/kernel utils)  │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### 3.1 Context definitions, responsibilities, ownership

**1 — Marketing Website** *(public, unauthenticated acquisition)*
- **Owns:** landing, public course catalog & course detail (read), categories, public trainer/instructor listing, product listing (read), service pages (cohorts/workshops/enterprise/advisory), legal (privacy/terms), and the **auth entry funnel** (login/register/forgot/reset/verify-email/mfa) as the acquisition→activation gateway.
- **Backend ownership:** *read models* published by Catalog + Commerce (product), and the auth surface of Identity. Marketing **owns no write model** — it consumes read projections + Identity auth.
- **Team owner:** Growth/Marketing + Web.

**2 — Learning** *(authenticated learner experience)*
- **Owns:** learner dashboard, my-learning, continue-learning, course player, lesson player, live-session **join/attendance (learner side)**, certificates (learner view), the learner's **notification center** and **account/profile** self-service.
- **Backend ownership:** Learning domain (enrollment/progress/playback); consumes Certification (issue/verify), Live (join), Catalog (course read), Notifications (inbox).
- **Team owner:** Learning squad.

**3 — Commerce** *(money & entitlements)*
- **Owns:** cart, checkout (+ success/failed), orders, invoices, contracts, coupons, refunds, payment webhooks, entitlement/fulfillment gating.
- **Backend ownership:** Commerce domain.
- **Team owner:** Commerce squad.

**4 — Instructor** *(create & operate teaching)* — **NEW context**
- **Owns:** teach dashboard, course authoring (curriculum/sections/lessons/media), the instructor's **own** course management, live-session **scheduling/management (instructor side)**, students roster, earnings (read from Commerce), instructor onboarding/application.
- **Backend ownership:** Authoring domain (write); instructor-scoped slice of Catalog (own courses) and Live (own sessions); read of Commerce (earnings) + Learning (progress of own students).
- **Team owner:** Creator/Instructor squad.

**5 — Organization** *(B2B account & seats)*
- **Owns:** org dashboard, organizations directory, org detail, member invite/management, seat pools/assignments, org-level consulting requests.
- **Backend ownership:** the **Organization** slice split out of today's `Crm` domain (organizations, members, seats, org billing profile).
- **Team owner:** B2B/Enterprise squad.

**6 — CRM** *(internal sales & support)*
- **Owns:** CRM dashboard, leads & pipeline, lead detail, consulting pipeline, company/contact accounts, activities/notes/tasks/tags.
- **Backend ownership:** the **CRM** slice split out of today's `Crm` domain (leads, pipelines, opportunities, consulting delivery, timeline).
- **Team owner:** RevOps/Support squad.

**7 — Analytics** *(insight & reporting)*
- **Owns:** analytics dashboards, KPIs, reports, report detail, saved dashboards, exports.
- **Backend ownership:** Analytics domain (read models fed by domain events).
- **Team owner:** Data squad.

**8 — Administration** *(platform operation)*
- **Owns:** the Filament `/admin` panel and all back-office management: user/role administration, catalog & pricing management, certificate templates, notification templates/automation, **content management** (homepage sections, landing pages, brand/theme, SEO) — including retiring `settings/theme` into admin.
- **Backend ownership:** admin-facing Filament resources across all domains + Identity administration + a new **Content** supporting area (homepage/landing/brand/SEO models).
- **Team owner:** Platform/Admin squad.

**Platform — Identity/Access (Shared Kernel):** users, authentication (Sanctum, cookie sessions), roles/permissions/policies, MFA, OTP, devices, audit log. Consumed by all; owns the canonical `User` and authorization primitives.

**Platform — Notifications (Supporting):** template rendering, channels (email/SMS/push/in-app), delivery jobs, automation rules. Other contexts **publish events**; Notifications reacts. Owns no product route (the learner-facing inbox page lives in **Learning**; Notifications provides the data via contract).

**Platform — Shared:** base Action/Service/Resource/Exception, ApiResponse envelope, value objects, helpers, correlation/logging.

### 3.2 One-owner assignment matrix (product surfaces)

| Surface / feature | Owning context | Notes (no second owner) |
|-------------------|----------------|-------------------------|
| Landing, service pages, legal | Marketing | — |
| Public course catalog & detail (read) | Marketing | reads Catalog projection |
| Auth (login/register/reset/verify/mfa) | Marketing | uses Identity kernel |
| Learner dashboard/my-learning/continue | Learning | — |
| Course/lesson player | Learning | — |
| Certificates (learner view) | Learning | reads Certification |
| Live session join/attendance | Learning | learner side only |
| Learner notification center | Learning | reads Notifications |
| Account/profile self-service | Learning | (learner's home) |
| Cart/checkout/orders/contracts/coupons | Commerce | — |
| Teach dashboard/authoring/sessions/students/earnings | Instructor | — |
| Instructor application/onboarding | Instructor | — |
| Org dashboard/members/seats/org-consulting | Organization | — |
| Leads/pipeline/consulting-delivery/accounts | CRM | — |
| Analytics/reports/dashboards/exports | Analytics | — |
| `/admin` panel + content/brand/SEO management | Administration | — |
| Users/roles/MFA/OTP/devices/audit | Platform: Identity | consumed by all |
| Templates/channels/delivery/automation | Platform: Notifications | consumed by all |

---

## 4. Context Boundaries (what crosses, what doesn't)

- **Marketing ↔ everything:** Marketing consumes **read projections only** (published course/product/instructor read models) + Identity auth. It never writes domain state.
- **Instructor → Catalog/Authoring:** Instructor is the **write** side of course content; Marketing/Learning are **read** sides. This removes today's Catalog 4-way leakage: Catalog becomes a **published-language** read model, written only by Instructor/Administration.
- **Commerce → Learning:** Commerce emits `OrderPaid`/`ContractAccepted`; Learning grants entitlements via a listener. Commerce does not call Learning directly beyond the published event/contract.
- **Learning → Certification:** `CourseCompleted` triggers certificate issuance (Certification is a supporting capability owned within Learning's boundary for the learner view; Administration owns template management).
- **Organization ⊥ CRM:** the current single `Crm` domain is **split**. Organization owns B2B account/seat/member state; CRM owns sales/support pipeline. They communicate via events (e.g., `OrganizationCreated` → CRM may create an account record) — never shared tables mutated by both.
- **Analytics ← all:** Analytics is a **read model** fed by domain events (it must **not** import other domains' concrete classes — this fixes the 6-domain coupling from audit 04). It depends on **event DTOs** only.
- **Administration → all:** Administration operates every context's admin surface **through each context's own services/policies**, not by reaching into tables — preserving each context's invariants.
- **Platform ← all:** every context depends on Identity/Notifications/Shared via **interfaces** (published contracts), never the reverse.

**Anti-corruption rule:** cross-context calls go through a per-context **public contract** (facade interface in `Contracts`), not concrete classes. A dependency-direction linter (Deptrac) encodes the allowed edges.

---

## 5. Cross-Context Communication

| From → To | Mechanism | Example |
|-----------|-----------|---------|
| Commerce → Learning | Domain event + listener | `OrderPaid` → grant enrollment |
| Commerce → Organization | Domain event | seat purchase → seat pool credit |
| Learning → Certification | Domain event | `CourseCompleted` → issue certificate |
| Instructor → Catalog | Command/service (write) | publish course → Catalog read model updates |
| Any → Notifications | Domain event | `UserEnrolled` → send welcome |
| All → Analytics | Domain event (DTO only) | `LessonCompleted` → metric snapshot |
| Organization ↔ CRM | Domain event | `OrganizationCreated` → CRM account |
| All → Identity | Interface call | `auth()->user()`, policy checks |
| Marketing ← Catalog/Commerce | Read model / API projection | public course JSON |

Synchronous reads use **published read models / API resources**; state changes propagate **asynchronously via events**. No context imports another context's Eloquent models directly.

---

## 6. Domain Dependency Graph (target)

```
                 ┌─────────────┐
                 │  Marketing  │  (read-only consumer)
                 └──────┬──────┘
        reads projections│           ┌──────────────┐
                 ┌───────▼───────┐   │  Instructor  │──writes──┐
                 │  Catalog RM   │◀──┤ (Authoring)  │          │
                 └───────────────┘   └──────┬───────┘          │
                                            │ own sessions      ▼
   ┌──────────┐   OrderPaid   ┌──────────┐  │            ┌───────────┐
   │ Commerce │──────────────▶│ Learning │◀─┘  Live join │  Live     │
   └────┬─────┘   entitlement └────┬─────┘◀─────────────│ (support) │
        │ seat credit              │ CourseCompleted     └───────────┘
        ▼                          ▼
  ┌──────────────┐          ┌──────────────┐
  │ Organization │◀──event─▶│ CRM          │      ┌───────────┐
  └──────────────┘          └──────────────┘      │ Analytics │◀── events (DTO) ── ALL
                                                   └───────────┘
  ┌──────────────────────── PLATFORM ────────────────────────┐
  │  Identity (kernel) ◀── all      Notifications ◀── events   │
  │  Shared (base utils) ◀── all                               │
  └───────────────────────────────────────────────────────────┘
  Administration ── operates every context via its services/policies
```

**Invariants:** the graph is a DAG; Platform has no outbound edges to product contexts; Analytics/Notifications are **sinks** (consume events, referenced by none for writes); Marketing is a **leaf consumer** (no writes).

---

## 7. Module Disposition Summary

| Current module | Disposition | Target context |
|----------------|-------------|----------------|
| Identity | Move to Platform (Shared Kernel) | Platform/Identity |
| Notifications | Move to Platform (Supporting) | Platform/Notifications |
| Shared | Keep as Platform | Platform/Shared |
| Catalog | Reframe as **read model** (published language); write via Instructor/Admin | Instructor (write) / Marketing+Learning (read) |
| Authoring | Adopt under Instructor | Instructor |
| Learning | Keep | Learning |
| Certification | Keep as supporting; learner view in Learning, templates in Admin | Learning (+Admin) |
| Live | Keep as supporting; join in Learning, manage in Instructor | Learning + Instructor |
| Commerce | Keep | Commerce |
| **Crm → split** | **Split into two** | Organization + CRM |
| Analytics | Keep (decouple to event DTOs) | Analytics |
| `(dashboard)` group | **Delete** (dead) | — |
| `settings/theme` | **Move** into Admin content | Administration |
| `public-header.tsx` | **Delete** (dead) | — |

**Net change:** 10 domains → **8 product contexts + 3 platform modules**, with the only true structural split being **Crm → Organization + CRM**, and the only new context being **Instructor** (backend already exists as Authoring/Live/Catalog slices — no new schema required; it is a re-composition, not a rebuild).

---

## 8. Guardrails Applied

- **Reduced complexity:** `(public)` dumping ground dissolved; dead `(dashboard)`/`public-header`/`settings/theme`-in-public removed.
- **Reduced coupling:** Catalog demoted to a read model; Analytics/Notifications consume event DTOs, not concrete classes; cross-context via contracts + Deptrac.
- **Scalability & multi-tenancy:** Organization split makes tenant/seat scoping a first-class boundary (aligns with the tenant-isolation fix in audit 09); each context can scale/extract independently along the DAG seams.
- **Kept Laravel DDD + Next.js App Router:** no framework change; contexts map to Next route groups and Laravel domain modules 1:1 (see Refactor 02 & 03).
- **No schema change required** for the re-composition; the Crm split is a **namespace/module** reorganization over existing tables (organizations/members/seats vs leads/pipelines) — data stays put.
