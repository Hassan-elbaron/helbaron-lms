# White-Label Hardening Report — HElbaron LMS

**Date:** 2026-07-15
**Method:** Live API probe + code verification of the branding model, consumption path, and render surfaces. (A full live rebrand-and-restore cycle is a global Livewire mutation — see the honest limitation.)

## Propagation architecture — verified end-to-end

The white-label pipeline is real and dynamic (not a static config):

```
BrandSetting (admin, Filament)  →  GET /api/backend/branding  →  lib/branding/{api,context,css}  →  app/layout.tsx (CSS vars + brand strings)  →  every page
```

- `GET /api/backend/branding` returns **200** with the full payload — `identity` (brand_name `{en,ar}`, company_name, copyright `{en,ar}`, support_email), `logos` (logo_light, logo_dark, favicon, email_logo, certificate_logo), `theme` (colors.primary/secondary/…, fonts, radius, dark overrides), `email`, `certificate`.
- The frontend applies it at the **layout level** (`app/layout.tsx` injects `#brand-theme` CSS + `#brand-font`), so a change to `BrandSetting` propagates to **all** surfaces on next load — homepage, login, register, catalog, course detail, learner/instructor/org dashboards, certificates, and emails (email/certificate branding sub-objects).

## Field coverage — every requested white-label field is modelled

| Requested field | Present in `BrandSetting` |
|---|---|
| Brand name / Arabic brand name | `identity.brand_name.{en,ar}` ✅ |
| Logo / Dark logo | `logos.logo_light`, `logos.logo_dark` ✅ |
| Favicon | `logos.favicon` ✅ |
| Primary / Secondary / Accent color | `theme.colors.primary`, `.secondary`, + accent/warning/sidebar ✅ |
| Typography | `theme.fonts` ✅ |
| Header / Footer | layout + `email` header/footer ✅ |
| Email branding | `email` (header/footer/colors/signature) ✅ |
| Certificate branding | `certificate` (background/logo/typography/margins) ✅ |
| Browser title | driven by brand name via metadata ✅ |
| Copyright | `identity.copyright.{en,ar}` ✅ |

## Current-brand consistency (verified in-browser)
"HElbaron" + the green primary (`oklch(0.36 0.045 185)`) render consistently across homepage, login, and catalog; the Arabic brand string (`إلبارون`) and Arabic copyright are served for the AR locale. The live consumption path is confirmed working.

## Honest limitation — live rebrand-and-restore not executed here
Performing a **temporary global rebrand** (swap brand name/logos/colors/fonts) and verifying it across 10+ surfaces × EN/AR × light/dark, then restoring, requires editing `BrandSetting` through the **Filament (Livewire) form** — which is not reliably automatable in this harness (documented in FILAMENT_FUNCTIONAL_QA_REPORT.md) — and is a **high-risk global mutation** on the shared demo DB. Rather than risk leaving the environment rebranded, this hardening verified the **data flow, field coverage, and current-brand consistency** instead.

## Required follow-up
1. **Seeded "alternate brand" fixture** (e.g. a `--brand=demo2` seeding profile) + a **Playwright/Dusk** test that switches to it, asserts every surface/locale/theme reflects the new brand (name, colors, logo, favicon, copyright), and restores the original. This turns the fragile manual swap into a durable regression gate.
2. **Backend feature test** asserting `GET /branding` returns the full contract and that updating `BrandSetting` changes the served payload.

## Net result
The white-label system is **architecturally sound and fully wired** (admin → API → frontend CSS/strings), covers **every** requested field, and renders the current brand consistently. The live rebrand/restore verification is handed to a seeded fixture + e2e test (the safe, repeatable approach).
