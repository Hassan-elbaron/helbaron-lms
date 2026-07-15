# Marketing Guidelines

Guidance for the public marketing surfaces (homepage, pricing, about, contact, events,
certificate verification, and CMS-driven homepage blocks). These pages set first
impressions, so they lean harder on the expressive end of the system — the fluid display
scale, generous whitespace, and the `hb-*` motion set — while staying token-driven,
bilingual, and accessible.

---

## 1. Visual hierarchy

- **Hero:** one dominant `text-display` (alias `text-hero`) headline, a `text-subtitle`
  supporting line in `text-muted-foreground`, then a single primary CTA (plus at most one
  secondary). Keep one idea per hero.
- **Sections:** each section leads with a `text-h2`/`text-dashboard-title` heading and an
  optional eyebrow (`text-label`, uppercase-ish tracking). Body copy uses `text-body` at a
  comfortable measure.
- **Cards & features:** feature/benefit cards use the standardized card language and
  `text-card-title`. Keep a consistent icon size (`Icon` `lg`/`xl`) per section.

## 2. Fluid headings

Marketing headings adopt the fluid `clamp()` scale (`text-display`, `text-h1`, `text-h2`,
…), so type scales smoothly from mobile to desktop without breakpoint jumps. Do not
override with fixed `text-5xl` values — use the role utilities so the rhythm stays uniform
and RTL-safe. Under Arabic/RTL, headings automatically fall back to IBM Plex Sans Arabic
(Fraunces has no Arabic glyphs) and drop negative tracking.

## 3. Call-to-action

- Primary CTA: `Button` `default`/`primary` (or `size="lg"` in heroes). One primary per
  view; secondary as `outline`/`ghost`.
- CTAs must have descriptive labels (not "Click here"); icon-only CTAs need an accessible
  name.
- CTA sections (`cta`, `newsletter`, `contact_strip` blocks) use `bg-primary`/`bg-card`
  panels with clear foreground pairing.

## 4. Whitespace & spacing rhythm

- Generous section padding: `--space-16`/`--space-20` vertical (`py-16`/`py-20`), tightened
  on mobile.
- Content centered within `--container-width` (80rem) with responsive inline gutters.
- Use `.stack-*` helpers for vertical rhythm within blocks; `--space-6` grid gaps for
  feature/card grids.

## 5. Responsive

- Mobile-first: single-column stacks that expand to multi-column grids at `sm`/`md`/`lg`.
- Raw marketing `<img>` (logos, thumbnails, team, clients) carry explicit `width`/`height`
  to prevent layout shift (CLS) — done during the performance pass.
- Verify every block at mobile, tablet, and desktop, in both LTR and RTL.

## 6. Motion

Marketing may use the expressive `hb-*` utilities — `.reveal` (scroll-reveal via
IntersectionObserver), `.animate-float`/`.animate-blob` (ambient hero art), `.marquee-track`
(logo rows), `.shine`/`.card-hover` (hover affordance), `.page-enter`/`.stagger-in`
(entrance) — plus the consolidated `.motion-*` set. All are guarded by
`prefers-reduced-motion`; never gate content or meaning on animation.

## 7. Accessibility for marketing

- Single `<main id="main-content">` landmark; skip link resolves to it.
- Every section heading is a real heading element in order (no skipped levels used purely
  for size — use the `.text-*` utilities to size).
- Decorative imagery is `alt=""`/`aria-hidden`; meaningful imagery has descriptive `alt`.
- Colour is never the only signal (badges/stats pair colour with text/icon).
- Homepage CMS blocks (`sample-blocks.ts` covers the full set: hero, features, statistics,
  numbers, categories, featured_courses/events, clients, pricing_preview, comparison_table,
  testimonials, partners, gallery, timeline, team, video, rich_text, cta, newsletter,
  contact_strip, faq) ship bilingual (en/ar) content and must remain accessible and RTL-safe
  in every renderer.
- SEO: marketing pages are indexable (`robots index:true`), carry OG/Twitter metadata and
  Organization JSON-LD from `layout.tsx`; the design showcase is explicitly excluded.
