# HElbaron — QA Issues & Critique

Critical walkthrough of the running app (web at `localhost:3000`, API at `localhost:8000`),
logged in as `admin@helbaron.local`. Findings are ordered by severity. Each item has a concrete fix.

Legend: 🔴 Blocker · 🟠 Major · 🟡 Minor/Polish · 🔵 Content/Data · ⚪ Not yet verified

---

## 🔴 Blockers

### B1. Filament admin panel is completely unusable (assets 503)
- **Symptom:** `/admin` loads unstyled (no CSS/JS); the Sign-in button does nothing.
- **Cause:** every Filament CSS/JS + `livewire.js` returns **HTTP 503**. The API runs the
  single-threaded `php artisan serve`; the panel loads ~12 assets in parallel and the dev server
  chokes. (A single direct request to the same asset returns 200 — only concurrency fails.)
- **Fix applied:** added `PHP_CLI_SERVER_WORKERS: 12` to `docker-compose.yml` (api service).
- **Action needed:** `docker compose up -d api` to recreate the container. **Until then the entire
  admin/back-office is inaccessible.**

### B2. `/org` portal was gated by roles that do not exist
- **Symptom:** the whole B2B org area was unreachable by anyone.
- **Cause:** `(org)/layout.tsx` required roles `org_admin` / `org_owner` — neither is seeded (only
  `super_admin`, `admin`, `instructor`, `student` exist).
- **Fix applied:** changed the guard to `admin`/`super_admin`. Now reachable. (Long-term: add real
  org roles instead of reusing admin.)

---

## 🟠 Major

### M1. Marketing numbers are hardcoded and contradict real data
- Landing stats say **100+ courses, 25K+ learners, 75 enterprise customers, $25M revenue**; the
  `/courses` hero says **"100+ courses live"** — but the real catalog has **~4 courses** and every
  dashboard shows **0** leads/orders/members/enrollments.
- These are static config values (`theme.ts`, `page-heroes.ts`), so a visitor sees fabricated
  proof-figures next to empty real data.
- **Fix:** drive the headline counts from the API (or clearly label them as targets), and hide the
  "trusted by / 25K learners" proof band until there's real traction.

### M2. Demo course previews play unrelated placeholder videos
- The "Featured courses" Play buttons open **3Blue1Brown / freeCodeCamp** clips — off-brand for a
  business academy and potentially confusing in a client demo.
- **Fix:** replace the `youtubeId`s in `src/config/demo.ts` with real HElbaron trailers, or set
  `DEMO_ENABLED = false` for client-facing demos, or use one neutral branded clip.

### M3. Seeded catalog content is Latin lorem-ipsum
- Real courses render as **"Tenetur sapiente at veniam…", "Quibusdam amet quasi"** etc. (Faker).
- Looks broken/unprofessional on the live `/courses` page.
- **Fix:** write a realistic demo seeder (real course titles/subtitles/trainers per the 12 verticals),
  or gate Faker seeds behind a `--demo` flag and ship a curated seeder.

### M4. Service pages are static marketing, disconnected from real data
- `/cohorts`, `/workshops`, `/enterprise`, `/advisory` show hardcoded figures (19 cohorts, 65%
  completion, 75 clients…) that don't come from the backend and contradict the empty state elsewhere.
- **Fix:** wire the stats to the API where equivalents exist (e.g. live cohorts), or mark clearly as
  illustrative.

### M5. No trainer/instructor experience on the web
- The `trainer@helbaron.local` account sees the **same student dashboard**. Authoring/instructor
  tools live only in Filament — which is currently broken (B1). So "trainer" as a distinct role is
  effectively untestable on the web today.
- **Fix:** either add an instructor area to `apps/web`, or document that authoring is Filament-only
  (and fix B1).

---

## 🟡 Minor / Polish

### P1. PageHero stat chip clips at the container edge
- On `/courses` (and likely other hero pages at some widths) the right-hand stat chip ("100 courses")
  is **cut off** by the panel's right edge / viewport.
- **Fix:** constrain the hero grid's right column (`overflow-hidden` on the panel already exists; give
  the chip `me-2`/`min-w-0` or move it inside the safe area).

### P2. Full-page "Loading…" flash on every protected navigation
- Moving between `/dashboard`, `/analytics`, `/crm`, `/org` shows a centered "Loading…" for a beat
  while the auth guard re-checks the session — the whole shell disappears.
- **Fix:** keep the sidebar/shell mounted and only skeleton the page body; cache the auth check so it
  doesn't re-flash on client navigations.

### P3. RTL logo placement
- In Arabic/RTL the header brand mark (H / HElbaron) appears pushed to the far edge / not clearly
  visible. Nav + CTAs mirror correctly, but confirm the logo sits at the start (right) in RTL.
- **Fix:** verify the header flex order under `dir=rtl`.

### P4. Coarse RBAC (only 4 roles)
- Only `super_admin/admin/instructor/student` exist. `/crm`, `/analytics`, `/org` are all gated behind
  `admin` — there's no dedicated CRM agent / org manager / analyst role.
- **Fix:** add roles + policies if separate back-office personas are needed.

### P5. section-card / static panels — verify hover feel
- Static content panels got a subtle shadow-on-hover; confirm it doesn't read as "clickable" where it
  isn't.

---

## 🔵 Content / Data

### D1. Most areas are empty (0 records)
- CRM: 0 leads / 0 opportunities. Commerce: no products/orders. Org: 1 org, 0 members/seats.
  Analytics KPIs: mostly 0 (Enrollments shows 1).
- The app looks hollow in a demo. **Fix:** ship a rich demo seeder (leads, orders, members,
  enrollments, notifications) so every screen has believable content.

### D2. Demo prices/ratings are invented
- `$29 / 4.9★ / 42 lessons` in demo cards are static and not tied to real products/pricing.

---

## ⚪ Not yet verified (blocked or out of this pass)

- **Filament admin** — resources, dashboard KPIs, CRUD, MFA (blocked by B1; recheck after restart).
- **Commerce end-to-end** — add-to-cart → checkout → fake payment → enrollment (needs products).
- **Learning flow** — enroll → play lesson (Mux) → progress → auto-certificate.
- **Auth flows** — register, email verify OTP, password reset, MFA enrol (need mail/SMS wired).
- **Mobile / responsive** — sidebar drawer, hero stacking, nav collapse (not tested).
- **Student & trainer experiences in depth** — only admin was walked end-to-end.
- **Cross-browser** — tested in Chrome only.

---

## ✅ What works well (for balance)
- Landing page: hero SVG illustration, animation, announcement, header/footer, demo courses + working
  YouTube modal — looks premium.
- Full **Arabic / RTL** mirroring across landing + app (verified `dir=rtl`, translated nav/content).
- **Dark/light** tokens, brand identity (deep teal / cream / copper / gold, serif headings).
- Web auth + session works; `/dashboard`, `/analytics`, `/crm`, `/org` all render with per-page
  header bands and load real API data (all API calls returned **200**).
- Frontend build is green: typecheck ✅, 60+ unit tests ✅, `next build` ✅ (43 routes).

---

## Recommended fix order
1. `docker compose up -d api` → re-test Filament admin (B1).
2. Ship a **realistic demo seeder** (M3, D1) — biggest visual win.
3. Make headline stats dynamic or clearly illustrative (M1, M4).
4. Replace/disable demo YouTube IDs (M2).
5. Polish PageHero clipping + loading flash (P1, P2).
