# Motion

A token-driven, opt-in motion system defined in `apps/web/src/app/globals.css`. Every
animation runs on `transform`/`opacity` (compositor-friendly), consumes the duration/easing
tokens, and is guarded by a global `prefers-reduced-motion` block. A
`prefers-reduced-transparency` guard drops scrims/blur.

---

## 1. Duration tokens

| Token | Value | Typical use |
| --- | --- | --- |
| `--duration-instant` | 75ms | micro state flips |
| `--duration-fast` | 150ms | hover, dropdowns, collapse |
| `--duration-normal` | 250ms | fades, slides, modals, toasts |
| `--duration-slow` | 400ms | page entrance, chart grow/draw |
| `--duration-slower` | 600ms | large emphasis moves |

## 2. Easing tokens

| Token | Curve | Use |
| --- | --- | --- |
| `--ease-standard` | `cubic-bezier(0.2, 0, 0, 1)` | general |
| `--ease-emphasized` | `cubic-bezier(0.2, 0, 0, 1)` | emphasis |
| `--ease-spring` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | toasts, playful pops |
| `--ease-decelerate` | `cubic-bezier(0.16, 1, 0.3, 1)` | entrances (ease-out feel) |
| `--ease-accelerate` | `cubic-bezier(0.4, 0, 1, 1)` | exits |

`ease-standard`/`ease-emphasized`/`ease-spring` are registered in `@theme inline`, so they
are also available as Tailwind easing utilities.

---

## 3. `.motion-*` utilities

All defined in `@layer utilities`, all reduced-motion safe:

**Fade / scale:** `.motion-fade-in`, `.motion-fade-out`, `.motion-scale-in`,
`.motion-scale-out`.

**Directional slides:** `.motion-slide-up`, `.motion-slide-down`, `.motion-slide-start`,
`.motion-slide-end`. The `start`/`end` slides use logical translate offsets that flip
under `[dir="rtl"]` automatically.

**Expand / collapse (disclosure):** `.motion-expand`, `.motion-collapse`
(animate `max-block-size` + opacity, `overflow:hidden`).

**Surface-specific entrances** (semantic aliases over the same primitives):
`.motion-page`, `.motion-modal`, `.motion-toast` (spring), `.motion-drawer`,
`.motion-dropdown` (transform-origin aware).

**Loading:** `.motion-spin` (1s linear infinite — used by `Spinner`), `.motion-pulse`.

**Hover affordance:** `.motion-hover` (transition-based `translateY(-2px)` on hover).

**Scrim:** `.motion-scrim` (fixed overlay at `z-[--z-overlay]`, `background: var(--overlay)`,
fade-in) for modal/drawer backdrops.

There is also an earlier `hb-*` set (`.animate-float`, `.reveal`, `.marquee-track`,
`.shine`, `.card-hover`, `.page-enter`, `.stagger-in`) used by marketing surfaces, and a
chart-entrance pair (`.chart-grow`, `.chart-draw`) — all under the same reduced-motion
guards.

```tsx
<div className="motion-fade-in">…</div>
<Dialog>…</Dialog>            {/* content uses motion-modal + motion-scrim */}
<aside className="motion-slide-start">…</aside>  {/* mirrors under RTL */}
```

---

## 4. Reduced motion & transparency

A single global block disables the whole consolidated system plus the `hb-*` and chart
sets when the user prefers reduced motion:

```css
@media (prefers-reduced-motion: reduce) {
  .motion-fade-in, .motion-scale-in, .motion-slide-up, /* … all motion-* … */
  .chart-grow, .chart-draw { animation: none !important; }
  .motion-hover, .motion-hover:hover { transition: none !important; transform: none !important; }
}
```

Reduced transparency is honoured too:

```css
@media (prefers-reduced-transparency: reduce) {
  .motion-scrim { background: var(--background); }       /* opaque scrim */
  [class*="backdrop-blur"] { backdrop-filter: none !important; }
}
```

---

## 5. Guidance

- Animate only `transform` and `opacity`; avoid layout-triggering properties.
- Pick duration by scope: micro ≤ `--duration-fast`, standard `--duration-normal`,
  page/emphasis `--duration-slow`.
- Entrances decelerate (`--ease-decelerate`), exits accelerate (`--ease-accelerate`).
- Use logical `start/end` slides for anything that must mirror in Arabic/RTL.
- Never rely on motion to convey required information; it must remain understandable with
  animations off.
