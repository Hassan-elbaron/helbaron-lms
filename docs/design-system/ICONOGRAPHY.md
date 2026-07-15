# Iconography

The system uses **one** icon family — [lucide-react](https://lucide.dev) — consumed
exclusively through the `Icon` wrapper (`apps/web/src/components/ui/icon.tsx`). A single
family keeps stroke weight, sizing and metaphors consistent, and named imports keep the
bundle tree-shaken.

---

## 1. The `Icon` wrapper

Exports: `Icon`, `ICON_SIZES`, `IconSize`.

```tsx
import { Icon } from "@/components/ui";
import { Search, Bell } from "lucide-react";

<Icon icon={Search} />                       {/* decorative, md (20px) */}
<Icon icon={Bell} size="sm" label="Notifications" />  {/* meaningful → labelled */}
```

**Props**

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `icon` | `LucideIcon` | — | the lucide component |
| `size` | `IconSize` | `md` | see size map |
| `strokeWidth` | `number` | `1.75` | consistent weight across the app |
| `label` | `string` | — | if present → accessible; else decorative |

The wrapper also applies `shrink-0` so icons never squish inside flex rows.

---

## 2. Size map

`ICON_SIZES` (pixels):

| Token | px | Typical use |
| --- | --- | --- |
| `xs` | 14 | dense inline chips, captions |
| `sm` | 16 | buttons, list rows, badges |
| `md` | 20 | default UI icons |
| `lg` | 24 | section headers, empty states |
| `xl` | 32 | hero/feature glyphs |

Stroke width is a fixed `1.75` for the whole app (override only for special cases).

---

## 3. Accessibility conventions

- **Decorative icons** (icon accompanies visible text, e.g. a button with a label): omit
  `label`. The wrapper renders the SVG `aria-hidden`, so screen readers skip it.
- **Meaningful icons** (icon is the only signifier, e.g. an icon-only button): pass
  `label`. The wrapper renders `role="img"` + `aria-label={label}`.
- For icon-only interactive controls, still give the control itself an accessible name
  (`aria-label` on the `<button>`), even if the inner `Icon` is labelled — the control name
  is what assistive tech announces on focus.
- Never encode state with an icon's colour alone; pair shape + text where the meaning
  matters (see `crm/lead-status-badge.tsx`, which combines a per-status icon with text).

---

## 4. Bundle hygiene

Always use **named imports** from `lucide-react` (`import { Check } from "lucide-react"`),
never a namespace import — this preserves tree-shaking (verified during the performance
pass). New icons should be chosen from lucide only; do not introduce a second icon set.
