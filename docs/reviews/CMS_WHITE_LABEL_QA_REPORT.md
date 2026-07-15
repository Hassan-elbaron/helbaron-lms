# CMS & White-Label QA Report — HElbaron LMS

**Date:** 2026-07-15
**Method:** Filament admin (signed in as `admin@helbaron.local`) + code verification of models/resources/versioning + real API probes and frontend rendering in the user's Chrome. Money/brand mutations were **not** committed against the shared demo environment (see the honest limitation on live Livewire CRUD at the end).

## Surface inventory — all present and wired

| Area | Filament resource | Model / versioning | Frontend consumption | Status |
|---|---|---|---|---|
| **Homepage CMS** | `HomepageSectionResource` | `HomepageSection` + **`HomepageSectionVersion`** (version history) | Landing page renders CMS-driven blocks (hero, offerings, trusted-by, testimonials, FAQ, catalog) | ✅ present + renders |
| **Static Pages** | `StaticPageResource` | `StaticPage` (+ `PageStatus` editorial status) snapshots to **`static_page_versions`** on every update, incl. rollback restores; `StaticPageVersion` model | `/about`, `/contact`, `/privacy`, `/terms` + generic `/p/{slug}` (verified `/p/help`, `/p/faq` render) | ✅ present + renders |
| **Navigation Builder** | `NavMenuResource`, `NavItemResource` | `NavMenu`, `NavItem` (internal/external links, locale/role/feature-flag visibility, ordering, nesting) | Primary nav renders (Courses/Cohorts/Workshops/Events/B2B/Consulting) | ✅ present + renders |
| **SEO Manager** | `SeoMetaResource` | `SeoMeta` (title/description/canonical/robots/OG/Twitter/JSON-LD/hreflang/sitemap) | `lib/seo/api.ts`; JSON-LD present in homepage DOM; sitemap/robots routes | ✅ present + renders |
| **White-Label / Branding** | `BrandSettingResource` | `BrandSetting` (JSON: `identity`, `logos`, `theme`, `email`, `certificate`) | `GET /branding` → `lib/branding/{api,context,css}` → applied in `app/layout.tsx` | ✅ present + consumed |

## White-Label — data flow verified end-to-end

`GET /api/backend/branding` returns **200** with the full payload the frontend consumes:
- `identity.brand_name` = `{en: "HElbaron", ar: "إلبارون"}` (brand name EN + AR ✅)
- `identity.copyright` = `{en: "All rights reserved.", ar: "جميع الحقوق محفوظة."}` (copyright EN + AR ✅)
- `theme.colors.primary` = `oklch(0.36 0.045 185)` (primary color ✅), plus `secondary`, `warning`, `sidebar`
- `logos.favicon` present (favicon ✅), plus `logo_light`, `logo_dark`, `email_logo`, `certificate_logo`
- `email` (header/footer/signature branding ✅), `certificate` (cert branding), `identity.company_name`, `theme.fonts` (typography ✅)

The white-label model therefore covers **every field the brief lists**: brand name (EN+AR), logo, favicon, primary & secondary colours, typography, header/footer (email + layout), and copyright (EN+AR). The frontend fetches this at the layout level and applies it as CSS variables + brand strings, so an admin `BrandSetting` change **propagates to the frontend** (it is not a static-only config; `config/theme.ts` only supplies defaults).

**Current-brand consistency (verified in-browser):** "HElbaron" + the green primary render consistently across **homepage, login, catalog** in the sessions exercised, and the Arabic brand string (`إلبارون`) + Arabic copyright are served for the AR locale. The consumption path (model → API → context/CSS → rendered) is confirmed working for the live brand.

## Versioning & rollback
- **Static pages**: every update snapshots the versioned fields (`slug, template, title, body, excerpt, hero_image, status`) into `static_page_versions`; rollback restores a prior snapshot (and re-snapshots), so **version history + rollback exist**.
- **Homepage sections**: `HomepageSectionVersion` provides section version history.
- **Editorial status**: static pages carry a `PageStatus` (draft/published/etc.), and homepage sections carry published-content/published-at fields — the publish/schedule/archive lifecycle is modelled.

## Honest limitations — what was NOT click-through-tested

- **Live Filament CRUD** (create/edit/reorder/disable/publish/schedule/archive a homepage block; version + rollback; create/edit a static page in EN+AR with slug/SEO/preview/publish/version/rollback; add/nest/reorder/disable nav items with locale/role/feature-flag visibility; edit every SEO field): these are **Livewire form + repeater** operations. As documented in INSTRUCTOR_AUTHORING_IMPLEMENTATION_GAP.md (Filament functional QA), Livewire's `wire:model`/`wire:submit` pipeline does not sync reliably from the browser-automation harness's synthetic events, so a full click-through CRUD cycle is not scriptable here. The **resources, forms, versioning tables, status lifecycles, and frontend consumption all exist and are code-verified + rendering** — but the interactive mutation cycle needs a human pass or a Livewire-aware e2e runner (Laravel Dusk / Playwright).
- **Live white-label rebrand-and-restore across homepage/login/catalog/learner+instructor dashboards × EN/AR × light/dark**: this is a **global, high-risk mutation** through the same Livewire form, on a shared demo DB. Rather than temporarily rebrand the whole environment via unreliable automation (risking a left-changed brand), this QA verified the **data flow + current-brand consistency** instead. Recommend adding a **seeded "alternate brand" fixture** + a Playwright test that swaps to it, asserts every surface/locale/theme reflects it, and restores — a durable regression check instead of a fragile manual swap.

## Recommendations
1. Add a **Laravel Dusk or Playwright** suite for the Filament CMS CRUD + versioning/rollback flows (these are exactly the paths the automation harness can't drive but a real e2e runner can).
2. Add a **seeded alternate-brand fixture** + an automated white-label swap/verify/restore test across the EN/AR × light/dark surface matrix.
3. Backend **feature tests** for `StaticPage`/`HomepageSection` version snapshot + rollback and for `NavItem` visibility rules (locale/role/feature-flag) — fast, deterministic coverage that doesn't depend on the UI.

## Net result
All CMS + white-label **surfaces exist, are wired admin→API→frontend, and render**; branding covers every required field with a verified end-to-end data flow and consistent current-brand rendering; static-page + homepage-section **version history/rollback exist**. The interactive Filament CRUD and the live global rebrand cycle are **not** browser-automatable here and are handed off to an e2e runner + seeded fixtures (recommendations above).
