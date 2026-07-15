# Component Library

UI primitives live in **`apps/web/src/components/ui/`** and are re-exported from the barrel
`@/components/ui` (`index.ts`). States live in `@/components/states`. Every primitive is
token-driven and uses `class-variance-authority` (CVA) with a shared variant vocabulary:

- **Colour variants:** `default`/`primary`, `secondary`, `destructive`, `success`,
  `warning`, `info`, `outline`, `ghost`, `link` (per component's applicable set).
- **Sizes:** `sm`, `md`/`default`, `lg`, `icon`.

All components use CSS logical properties for RTL correctness and ship correct
roles/ARIA (see `ACCESSIBILITY.md`).

---

## 1. Button — `button.tsx`

Exports `Button`, `buttonVariants`.

- **Variants:** `default`, `primary` (alias of default), `destructive`, `outline`,
  `secondary`, `ghost`, `link`, `success`, `info`.
- **Sizes:** `default`, `md` (alias of default), `sm`, `lg`, `icon`.
- **Props:** all native `<button>` attrs + `asChild` (Radix `Slot`; renders a single child
  element, no spinner), `loading` (renders a `Loader2` spinner and disables; real
  `<button>` only).
- **States:** hover (`hover:bg-*/90`), active (`active:translate-y-px`), focus-visible
  ring, disabled/aria-disabled (`opacity-[--opacity-disabled]`, pointer-events none).
- Colour, radius (`rounded-md`), motion (`duration-[--duration-fast]`) and ring are all
  token-driven.

```tsx
<Button>Save</Button>
<Button variant="success">Approve</Button>
<Button variant="info" size="sm">Details</Button>
<Button variant="destructive" loading>Deleting…</Button>
<Button asChild><Link href="/x">Go</Link></Button>
```

---

## 2. Badge — `badge.tsx`

Exports `Badge`, `badgeVariants`. Variants: `default`, `secondary`, `destructive`,
`success`, `warning`, `info`, `outline`. No size axis. Focus ring. Use for status pills —
pair with an icon or text when it conveys state (see `LeadStatusBadge`).

---

## 3. Icon — `icon.tsx`

Exports `Icon`, `ICON_SIZES`, `IconSize`. A single wrapper around any lucide icon.

- **Sizes** (`ICON_SIZES`, px): `xs=14`, `sm=16`, `md=20` (default), `lg=24`, `xl=32`.
- **Props:** `icon` (LucideIcon), `size`, `strokeWidth` (default `1.75`), `label`.
- **A11y:** `label` → `role="img"` + `aria-label`; omitted → `aria-hidden`. `shrink-0`.

See `ICONOGRAPHY.md`.

---

## 4. New primitives (this workstream)

| Component | Exports | Notes |
| --- | --- | --- |
| **Tooltip** `tooltip.tsx` | `TooltipProvider` (no-op), `Tooltip`, `TooltipTrigger`, `TooltipContent` | Custom (no dep). `side`: `top`\|`bottom`\|`start`\|`end`. Hover/focus/Escape; `role="tooltip"`, `aria-describedby`. |
| **Popover** `popover.tsx` | `Popover`, `PopoverTrigger`, `PopoverContent`, `PopoverClose` | Custom. Controlled `open`/`onOpenChange`; `align`: `start`\|`center`\|`end`. Outside-click + Escape; `role="dialog"`, `aria-expanded`, `aria-haspopup`. |
| **Accordion** `accordion.tsx` | `Accordion`, `AccordionItem`, `AccordionTrigger`, `AccordionContent` | Custom. `type` `single`\|`multiple`, `collapsible`. `aria-expanded/controls`, content `role="region"` + `hidden`. |
| **Spinner** `spinner.tsx` | `Spinner`, `SpinnerProps` | Sizes `sm`/`md`/`lg`/`icon`. `label` → `role="status"`; else `aria-hidden`. Uses `.motion-spin`. |
| **RadioGroup** `radio-group.tsx` | `RadioGroup`, `RadioGroupItem` | Custom. `role="radiogroup"`; controlled/uncontrolled `value`; native radios; disabled. |
| **Switch** `switch.tsx` | `Switch`, `SwitchProps` | `role="switch"`, `aria-checked`, `data-state`; RTL thumb mirror; `onCheckedChange`. |
| **Textarea** `textarea.tsx` | `Textarea`, `TextareaProps` | Native textarea; disabled/read-only/`aria-invalid` styling. |
| **Progress** `progress.tsx` | `Progress`, `ProgressProps` | Variants `default`/`success`/`warning`/`destructive`/`info`; `value` (0–100 clamped), `label`; `role="progressbar"` + aria-value*; RTL fill from inline-start. |

---

## 5. Existing / Radix-backed primitives

| Component | Backing | Key exports |
| --- | --- | --- |
| Card `card.tsx` | native | `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`, `CardFooter` (`.elevation-1`) |
| Input `input.tsx` | native | `Input` (disabled/read-only/`aria-invalid`) |
| Checkbox `checkbox.tsx` | native | `Checkbox` (RHF `register()`-compatible) |
| Label `label.tsx` | Radix | `Label` (peer-disabled styling) |
| Select `select.tsx` | Radix | `Select`, `SelectTrigger`, `SelectContent`, `SelectItem`, `SelectValue`, `SelectGroup` |
| Tabs `tabs.tsx` | Radix | `Tabs`, `TabsList`, `TabsTrigger`, `TabsContent` |
| Dialog `dialog.tsx` | Radix | `Dialog*` — overlay `z-[--z-overlay]`, content `z-[--z-modal]`, `sr-only` Close |
| Drawer `drawer.tsx` | vaul | `Drawer*` — bottom sheet, drag handle |
| DropdownMenu `dropdown-menu.tsx` | Radix | `DropdownMenu*` incl. `CheckboxItem`, `Label`, `Separator` |
| Toast `toast.tsx` | sonner | `Toaster`, `toast` — `dir`-aware position (bottom-left RTL / bottom-right LTR) |
| Table `table.tsx` | native | `Table`, `TableHeader`, `TableBody`, `TableRow`, `TableHead`, `TableCell`, `TableCaption`; `density` `comfortable`\|`compact`, sticky header |
| Separator `separator.tsx` | Radix | `Separator` (`orientation`, `decorative`) |
| Avatar `avatar.tsx` | Radix | `Avatar`, `AvatarImage`, `AvatarFallback` |
| Breadcrumb `breadcrumb.tsx` | native | `Breadcrumb` (`items: Crumb[]`) — `<nav aria-label="Breadcrumb">`, `aria-current`, RTL chevron flip |
| Pagination `pagination.tsx` | native | `Pagination` (`page`, `lastPage`, `onPageChange`) — `<nav aria-label="Pagination">`, RTL chevrons |
| Skeleton `skeleton.tsx` | native | `Skeleton`, `SkeletonText` (variants text/avatar/card/table-row) |

---

## 6. Data Grid — `data-grid.tsx`

A rich, dependency-free table. Exports `DataGrid<T>`, `ColumnDef<T>`, `DataGridProps<T>`,
`DataGridLabels`, `SortState`, `SortDirection`.

**`ColumnDef<T>`:** `key`, `header`, `cell`, `className`, `headerClassName`, `align`
(`start`\|`center`\|`end`), `sortable`, `sortValue`, `hideable`, `defaultHidden`, `sticky`
(`start`\|`end`).

**Features (all opt-in):** sticky header (`stickyHeader` + `maxHeight`); density
(`comfortable`\|`compact`); sorting with `aria-sort` (`ascending`/`descending`/`none`),
controlled (`sort`/`onSortChange`) or built-in client-side; sticky columns; **selection**
+ **bulk-action bar** (`selectable`, `bulkActions`, `onSelectionChange`; select-all with
indeterminate); **column-visibility toggle** (`columnToggle`, via Popover + Checkbox);
toolbar/filter slot (`toolbar`); **responsive card fallback** (`responsiveCards`,
`renderCard`; table hidden on mobile, cards on desktop-md); loading (Skeleton rows), empty
(`EmptyState`), error (`ErrorState` + `onRetry`); pagination; i18n via `labels`.

See `DASHBOARD_GUIDELINES.md`.

---

## 7. Forms — `form-field.tsx`, `form.tsx`

**`FormField`** (`form-field.tsx`) — the labelled control wrapper. Props: `label`, `id`,
`error`, `hint`, `success`, `required`, `hideLabel`, `loading`, `className`,
`labelClassName`, `children`. It clones the control (or accepts a render-fn) and wires:
`aria-invalid` (on error), `aria-describedby` (hint + error + success ids),
`aria-required`; error `role="alert"`, success `role="status"` (suppressed while an error
is present); visible `*` + `sr-only "(required)"`; `aria-busy` when loading. RTL-safe
logical spacing.

**`form.tsx`** — `Form` (renders `noValidate`), `FormSection` (`title`/`description`),
`FormActions`, `FormAlert`. `FormAlert` variants `error`/`success`/`warning`/`info`; live
regions: error/warning → `role="alert"`, success/info → `role="status"`; icons
`AlertTriangle`/`CheckCircle2`/`Info`.

Auth field/form-alert delegate to these.

```tsx
<Form onSubmit={…}>
  <FormSection title="Profile">
    <FormField label="Email" required error={errors.email} hint="We never share it.">
      <Input type="email" />
    </FormField>
  </FormSection>
  <FormActions><Button type="submit">Save</Button></FormActions>
</Form>
```

---

## 8. Charts — `components/ui/charts/`

Dependency-free tokenized SVG. See `DESIGN_SYSTEM.md` and the charts section of the report;
key exports: `BarChart`, `LineChart` (+ `area` prop), `Sparkline`, `DonutChart`,
`ProgressRing`, plus `ChartFigure`/`ChartLegend`, `theme.ts` (`CHART_SERIES`, `chartColor`,
`CHART_TOKENS`). Every chart is `role="img"` + `aria-label` with a visually-hidden
`<table>` mirroring the data; the inner SVG is `aria-hidden`.

---

## 9. Component states — `components/states/`

A canonical state set (barrel `@/components/states`):

- `LoadingState` / `PageLoading` — `role="status" aria-live="polite"`, Spinner.
- `Skeleton` / `SkeletonText` — shimmer placeholders (variants text/avatar/card/table-row).
- `EmptyState` — `{title, description, icon, action}`, default Inbox icon.
- `ErrorState` — `{title, message, onRetry}`, `role="alert"`, retry Button.
- `ErrorBoundary` — class boundary rendering `ErrorState` on catch (`fallback` prop).
- `SuccessState` — `{title, message, action}`, `role="status"`, CheckCircle2.
- `OfflineBanner` + `useOnlineStatus()` — `role="status"`, WifiOff, `.motion-slide-down`.
- `ComingSoon` — `PageHeader` + secondary `Badge` for not-yet-shipped surfaces.

**Conventions** for the states that are attribute/utility-driven rather than components:
`disabled` (`opacity-[--opacity-disabled]`, pointer-events none), `readonly`, `hover`
(`hover:bg-*/90`, `.hover:elevation-*`), `focus` (global focus-visible ring),
`active` (`active:translate-y-px`), `selected` (`data-[state=selected]`), validation
(`aria-invalid`, `role="alert"`), permission (gate + `ComingSoon`/empty).

---

## 10. Domain card language

Domain surfaces (courses, CRM, analytics widgets, dashboards) are unified onto **one** card
language built on `Card`/`CardHeader`/`CardContent`/`CardFooter` + `.elevation-1`
(`.hover:elevation-*` for interactive cards), token colours (`bg-card`,
`text-card-foreground`, `border-border`), the radius scale, and `text-card-title` for
titles. Status is expressed with `Badge` variants and, where meaning is state-bearing, an
icon (never colour alone). `PageHeader` provides the standard page/section header. New
domain cards should compose these primitives rather than introducing bespoke card styles.
