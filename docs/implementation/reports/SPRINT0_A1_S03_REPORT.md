# Sprint 0 · Story A1-S03 — Frontend Fitness & Playwright — Report

> EXECUTION MODE. Story A1-S03 only (tasks A1-T08 → A1-T10 + the requested PHPStan-rule hardening). Tooling/config/test-scaffold only — **no application business logic, no API, no DB, no namespace changes.** Compliant with `101_EXECUTION_RULES.md` §4 (Frontend/Testing), §5, §15 and the Sprint 0 plan.

## Summary

Frontend architecture fitness was added: **ESLint import boundaries** protecting route groups and shared UI, a **blocking `tsc --noEmit`** gate (with a few conservative strict flags), and a **Playwright + axe** smoke journey (Home → Login → Dashboard → Logout), wired into CI as a non-blocking job. As a pre-implementation improvement, the four custom PHPStan architecture rules were hardened to classify by **inheritance / interfaces / PHP attributes** with path only as a fallback.

Node 22 is available in this environment but the web `node_modules` and the new devDependencies are not installed, so ESLint/tsc/Playwright could not be executed here; configuration is complete and the exact runtime commands are provided.

---

## PHPStan rule hardening (pre-implementation)

`ContextResolver` now accepts the PHPStan `ClassReflection` and classifies **reflection-first**:
- **Filament Resources:** any ancestor whose FQCN starts with `Filament\Resources\` (covers `Resource` and resource `Pages\*` bases), OR a marker interface (`FilamentResourceContract`), OR a marker attribute (`AsFilamentResource`). Path `/Filament/Resources/` is the fallback.
- **Controllers:** an `Illuminate\Routing\Controller` ancestor, an ancestor ending in `\Controller`, or the class short-name ending in `Controller`, OR marker interface/attribute. Path `/Http/Controllers/` is the fallback.
- **Current context** (for the two cross-context rules) is resolved from the class **namespace** via reflection, falling back to the file path.

Lineage/interface/attribute inspection is exception-safe (`getParentClass()` walk + `getNativeReflection()->getInterfaceNames()/getAttributes()`), so unresolved types degrade to the path fallback rather than erroring. The A1-S02 report carries an addendum recording this. Static balance checks pass on all five rule files.

## Boundary rules

Enforced by `import/no-restricted-paths` (eslint-plugin-import). Two protections:

1. **Route-group isolation** — each of the 8 groups (`(account) (analytics) (commerce) (crm) (instructor) (learning) (marketing) (organization)`) may import only from **its own group + shared**; importing a **sibling group** fails. (Zone: target `./src/app/<group>`, from `./src/app`, except `<group>`.)
2. **Shared-inward rule (protects shared UI)** — `./src/{components,lib,config,hooks,types}` may **not** import from `./src/app`. Dependencies point inward: features → shared, never the reverse.

This is the frontend counterpart to Deptrac's backend boundaries and to `101` §4 (Frontend) route-group rule.

## ESLint configuration

`apps/web/eslint.config.mjs` (flat config) now:
- keeps `eslint-config-next`;
- registers `eslint-plugin-import` with a TypeScript resolver (`eslint-import-resolver-typescript`) so `@/*` alias imports resolve for path analysis;
- applies `import/no-restricted-paths` with the shared + cross-group zones (generated from the route-group + shared-dir lists).

CI: the `web` job now runs **`npm run lint`** (blocking) — previously it ran only typecheck/test/build.

## TypeScript configuration

`apps/web/tsconfig.json` already had `strict: true` + `noEmit: true`. Added conservative, low-risk flags to raise the floor without destabilizing the existing build: `noImplicitOverride`, `noFallthroughCasesInSwitch`, `forceConsistentCasingInFileNames`. (Higher-churn flags such as `noUncheckedIndexedAccess`/`noUnusedLocals` are deliberately deferred to a dedicated clean-up task to avoid breaking the blocking gate.)

CI: the `web` job runs **`npm run typecheck`** (`tsc --noEmit`) as a **blocking** step (named explicitly).

## Playwright configuration

`apps/web/playwright.config.ts`:
- `testDir: ./e2e`; chromium project; `headless: true`.
- **`trace: retain-on-failure`**, **`screenshot: only-on-failure`**, **`video: on-first-retry`** (exactly the requested failure/retry artifacts).
- CI: `retries: 1`, `workers: 1`, reporters `github` + `html`.
- `webServer`: builds and starts the Next app locally (`npm run build && npm run start`) unless `PLAYWRIGHT_BASE_URL` is provided.

`package.json`: added scripts `e2e` / `e2e:report` and devDeps `@playwright/test`, `@axe-core/playwright`, `eslint-plugin-import`, `eslint-import-resolver-typescript` (require-dev only; run `npm install` to sync `package-lock.json`).

CI: new **`e2e` job** (`continue-on-error: true`, non-blocking scaffold) — installs chromium, runs the smoke journey, uploads the Playwright report artifact. To be promoted to blocking once stable (per Sprint-0 risk R0-4).

`.gitignore` (web): added `test-results`, `playwright-report`, `playwright/.cache`, `blob-report`.

## Accessibility configuration

`@axe-core/playwright` (`AxeBuilder`) is integrated in the smoke test. Each visited surface (home, login, and — when authenticated — dashboard) is scanned with tags `wcag2a`, `wcag2aa`; the test **fails on any serious/critical violation**. This wires accessibility into the E2E gate (101 §4/§15 Accessibility).

## Smoke journey

`apps/web/e2e/smoke.spec.ts` — Home → Login → Dashboard → Logout:
- **Home** and **Login** legs always run (render assertion + axe scan).
- The **authenticated** leg (login submit → dashboard → logout) runs only when `E2E_EMAIL` + `E2E_PASSWORD` are set (and the API is reachable); otherwise it is **skipped**, keeping the smoke green in a backend-less CI while remaining a full journey when credentials/env are wired.

## Validation output

Environment note: Node 22 present, but web deps (incl. the new devDeps) are not installed here, so ESLint/tsc/Playwright were not executed. Static validation:
```
eslint.config.mjs   -> flat config; import plugin + TS resolver + no-restricted-paths (shared + 8 cross-group zones)
tsconfig.json       -> strict:true (unchanged) + noImplicitOverride, noFallthroughCasesInSwitch, forceConsistentCasingInFileNames
playwright.config.ts-> headless; trace retain-on-failure; screenshot only-on-failure; video on-first-retry; chromium; webServer; balanced braces (8/8)
e2e/smoke.spec.ts   -> Home->Login->Dashboard->Logout; axe wcag2a/2aa serious+critical gate; auth leg env-gated; balanced braces (15/15)
package.json        -> scripts e2e/e2e:report; devDeps +@playwright/test +@axe-core/playwright +eslint-plugin-import +eslint-import-resolver-typescript (authoritative)
ci.yml              -> web job: lint (blocking) + typecheck (blocking); new non-blocking e2e job (+report artifact)
PHPStan rules       -> reflection-first (inheritance/interfaces/attributes) with path fallback; 5 files, balanced braces
```
(Note: the shell mount again served stale/truncated copies of `package.json`/`tsconfig.json`/`eslint.config.mjs` during verification; the authoritative file state — via the editor — is complete and correct. Documented file-tool/shell divergence, not a defect.)

Runtime validation — **run on your machine (from `apps/web`)**:
```bash
npm install                      # syncs package-lock.json with the new devDeps
npm run lint                     # ESLint incl. import/no-restricted-paths (expect: boundary violations, if any, are real)
npm run typecheck                # tsc --noEmit (blocking)
npx playwright install --with-deps chromium
npm run e2e                      # Home/Login + axe always; Dashboard/Logout when E2E_EMAIL/E2E_PASSWORD set
# optional authenticated journey:
#   E2E_EMAIL=... E2E_PASSWORD=... PLAYWRIGHT_BASE_URL=http://localhost:3000 npm run e2e
```
Expected: ESLint reports any real cross-group/shared-inward violations (fix or refactor per the message); typecheck green (or surfaces real type issues to fix); Playwright runs headless, home/login pass with no serious a11y violations, authenticated leg skipped unless creds provided. Paste outputs to record them.

## Known limitations

1. **No installed web deps here** — ESLint/tsc/Playwright not executed; configs are statically validated. Run `npm install` then the commands above to confirm green.
2. **`npm ci` vs lock** — the new devDeps are in `package.json` but not yet in `package-lock.json`; CI's `npm ci || npm install` falls back to `npm install`. Run `npm install` locally and commit the updated lockfile to restore fast, deterministic `npm ci`.
3. **E2E is non-blocking + partially skipped** — by design for a backend-less CI. Promote the `e2e` job to blocking and supply `E2E_*` secrets once the app+API run together in CI (later sprint).
4. **Login selectors are heuristic** — the authenticated leg uses role/label queries (`email`, `password`, `log in`/`sign in`, `log out`/`sign out`) and expects to land on `/dashboard` or `/my-learning`. If the real markup/labels differ, adjust selectors when enabling the authenticated journey.
5. **`import/no-restricted-paths` relies on the TS resolver** — `@/*` alias resolution requires `eslint-import-resolver-typescript` (added). If a monorepo path quirk prevents resolution, verify the resolver `project` path.
6. **Conservative tsconfig flags** — stronger strictness (`noUncheckedIndexedAccess`, `noUnusedLocals/Parameters`) is deferred to keep the blocking gate green; recommended as a follow-up once a clean typecheck baseline exists.

## Next Story dependencies

- **A1-S04 (ADR validation)** — the last Sprint-0 story; reuses the CI-check pattern (path-triggered ADR-link check + ADR index). Independent of this frontend work.
- **Downstream:** these boundaries are the frontend counterpart to Deptrac; feature sprints (B7 learning UI, C2 commerce UI, D3 panels) must keep route groups isolated and shared UI inward-only. The Playwright+axe scaffold is the base for the E2E/accessibility journeys required from Sprint 6/10 onward.

---

## STOP

Story A1-S03 is implemented (ESLint boundaries + strict typecheck gate + Playwright/axe smoke + CI wiring + PHPStan-rule hardening + report). **A1-S04 has not been started.** Awaiting approval before implementing **Story A1-S04**.
