# HElbaron LMS — Product Director Review (01)

**Repository:** https://github.com/Hassan-elbaron/helbaron-lms
**Scope:** Full product structure audit (not code quality) — Next.js web app (`apps/web`) + Filament admin (`apps/api`).
**Date:** 2026-07-07
**Author role:** Product Director / LMS Product Owner
**Method:** Static inventory of every `page.tsx`, every route-group `layout.tsx`, `src/config/nav.ts`, `src/config/theme.ts`, header/footer components, and every Filament Resource in `apps/api`.

---

## 1. Executive Summary

HElbaron is a bilingual (AR/EN) LMS + commerce + CRM + live-training + certification platform with a Laravel 12 REST API, a Filament v4 admin panel, and a custom Next.js 15 storefront/app. The **backend domain coverage is broad and mature** (10 domains, ~24 Filament resources, full REST surface). The **web product layer, however, is only partially wired into a coherent product**: several roles from the intended model have no front-end at all, a number of navigation links point to pages that do not exist or that redirect, and a large part of the marketing surface (homepage, service/landing pages, brand identity, SEO) is **hardcoded in TypeScript with no admin control**, which contradicts the "admin-controllable product" goal.

The three highest-impact problems:

1. **Instructor has no product.** The role is listed in the intended model and exists in the API/admin (Authoring), but there is **zero instructor-facing web route** — no teach dashboard, no course authoring UI, no earnings, no session management. Instructors currently can only be operated on *by* an admin.
2. **Marketing + brand + SEO are not admin-controllable.** Homepage sections, the five service/landing pages (`/cohorts`, `/workshops`, `/enterprise`, `/advisory`), the brand identity, and all SEO metadata live in `src/config/theme.ts` and static page files. Editing any of them requires a developer + redeploy. There is no Filament resource for any of these.
3. **Broken and orphaned navigation.** `/settings` is linked from two nav menus but has no page (404). `/settings/theme` (an internal brand-preview tool) is exposed in the **public** header and footer. The `(dashboard)` route group is a dead layout with no page. `/continue-learning` exists but is in no menu. The public footer links to gated routes (`/org`, `/certificates`).

The product is **≈70% assembled**: the pieces exist, but the connective tissue (navigation model, role-based entry points, admin control of content) is incomplete. None of the issues below require UI redesign — they are structural wiring and missing-resource problems.

---

## 2. Critical Product Issues

| # | Severity | Issue | Actionable Fix |
|---|----------|-------|----------------|
| C1 | Critical | **Instructor role has no front-end.** No `/teach` area; authoring only exists inside `/admin`. | Build an `(instructor)` route group gated to `role=instructor` with: teach dashboard, my-courses, course/curriculum editor (wraps existing Authoring API), live sessions, students, earnings. |
| C2 | Critical | **`/settings` is a dead link (404).** Referenced in `dashboardNav` and `organizationNav` (`nav.ts`), no `settings/page.tsx` exists. | Create `/settings` account page (profile, security/MFA, notifications, language) OR repoint both nav items to `/profile`. Remove the broken entry from `organizationNav`. |
| C3 | Critical | **Internal brand tool exposed publicly.** `/settings/theme` is linked from the public header nav ("Brand") and footer ("Brand identity"). It is a developer preview of `theme.ts`, not a customer page. | Remove `/settings/theme` from public `theme.ts` nav + footer. Move it under an admin-only route, and replace it with a real DB-backed Brand/Theme Filament resource (see M-series). |
| C4 | High | **Homepage, service pages, brand, SEO are hardcoded** in `theme.ts` and static `page.tsx` files — no admin control, no CMS. | Introduce DB-backed content models + Filament resources: `HomepageSection`, `LandingPage`, `BrandSetting`, `SeoMeta`. Web reads them via API instead of importing `theme.ts`. |
| C5 | High | **Coarse RBAC — three intended roles collapse into `admin`.** Organization Manager, CRM/Support, and Finance/Commerce Admin all map to `roles=["admin","super_admin"]`. | Introduce dedicated Spatie roles (`org_manager`, `support_agent`, `finance_admin`) + policies, and gate `(org)`, `(crm)`, `(analytics)`/commerce areas per role instead of a single `admin`. |
| C6 | High | **Dead `(dashboard)` route group.** `(dashboard)/layout.tsx` exists with `RequireAuth` + `dashboardNav` but has **no page**; the real `/dashboard` resolves under `(student)`. | Delete the `(dashboard)` group, or move `/dashboard` into it and make it the shared authenticated shell. Remove unused `dashboardNav` or repurpose. |
| C7 | Medium | **Orphan page `/continue-learning`.** Exists and is functional but appears in no navigation. | Add to `studentNav` (or surface as a CTA on `/dashboard` and `/my-learning`). |
| C8 | Medium | **Public footer links to gated routes.** Footer → `/org` (admin-only) and `/certificates` (student-only) redirect anonymous users to login. "Become an instructor" → `/trainers` (a listing, not an onboarding page). | Repoint footer: `/org` → `/enterprise`; `/certificates` → a public "Certificates" explainer or remove; "Become an instructor" → new `/teach/apply` route. |
| C9 | Medium | **Inconsistent auth-guard placement.** `(student)/(org)/(crm)/(analytics)` guard at the layout; the eight authenticated pages under `(public)` (cart, checkout, orders, contracts, learn, lessons) guard **per-page** with `RequireAuth`. | Standardize: move authenticated commerce/learning pages into guarded groups (e.g. `(shop)`, `(learn)`) so guarding is consistent and cannot be forgotten. |
| C10 | Medium | **No unified post-login destination / role router.** `RequireGuest` redirects to `/`; there is no logic sending instructors, admins, and students to their respective homes. | Add a `/` (or `/home`) role-aware redirect: student→`/dashboard`, instructor→`/teach`, admin→`/admin` or `/analytics`. |

---

## 3. Route Inventory

Legend — **Auth:** Public / Guest-only / Auth / Auth+Role. **Admin-controllable:** can an admin change the page's content/behavior from `/admin` today? **Status:** keep / merge / remove / rebuild.

### 3.1 Marketing & Public (no auth)

| URL | Role | Purpose | Entry point | Next action | Auth | Nav placement | Admin-controllable | Status |
|-----|------|---------|-------------|-------------|------|---------------|--------------------|--------|
| `/` | Visitor | Landing / brand story | Direct, ads | Explore courses / register | Public | Root | ❌ hardcoded `theme.ts` | rebuild (CMS) |
| `/courses` | Visitor/Student | Course catalog + filters | Header nav | Open course | Public | Header | ✅ CourseResource | keep |
| `/courses/[public_id]` | Visitor/Student | Course detail | Catalog | Enroll / add to cart | Public | — | ✅ CourseResource | keep |
| `/categories` | Visitor | Browse by category | Header nav | Open category→courses | Public | Header | ✅ CategoryResource | keep |
| `/trainers` | Visitor | Trainer/instructor listing | Header/footer | Open trainer | Public | Header | ⚠️ UserResource only | keep + enrich |
| `/products` | Visitor | Purchasable products | Header nav | Add to cart | Public | Header | ✅ ProductResource | keep |
| `/cohorts` | Visitor | Live cohorts service page | Header nav | Contact/enroll | Public | Header | ❌ hardcoded | rebuild (CMS) |
| `/workshops` | Visitor | In-person workshops page | Header nav | Contact | Public | Header | ❌ hardcoded | rebuild (CMS) |
| `/enterprise` | Visitor | B2B/B2G training page | Header nav | Book demo | Public | Header | ❌ hardcoded | rebuild (CMS) |
| `/advisory` | Visitor | Consulting page | Header nav | Contact advisory | Public | Header | ❌ hardcoded | rebuild (CMS) |
| `/privacy` | Visitor | Legal | Footer | — | Public | Footer | ❌ hardcoded | keep (low priority) |
| `/terms` | Visitor | Legal | Footer | — | Public | Footer | ❌ hardcoded | keep (low priority) |
| `/settings/theme` | Developer | Brand/theme preview of `theme.ts` | Public "Brand" nav (!) | — | Public | Header+Footer | ❌ static | **rebuild** → admin-only Brand resource |

### 3.2 Authentication & Onboarding

| URL | Role | Purpose | Entry | Next action | Auth | Nav | Admin-ctrl | Status |
|-----|------|---------|-------|-------------|------|-----|-----------|--------|
| `/login` | Visitor | Sign in | Header CTA | → role home | Guest-only | Header | n/a | keep |
| `/register` | Visitor | Sign up | Header/CTA | → verify-email | Guest-only | Header/CTA | n/a | keep |
| `/forgot-password` | Visitor | Request reset | Login | → reset | Guest-only | — | n/a | keep |
| `/reset-password` | Visitor | Set new password | Email link | → login | Guest-only | — | n/a | keep |
| `/verify-email` | New user | OTP email verify | Post-register | → dashboard | Token | — | n/a | keep |
| `/mfa` | New user | MFA challenge/setup | Login when MFA on | → dashboard | Token | — | n/a | keep |

### 3.3 Student (Auth, `studentNav`)

| URL | Role | Purpose | Entry | Next action | Auth | Nav | Admin-ctrl | Status |
|-----|------|---------|-------|-------------|------|-----|-----------|--------|
| `/dashboard` | Student | Learner home | Post-login | Continue learning | Auth | studentNav | ⚠️ data only | keep |
| `/my-learning` | Student | Enrolled courses | Nav | Open course/learn | Auth | studentNav | ✅ EnrollmentResource | keep |
| `/continue-learning` | Student | Resume last lesson | (none) | Open lesson | Auth | **orphan** | ⚠️ data only | keep + add to nav |
| `/certificates` | Student | Earned certificates | Nav | View/verify | Auth | studentNav | ✅ CertificateResource | keep |
| `/notifications` | Student | Notification center | Nav/bell | Read/act | Auth | studentNav | ✅ NotificationResource | keep |
| `/profile` | Student | Profile & settings | Nav/user menu | Edit profile | Auth | studentNav | ⚠️ UserResource | keep |

### 3.4 Commerce & Learning (Auth via **page-level** `RequireAuth`, live under `(public)`)

| URL | Role | Purpose | Entry | Next action | Auth | Nav | Admin-ctrl | Status |
|-----|------|---------|-------|-------------|------|-----|-----------|--------|
| `/cart` | Student | Shopping cart | Header cart icon | Checkout | Auth (page) | Header icon | ✅ (orders) | keep → move to `(shop)` |
| `/checkout` | Student | Payment | Cart | Success/failed | Auth (page) | — | ⚠️ gateway config | keep → move to `(shop)` |
| `/checkout/success` | Student | Order confirmation | Checkout | Go to course | Auth (page) | — | — | keep |
| `/checkout/failed` | Student | Payment failed | Checkout | Retry | Auth (page) | — | — | keep |
| `/orders` | Student | Order history | User menu | View order | Auth (page) | **orphan-ish** | ✅ OrderResource | keep + add to nav |
| `/contracts` | Student/Org | Contract acceptance | Order/email | Accept | Auth (page) | **orphan-ish** | ✅ ContractTemplateResource | keep + add to nav |
| `/courses/[public_id]/learn` | Student | Course player shell | My-learning | Open lesson | Auth (page) | — | ⚠️ Authoring | keep → move to `(learn)` |
| `/lessons/[public_id]` | Student | Lesson player | Learn/curriculum | Next lesson | Auth (page) | — | ⚠️ Authoring | keep → move to `(learn)` |

### 3.5 Organization Portal (Auth+Role `admin`/`super_admin`, `organizationNav`)

| URL | Role | Purpose | Entry | Next action | Auth | Nav | Admin-ctrl | Status |
|-----|------|---------|-------|-------------|------|-----|-----------|--------|
| `/org` | Org Manager* | Org dashboard | Nav | Manage orgs | Auth+admin | organizationNav | ✅ OrganizationResource | keep → re-gate to `org_manager` |
| `/org/organizations` | Org Manager* | Organizations list | Nav | Open org | Auth+admin | organizationNav | ✅ | keep |
| `/org/organizations/[public_id]` | Org Manager* | Org detail + invite member | List | Invite/manage | Auth+admin | — | ✅ | keep |
| `/org/consulting` | Org Manager* | Consulting requests | Nav | Create request | Auth+admin | organizationNav | ✅ ConsultingRequestResource | keep |

\* Intended role is Organization Manager; currently gated to `admin` (see C5).

### 3.6 CRM / Support (Auth+Role `admin`/`super_admin`, `crmNav`)

| URL | Role | Purpose | Entry | Next action | Auth | Nav | Admin-ctrl | Status |
|-----|------|---------|-------|-------------|------|-----|-----------|--------|
| `/crm` | CRM/Support* | CRM dashboard | Nav | Triage | Auth+admin | crmNav | ✅ | keep → re-gate to `support_agent` |
| `/crm/leads` | CRM/Support* | Leads list + create | Nav | Open lead | Auth+admin | crmNav | ✅ LeadResource | keep |
| `/crm/leads/[public_id]` | CRM/Support* | Lead detail | List | Convert/move stage | Auth+admin | — | ✅ | keep |
| `/crm/consulting` | CRM/Support* | Consulting pipeline | Nav | Manage | Auth+admin | crmNav | ✅ | keep |
| `/crm/organizations` | CRM/Support* | Org accounts | Nav | Open account | Auth+admin | crmNav | ✅ | keep |

### 3.7 Analytics (Auth+Role `admin`/`super_admin`, `analyticsNav`)

| URL | Role | Purpose | Entry | Next action | Auth | Nav | Admin-ctrl | Status |
|-----|------|---------|-------|-------------|------|-----|-----------|--------|
| `/analytics` | Finance/Analytics* | KPI dashboard | Nav | Drill down | Auth+admin | analyticsNav | ✅ | keep |
| `/reports` | Finance/Analytics* | Report list | Nav | Run report | Auth+admin | analyticsNav | ✅ ReportDefinitionResource | keep |
| `/reports/[public_id]` | Finance/Analytics* | Report detail + export | List | Export CSV/XLSX | Auth+admin | — | ✅ ExportJobResource | keep |
| `/dashboards` | Finance/Analytics* | Saved dashboards | Nav | Open dashboard | Auth+admin | analyticsNav | ✅ DashboardResource | keep |

### 3.8 Dead / Structural

| Path | Purpose | Status |
|------|---------|--------|
| `(dashboard)/layout.tsx` | Authenticated shell w/ `dashboardNav` — **no page** | **remove or merge** |
| `(marketing)/layout.tsx` | Wraps root landing | keep |

---

## 4. Orphan Pages & Disconnected Flows

| # | Type | Item | Detail | Fix |
|---|------|------|--------|-----|
| O1 | Broken link | `/settings` | In `dashboardNav` + `organizationNav`; **no page** → 404 | Build `/settings` or repoint to `/profile`; delete from `organizationNav` |
| O2 | Misplaced page | `/settings/theme` | Internal brand tool linked in **public** header + footer | Remove from public nav; move to admin-only |
| O3 | Dead group | `(dashboard)` | Layout with no page; `dashboardNav` unused in practice | Delete group or host `/dashboard` there |
| O4 | Orphan page | `/continue-learning` | Functional, no nav entry | Add to `studentNav` + dashboard CTA |
| O5 | Orphan-ish | `/orders`, `/contracts` | Only reachable by deep link/user menu; not in a stable nav | Add to `studentNav` (Orders) + user menu |
| O6 | Gated from public | Footer `/org` | Admin-only route in public footer → login redirect | Repoint to `/enterprise` |
| O7 | Gated from public | Footer `/certificates` | Student-only route in public footer | Public explainer page or remove |
| O8 | Wrong target | Footer "Become an instructor" → `/trainers` | Listing page, not onboarding | New `/teach/apply` route |
| O9 | No-op | Footer `#lang` anchor | Does nothing | Wire to language toggle |
| O10 | Missing flow | Instructor journey | No route from "become instructor" → apply → approved → teach | Build `(instructor)` group + application flow |
| O11 | Missing flow | Post-login role routing | All users land on same guest→`/` logic | Role-aware redirect (C10) |

---

## 5. Missing Admin Controls

Existing Filament resources (24): Users; Category, Course; Section, Lesson; Enrollment; Product, Coupon, Order, ContractTemplate; Certificate, CertificateTemplate, Badge; LiveCourse, LiveSession; Lead, Organization, ConsultingRequest; Dashboard, ReportDefinition, ExportJob; Notification, NotificationTemplate, AutomationRule.

| Control area | Required | Exists today | Gap | Fix |
|--------------|----------|--------------|-----|-----|
| Homepage sections | Editable sections/order | ❌ hardcoded in `theme.ts` | Full | New `HomepageSection` model + Filament resource; web reads via API |
| Landing pages (`/cohorts`,`/workshops`,`/enterprise`,`/advisory`) | CMS-editable | ❌ static `page.tsx` | Full | `LandingPage` model + resource (slug, hero, blocks, CTA) |
| Course catalog | Manage courses/visibility/order | ✅ CourseResource | Minor (featured/order) | Add featured toggle + sort to resource |
| Course details | Full course edit | ✅ CourseResource | OK | keep |
| Instructors | Approve, profile, assign | ⚠️ generic UserResource | Major | `InstructorProfile` resource + approval workflow |
| Students | Manage learners | ⚠️ generic UserResource | Minor | Student-scoped view/filter + enrollment actions |
| Pricing | Manage prices/plans | ⚠️ inside ProductResource | Moderate | Dedicated price/plan management + subscription tiers |
| Coupons | CRUD, scope, limits | ✅ CouponResource | OK | keep |
| Certificates | Templates, issue, revoke | ✅ Certificate + Template + Badge | OK | keep |
| CRM | Leads, orgs, consulting | ✅ | OK | keep |
| Analytics | Dashboards, reports, exports | ✅ | OK | keep |
| Notifications | Templates, rules, send | ✅ | OK | keep |
| SEO | Per-page meta, OG, sitemap | ❌ none | Full | `SeoMeta` model + resource; `HasSeo` trait already on models — expose it |
| Theme & Brand identity | Colors, fonts, logo, announcement | ❌ static `theme.ts` | Full | `BrandSetting` singleton resource; web reads via API; retire `/settings/theme` static tool |

**Summary:** 6 of 14 required control areas have **no** admin control (homepage, landing pages, instructors[major], SEO, theme/brand, pricing[partial]). All are currently developer-edit-and-redeploy.

---

## 6. Product Journey Map (target model)

```
VISITOR
  /  →  /courses · /categories · /trainers · /cohorts · /workshops · /enterprise · /advisory
     →  /courses/[id]  →  [Add to cart | Enroll]  →  /register → /verify-email
                                                          │
STUDENT (role: student) ───────────────────────────────── ▼
  /dashboard → /my-learning → /courses/[id]/learn → /lessons/[id] → /certificates
            → /continue-learning        → /cart → /checkout → /checkout/success → /orders → /contracts
            → /notifications → /profile → /settings

INSTRUCTOR (role: instructor)  ← MISSING, to build
  /teach → /teach/courses → /teach/courses/[id]/edit (curriculum) → /teach/sessions → /teach/students → /teach/earnings
  Onboarding: /teach/apply → (admin approval) → /teach

ORG MANAGER (role: org_manager)      /org → /org/organizations → /org/organizations/[id] (invite) → /org/consulting
CRM / SUPPORT (role: support_agent)  /crm → /crm/leads → /crm/leads/[id] → /crm/consulting → /crm/organizations
FINANCE / COMMERCE (role: finance_admin)  /analytics → /reports → /reports/[id] · Commerce admin (orders/coupons/pricing)
PLATFORM ADMIN (role: admin/super_admin)  /admin (Filament: all 24 resources) + all above
```

**Broken edges to repair:** Visitor→"Become instructor" has no destination (O8/O10). Student→`/settings` 404 (O1/C2). Public→`/settings/theme` should not exist (O2/C3). No role-based redirect after login (O11/C10).

---

## 7. Required Fixes (prioritized, actionable)

**P0 — Blockers to a coherent product**

- F1 (C2/O1): Create `apps/web/src/app/(student)/settings/page.tsx` (account: profile, security/MFA, notifications, language) and repoint `dashboardNav`/`organizationNav` `/settings` to it. Delete broken `/settings` from `organizationNav` if not built.
- F2 (C3/O2): In `src/config/theme.ts` remove the `Brand → /settings/theme` nav item and the footer "Brand identity" link. Move `settings/theme` behind an admin-only guard.
- F3 (C6/O3): Delete `apps/web/src/app/(dashboard)/` (layout + unused `dashboardNav`) OR relocate `/dashboard` into it and consolidate the authenticated shell.
- F4 (C1/O10): Scaffold `(instructor)` route group gated `roles=["instructor"]` with routes `/teach`, `/teach/courses`, `/teach/courses/[id]/edit`, `/teach/sessions`, `/teach/students`, `/teach/earnings`, plus `/teach/apply`. Wire to existing Authoring + Live APIs.

**P1 — Navigation integrity**

- F5 (O4): Add `/continue-learning` to `studentNav`.
- F6 (O5): Add `Orders` (`/orders`) to `studentNav` and user menu; link `/contracts` from order detail.
- F7 (C8/O6–O9): Fix footer targets — `/org`→`/enterprise`, `/certificates`→public explainer/remove, "Become an instructor"→`/teach/apply`, wire `#lang` to the language toggle.
- F8 (C10/O11): Add role-aware post-login redirect (student→`/dashboard`, instructor→`/teach`, admin→`/analytics`).

**P2 — Admin controllability (content out of code)**

- F9 (C4): Add models + Filament resources `HomepageSection`, `LandingPage`; migrate `/`, `/cohorts`, `/workshops`, `/enterprise`, `/advisory` to read from API.
- F10 (C4): Add `BrandSetting` singleton resource (colors, fonts, logo, announcement); web fetches theme via API; retire static `theme.ts` brand tool.
- F11: Add `SeoMeta` resource; expose the existing `HasSeo` trait fields per page/course/category; generate sitemap/robots.
- F12: Add `InstructorProfile` resource + approval workflow; dedicated Student view/filter on Users; dedicated pricing/plan management.

**P3 — RBAC granularity**

- F13 (C5): Add Spatie roles `org_manager`, `support_agent`, `finance_admin` + policies; change `(org)`/`(crm)`/`(analytics)` guards from `["admin","super_admin"]` to the correct role each.
- F14 (C9): Standardize auth guards — introduce `(shop)` and `(learn)` guarded groups; move cart/checkout/orders/contracts/learn/lessons out of `(public)`.

---

## 8. Acceptance Criteria

**Navigation integrity**
- AC1: Every item in `nav.ts`, `theme.ts` nav, header, and footer resolves to an existing route (no 404). Automated: a link-check test crawls all `href`s and asserts each maps to a `page.tsx`.
- AC2: No route is reachable only by deep link — every non-detail page is linked from at least one menu, CTA, or parent page. Detail pages (`[public_id]`) are exempt.
- AC3: `/settings/theme` is not present in any public (unauthenticated) navigation.
- AC4: The `(dashboard)` group either has a page or is deleted; no empty route groups remain.

**Roles & journeys**
- AC5: Each of the 7 roles (Visitor, Student, Instructor, Org Manager, CRM/Support, Finance/Commerce, Admin) has a defined entry route and at least one complete journey per §6.
- AC6: An account with `role=instructor` can log in and reach a functional `/teach` home; no instructor is forced into `/admin`.
- AC7: After login, users are redirected to their role home (student→`/dashboard`, instructor→`/teach`, admin→`/analytics`).
- AC8: `(org)`, `(crm)`, `(analytics)` are each gated to their dedicated role, not a single `admin` role.

**Admin controllability**
- AC9: Homepage sections, the four service/landing pages, brand identity, and SEO meta are all editable from `/admin` and reflected on the site **without a code deploy**.
- AC10: The 14 control areas in §5 each have a corresponding Filament resource or documented, intentional exclusion.

**Auth safety**
- AC11: Every authenticated page enforces auth at the route-group layout (no page-only guarding that can be omitted).
- AC12: No gated route is linked from public surfaces in a way that dead-ends anonymous users.

**Traceability**
- AC13: Every issue in §2 and §4 has a mapped fix in §7 and a verifiable criterion here. (Satisfied: C1–C10, O1–O11 → F1–F14.)

---

### Appendix A — Counts
- Web routes (page.tsx): **44** across 9 route groups + 1 standalone (`settings/theme`).
- Route-group layouts: **9** (1 dead: `(dashboard)`).
- Filament admin resources: **24** across 10 domains.
- Roles intended: **7**; roles implemented in Spatie: **4** (`super_admin`, `admin`, `instructor`, `student`).
- Required admin control areas: **14**; with no control today: **6**.
