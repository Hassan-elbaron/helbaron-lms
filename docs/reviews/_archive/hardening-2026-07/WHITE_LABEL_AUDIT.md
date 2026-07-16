# White-Label Audit

**Date:** 2026-07-16
**Scope:** `apps/web` + `apps/api`. Evidence-based (file paths + lines). No changes applied yet — this is the audit that scopes the fixes.

---

## Current state

The white-label infrastructure is **well-built and comprehensive**. A single-row `BrandSetting` singleton (`apps/api/app/Platform/Branding/Models/BrandSetting.php`) exposes JSON groups `identity`, `logos`, `theme`, `email`, `certificate` → served via `GET /api/v1/branding` → server-fetched by `getBranding()` (`apps/web/src/lib/branding/api.ts`) → `BrandingProvider` → consumed by header, footer, root layout, and CSS-variable theming. Admin edits everything through `BrandSettingResource`.

**Admin-editable fields (verified):** brand/short/company name, copyright, address, support email/phone; social links (twitter/linkedin/facebook/instagram/youtube); logos (light/dark, favicon, apple/pwa/email/certificate/loader/login-bg); theme (12 colour slots ×2 modes, radius, container width, fonts, `google_font`, shadow/spacing presets); email header/footer/colours/signature/social; certificate background/logo/signature/stamp/qr/font/colours/margins. **The schema is not the problem — consumption is.**

## What is correctly admin-driven (✓, verified)

| Area | Evidence |
|---|---|
| Colours & fonts (token-driven) | `lib/branding/css.ts` → CSS vars; `layout.tsx:88-101` injects `brandThemeCss` + `googleFontCss`. **0** hardcoded 6-digit hex in `src/components` and `src/app`; the 9 three-digit hex are decorative SVG art. |
| Header | `landing-header.tsx:17-55` — brand name + `logo_light` from `useBranding()` |
| Footer brand/copyright/social | `landing-footer.tsx:19-31,138` — from `useBranding()`; empty-string defaults leak nothing |
| Certificates (issuer/signature/template) | `CertificateRenderService.php:37-51` reads `CertificateSetting::current()`; template/logo from admin `CertificateTemplate` |
| Social links | admin `identity.social_links`; **no** hardcoded social URLs in `apps/web/src` |

## Problems found (classified, prioritized)

1. **Transactional emails ignore admin branding — biggest gap.** OTP/reset/phone notifications (`apps/api/app/Platform/Identity/Notifications/{EmailOtp,ResetPassword,PhoneOtp}Notification.php`) use plain `MailMessage` with hardcoded English text and `config('app.name')` — **not** `BrandSetting`. The entire admin-editable `BrandSetting.email` group + `logos.email_logo` is **consumed by nothing**.
2. **Marketing pages + i18n hardcode "HElbaron".** ~15 route files under `app/(marketing)/(site)/*` (about, contact, privacy, terms, pricing, events, verify, categories, courses, products, trainers) hardcode the brand in metadata/body instead of `getBranding()`. `lib/i18n/dictionaries.ts` hardcodes it in auth/verify subtitles (EN 111,120,305,612 / AR 768,777,962,1238). Components: `legal-page.tsx:12`, `cms-page.tsx:37`, `sidebar.tsx:27`, `testimonials-section.tsx:15-16`.
3. **Footer locations/legal + contact info hardcoded.** `config/theme.ts:141` hardcodes `locations: ["Cairo","Dubai","Riyadh"]` and legal links instead of `identity.address`; `contact/page.tsx:71-72` hardcodes `hello@helbaron.academy` instead of `identity.support_email`.
4. **Two competing certificate-branding sources.** `BrandSetting.certificate` (logo/background/stamp/colours/font/margins) is admin-editable but **unused**; the renderer uses the separate `CertificateSetting`/`CertificateTemplate`. Overlapping — should consolidate to one source of truth.
5. **No branded invoice/receipt document.** `Invoice` model stores numbers only; there is no invoice PDF/template rendering company identity. Not a branding *leak*, but a feature gap if invoices must carry brand identity. No contract/agreement feature exists.

## Legitimate fallbacks (NOT problems — do not "fix")

`lib/branding/api.ts:111-116`, `config/site.ts:2`, `config/theme.ts:16`, `BrandSetting.php:71-82` default to "HElbaron" / `support@helbaron.com` **as fallbacks** when the admin has not set values — correct behavior. Internal cookie/cache prefixes (`helbaron_session`, `helbaron.user`) and `.env APP_NAME` are non-user-facing — correct.

## Changes applied

None yet (audit phase). Fixes are scoped below; each will be behavior-preserving (fallbacks retained) and verified against typecheck + tests before commit.

## Remaining opportunities (proposed fix order, all behavior-preserving)

1. **Emails → BrandSetting** (highest impact): a shared branded mail layout that reads `BrandSetting.email` (header/footer/colours/signature/logo) and brand name, used by the three notifications. Fallback to `config('app.name')` when unset.
2. **Marketing metadata/body → `getBranding()`**: replace the ~15 hardcoded "HElbaron" occurrences in marketing routes/components with the branding value (server component already fetches it), keeping the string only as the default inside `getBranding()`.
3. **i18n brand interpolation**: make `dictionaries.ts` brand-name a `{brand}` token interpolated from branding, not a literal.
4. **Footer locations/contact → `identity.address`/`support_email`** with the current values as defaults.
5. **Consolidate certificate branding** onto one source (`CertificateSetting` is the live one; either wire `BrandSetting.certificate` through or remove it to end the ambiguity).

> These touch ~20 files across both apps and each needs host `typecheck`/`test`/build verification, so they will be implemented in small, individually-verified batches — not one large unreviewable change.
