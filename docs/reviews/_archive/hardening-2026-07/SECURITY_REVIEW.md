# Security Review (Audit)

**Date:** 2026-07-16 · **Scope:** `apps/web` + `apps/api` + CI/Docker. Evidence-based; no changes applied.

## Current state (verified)
- **Security headers** (`apps/web/next.config.ts:38-46`) on all routes: CSP, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: DENY`, `Permissions-Policy: camera=(),microphone=(),geolocation=()`, `Strict-Transport-Security: max-age=63072000; includeSubDomains`. ✓
- **CSP** (`:23-36`): `default-src 'self'`; `object-src 'none'`; `base-uri 'self'`; `frame-ancestors 'none'`; `form-action 'self'`; `connect-src 'self' {apiOrigin}`; framed media restricted to youtube-nocookie + player.mux.com. `'unsafe-eval'` scoped to dev only (prod excludes it). ✓
- **Container images**: CI run #16 Trivy CRITICAL/HIGH — **API image 0 vulnerabilities** (alpine + composer-vendor both clean); **Web image clean** (with `.trivyignore` applied). ✓
- **Secret scan**: gitleaks green ("No leaks detected"). ✓
- **Backend**: 33 FormRequests + 22 Policies; mass-assignment limited to system-managed pivots (`$guarded=[]` only on `CourseTrainer`/`SessionTrainer`/`AuditLog`, never filled from request input). ✓

## Problems / opportunities (prioritized, evidence)
| # | Severity | Finding | Evidence / status |
|---|---|---|---|
| SEC-1 | Med | **CSP `script-src 'unsafe-inline'` in production** — required by Next's inline runtime until a nonce-based CSP is adopted (documented) | `next.config.ts:14-22`. Opportunity: nonce-based CSP (Next middleware nonce + `strict-dynamic`). Higher-effort; regression surface = hydration/inline scripts. |
| SEC-2 | Low | **`.trivyignore` picomatch exception (CVE-2026-33671)** — **upstream fix confirmed exists (picomatch ≥ 4.0.4)**; host tree already pinned to 4.0.4 via `overrides`. The exception covers only the copy **vendored in `next/dist/compiled/picomatch`** (npm-unreachable; version field stripped, cannot be read statically). Not runtime-exploitable — the app never matches attacker-controlled globs (build-time tooling only). | `apps/web/.trivyignore`. **Removal test (backlog):** delete the ignore, run the web-image Trivy job in CI; if it stays green, Next's bundled copy is ≥4.0.4 → drop the exception permanently; if it flags picomatch, keep the documented exception until a Next bump. This is a CI-measured experiment — not claimed removable without that green run. |
| SEC-3 | Low | **Cookie flag verification** — auth is cookie-based (`helbaron_session`); confirm `HttpOnly` + `Secure` + `SameSite` are set at issue time on the API/session route | `middleware.ts:9`, `app/api/session/route.ts` (verify; not asserted here) |
| SEC-4 | Info | **Node 20 → 24 deprecation warnings** in CI actions (cosmetic, non-blocking) | CI run #16 annotations |

## Changes applied
None (audit). SEC-2 removal test and SEC-3 verification queued in the backlog; SEC-1 nonce-CSP is a larger optional hardening.

## Verdict
Security posture is strong: strict headers, clean production-image scans, no secrets, disciplined validation/authorization. The one temporary exception (picomatch) has a confirmed upstream fix; its removal is a bounded, CI-measured experiment. No high-severity issues found.

Sources: [CVE-2026-33671 · GitHub Advisory (GHSA-c2c7-rcm5-vvqj)](https://github.com/advisories/GHSA-c2c7-rcm5-vvqj), [Snyk SNYK-JS-PICOMATCH-15765511](https://security.snyk.io/vuln/SNYK-JS-PICOMATCH-15765511)
