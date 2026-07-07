# HElbaron LMS — Route Restructure (Refactor 02)

**Type:** Documentation only. Structural route re-ownership + minimal, redirect-covered URL changes. No code, no UI.
**Principle:** **Preserve public/SEO URLs; reorganize ownership via Next.js route groups** (groups `(name)` do not add URL segments, so re-owning a page rarely changes its URL). Only relocate URLs where a page is misplaced or a context needs a clean prefix — always with a redirect.
**Contexts:** Marketing · Learning · Commerce · Instructor · Organization · CRM · Analytics · Administration · (Platform: auth/account chrome).

---

## 1. Naming Rules (canonical)

1. **Public marketing URLs are stable and never change** (SEO/backlinks): `/`, `/courses`, `/courses/[slug]`, `/categories`, `/trainers`, `/products`, `/cohorts`, `/workshops`, `/enterprise`, `/advisory`, `/privacy`, `/terms`.
2. **Route groups mirror bounded contexts** and carry no URL segment: `(marketing)`, `(learning)`, `(commerce)`, `(instructor)`, `(organization)`, `(crm)`, `(analytics)`, `(account)`. Administration lives in the separate Filament app at `/admin`.
3. **Authenticated area prefixes are explicit and context-owned:** `/learn/*`, `/teach/*`, `/org/*`, `/crm/*`, `/analytics|/reports|/dashboards`, `/account/*`.
4. **kebab-case** for all segments; **plural collections** (`/courses`, `/orders`); **entity detail by stable public id/slug** (`/courses/[public_id]`).
5. **One context owns one URL subtree.** No subtree is served by two groups.
6. **No internal/dev tool in a public subtree** (e.g., theme tooling only under `/admin`).
7. **Redirects are permanent (308/301)** for moved canonical URLs, temporary (307) only for auth gating.
8. **Deprecated routes** return a redirect for ≥2 releases, then 410/removal.

---

## 2. Full Route Inventory → New Ownership & URL

Legend — **URL change?** ✅ stays / ➜ moves (redirect) / ✂ removed. **Diff** = migration difficulty (S/M/L). **Breaking** = external impact.

### 2.1 Marketing Website (public, unauth)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/` | (marketing) | (marketing) | `/` | ✅ | canonical landing | S | none | — |
| `/courses` | (public) | (marketing) | `/courses` | ✅ | public catalog is Marketing | S | none | — |
| `/courses/[public_id]` | (public) | (marketing) | `/courses/[public_id]` | ✅ | public course detail | S | none | — |
| `/categories` | (public) | (marketing) | `/categories` | ✅ | — | S | none | — |
| `/trainers` | (public) | (marketing) | `/trainers` | ✅ | public instructor listing | S | none | — |
| `/products` | (public) | (marketing) | `/products` | ✅ | public product listing | S | none | — |
| `/cohorts` | (public) | (marketing) | `/cohorts` | ✅ | service page | S | none | — |
| `/workshops` | (public) | (marketing) | `/workshops` | ✅ | service page | S | none | — |
| `/enterprise` | (public) | (marketing) | `/enterprise` | ✅ | service page | S | none | — |
| `/advisory` | (public) | (marketing) | `/advisory` | ✅ | service page | S | none | — |
| `/privacy` | (public) | (marketing) | `/privacy` | ✅ | legal | S | none | — |
| `/terms` | (public) | (marketing) | `/terms` | ✅ | legal | S | none | — |

### 2.2 Auth funnel (Marketing-owned chrome, Platform/Identity logic)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/login` | (auth) | (marketing)/(auth) | `/login` | ✅ | acquisition→activation | S | none | — |
| `/register` | (auth) | (marketing)/(auth) | `/register` | ✅ | — | S | none | — |
| `/forgot-password` | (auth) | (marketing)/(auth) | `/forgot-password` | ✅ | — | S | none | — |
| `/reset-password` | (auth) | (marketing)/(auth) | `/reset-password` | ✅ | — | S | none | — |
| `/verify-email` | (onboarding) | (marketing)/(auth) | `/verify-email` | ✅ | activation step | S | none | — |
| `/mfa` | (onboarding) | (marketing)/(auth) | `/mfa` | ✅ | activation step | S | none | — |

### 2.3 Learning (authenticated learner)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/dashboard` | (student) | (learning) | `/dashboard` | ✅ | learner home | S | none | — |
| `/my-learning` | (student) | (learning) | `/my-learning` | ✅ | — | S | none | — |
| `/continue-learning` | (student) | (learning) | `/continue-learning` | ✅ | (also add to nav) | S | none | — |
| `/certificates` | (student) | (learning) | `/certificates` | ✅ | learner cert view | S | none | — |
| `/courses/[public_id]/learn` | (public) | (learning) | `/learn/[public_id]` | ➜ | move player out of public marketing subtree into Learning | M | internal links + bookmarks | 308 `/courses/:id/learn` → `/learn/:id` |
| `/lessons/[public_id]` | (public) | (learning) | `/learn/[public_id]/lessons/[lesson_id]` | ➜ | lesson belongs under its course in Learning | M | internal links | 308 `/lessons/:id` → resolve to `/learn/:course/lessons/:id` |

### 2.4 Account (learner self-service; Platform/Identity data, Learning-adjacent chrome)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/profile` | (student) | (account) | `/account/profile` | ➜ | account self-service subtree | S | internal links | 308 `/profile` → `/account/profile` |
| `/notifications` | (student) | (account) | `/account/notifications` | ➜ | notification center is account | S | internal links | 308 `/notifications` → `/account/notifications` |
| `/settings` (missing, 404) | dashboardNav ref | (account) | `/account/settings` | new | fixes the dead 404 link | S | fixes breakage | — |

### 2.5 Commerce (authenticated buyer)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/cart` | (public) | (commerce) | `/cart` | ✅ | keep URL, re-own + guard | S | none | — |
| `/checkout` | (public) | (commerce) | `/checkout` | ✅ | — | S | none | — |
| `/checkout/success` | (public) | (commerce) | `/checkout/success` | ✅ | — | S | none | — |
| `/checkout/failed` | (public) | (commerce) | `/checkout/failed` | ✅ | — | S | none | — |
| `/orders` | (public) | (commerce) | `/orders` | ✅ | (add to nav) | S | none | — |
| `/contracts` | (public) | (commerce) | `/contracts` | ✅ | (link from order) | S | none | — |

### 2.6 Instructor (NEW context)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| — (none today) | — | (instructor) | `/teach` | new | teach dashboard | M | none (new) | — |
| — | — | (instructor) | `/teach/courses` | new | own courses | M | none | — |
| — | — | (instructor) | `/teach/courses/[public_id]/edit` | new | curriculum editor (Authoring API) | L | none | — |
| — | — | (instructor) | `/teach/sessions` | new | live sessions (Live API) | M | none | — |
| — | — | (instructor) | `/teach/students` | new | roster | M | none | — |
| — | — | (instructor) | `/teach/earnings` | new | Commerce read | M | none | — |
| — | — | (instructor) | `/teach/apply` | new | onboarding/application | S | none | — |

### 2.7 Organization (B2B)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/org` | (org) | (organization) | `/org` | ✅ | re-owned; re-gate to org_manager | S | none | — |
| `/org/organizations` | (org) | (organization) | `/org/organizations` | ✅ | — | S | none | — |
| `/org/organizations/[public_id]` | (org) | (organization) | `/org/organizations/[public_id]` | ✅ | — | S | none | — |
| `/org/consulting` | (org) | (organization) | `/org/consulting` | ✅ | org-side consulting requests | S | none | — |

### 2.8 CRM (internal)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/crm` | (crm) | (crm) | `/crm` | ✅ | re-gate to support role | S | none | — |
| `/crm/leads` | (crm) | (crm) | `/crm/leads` | ✅ | — | S | none | — |
| `/crm/leads/[public_id]` | (crm) | (crm) | `/crm/leads/[public_id]` | ✅ | — | S | none | — |
| `/crm/consulting` | (crm) | (crm) | `/crm/consulting` | ✅ | consulting **delivery** (vs org request) | S | none | — |
| `/crm/organizations` | (crm) | (crm) | `/crm/accounts` | ➜ | disambiguate from Organization context; CRM sees "accounts" | S | internal only | 308 `/crm/organizations` → `/crm/accounts` |

### 2.9 Analytics (internal)

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/analytics` | (analytics) | (analytics) | `/analytics` | ✅ | — | S | none | — |
| `/reports` | (analytics) | (analytics) | `/reports` | ✅ | — | S | none | — |
| `/reports/[public_id]` | (analytics) | (analytics) | `/reports/[public_id]` | ✅ | — | S | none | — |
| `/dashboards` | (analytics) | (analytics) | `/dashboards` | ✅ | — | S | none | — |

### 2.10 Administration & removed

| Current path | Current group | New group | New URL | URL change? | Reason | Diff | Breaking | Redirect |
|--------------|---------------|-----------|---------|-------------|--------|------|----------|----------|
| `/admin/*` (Filament) | apps/api | Administration | `/admin/*` | ✅ | back-office | S | none | — |
| `/settings/theme` | standalone | Administration | `/admin/brand` (Filament) | ➜/✂ | internal tool leaves public web; becomes admin content | M | remove from public nav | 308 `/settings/theme` → `/login` (or 404 for anon) |
| `(dashboard)` group | (dashboard) | — | — | ✂ | dead group, no page | S | none | delete |
| `components/catalog/public-header.tsx` | — | — | — | ✂ | dead component | S | none | delete |

---

## 3. Redirect Plan (consolidated)

**Permanent (308) canonical moves** — implement in `apps/web/middleware.ts` and/or `next.config.ts` `redirects()`:

```
/courses/:id/learn        → /learn/:id
/lessons/:id              → /learn/:course/lessons/:id   (resolve course server-side)
/profile                  → /account/profile
/notifications            → /account/notifications
/crm/organizations        → /crm/accounts
/settings/theme           → (anon) 404  |  (auth) /admin/brand
```

**Temporary (307) auth gating** — via middleware (defense-in-depth over client guards):
```
unauthenticated → guarded subtree (/learn,/account,/teach,/org,/crm,/analytics,/orders,/cart-checkout writes) → /login?next=<path>
authenticated on (/login,/register,...) → role home (/dashboard | /teach | /analytics | /org | /crm)
```

**New routes (no redirect, add to sitemap/nav):** `/account/settings`, `/teach`, `/teach/courses`, `/teach/courses/:id/edit`, `/teach/sessions`, `/teach/students`, `/teach/earnings`, `/teach/apply`, `/orders` (nav), `/continue-learning` (nav).

---

## 4. Deprecated Routes

| Deprecated | Replacement | Policy |
|-----------|-------------|--------|
| `/courses/:id/learn` | `/learn/:id` | 308 for ≥2 releases, then keep 308 (SEO-safe) |
| `/lessons/:id` | `/learn/:course/lessons/:id` | 308 permanently (bookmarks) |
| `/profile`, `/notifications` | `/account/*` | 308 permanently |
| `/crm/organizations` | `/crm/accounts` | 308 (internal, low risk) |
| `/settings/theme` (public) | `/admin/brand` | remove from nav immediately; 404/redirect |
| `(dashboard)` group | — | delete now (dead) |

No **public marketing URL** is deprecated — zero SEO risk on the acquisition surface.

---

## 5. Route-Group → Context Map (final)

```
apps/web/src/app/
  (marketing)/            → Marketing (landing, catalog read, service, legal)
    (auth)/               → Marketing-owned auth funnel (Identity logic)
  (learning)/             → Learning (dashboard, my-learning, /learn/*)
  (account)/              → Account self-service (Identity data)
  (commerce)/             → Commerce (cart, checkout, orders, contracts)
  (instructor)/           → Instructor (/teach/*)   [NEW]
  (organization)/         → Organization (/org/*)
  (crm)/                  → CRM (/crm/*)
  (analytics)/            → Analytics (/analytics,/reports,/dashboards)
  # Administration = Filament at apps/api /admin (separate app)
```

Each guarded group enforces auth/role at the **layout** (not per-page), and middleware enforces the same at the edge — resolving the inconsistent guarding noted in prior audits, with **one context per subtree**.
