# Dashboard Guidelines

Guidance for building the authenticated app surfaces (learning, instructor, organization,
analytics, CRM, commerce, account). Dashboards compose a small set of standardized building
blocks so every area feels like one product.

---

## 1. Page scaffold

Every dashboard page follows the same top-to-bottom scaffold:

```
<main id="main-content">
  <PageHeader title eyebrow? subtitle? icon? action? />   ← standard page header
  <toolbar? />                                            ← filters + quick actions (optional)
  <section class="widget-grid">                           ← responsive widget grid
     <Card> … KPI / chart / list … </Card>
     …
  </section>
</main>
```

- **`PageHeader`** (`components/student/page-header.tsx`) — props `{title, subtitle,
  eyebrow, icon, action}`. Renders the serif `<h1>` (fluid `text-dashboard-title` scale),
  an optional eyebrow/icon, and a right-aligned `action` slot for the primary action. It is
  the single page-title component; do not hand-roll page headers.
- Wrap page content in the `<main id="main-content">` landmark so the skip link resolves.

## 2. Responsive widget grid

Use a responsive CSS grid that reflows by breakpoint (single column on mobile → 2–4 columns
on larger viewports), with `--space-6` gaps. Widgets are `Card`s of consistent padding and
`.elevation-1` (raise to `.hover:elevation-3` for interactive/linked cards). KPI widgets,
charts, and lists all sit in the same grid so alignment stays consistent.

## 3. Standardized cards

All widgets use the one card language (`Card`/`CardHeader`/`CardTitle`/`CardContent`/
`CardFooter`, `text-card-title`, token colours, radius scale). Status uses `Badge` variants
plus an icon where meaning is state-bearing. Avoid bespoke card styling — compose the
primitives.

## 4. Charts

Use the dependency-free tokenized chart layer (`@/components/ui/charts`):

- `BarChart`, `LineChart` (+ `area`), `Sparkline`, `DonutChart`, `ProgressRing`.
- Series colours come from `CHART_SERIES` / `chartColor(i)` (8 token-backed colours), so
  charts re-theme with the brand and adapt to dark mode.
- Every chart is `role="img"` + `aria-label` with a visually-hidden data `<table>` — no
  extra work needed for accessibility.
- Prefer a `Sparkline` inside KPI cards, `DonutChart`/`ProgressRing` for completion/quota,
  `BarChart`/`LineChart` for trends. Keep one chart type per idea; add a `ChartLegend` when
  there are multiple series.

## 5. Tables

Use `DataGrid` for rich, interactive tabular data (sorting with `aria-sort`, selection +
bulk actions, column visibility, filter/toolbar slot, pagination, density, and a
**responsive card fallback** on mobile via `responsiveCards`/`renderCard`). Use the
`Table` primitive for simple, static tables. Always supply `loading`, `empty`, and (where
fetching) `error`/`onRetry` states — `DataGrid` renders `Skeleton` rows, `EmptyState`, and
`ErrorState` for you.

## 6. Filters & quick actions

Place filters and quick actions in the `DataGrid` `toolbar` slot or a header toolbar above
the grid. Primary actions go in the `PageHeader` `action` slot. Keep filter controls
token-styled (`Select`, `Input`, `Switch`, `Popover` for advanced filters) and RTL-safe.

## 7. States

Wire the canonical states everywhere data loads:

- Loading → `LoadingState`/`PageLoading` or `Skeleton` rows.
- Empty → `EmptyState` with a helpful title/description and a primary `action`.
- Error → `ErrorState` with `onRetry`; wrap risky subtrees in `ErrorBoundary`.
- Offline → `OfflineBanner` (+ `useOnlineStatus`).
- Not-yet-built → `ComingSoon`.

## 8. Per-dashboard notes

- **Learning / Instructor:** progress uses `ProgressRing`/`Progress`; course lists use the
  standardized card + `Badge` status; instructor tables use `DataGrid` with selection.
- **Analytics / Reports:** KPI cards with `Sparkline`; trend `LineChart`/`BarChart`;
  distribution `DonutChart`. Respect the responsive widget grid.
- **CRM:** lead status via `LeadStatusBadge` (icon + text, colour-independent); pipeline
  tables via `DataGrid` with bulk actions.
- **Organization / Commerce / Account:** forms use `Form`/`FormField`/`FormSection`;
  settings surfaces that aren't shipped yet use `ComingSoon`.

All dashboard surfaces must pass through `<main id="main-content">`, keep colour non-sole,
and mirror correctly under RTL.
