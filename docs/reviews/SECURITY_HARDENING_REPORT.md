# Security Hardening Report — HElbaron LMS

**Date:** 2026-07-15
**Method:** Live header/behaviour checks in the user's Chrome against the running stack + authoritative source verification (Next.js config, BFF session route, API providers/routes). One defect was found and **fixed**.

## Defect found and fixed

### SEC-01 — [High, FIXED] Open redirect via the login `redirect` parameter (CWE-601)
- **Where:** `app/(marketing)/(auth)/login/page.tsx` — `onSuccess: () => router.replace(params.get("redirect") ?? "/")` passed the untrusted `redirect` query param straight to the router. `/(...)/login?redirect=https://evil.com` (or protocol-relative `//evil.com`) would redirect the user **off-site** after a successful login — a phishing/credential-relay vector.
- **Fix:** added `safeRedirect()` in `lib/utils.ts` and used it at the redirect site. It only allows **root-relative, same-origin paths** (must start with a single `/`, not `//` or `/\`, and must not smuggle a scheme or control chars); anything else falls back to `/`.
- **Verification (logic, in-browser):** `safeRedirect` maps `https://evil.com` → `/`, `//evil.com` → `/`, `/\evil.com` → `/`, while preserving `/dashboard` and `/orders?tab=paid`. Login page recompiles and renders. Files: `apps/web/src/lib/utils.ts`, `apps/web/src/app/(marketing)/(auth)/login/page.tsx`.

## Verified secure (evidence-based)

| Control | Status | Evidence |
|---|---|---|
| **Clickjacking** | ✅ | Live response headers: `X-Frame-Options: DENY` **and** CSP `frame-ancestors 'none'` (double coverage). |
| **CSP** | ✅ | `Content-Security-Policy` present with `script-src 'self'`; `'unsafe-eval'` appears **only in dev** (Fast Refresh) — production build omits it (dev-aware CSP, the LB-01 fix). |
| **MIME sniffing** | ✅ | `X-Content-Type-Options: nosniff`. |
| **Referrer leakage** | ✅ | `Referrer-Policy: strict-origin-when-cross-origin`. |
| **Transport security** | ✅ | `Strict-Transport-Security: max-age=63072000; includeSubDomains` (HSTS). |
| **Feature policy** | ✅ | `Permissions-Policy: camera=(), microphone=(), geolocation=()`. |
| **Session cookie flags** | ✅ | BFF (`app/api/session/route.ts`) sets the Sanctum token cookie **`httpOnly: true, secure (prod), sameSite: "lax"`** — never exposed to JS (XSS token-exfil mitigated). Only a **non-sensitive** boolean marker (`helbaron_authed`) is JS-readable. Logout clears both (`maxAge: 0`). |
| **CSRF** | ✅ | SameSite=Lax cookies + same-origin BFF proxy (`/api/backend/*`) + Sanctum. |
| **XSS** | ✅ | All CMS/lesson-authored HTML is sanitized with **DOMPurify** (`components/homepage/blocks/rich-text-block.tsx`, `components/learning/lesson-content.tsx`); React auto-escapes; CSP `script-src 'self'`. Other `dangerouslySetInnerHTML` are JSON-LD and admin brand CSS (structured/admin config). |
| **Rate limiting (brute force)** | ✅ | Named limiters defined + applied: `identity-login` (10/min), `identity-register` (6/min), `identity-password` (6/min), `identity-otp-verify` (10/min), `commerce-checkout` (10/min), `certification-verify` (30/min). |
| **Session expiration** | ✅ | An expired session (401 from BFF) clears the marker cookie and redirects to `/login` (auth-context refresh handler; verified earlier — OBS-02). |
| **Permission guards / broken authz** | ✅ | Frontend `RequireAuth`/`RequireGuest` + role checks; API `auth:sanctum` middleware groups (cart/checkout/orders/contracts/teach); instructor mutations are ownership-guarded; commerce reads are user-scoped (a learner sees only their own orders/contracts — verified in Commerce QA). |
| **Broken object references (IDOR)** | ✅ (spot) | Commerce list endpoints are user-scoped; contract accept is by id within the auth'd user's scope; no unauthenticated by-id object routes in commerce. |

## Notes / low-severity hardening recommendations (not blockers)

- **JSON-LD `</script>` escaping (Low):** several pages emit `dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}` inside `<script type="application/ld+json">`. `JSON.stringify` does not escape `<`, so a CMS/SEO field containing `</script>` could theoretically break out. Data is largely admin-controlled (titles/brand), so risk is low, but escaping `<`/`>`/`&` (or `<` → `<`) in the JSON-LD output would fully close it. Recommend a tiny `jsonLdSafe()` wrapper.
- **File-upload validation (verify):** Filament upload fields (course thumbnail, media) should be confirmed to enforce `acceptedFileTypes` + `maxSize`. This could not be exercised through the automation harness (Livewire upload) — recommend a backend feature test asserting mime/size rules on the upload FormRequests, and a manual upload check.
- **Mixed content:** N/A on localhost; HSTS + CSP enforce HTTPS-only in production.

## Not executable in this environment (handed off)
- Active DAST/penetration testing (SQLi/traversal fuzzing) is out of scope for a browser review; the app uses Eloquent (parameterized) and FormRequest validation throughout. Recommend a scheduled DAST scan (OWASP ZAP) against a staging deploy as a release gate.

## Net result
Strong security posture: full security-header set (incl. HSTS + double clickjacking protection), best-practice httpOnly/Secure/SameSite session-cookie handling, DOMPurify-sanitized rich content, comprehensive auth rate-limiting, and user-scoped authorization. **One High defect (open redirect) found and fixed (SEC-01).** Two low-severity hardening items (JSON-LD escaping, explicit upload-rule tests) are documented for follow-up.
