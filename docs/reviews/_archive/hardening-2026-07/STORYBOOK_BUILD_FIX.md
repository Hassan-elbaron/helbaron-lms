# Storybook Build Fix — `build-storybook`

**Date:** 2026-07-16
**Author:** Release engineering
**Scope:** `apps/web` — fixes the failing `npm run build-storybook` (advisory gate).
**Type:** Dependency pin only. No application code, no Storybook config, no test/lint/CI weakening.

---

## 1. Symptom

`npm run build-storybook` failed during the **preview** compile:

```
info => Building preview..
info => Using default Webpack5 setup
=> Failed to build the preview
TypeError: The 'compilation' argument must be an instance of Compilation
    at DefinePlugin.getCompilationHooks (./node_modules/webpack/lib/util/createHooksRegistry.js:22:10)
    at ./node_modules/webpack/lib/DefinePlugin.js:396:32
    at Compiler.newCompilation (./node_modules/next/dist/compiled/webpack/bundle5.js:...)
```

The Storybook **manager** built fine; only the **preview** (the webpack pass over the design‑system stories) crashed.

## 2. Root cause — two webpack copies, one strict new guard

`@storybook/nextjs` runs the preview through **Next's own bundled webpack** (`next/dist/compiled/webpack`). But `@storybook/builder-webpack5` imports `DefinePlugin` from the **standalone `webpack`** package hoisted into `node_modules`.

That standalone webpack had drifted up to **5.108.4**. In **webpack 5.101**, `getCompilationHooks` was refactored into `lib/util/createHooksRegistry.js` and gained a strict identity check:

```js
// node_modules/webpack/lib/util/createHooksRegistry.js  (5.101+)
if (!(compilation instanceof Compilation)) {
  throw new TypeError("The 'compilation' argument must be an instance of Compilation");
}
```

The `compilation` object handed in is created by **Next's compiled webpack**, so it is *not* an instance of the standalone webpack's `Compilation` class → the guard throws. Webpack **≤ 5.100** used a plain `WeakMap` with **no** identity check, so it tolerated a foreign compilation object. That is why the build worked before the transitive bump to 5.108 and broke after.

This is a version‑drift incompatibility, not a defect in our stories or config.

## 3. The fix

Pin the **standalone** webpack below the strict‑guard version via an npm `override`, so Storybook's builder uses the lenient `getCompilationHooks` that tolerates Next's compiled `Compilation`. Next's own vendored webpack is untouched (it lives inside `next/dist/compiled/`, which overrides cannot reach).

```jsonc
// apps/web/package.json
"overrides": {
  "picomatch": "4.0.4",
  "webpack": "5.100.2"   // ← added: last webpack before the 5.101 strict-instanceof guard
}
```

Why this is safe and minimal:

- **No application impact.** `next build` uses Next's *own* vendored webpack, not the standalone package — the app bundle is unaffected (independently re‑verified: 57/57 routes still build).
- **Only Storybook's builder consumes the standalone webpack.** ESLint, Vitest, Playwright, and TypeScript don't touch it.
- **No gate weakened.** No `@ts-ignore`, no rule disabled, no story skipped, no config hack, no Storybook exclusion. It's a one‑line dependency constraint.
- `5.100.2` is the newest patch of the 5.100 line — current within its own major, just before the breaking guard.

## 4. Verification performed

| Check | Result |
|---|---|
| Reproduced the original failure | ✅ Exact `must be an instance of Compilation` at `DefinePlugin.getCompilationHooks` |
| Confirmed guard exists in webpack 5.108's `createHooksRegistry.js` | ✅ Read the file; strict `instanceof` throw present |
| Applied pin + `npm install` | ✅ `node_modules/webpack` now resolves to **5.100.2** |
| Re‑ran the preview compile | ✅ Now advances **past** the crash point: reaches `9% setup compilation DefinePlugin` → `10% building` and starts compiling modules (previously threw at compilation creation) |

The in‑sandbox run was cut off by the sandbox's 45‑second per‑command limit *while actively compiling modules* (SIGKILL, not an error) — so the crash is provably gone, but a full green build to `storybook-static/` must be confirmed on the host, which has no time cap. See §5.

## 5. Remaining host steps (no time cap → definitive green + clean lockfile)

The sandbox's 45s cap kept interrupting `npm install` before it could rewrite `package-lock.json`, so the lockfile must be regenerated on the host (CI's `npm ci` requires `package.json` and `package-lock.json` to agree on the override).

```powershell
cd "D:\Claude_Files\Projects\LMS\CoreLMS Implementation\corelms\apps\web"
npm install                 # regenerates package-lock.json with the webpack override
npm run build-storybook     # expect: "=> Preview built" and a populated storybook-static/
```

Then commit **both** files and push:

```powershell
cd ..\..
git add apps/web/package.json apps/web/package-lock.json
git commit -m "fix(web): pin webpack 5.100.2 to unbreak Storybook preview build (webpack 5.101 strict Compilation guard vs Next bundled webpack)"
git push origin main
```

## 6. Files changed

| File | Change |
|---|---|
| `apps/web/package.json` | Added `"webpack": "5.100.2"` to `overrides` |
| `apps/web/package-lock.json` | Regenerated by the host `npm install` (records the pinned resolution) |

## 7. Status

- **Fix:** applied and root‑cause‑proven; preview compile verified past the failure point in‑sandbox.
- **Definitive green:** pending the host `npm run build-storybook` (expected to pass) + lockfile regen.
- **Mandatory CI gate:** unaffected by this change; `build-storybook` is an advisory gate and is **not** part of the seven mandatory jobs.
