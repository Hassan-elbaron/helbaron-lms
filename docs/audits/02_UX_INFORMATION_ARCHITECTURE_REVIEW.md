# HElbaron LMS — UX & Information Architecture Review (02)

**Repository:** local working copy (`apps/web`, `apps/api`).
**Scope:** UX, IA, usability, navigation, product flow ONLY. No backend logic, DB, or code-quality review.
**Assumes:** Product Director Review (01) is complete; not repeated here.
**Method:** Direct inspection of all 46 `page.tsx`, 9 route-group layouts, `nav.ts`, `theme.ts`, all three header components, sidebar, topbar, user-menu, breadcrumb, page-transition, and the shared state components (`QueryState`, `EmptyState`, `ErrorState`, `LoadingState`, `ErrorBoundary`).
**Benchmark bar:** Coursera, Udemy Business, Thinkific, Circle, Kajabi, Canvas LMS.

---

## Executive Summary

HElbaron's building blocks are solid: a shared `QueryState` wrapper gives **consistent loading/error/empty handling on 25 of 46 pages**, motion is centralized and respects reduced-motion, and the storefront and landing now share one chrome. But the platform **feels disconnected because its information architecture is split into two unbridged worlds** and its shared navigation surfaces are under-built.

The two worlds are (1) the **marketing/storefront** chrome (`LandingHeader` + footer, no sidebar) covering `/`, catalog, service pages, cart, checkout; and (2) the **authenticated app** chrome (`AppShell`: sidebar + topbar) covering student, org, CRM, analytics. There is no consistent bridge between them: an authenticated learner in `/dashboard` has no header link back to the catalog, and a shopper in `/products` has **no cart icon** in the active header (the header that had one, `PublicHeader`, is dead code and unused).

The five most damaging UX/IA problems:

1. **Mobile navigation is broken on the entire public + storefront surface.** `LandingHeader`'s nav is `hidden … lg:flex` with **no hamburger/drawer fallback**. On phones and tablets, Courses, Cohorts, Workshops, Enterprise, Consulting, and Brand are all unreachable — only the logo, language, theme, and sign-in remain.
2. **Breadcrumbs are never rendered.** A `Breadcrumb` component exists but has **0 usages**. Every deep page (course detail, lesson, lead detail, org detail, report detail) drops the user with no trail and inconsistent back navigation (only 4 pages implement a manual back link).
3. **The user menu's "Settings" item is a dead no-op** (no `onSelect`, no `href`) and the menu lacks links to Profile, Orders, and Notifications — so the primary account hub does almost nothing.
4. **No global entry points for cart, notifications, or search.** No cart badge, no notification bell, no search anywhere — all three are baseline in every benchmark LMS.
5. **Core learning UX is missing: no quiz and no assignment pages exist at all.** The learning journey ends at video/lesson content; there is no assessment, which every benchmark (Coursera/Udemy/Canvas) treats as core.

None of these require a visual redesign — they are IA wiring, missing shared components, and responsive fixes.

---

## UX Score

**5.4 / 10** — "functional per-page, disconnected as a product."

| Dimension | Score | Note |
|-----------|-------|------|
| Per-page state handling | 8/10 | `QueryState` gives consistent loading/error/empty on most data pages |
| Navigation completeness | 4/10 | dead menu items, no search, no bell, no cart badge, mobile nav broken |
| Cross-area connectivity | 3/10 | marketing ↔ app ↔ admin are siloed |
| Wayfinding (breadcrumbs/back/titles) | 3/10 | breadcrumbs unused; back nav inconsistent |
| Learning experience depth | 4/10 | no quiz/assignment; player exists |
| Feedback & affordances | 6/10 | toasts/skeletons exist; empties often lack CTAs |

## IA Score

**5.0 / 10** — "logical within silos, incoherent across them."

| Dimension | Score | Note |
|-----------|-------|------|
| Page hierarchy | 6/10 | route groups are clean; detail pages exist |
| Navigation hierarchy | 4/10 | flat sidebars, no grouping/sections |
| Discoverability | 4/10 | service pages vanish once authenticated; features hidden |
| Logical grouping | 5/10 | commerce/learning pages live under `(public)` not a guarded group |
| Menu organization | 4/10 | 3 nav sources (`nav.ts`, `theme.ts`, footer) drift apart |
| Role → entry-point clarity | 4/10 | no role-aware home; instructor has no IA at all |

---

## Navigation Problems

| # | Severity | Problem | Evidence | Why it's a problem | Recommended solution | Expected impact |
|---|----------|---------|----------|--------------------|----------------------|-----------------|
| N1 | Critical | Public/storefront nav hidden on mobile with no fallback | `landing-header.tsx`: `<nav className="hidden … lg:flex">`, no menu button | Phones/tablets lose all primary nav; catalog undiscoverable on the most common device | Add a mobile hamburger + `Drawer` to `LandingHeader` mirroring `AppShell` | Restores navigation for ~50%+ of traffic |
| N2 | Critical | Breadcrumbs never rendered | `grep breadcrumb` → 0 usages outside `ui/breadcrumb.tsx` | No wayfinding on deep pages; users can't understand where they are or climb the hierarchy | Add a `Breadcrumbs` slot in `AppShell` topbar + on storefront detail pages, driven by route | Faster wayfinding; fewer dead-ends |
| N3 | High | "Settings" menu item is a dead no-op | `user-menu.tsx`: `<DropdownMenuItem>` for settings has no `onSelect`/`href` | Clicking the account's main action does nothing — feels broken | Link it to `/settings` (or `/profile`); add Profile, Orders, Notifications, Dashboard items | Functional account hub |
| N4 | High | No cart entry point in the active header | `(public)/layout.tsx` uses `LandingHeader` (no cart); `PublicHeader` (has cart) is unused dead code | On `/products` a user can add to cart but cannot see/open it except by typing `/cart` | Add cart icon + item-count badge to `LandingHeader`; delete dead `PublicHeader` | Recoverable checkout funnel |
| N5 | High | No notifications bell anywhere | `topbar.tsx` has only lang/theme/user; `/notifications` reachable only via sidebar | Notifications are invisible; the page is effectively orphaned from daily use | Add a bell with unread badge in `Topbar` (and storefront header for authed users) | Engagement, re-entry |
| N6 | High | No global search | No search component in any header/shell | Every benchmark LMS has course/content search; discovery relies on manual nav | Add a search field to `Topbar` and `LandingHeader` (course/catalog search) | Discovery, task speed |
| N7 | Medium | Three nav sources drift | `nav.ts` (sidebars), `theme.ts.nav` (landing), footer arrays | Links diverge (`/settings` 404, service pages only in landing) | Centralize nav in one typed module consumed by all chromes | Consistency, fewer 404s |
| N8 | Medium | Flat sidebars, no grouping | `sidebar.tsx` renders a single flat list | As sections grow (org, billing, settings) a flat list won't scale; poor scanability | Introduce section groups/labels in `NavItem[]` (e.g. "Learn", "Account") | Scalable IA |
| N9 | Medium | Brand wordmark in sidebar is not a link | `sidebar.tsx`: brand is a `<div>`, not `<Link href="/">` | Users expect logo→home; missing affordance | Wrap brand in a `Link` to the role home | Standard affordance |
| N10 | Medium | Inconsistent back navigation | Back link only in 4 detail pages (`courses/[id]`, `crm/leads/[id]`, `org/…/[id]`, `reports/[id]`); lesson player has none | Users get stranded on lesson/detail pages | Standardize breadcrumb + back affordance across all `[public_id]` pages | Predictable exits |
| N11 | Medium | Service pages disappear when authenticated | `theme.ts.nav` (Cohorts/Workshops/Enterprise/Advisory) not present in any `AppShell` nav | Logged-in users can't rediscover cohorts/workshops/advisory | Add a "Explore" or catalog link from `AppShell` topbar back to storefront | Cross-sell, connectivity |
| N12 | Low | Dead component `PublicHeader` | Defined, `grep` shows no import | Confusing for maintainers; risk of divergent headers | Delete it | Cleaner IA |

---

## User Journey Problems

Severity key: 🔴 critical, 🟠 high, 🟡 medium.

| Journey | Friction / break | Evidence | Fix |
|---------|------------------|----------|-----|
| Visitor | On mobile cannot browse anything but `/` | N1 | Mobile nav (N1) |
| Registration | `/register` → `/verify-email`; OK, but no progress indicator across register→verify→onboarding | onboarding group has no stepper | Add a 3-step onboarding progress header |
| Login | 🟠 No role-aware redirect; everyone → `/` then must self-navigate | `RequireGuest redirectTo="/"` | Redirect by role (student→`/dashboard`, instructor→`/teach`, admin→`/analytics`) |
| Forgot password | `/forgot-password` → email → `/reset-password` → login; OK | guards present | Add explicit "check your inbox" confirmation screen if absent |
| Email verification | OK but dead-ends on success without a "continue" CTA to dashboard | onboarding pages | Add primary CTA → role home |
| Student onboarding | 🟠 No onboarding at all (no goals, interests, first-course prompt) | no onboarding route beyond verify/mfa | Add a lightweight first-run onboarding (pick interests → recommended courses) |
| Browse courses | OK (filters/pagination) but no search (N6) and no saved/wishlist | `/courses` | Add search + wishlist |
| Course details | 🟡 Back link present; but no breadcrumb, no "what you'll learn/curriculum preview → enroll" sticky CTA on mobile | `courses/[id]` | Breadcrumb + sticky enroll CTA |
| Enrollment | 🟠 Enroll vs Add-to-cart ambiguity (two paths, no guidance) | catalog cards + product pages | Clarify: free→Enroll, paid→Add to cart; single primary CTA per course |
| Checkout | 🟠 Cart not reachable from header (N4); no visible steps (cart→checkout→success) | `(public)` chrome | Cart badge + checkout step indicator |
| Continue learning | 🟡 `/continue-learning` is orphaned (in no menu) | nav.ts `studentNav` lacks it | Add to sidebar + dashboard hero CTA |
| Finish lesson | 🟠 Lesson player has no back/breadcrumb and unclear "next lesson"/"mark complete → next" flow | `lessons/[id]` no back nav (N10) | Add curriculum breadcrumb + persistent Next/Complete |
| Quiz | 🔴 Does not exist | no quiz route/page | Build quiz taking + result pages (assessment domain) |
| Assignment | 🔴 Does not exist | no assignment route/page | Build assignment submission + feedback pages |
| Certificate | 🟡 `/certificates` OK but not linked from course-complete moment | learning flow | On course completion, surface certificate CTA inline |
| Profile | 🟡 `/profile` exists; user-menu can't reach it (N3) | user-menu | Link from menu |
| Notifications | 🟠 No bell entry point (N5) | topbar | Add bell |
| Orders | 🟡 `/orders` reachable only by deep link | not in `studentNav`/menu | Add to menu + sidebar |
| Instructor | 🔴 No journey exists (no `/teach`) | no instructor routes | Build instructor area (per Review 01) |
| Org manager | 🟡 Works but gated to `admin`; no cross-link from account | `(org)` role gate | Role fix + entry from user-menu for org users |
| CRM | 🟡 Works; no global search/quick-add lead from anywhere | `(crm)` | Quick-add action in topbar for CRM role |
| Analytics | 🟡 Works; export flow buried in report detail | `reports/[id]` | Surface "Export" as quick action on `/analytics` |
| Admin | 🟡 Separate Filament app; no link from web app to `/admin` for admins | no bridge | Add "Admin panel" link in user-menu for admins |

---

## Dashboard Problems

| Dashboard | Missing widgets / issues | Severity | Fix |
|-----------|--------------------------|----------|-----|
| Student `/dashboard` | No "Resume learning" hero (despite `/continue-learning` existing), no upcoming live sessions, no deadlines, no recommended courses, no quick actions | 🟠 | Add resume-hero, upcoming sessions, recommendations, and 3 quick actions (Browse, My learning, Certificates) |
| Organization `/org` | No seat-usage summary, no pending invites, no member activity; list-first with no KPIs | 🟠 | Add KPI row (seats used, active learners, pending invites) + quick "Invite member" |
| CRM `/crm` | No "my tasks today", no pipeline value, no quick-add lead | 🟠 | Add task/pipeline widgets + quick-add |
| Analytics `/analytics` | KPIs present but no date-range control visible, no drill-through affordance, export buried | 🟡 | Add global date range + export quick action |
| Admin (Filament `PlatformOverview`) | Single overview widget; no operational shortcuts (pending refunds, failed payments, new signups) | 🟡 | Add actionable widgets + deep links to filtered resources |

Cross-cutting: **no dashboard exposes quick actions or shortcuts** — every task starts from the sidebar. Benchmarks put 3–5 primary actions on the dashboard itself.

---

## CTA Problems

| # | Severity | Problem | Evidence | Fix | Impact |
|---|----------|---------|----------|-----|--------|
| CTA1 | High | Multiple primary CTAs compete on landing (Start free + Explore + service CTAs) with no single hierarchy | `theme.ts` hero has primary+secondary, sections add more | Enforce one primary per view; demote others to secondary/tertiary | Clearer conversion |
| CTA2 | High | Enroll vs Add-to-cart duality unguided | catalog cards + `products` | Course-type-driven single CTA | Fewer mis-clicks |
| CTA3 | Medium | Empty states usually lack a CTA | `EmptyState` default renders no `action` unless passed | Give every empty state a primary action (e.g. "Browse courses") | Recovery from empties |
| CTA4 | Medium | Success screens dead-end | `checkout/success`, `verify-email` lack forward CTA to next step | Add explicit "next" primary CTA | Flow continuity |
| CTA5 | Low | Dead CTA in user menu (Settings) | N3 | Wire it | Trust |

---

## Empty State Problems

| # | Severity | Problem | Evidence | Fix |
|---|----------|---------|----------|-----|
| E1 | Medium | Generic default empty ("Inbox" icon, no action) reused everywhere | `empty-state.tsx` default | Pass contextual title + icon + primary action per page (e.g. My learning → "Browse courses") |
| E2 | Medium | 21/46 pages use EmptyState; the rest (marketing/service/legal) have no empty concept, fine, but cart/orders/notifications empties need CTAs | usage grep | Ensure cart-empty → "Browse", orders-empty → "Browse", notifications-empty → reassurance copy |
| E3 | Low | No illustrations/onboarding empties | component uses lucide icon only | Optional: richer first-run empties for dashboard |

---

## Loading State Problems

| # | Severity | Problem | Evidence | Fix |
|---|----------|---------|----------|-----|
| L1 | Medium | `QueryState` shows a generic `LoadingState` spinner, not skeletons matching layout | `query-state.tsx` default `<LoadingState/>`; `Skeleton` used in only 3 pages | Pass layout-shaped skeletons to `QueryState` `loading` prop on list/detail pages | Perceived performance |
| L2 | Low | No route-level loading (`loading.tsx`) for App Router segments | no `loading.tsx` files | Add `loading.tsx` skeletons per group for navigation transitions |
| L3 | Low | No optimistic feedback on mutations beyond toasts | toasts exist | Add button `loading` states on submit (Button supports it) consistently |

---

## Error State Problems

| # | Severity | Problem | Evidence | Fix |
|---|----------|---------|----------|-----|
| ER1 | High | No React error boundary in use | `ErrorBoundary` defined, `grep` = 0 usages; no `error.tsx` in any segment | A render error white-screens the app | Add `error.tsx` per route group + wrap shells in `ErrorBoundary` |
| ER2 | Medium | Query errors are consistent (`QueryState` → `ErrorState` w/ retry) — good, but non-query pages (marketing/checkout) have no error surface | usage grep (ErrorState in 2 files) | Add error handling to checkout/payment and auth flows explicitly |
| ER3 | Medium | No 404 `not-found.tsx` | none present | Add a branded `not-found.tsx` with links home/catalog (critical given the known `/settings` 404) |

---

## Accessibility Problems

| # | Severity | Problem | Evidence | Fix |
|---|----------|---------|----------|-----|
| A1 | High | Dead menu item confuses SR/keyboard users (focusable, does nothing) | N3 | Make it a real link or remove |
| A2 | High | Mobile nav unreachable = keyboard/SR users on small viewports lose nav | N1 | Mobile drawer (N1) |
| A3 | Medium | No visible "skip to content" link | none in `layout.tsx`/shells | Add skip-link as first focusable element |
| A4 | Medium | Breadcrumb landmark missing (no `nav aria-label="Breadcrumb"`) | N2 | Ship breadcrumbs with proper landmark |
| A5 | Medium | Unverified color contrast of `text-muted-foreground` on cream `--background` | `theme.ts` palette | Verify AA (4.5:1) for body/muted text; adjust token if needed |
| A6 | Low | Icon-only buttons mostly labeled (good) but cart/bell to be added must include `aria-label` | topbar patterns | Ensure new controls have labels + focus-visible rings |
| A7 | Low | Language toggle is a footer `#lang` anchor in one place (no-op) | `theme.ts` footer | Wire to `LangToggle` |

Positives: sidebar uses `aria-current`, `aria-label="Primary"`, icons `aria-hidden`; forms use `auth/field` labels. Baseline is decent.

---

## Mobile UX Problems

| # | Severity | Problem | Evidence | Fix |
|---|----------|---------|----------|-----|
| M1 | Critical | Public/storefront nav hidden with no mobile menu | N1 | Add hamburger + drawer to `LandingHeader` |
| M2 | High | Data tables (CRM leads, orders, analytics) not verified for small screens | `ui/table.tsx`, `data-grid.tsx` used in list pages | Add horizontal scroll containers or card fallbacks < md |
| M3 | High | Lesson player + curriculum sidebar layout on mobile unclear (sidebar may crowd video) | `learning/curriculum-sidebar.tsx` | Collapse curriculum into a bottom sheet/drawer on mobile |
| M4 | Medium | Checkout on mobile lacks sticky pay/summary | `checkout/page.tsx` | Sticky order summary + pay button on mobile |
| M5 | Medium | Dashboard cards density on mobile unverified | student/org dashboards | Ensure single-column stacking + tap targets ≥44px |

App (`AppShell`) mobile is good: drawer nav via `useMediaQuery`. The gap is entirely the public chrome and content-heavy pages.

---

## UX Consistency Problems

| # | Severity | Problem | Evidence | Fix |
|---|----------|---------|----------|-----|
| CN1 | High | Two page-title systems | student `PageHeader` (gradient band) vs marketing `PageHero` vs plain headings | Pick one titling pattern per context and document it |
| CN2 | High | Two chromes with different container widths/padding | `(public)` `max-w-6xl px-4 py-10` vs `AppShell` `p-4 md:p-6` full-width | Define shared layout tokens (max width, page padding) |
| CN3 | Medium | Three header components historically (`LandingHeader`, `PublicHeader` dead, `Topbar`) | grep | Consolidate to two intentional chromes; delete dead one |
| CN4 | Medium | Filters/sorting/pagination patterns not standardized across list pages | catalog vs crm vs analytics lists | Extract shared list toolbar (search + filter + sort + pagination) |
| CN5 | Medium | Button placement/hierarchy varies (primary sometimes left, sometimes right in RTL/LTR) | forms/dialogs | Define action-row convention (primary trailing) honoring RTL |
| CN6 | Low | Icon set consistent (lucide) — good; keep enforced | components | Lint against ad-hoc SVGs |

---

## Orphan Pages

(UX-relevant; Review 01 covers product-level. Listed here for navigation completeness.)

| Page | Why orphan (UX) | Fix |
|------|-----------------|-----|
| `/continue-learning` | In no menu; duplicates dashboard intent | Add to `studentNav` + dashboard resume-hero |
| `/orders` | Reachable only by deep link | Add to user-menu + `studentNav` |
| `/contracts` | Reachable only by deep link/email | Link from order detail + account |
| `/settings/theme` | Internal tool linked in public nav | Remove from public nav; move to admin |
| `/notifications` | No bell entry point | Add bell (N5) |
| Component `PublicHeader` | Dead code | Delete |

---

## Navigation Map (current vs target)

```
CURRENT (two unbridged worlds + siloed admin)

  [Marketing/Storefront chrome: LandingHeader + Footer]         [App chrome: Sidebar + Topbar]        [Filament /admin]
   /  /courses /categories /trainers /products                   /dashboard /my-learning ...            (separate app)
   /cohorts /workshops /enterprise /advisory                     /org* /crm* /analytics*
   /cart /checkout (auth, page-level)                            (no link back to catalog)
        │  no cart icon in header (N4)                            user-menu: Settings=dead (N3)
        │  no mobile nav (N1)                                     no breadcrumbs (N2), no search (N6), no bell (N5)
        └───────────────────────  NO BRIDGE  ───────────────────┘
   * gated to admin only

TARGET (one connected shell with role-aware areas)

  Global top bar (all authed areas): [Logo→home] [Search] [Explore catalog] [Bell] [Cart] [User menu ▾]
                                                                     User menu: Dashboard · Profile · Orders · Settings · (Admin panel) · Sign out
  Left sidebar (grouped, role-aware):
    LEARN:   Dashboard · My learning · Continue · Certificates
    ACCOUNT: Orders · Notifications · Profile · Settings
    (instructor) TEACH: Courses · Sessions · Students · Earnings
    (org) ORG · (crm) CRM · (analytics) ANALYTICS  → each gated to its role
  Breadcrumbs under top bar on every deep page.
  Public/storefront: same top bar (search+cart+explore) with mobile hamburger drawer.
```

---

## Journey Maps (target, friction removed)

```
STUDENT (happy path)
  /login ──(role redirect)──▶ /dashboard
     │  resume-hero ▶ /courses/[id]/learn ▶ /lessons/[id] ─(Next/Complete)▶ … ▶ course complete
     │                                                         └▶ inline Certificate CTA ▶ /certificates
     ├ Browse ▶ /courses (search+filter) ▶ /courses/[id] ─(Add to cart)▶ cart badge ▶ /cart ▶ /checkout ▶ success ▶ /orders
     └ Bell ▶ /notifications      User menu ▶ Profile/Orders/Settings

INSTRUCTOR (to build)
  /login ▶ /teach (dashboard: drafts, students, upcoming sessions, earnings)
     ├ /teach/courses ▶ /teach/courses/[id]/edit (curriculum)
     ├ /teach/sessions   └ /teach/students   └ /teach/earnings

ORG MANAGER  /login ▶ /org (KPIs: seats, invites) ▶ /org/organizations/[id] (invite) ▶ /org/consulting
CRM/SUPPORT  /login ▶ /crm (my tasks, pipeline, quick-add lead) ▶ /crm/leads/[id]
FINANCE      /login ▶ /analytics (date range) ▶ /reports/[id] (export) ; commerce admin via /admin
ADMIN        user-menu ▶ Admin panel (/admin, Filament)
```

---

## Prioritized Fixes (with AI-agent-ready prompts)

### P0 — Connectivity & mobile (ship first)

**FX1 — Mobile nav for public/storefront (N1, M1, A2)**
> In `apps/web/src/components/landing/landing-header.tsx`, add a mobile navigation. Below the `lg` breakpoint, hide the inline `<nav>` and show a hamburger `Button` (aria-label "Menu") that opens the existing `Drawer`/`DrawerContent` from `@/components/ui/drawer`. Render the same `brandTheme.nav` links (plus Sign in / Start free) vertically inside the drawer, using `useI18n` for locale and `pickLocale`. Ensure the drawer closes on link click and traps focus. Keep desktop behavior unchanged.

**FX2 — Unified top bar: cart, bell, search, working user menu (N3, N4, N5, N6)**
> 1) In `apps/web/src/components/layout/user-menu.tsx`, make the "Settings" item a `<DropdownMenuItem asChild><Link href="/settings">…</Link></DropdownMenuItem>` and add items linking to `/dashboard`, `/profile`, `/orders`, `/notifications`; for users with role admin/super_admin add an "Admin panel" item linking to the API `/admin` URL from config.
> 2) In `apps/web/src/components/layout/topbar.tsx`, add (before LangToggle) a search input (submits to `/courses?q=`), a notifications bell `Button` with an unread-count badge (aria-label "Notifications", links to `/notifications`), and a cart icon with item-count badge (links to `/cart`).
> 3) Add the same cart icon + bell (for authenticated users) to `landing-header.tsx`. Delete the unused `apps/web/src/components/catalog/public-header.tsx`.

**FX3 — Breadcrumbs everywhere (N2, N10, A4)**
> Create `apps/web/src/components/layout/breadcrumbs.tsx` that derives a trail from `usePathname()` + a route→label map, rendered with the existing `@/components/ui/breadcrumb` primitives inside a `<nav aria-label="Breadcrumb">`. Render it in `AppShell` (below `Topbar`) and on every storefront `[public_id]` detail page. Remove ad-hoc back links where the breadcrumb now covers them; keep a back affordance on the lesson player.

**FX4 — Role-aware post-login redirect (Login journey, N/A)**
> In `apps/web/src/lib/auth/guards.tsx` (or a new `useRoleHome` hook), after authentication resolve the user's role and redirect: student→`/dashboard`, instructor→`/teach`, admin/super_admin→`/analytics`, org_manager→`/org`, support→`/crm`. Update `RequireGuest` to send authenticated users to their role home instead of `/`.

### P1 — Wayfinding, dashboards, errors

**FX5 — Sidebar grouping + brand link + orphan links (N8, N9, orphans)**
> Extend `NavItem` in `apps/web/src/config/nav.ts` to support optional `group` labels. Update `sidebar.tsx` to render grouped sections with small uppercase labels. Add `/continue-learning`, `/orders`, `/notifications` to `studentNav` under an "Account"/"Learn" grouping. Wrap the sidebar brand wordmark in `<Link href="/dashboard">`.

**FX6 — Error/404/loading scaffolding (ER1, ER3, L2)**
> Add `error.tsx` (using `@/components/states/error-boundary` + `ErrorState`) and `loading.tsx` (layout-shaped skeletons) to each route group under `apps/web/src/app`. Add a branded `not-found.tsx` at the app root with links to `/` and `/courses`.

**FX7 — Dashboard quick actions & widgets (Dashboard problems)**
> On `/dashboard`, add a "Resume learning" hero (from continue-learning data), an "Upcoming live sessions" list, "Recommended courses", and a quick-action row (Browse, My learning, Certificates). On `/org`, `/crm`, `/analytics` add a KPI row + one primary quick action each (Invite member / Add lead / Export).

**FX8 — Empty & success CTAs (CTA3, CTA4, E1, E2)**
> Audit every `QueryState`/`EmptyState` usage and pass a contextual `title`, `icon`, and primary `action`: My learning→"Browse courses", Cart empty→"Browse courses", Orders empty→"Browse courses", Notifications empty→reassurance copy. On `checkout/success` and `verify-email`, add a primary CTA advancing to the role home/next step.

### P2 — Consistency, learning depth, a11y

**FX9 — Shared list toolbar + layout tokens (CN1–CN5)**
> Extract a `ListToolbar` (search + filter + sort + pagination) used by catalog, CRM, orders, analytics lists. Define shared page container tokens (max width, padding) and apply to both chromes. Standardize on one page-title component per context.

**FX10 — Assessment UX (Quiz/Assignment journeys) [depends on backend assessment domain]**
> Once an assessment API exists, add `/courses/[id]/quiz/[quizId]` (question flow + result) and `/courses/[id]/assignments/[id]` (submission + feedback) pages, wired into the lesson player's "Next" flow and gated by enrollment.

**FX11 — Accessibility pass (A3, A5, A6)**
> Add a "Skip to content" link as the first focusable element in `app/layout.tsx`. Verify `--muted-foreground` on `--background` meets WCAG AA (4.5:1) in light and dark; adjust token if it fails. Ensure all new icon buttons (cart/bell/search) have `aria-label` and visible focus rings.

---

## Acceptance Criteria

**Navigation & IA**
- AC1: On a 375px viewport, every primary nav destination (Courses, Cohorts, Workshops, Enterprise, Advisory, Sign in) is reachable from `/` and all storefront pages via a mobile menu. (FX1)
- AC2: Breadcrumbs render on 100% of authenticated pages and all storefront `[public_id]` detail pages, with a `nav[aria-label="Breadcrumb"]` landmark. (FX3)
- AC3: The user menu contains working links to Dashboard, Profile, Orders, Notifications, Settings, Sign out (and Admin panel for admins); no item is a no-op. (FX2)
- AC4: Cart icon with live item count and a notifications bell with unread count appear in the top bar on every authenticated page; cart is reachable from storefront without typing a URL. (FX2)
- AC5: A global search control exists in both chromes and routes to catalog search. (FX2)
- AC6: `/continue-learning`, `/orders`, `/notifications` each appear in at least one persistent menu. (FX5)

**Journeys**
- AC7: After login, each role lands on its role home without manual navigation. (FX4)
- AC8: From `/dashboard`, a learner can resume the last lesson in ≤1 click. (FX7)
- AC9: The checkout funnel (browse→cart→checkout→success→orders) is completable using only on-screen controls (no typed URLs) on mobile and desktop. (FX2/FX8)
- AC10: Lesson completion surfaces a certificate CTA when a course is completed. (FX8/FX10)

**States**
- AC11: Every route group has `error.tsx`, `loading.tsx`; the app has a branded `not-found.tsx`. A thrown render error shows a recoverable error UI, not a white screen. (FX6)
- AC12: Every empty state renders a contextual message + at least one primary action. (FX8)

**Consistency & a11y**
- AC13: One documented page-title pattern per context; both chromes share container width/padding tokens. (FX9)
- AC14: Keyboard-only users can reach main content via a skip link and operate all header controls; body/muted text passes WCAG AA contrast. (FX11)
- AC15: No dead/unused nav components remain (`PublicHeader` deleted). (FX2)

**Traceability**
- AC16: Every issue (N1–N12, CTA1–5, E1–3, L1–3, ER1–3, A1–7, M1–5, CN1–6) maps to a fix (FX1–FX11) and a criterion above.

---

### Appendix — Evidence index
- Chrome: `components/layout/{app-shell,sidebar,topbar,user-menu,page-transition}.tsx`; `components/landing/landing-header.tsx`; `components/catalog/public-header.tsx` (unused).
- Nav sources: `config/nav.ts`, `config/theme.ts` (nav + footer arrays).
- States: `components/student/query-state.tsx`; `components/states/{empty-state,error-state,loading-state,error-boundary}.tsx`.
- Coverage: `QueryState` 25/46 pages, `EmptyState` 21, `ErrorState`/`LoadingState` 2 each, `ErrorBoundary` 0, `Breadcrumb` 0, back-nav 4 pages.
- Missing routes: no quiz, no assignment, no instructor area, no `/settings`.
