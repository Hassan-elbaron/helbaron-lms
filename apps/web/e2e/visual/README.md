# Visual Regression Suite (Part 16)

Playwright `toHaveScreenshot()` baselines for the design system and key pages. These specs are
**committed for CI** and are **not runnable in the build sandbox** (no running server / no browsers).

## What is covered

| Spec | Surface |
| --- | --- |
| `design-showcase.spec.ts` | The internal `/design-system` showcase, captured per section (colors, typography, spacing, radius, icons, buttons, badges, inputs, cards, tabs/accordion, overlays, navigation, states, charts, tables, forms, homepage blocks) + an open-dialog capture + a full-page dark capture. Single most efficient component coverage. |
| `marketing.spec.ts` | Public pages: homepage, courses, pricing, about, contact, certificate **verify**, login, register. |
| `app-shell.spec.ts` | Authenticated shell (dashboard, my-learning, certificates) — navigation sidebar/topbar, tables, cards. Requires a logged-in storage state (skips otherwise). |

The showcase is the primary coverage for **forms, dialogs, tables, charts, cards, and navigation**
components; the marketing/app-shell specs cover real page composition.

## Running

Requires a running app and installed browsers (CI or local):

```bash
# one-time: install browsers
npx playwright install --with-deps chromium

# run the visual project (builds + starts the app automatically unless PLAYWRIGHT_BASE_URL is set)
npm run test:visual

# generate / refresh baselines (first run, or after an intentional design change)
npm run test:visual -- --update-snapshots

# open the HTML report of diffs
npm run e2e:report
```

Baselines are written next to the specs under `e2e/visual/<spec>.spec.ts-snapshots/` and should be
committed. Regenerate them only for **intentional** design changes, and review the image diff.

### Environment

- **`PLAYWRIGHT_BASE_URL`** — test an already-running server (otherwise Playwright runs
  `npm run build && npm run start`).
- **`NEXT_PUBLIC_ENABLE_DESIGN_SHOWCASE=true`** — required for the showcase specs against a
  **production** build (in development the route is enabled by default). Without it the route 404s
  and the showcase specs skip.
- **`PLAYWRIGHT_STORAGE_STATE`** — path to a Playwright storage-state JSON for the authenticated
  app-shell specs. Create one with a login setup, e.g.:
  ```ts
  // scripts/save-auth.ts (illustrative)
  const ctx = await browser.newContext();
  // …perform login via UI or API…
  await ctx.storageState({ path: "playwright/.auth/user.json" });
  ```
  then `PLAYWRIGHT_STORAGE_STATE=playwright/.auth/user.json npm run test:visual`.

## Determinism

Configured in `playwright.config.ts` (project `visual`) and `_helpers.ts`:

- **Animations disabled** at capture time (`expect.toHaveScreenshot.animations: "disabled"`) and
  **`reducedMotion: "reduce"`** on the context (also disables the token motion utilities via the
  `prefers-reduced-motion` guards in `globals.css`).
- **Fixed viewport** `1280×800`, `deviceScaleFactor: 1`, `scale: "css"`.
- **Pinned theme** via next-themes' `localStorage["theme"]` (`pinTheme`) + context `colorScheme`.
- **Caret hidden**, **`maxDiffPixelRatio: 0.02`** tolerance for sub-pixel AA noise.
- **Masked dynamic regions**: all `iframe`s (video embeds) and any element tagged
  `data-visual-mask` are masked so live/relative content never fails a diff.
- `settle()` waits for `networkidle` + `document.fonts.ready` before capturing.

For the most stable marketing/app-shell baselines, run against a **demo-seeded** backend so catalog
and dashboard data are deterministic.

> Note: baseline PNGs are environment-sensitive (fonts, GPU, OS). Generate and store them from the
> same environment CI uses (a Linux Playwright container is recommended).
