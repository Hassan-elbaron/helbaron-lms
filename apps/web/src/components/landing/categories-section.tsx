"use client";

import Link from "next/link";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale, type Swatch } from "@/config/theme";
import { Section, SectionHeading } from "./section";
import { Reveal } from "./reveal";

const BADGE: Record<Swatch, string> = {
  teal: "bg-primary text-primary-foreground",
  copper: "bg-copper text-copper-foreground",
  gold: "bg-gold text-gold-foreground",
  red: "bg-destructive text-destructive-foreground",
};

export function CategoriesSection() {
  const { locale } = useI18n();
  const h = brandTheme.verticalsHeading;
  return (
    <Section className="bg-[radial-gradient(120%_80%_at_50%_0%,oklch(0.985_0.012_88)_0%,transparent_60%)]">
      <SectionHeading
        eyebrow={pickLocale(h.eyebrow, locale)}
        title1={pickLocale(h.title1, locale)}
        title2={pickLocale(h.title2, locale)}
        subtitle={pickLocale(h.subtitle, locale)}
      />
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {brandTheme.categories.map((c, i) => (
          <Reveal key={c.code} delay={(i % 4) * 70}>
            <Link
              href="/categories"
              className="card-hover group relative flex h-full items-center gap-4 rounded-xl border bg-card p-4 hover:border-primary/30 hover:shadow-lg"
            >
              {c.hot ? (
                <span className="absolute end-3 top-3 rounded-full bg-destructive px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide text-destructive-foreground">HOT</span>
              ) : null}
              <span className={`flex size-11 shrink-0 items-center justify-center rounded-lg font-serif text-sm font-bold ${BADGE[c.color]}`}>
                {c.code}
              </span>
              <div className="min-w-0 flex-1">
                <p className="truncate font-serif text-base font-semibold">{pickLocale(c.name, locale)}</p>
                <p className="text-xs text-muted-foreground">{pickLocale(c.count, locale)}</p>
              </div>
            </Link>
          </Reveal>
        ))}
      </div>
    </Section>
  );
}
