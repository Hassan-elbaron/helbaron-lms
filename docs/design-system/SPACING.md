# Spacing & Layout

One spacing scale, one set of container widths, defined in
`apps/web/src/app/globals.css`. The scale is authored as `--space-*` custom properties and
deliberately **mirrors Tailwind's default rem rhythm** — it is *not* registered in
`@theme`, so Tailwind's numeric spacing utilities (`p-4`, `gap-6`, `mt-8`, …) remain the
day-to-day API while `--space-*` is the single source of truth for anything that needs a
raw value.

---

## 1. The scale

| Token | Value | Token | Value |
| --- | --- | --- | --- |
| `--space-0` | 0 | `--space-5` | 1.25rem |
| `--space-px` | 1px | `--space-6` | 1.5rem |
| `--space-0-5` | 0.125rem | `--space-8` | 2rem |
| `--space-1` | 0.25rem | `--space-10` | 2.5rem |
| `--space-1-5` | 0.375rem | `--space-12` | 3rem |
| `--space-2` | 0.5rem | `--space-16` | 4rem |
| `--space-3` | 0.75rem | `--space-20` | 5rem |
| `--space-4` | 1rem | `--space-24` | 6rem |

Because the values match Tailwind's scale, `p-4` and `padding: var(--space-4)` are
equivalent — use the utility in markup, use the token when authoring component CSS.

---

## 2. Vertical-rhythm helpers

Opt-in utility classes (in `@layer utilities`) apply consistent block spacing between
siblings using logical `margin-block-start` (RTL-safe):

`.stack-1`, `.stack-2`, `.stack-3`, `.stack-4`, `.stack-6`, `.stack-8` — each sets
`> * + * { margin-block-start: var(--space-N); }`.

```tsx
<div className="stack-4">
  <h2 className="text-h2">Title</h2>
  <p className="text-body">Paragraph…</p>
  <Button>Action</Button>
</div>
```

These are additive — pages are not mass-rewritten to use them; reach for them when building
a new vertical stack.

---

## 3. Container widths

Named `--container-width*` (not `--container-*`) so Tailwind's `max-w-*` scale is untouched.
`--container-width` is also the base the Theme Manager overrides.

| Token | Value |
| --- | --- |
| `--container-width` | 80rem (base) |
| `--container-width-sm` | 40rem |
| `--container-width-md` | 48rem |
| `--container-width-lg` | 64rem |
| `--container-width-xl` | 80rem |
| `--container-width-2xl` | 96rem |

Page shells center content within `--container-width` with responsive inline padding. The
Theme Manager can rescale the base per-tenant via `theme.container_width`.

---

## 4. Conventions

- **Page gutters:** responsive inline padding (`px-4 sm:px-6 lg:px-8`) inside a
  max-width container.
- **Section rhythm:** major page sections separated by `--space-16`/`--space-20`
  (`py-16`/`py-20`), tightening on mobile.
- **Card padding:** `--space-6` (`p-6`) for standard cards; `--space-4` for compact.
- **Grid gaps:** `--space-6` for widget/card grids; `--space-4` for dense lists.
- **Control spacing:** `--space-2`/`--space-3` between inline controls and their icons/labels.
- Prefer logical spacing (`ms-/me-/ps-/pe-`, `margin-block-*`) so layouts mirror under RTL.
