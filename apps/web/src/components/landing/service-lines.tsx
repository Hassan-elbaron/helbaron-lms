"use client";

import Link from "next/link";
import { GraduationCap, Users, Presentation, Building2, Compass, ArrowRight } from "lucide-react";
import type { LucideIcon } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale, type Surface } from "@/config/theme";
import { Section, SectionHeading } from "./section";
import { Reveal } from "./reveal";

const ICONS: Record<string, LucideIcon> = {
  courses: GraduationCap, cohorts: Users, workshops: Presentation, enterprise: Building2, advisory: Compass,
};

const SURFACE: Record<Surface, { card: string; badge: string; muted: string; cta: string }> = {
  light: { card: "bg-card text-card-foreground border-border", badge: "bg-primary/10 text-primary", muted: "text-muted-foreground", cta: "text-copper" },
  teal: { card: "bg-primary text-primary-foreground border-transparent", badge: "bg-white/15 text-gold", muted: "text-primary-foreground/75", cta: "text-gold" },
  copper: { card: "bg-copper text-copper-foreground border-transparent", badge: "bg-white/20 text-copper-foreground", muted: "text-copper-foreground/80", cta: "text-copper-foreground" },
};

export function ServiceLines() {
  const { locale } = useI18n();
  const h = brandTheme.serviceHeading;
  return (
    <Section>
      <SectionHeading
        eyebrow={pickLocale(h.eyebrow, locale)}
        title1={pickLocale(h.title1, locale)}
        title2={pickLocale(h.title2, locale)}
        subtitle={pickLocale(h.subtitle, locale)}
      />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        {brandTheme.serviceLines.map((s, i) => {
          const Icon = ICONS[s.icon] ?? GraduationCap;
          const sf = SURFACE[s.fill];
          return (
            <Reveal key={s.no} delay={i * 90}>
              <Link
                href={s.href}
                className={`card-hover shine group relative flex h-full flex-col overflow-hidden rounded-2xl border p-6 shadow-sm hover:shadow-xl ${sf.card}`}
              >
                <div className="mb-4 flex items-center justify-between">
                  <span className={`flex size-10 items-center justify-center rounded-xl ${sf.badge}`}>
                    <Icon className="size-5" aria-hidden />
                  </span>
                  <span className={`font-serif text-lg font-semibold ${sf.badge} rounded-lg px-2 py-0.5`}>{s.no}</span>
                </div>
                <h3 className="font-serif text-lg font-semibold">{pickLocale(s.name, locale)}</h3>
                <p className={`mt-2 flex-1 text-sm leading-relaxed ${sf.muted}`}>{pickLocale(s.desc, locale)}</p>
                <span className={`mt-4 inline-flex items-center gap-1 text-sm font-semibold ${sf.cta}`}>
                  {pickLocale(s.cta, locale)}
                  <ArrowRight className="size-4 transition-transform group-hover:translate-x-1 rtl:rotate-180 rtl:group-hover:-translate-x-1" aria-hidden />
                </span>
              </Link>
            </Reveal>
          );
        })}
      </div>
    </Section>
  );
}
