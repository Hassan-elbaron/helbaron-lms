# HElbaron LMS — Application Security Audit (09)

**Repository:** local working copy (`apps/api` Laravel 12 + Sanctum, `apps/web` Next.js 15).
**Scope:** Application security ONLY (authn/authz, sessions, API, input, files, payments, media, tenancy, data protection, OWASP). No UI/UX/architecture/DevOps/product review.
**Assumes:** Reviews 01–08 exist; not repeated.
**Method:** White-box code audit + targeted sink sweeps: raw SQL, `dangerouslySetInnerHTML`, rate-limit coverage, crypto/hashing, CORS/CSP/security-headers configs, MFA/OTP storage, model `$fillable`/`$hidden`, tenant scoping. Pen-tester lens: what an authenticated-but-malicious user, a malicious instructor, and an external attacker can do before launch.
**Framework:** OWASP Top 10 (2021) + ASVS L2 orientation.

---

## Executive Summary

HElbaron's backend has a **notably mature security posture** for a pre-launch product — stronger than most. The evidence:

- **Password storage:** `'password' => 'hashed'` cast (Laravel bcrypt/argon). No plaintext.
- **OTP:** stored as `hash('sha256', $code)` and verified with **`hash_equals`** (timing-safe) — not plaintext, not `==`.
- **MFA:** `two_factor_secret` = `encrypted`, `two_factor_recovery_codes` = `encrypted:array` — encrypted at rest.
- **Serialization safety:** `password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes` are in `$hidden` — no credential leakage in API responses.
- **Mass assignment:** all 81 models use `$fillable` (0 unguarded).
- **Rate limiting:** named throttles on register, login, forgot/reset password, email/phone OTP verify, and MFA verify — real brute-force protection on the identity surface.
- **CORS:** explicit env allow-list, **never `*`**, credentials scoped to the first-party SPA.
- **Security headers + CSP:** `SecurityHeaders` middleware emits `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, COOP/CORP `same-origin`, `Permissions-Policy`, HSTS (HTTPS-only), and a **strict API CSP** (`default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'`).
- **Payment/webhook:** signature-verified, **idempotent by `event_id`**, order row-locked (per 05).
- **SQL injection:** the only 3 raw expressions (`orderByRaw`, `selectRaw`) use **static strings with no user input** — no injection.

That said, an external pen-tester would still flag a **small set of real, exploitable issues** — most importantly a stored-XSS chain and tenant-isolation weakness:

1. **[HIGH → CRITICAL chain] Stored XSS in lesson content + bearer token in `localStorage`.** `lesson-content.tsx` renders admin/instructor-authored article HTML via `dangerouslySetInnerHTML` with a developer TODO ("Sanitize server-side before production exposure") — and the **Next.js frontend has no CSP** (the strict CSP is API-only). A malicious/compromised instructor can inject `<img onerror=…>`/`<script>` into a lesson; it executes in every enrolled student's browser, and because the auth token lives in `localStorage`, the script **exfiltrates the token → full account takeover**. The two individually-medium issues combine into a critical account-takeover path.
2. **[HIGH] Broken object/tenant isolation (BOLA/IDOR).** Organization scoping is **manual** (only 2 global scopes across 8 org-scoped CRM models — per 05). A missed `where('organization_id', …)` on any endpoint leaks another org's leads/contacts/consulting data. Route-model-binding by non-sequential `public_id` + policies mitigate simple IDOR, but tenant isolation is not enforced by default.
3. **[MEDIUM] Auth token in `localStorage`** (root of #1's impact) — should be an httpOnly cookie.
4. **[MEDIUM] No CSP/security headers on the Next.js app** — the strong CSP protects API JSON, not the HTML surface where XSS actually executes.
5. **[MEDIUM] No dependency/image/secret scanning in CI** (per 08) — unknown vulnerable-dependency exposure.
6. **[MEDIUM] Rate limiting not extended to commerce/verification** — checkout, coupon-apply, and public certificate verification lack throttles (coupon-brute / verification-enumeration / abuse).

**Verdict:** the cryptographic and authentication fundamentals are strong; the launch blockers are the **XSS chain (#1)**, **tenant isolation (#2)**, and **auth token storage (#3)**, plus hardening (#4–#6).

---

## Overall Security Score

**7.3 / 10** — "mature fundamentals; a stored-XSS chain, tenant-isolation gap, and token-storage choice to close before launch."

| Domain | Score | Note |
|--------|-------|------|
| Authentication | 8.5 | Hashed pw, MFA (encrypted), OTP (sha256+hash_equals), throttled |
| Authorization | 6.5 | 20 policies; manual tenant scoping is the hole |
| Session/token | 5.5 | Sanctum; but token in localStorage |
| API security | 7.5 | Envelope, CORS allow-list, idempotent webhook |
| Input validation | 7.0 | Form Requests; some coverage gaps (per 05) |
| Injection (SQLi) | 9.0 | No user input in raw SQL |
| XSS | 4.5 | Unsanitized lesson HTML + no web CSP |
| File upload | 6.0 | Validate mimes/size (verify per 05) |
| Payment | 8.0 | Signature + idempotency + coupon lock |
| Media | 8.0 | Signed Mux/CloudFront URLs |
| Tenant isolation | 5.0 | Manual, leak-prone |
| Sensitive data | 8.0 | Encrypted MFA, hidden secrets, no PII in responses |
| Logging/audit | 7.0 | Correlation IDs; audit coverage to verify |
| Headers/CSP (API) | 9.0 | Strict, config-driven |
| Headers/CSP (web) | 3.0 | Absent |
| Dependency security | 4.0 | No scanning |

---

## Authentication Security — 8.5

Sanctum bearer + email/phone OTP + TOTP MFA + password reset. Password `hashed`; OTP `sha256` + `hash_equals`; MFA secret & recovery codes `encrypted`. Throttles on all identity endpoints (`identity-login/register/password/otp-verify`). Admin panel adds `EnforceAdminMfa`.

| # | Sev | Finding | Attack scenario | Recommendation |
|---|-----|---------|-----------------|----------------|
| AUTHN-1 | Med | Password reset delivers raw token to SPA URL (`/reset-password?token=`) | Token in referer/history/logs if TLS or logging misconfigured | Confirm token is single-use, short-TTL, and not logged; consider code+email binding |
| AUTHN-2 | Low | Verify account-lockout/backoff after repeated MFA/OTP failures (beyond request throttle) | Online guessing of 6-digit OTP within rate window | Add per-account failed-attempt lockout + exponential backoff |
| AUTHN-3 | Low | Enumeration: ensure login/forgot responses are uniform for existing vs unknown emails | User enumeration | Return generic responses/timing |

## Authorization Security — 6.5

20 policies cover every aggregate (per 05). Route-model binding by `public_id` (non-sequential) reduces enumeration. **Gap:** tenant scoping is manual (TEN-1 below); also client-side role guards are UX-only (backend policies are the real gate — good).

| # | Sev | Finding | Attack scenario | Recommendation |
|---|-----|---------|-----------------|----------------|
| AUTHZ-1 | High | Manual org scoping (see Tenant Isolation) | Org A user reads Org B data via a missed scope | Global tenant scope + tests |
| AUTHZ-2 | Med | Verify every controller mutation calls `authorize()`/policy (41 controllers, 20 policies) | Missing gate → privilege escalation | Audit each write path for an explicit authorization check |
| AUTHZ-3 | Med | Instructor/course-ownership enforcement on authoring/live mutations | Instructor edits another instructor's course/session | Ownership checks in policies |

## Session Security — 5.5

| # | Sev | Finding | Attack scenario | Business impact | Recommendation |
|---|-----|---------|-----------------|-----------------|----------------|
| SES-1 | Med→High (chained) | Bearer token stored in `localStorage` (`helbaron.token`) | Any XSS (e.g., XSS-1) reads the token → account takeover | Full account/session takeover | Move to httpOnly, Secure, SameSite cookie (Sanctum stateful); remove token from JS-readable storage |
| SES-2 | Low | Confirm token revocation on logout + device revoke invalidates server-side token | Stolen token replay after logout | Ensure server-side token deletion (Sanctum `delete()`), not just client clear |

## API Security — 7.5

Standard envelope, correlation IDs, allow-list CORS with credentials, idempotent signature-verified webhook. Verify: (a) no verbose error/stack leakage in prod (`APP_DEBUG=false`), (b) mass-assignment safe (confirmed), (c) pagination limits capped to prevent resource exhaustion.

| # | Sev | Finding | Recommendation |
|---|-----|---------|----------------|
| API-1 | Med | Ensure `APP_DEBUG=false` in prod and exceptions return the sanitized envelope (no stack traces) | Enforce via env-validate + test |
| API-2 | Med | Cap `per_page`/list sizes; reject oversized page params | Add max bounds in Form Requests |
| API-3 | Low | Add security.txt + disable `X-Powered-By`/server tokens (nginx `server_tokens off` set) | Minor hardening |

## Input Validation — 7.0

31 Form Requests + Zod on the client (defense in depth). Gaps (per 05): not every write path has a FormRequest; commerce numeric bounds and file mime/size validation need confirmation.

| # | Sev | Finding | Recommendation |
|---|-----|---------|----------------|
| VAL-1 | Med | Ensure every state-changing endpoint validates server-side (client Zod is not a control) | FormRequest on all writes |
| VAL-2 | Med | Enforce enum/`Rule::enum`, integer minor-units + min/max on money, bounded quantities | Strict commerce validation |

## File Upload Security — 6.0

Uploads flow to S3; `client_max_body_size 52M` at nginx. Verify server-side validation.

| # | Sev | Finding | Attack scenario | Recommendation |
|---|-----|---------|-----------------|----------------|
| FILE-1 | Med | Confirm mime/extension/size validation + re-encode images | Malicious file (SVG-with-script, polyglot) served to users | Validate `mimes`, cap size, store off-origin (S3), serve via signed URLs with correct `Content-Type` + `Content-Disposition` |
| FILE-2 | Low | Ensure no user-controlled path in storage keys (path traversal) | `../` in filename overwrites objects | Generate server-side keys (UUID); never trust client filename |

## Payment Security — 8.0

Signature-verified webhook, dedup by `event_id`, order `lockForUpdate`, coupon `lockForUpdate`, enrollment gated post-payment (per 05). Strong.

| # | Sev | Finding | Attack scenario | Recommendation |
|---|-----|---------|-----------------|----------------|
| PAY-1 | Med | Coupon abuse: re-validate expiry/cap/per-user/scope **under the lock**, not just at cart time | Race to over-redeem or apply expired coupon | Re-validate inside locked section (per 05/CHK-2) |
| PAY-2 | Med | No throttle on checkout/coupon-apply | Coupon brute-forcing; order spam | Add `throttle` to commerce endpoints |
| PAY-3 | Low | Ensure amounts are recomputed server-side from product prices (never trust client totals) | Price tampering | Confirm server authoritative pricing (PricingService) |

## Media Security — 8.0

Signed Mux playback + CloudFront signed URLs (per 05). Verify: playback token requires **active enrollment** and is short-lived; signed URLs are not cacheable/shareable beyond TTL.

| # | Sev | Finding | Attack scenario | Recommendation |
|---|-----|---------|-----------------|----------------|
| MED-1 | Med | Confirm enrollment check precedes playback-token issuance | Non-enrolled user obtains a playback token | Enforce `LessonAccessService` gate before signing |
| MED-2 | Low | Short TTL + single-audience on signed URLs | Link sharing / hotlinking | Keep TTLs tight; bind to user where possible |

## Tenant Isolation — 5.0

| # | Sev | Finding | Evidence | Attack scenario | Business impact | Recommendation |
|---|-----|---------|----------|-----------------|-----------------|----------------|
| TEN-1 | High | Org isolation is manual (2 global scopes vs 8 `organization_id` models) | per 05 sweep | Authenticated Org A user requests Org B object (BOLA); any endpoint missing the scope leaks leads/contacts/consulting/seats | Cross-tenant data breach; compliance exposure | Add a `BelongsToOrganization` global scope/trait applied to all org-owned models; write tenant-isolation tests (A cannot read/modify B) for every org endpoint |

## Sensitive Data Protection — 8.0

Encrypted MFA secret/recovery codes; hashed passwords; OTP hashed; secrets in `$hidden` (no leakage). No stray theme/hardcoded secrets in the repo (per publishing scan). Verify: PII (email/phone) is not written to logs; export files (CSV/XLSX) with PII are access-controlled + expiring.

| # | Sev | Finding | Recommendation |
|---|-----|---------|----------------|
| DATA-1 | Med | Confirm logs scrub PII and never log OTPs/tokens/passwords | Add a logging processor to redact sensitive keys |
| DATA-2 | Med | Analytics exports may contain PII | Store in private bucket, signed short-TTL links, lifecycle-expire |

## Logging and Audit — 7.0

Correlation IDs end-to-end; JSON logs. `HasAudit` trait exists. Verify the audit trail captures sensitive mutations with actor + before/after: role/permission changes, refunds, enrollment grant/revoke, certificate revoke/reissue, MFA disable, org member changes.

| # | Sev | Finding | Recommendation |
|---|-----|---------|----------------|
| LOG-1 | Med | Confirm audit coverage on privileged actions | Add audit assertions/tests on the sensitive mutations above |
| LOG-2 | Low | Tamper-resistance of audit log | Append-only store / off-box shipping |

## OWASP Top 10 (2021) Review

| Category | Status | Evidence / Finding |
|----------|--------|--------------------|
| A01 Broken Access Control | ⚠️ Partial | Policies strong; **manual tenant scoping (TEN-1)**; client-only role guards (backend enforces) |
| A02 Cryptographic Failures | ✅ Good | Hashed pw, encrypted MFA, sha256 OTP, HSTS, TLS upstream |
| A03 Injection | ✅ Good (SQLi) / ⚠️ XSS | No SQLi (static raw SQL); **stored XSS via lesson HTML (XSS-1)** |
| A04 Insecure Design | ✅ Mostly | Fulfillment gating, idempotent webhook, ports/adapters |
| A05 Security Misconfiguration | ⚠️ Partial | Strong API headers/CSP; **no web CSP (XSS-2)**; ensure `APP_DEBUG=false` |
| A06 Vulnerable Components | ⚠️ Unknown | **No dependency scanning (DEP-1)** |
| A07 Auth Failures | ✅ Good | MFA, throttling, hashed creds; add account lockout (AUTHN-2) |
| A08 Software & Data Integrity | ⚠️ Partial | Webhook signature ✅; no image/signature scanning in CI (per 08) |
| A09 Logging & Monitoring Failures | ⚠️ Partial | Correlation logs ✅; no alerting (per 08); audit coverage to verify |
| A10 SSRF | ✅ Low | No obvious user-controlled outbound fetch; verify webhook/callback URLs aren't user-set |

## OWASP ASVS Review (L2 orientation)

- **V2 Authentication:** Strong (MFA, hashed, throttled). Gaps: account lockout, enumeration uniformity.
- **V3 Session Management:** Weak spot — token in `localStorage` (should be httpOnly cookie); verify server-side revocation.
- **V4 Access Control:** Object-level via policies + `public_id`; **function/tenant-level scoping manual** — the ASVS gap.
- **V5 Validation/Encoding:** Server FormRequests + client Zod; **output encoding fails for lesson HTML** (unsanitized).
- **V7 Cryptography:** Encrypted secrets, hashed OTP/pw — good; verify key management (APP_KEY rotation).
- **V9 Communications:** HSTS + TLS upstream + strict CORS — good.
- **V12 Files/Resources:** Verify upload validation + server-generated keys.
- **V13 API:** Envelope, rate limits on auth; extend to commerce.

## Dependency Security — 4.0

| # | Sev | Finding | Recommendation |
|---|-----|---------|----------------|
| DEP-1 | Med | No `composer audit`/`npm audit`/Trivy/gitleaks in CI (per 08) | Add all four as CI gates; fail on High/Critical |
| DEP-2 | Low | No SBOM / no base-image update policy | Generate SBOM; automate base-image rebuilds |

## Penetration Test Findings (prioritized)

| ID | Severity | Title | Exploit summary |
|----|----------|-------|-----------------|
| PT-1 | **Critical (chain)** | Account takeover via stored XSS + localStorage token | Malicious instructor injects script into lesson article HTML (XSS-1); no web CSP (XSS-2); student opens lesson; script reads `localStorage["helbaron.token"]` (SES-1) and exfiltrates → attacker assumes student/any-viewer session |
| PT-2 | High | Cross-tenant data access (BOLA) | Authenticated Org A user hits an org endpoint missing the manual scope → reads/modifies Org B leads/contacts (TEN-1) |
| PT-3 | Medium | Coupon brute-force / abuse | No throttle on coupon-apply + validity checked outside lock (PAY-1/PAY-2) |
| PT-4 | Medium | Certificate-verification enumeration / abuse | Public verify endpoint unthrottled → scrape/enumerate verification codes |
| PT-5 | Medium | Non-enrolled media access (if gate missing) | Request playback token without enrollment (MED-1) |
| PT-6 | Low | User enumeration via auth responses | Distinguish existing vs unknown accounts (AUTHN-3) |

---

## High Priority Fixes (ordered)

- **P0-1 (PT-1 / XSS-1+XSS-2+SES-1):** Sanitize lesson HTML (server + client), add a CSP to the Next.js app, and move the auth token to an httpOnly cookie — break the account-takeover chain at all three links.
- **P0-2 (PT-2 / TEN-1):** Enforce tenant isolation via a global scope + tests.
- **P1-1 (PT-3/PT-4 / PAY-2):** Rate-limit checkout, coupon-apply, and certificate verification; re-validate coupons under lock.
- **P1-2 (MED-1):** Enforce enrollment before issuing media playback tokens.
- **P1-3 (DEP-1):** Add dependency/image/secret scanning to CI.
- **P2-1 (DATA-1/LOG-1):** PII log scrubbing + audit coverage on privileged actions.
- **P2-2 (AUTHN-2/AUTHN-3, API-1):** Account lockout, uniform auth responses, `APP_DEBUG=false` enforcement.

---

## AI Implementation Prompts

**AIP-1 — Kill the XSS→token chain (PT-1)**
> (a) In `apps/api`, sanitize lesson article HTML server-side on save (HTMLPurifier allow-list: block `<script>`, event handlers, `javascript:` URLs, `<iframe>` except trusted embeds) so stored content is safe. (b) In `apps/web/src/components/learning/lesson-content.tsx`, additionally sanitize with DOMPurify before `dangerouslySetInnerHTML` as defense-in-depth. (c) Add a Content-Security-Policy to the Next.js app (via `next.config.ts` headers or middleware) disallowing inline scripts (`script-src 'self'`), so injected inline scripts cannot execute. (d) Migrate the auth token from `localStorage` to an httpOnly, Secure, SameSite=strict cookie (Sanctum stateful); update `lib/api/client.ts` to `credentials: "include"` and remove `helbaron.token`.

**AIP-2 — Tenant isolation global scope (PT-2/TEN-1)**
> Create `App\Domains\Crm\Concerns\BelongsToOrganization` applying a global scope that constrains queries to the authenticated actor's `organization_id`, with an explicit `withoutOrganizationScope()` for admin/system use. Apply it to all 8 org-scoped models. Add feature tests asserting Org A users receive 403/404 for Org B records on every list and detail endpoint (leads, contacts, consulting, members, seats).

**AIP-3 — Rate-limit money & verification endpoints (PT-3/PT-4)**
> Register named RateLimiters `commerce-checkout`, `commerce-coupon`, and `certificate-verify`, and apply `throttle:<name>` to the checkout, coupon-apply, and public certificate verification routes. In `CheckoutAction`, re-validate coupon expiry/usage-cap/per-user-limit/scope inside the `lockForUpdate` section before incrementing `redeemed_count`. Add tests for coupon race + verification throttle.

**AIP-4 — Enforce enrollment before media tokens (PT-5/MED-1)**
> Ensure the lesson playback-token endpoint calls `LessonAccessService` (active enrollment + lesson access) before issuing a Mux/CloudFront signed URL, returns 403 otherwise, and issues short-TTL, single-audience tokens. Add tests for non-enrolled and expired-enrollment users.

**AIP-5 — CI security gates (DEP-1)**
> Add to CI: `composer audit` (api), `npm audit --audit-level=high` (web), Trivy image scan (fail on HIGH/CRITICAL), and gitleaks secret scan. Fail the build on high-severity findings.

**AIP-6 — Sensitive logging + audit coverage (DATA-1/LOG-1)**
> Add a Monolog processor that redacts keys matching password/token/otp/secret/authorization from log context. Verify (and add tests) that `HasAudit` records actor + before/after for role/permission changes, refunds, enrollment grant/revoke, certificate revoke/reissue, MFA disable, and org membership changes.

**AIP-7 — Auth hardening (AUTHN-2/AUTHN-3/API-1)**
> Add per-account failed-attempt lockout with exponential backoff for login/OTP/MFA (in addition to request throttling); make login/forgot-password responses and timing uniform for existing vs unknown accounts; enforce `APP_DEBUG=false` in production via the env-validate init gate and assert exceptions return the sanitized envelope without stack traces.

---

## Acceptance Criteria

- AC1 (PT-1): Lesson HTML is sanitized server-side and client-side; the web app sends a CSP that blocks inline script; the auth token is not present in `localStorage`. A crafted `<img onerror>`/`<script>` in a lesson does not execute and cannot read the token. (pentest re-test passes)
- AC2 (PT-2): Cross-organization access is denied by default; tenant-isolation tests pass for every org endpoint.
- AC3 (PT-3): Checkout, coupon-apply, and certificate verification are rate-limited; coupon validity is re-checked under lock; race/brute tests pass.
- AC4 (PT-5): Media playback tokens require active enrollment and are short-lived; non-enrolled access returns 403.
- AC5 (DEP-1): CI fails on high-severity dependency, image, or secret findings.
- AC6 (DATA-1/LOG-1): Logs contain no secrets/PII; audit records exist for all privileged mutations.
- AC7 (AUTHN): Account lockout + uniform auth responses in place; `APP_DEBUG=false` enforced in prod; no stack traces in API errors.
- AC8 (crypto/session): Passwords hashed, MFA/recovery encrypted, OTP hashed (already ✅ — regression-tested); server-side token revocation on logout/device-revoke verified.
- AC9 (traceability): Every finding (AUTHN/AUTHZ/SES/API/VAL/FILE/PAY/MED/TEN/DATA/LOG/DEP + PT IDs) maps to a fix and a criterion.

---

### Appendix — Evidence index
- Crypto: `User.php` casts (`password=>hashed`, `two_factor_secret=>encrypted`, `two_factor_recovery_codes=>encrypted:array`); `$hidden` includes password/remember_token/2FA fields. `OtpService` (`hash('sha256',$code)` + `hash_equals`).
- Headers/CSP: `config/security.php` (nosniff, X-Frame DENY, Referrer-Policy, COOP/CORP same-origin, Permissions-Policy, CSP `default-src 'none'…`, HSTS), applied by `SecurityHeaders` middleware (HSTS HTTPS-only).
- CORS: `config/cors.php` (env allow-list, never `*`, credentials, scoped headers).
- Rate limiting: `Identity/routes/auth.php` (throttle on register/login/password/otp-verify/mfa); `Notifications/Services/RateLimiterService`.
- Injection: only `orderByRaw('read_at IS NOT NULL')`, `selectRaw('period, SUM(value)…')`, `selectRaw("to_char(period,'YYYY-MM')…")` — static, no user input.
- XSS: `apps/web/src/components/learning/lesson-content.tsx:103` `dangerouslySetInnerHTML` with TODO "Sanitize server-side before production exposure"; Next app has no CSP (per 06).
- Token storage: `lib/api/client.ts` `localStorage["helbaron.token"]` (per 05/06).
- Tenant: 2 global-scope files vs 8 CRM models with `organization_id` (per 05).
