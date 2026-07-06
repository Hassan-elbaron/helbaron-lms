"use client";

import Link from "next/link";
import {
  Users, CalendarClock, Video, CheckCircle2, MessageSquare, Award, Wrench, Presentation,
  MapPin, Package, Handshake, Layers, ShieldCheck, FileText, Headset, BarChart3, LifeBuoy,
  Compass, Settings, TrendingUp, Rocket, GraduationCap, ArrowRight,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale, type BrandTheme } from "@/config/theme";
import { Button } from "@/components/ui/button";
import { Reveal } from "@/components/landing/reveal";
import { CountUp } from "@/components/landing/count-up";
import { HeroArt } from "@/components/landing/hero-art";

const ICONS: Record<string, LucideIcon> = {
  Users, CalendarClock, Video, CheckCircle2, MessageSquare, Award, Wrench, Presentation,
  MapPin, Package, Handshake, Layers, ShieldCheck, FileText, Headset, BarChart3, LifeBuoy,
  Compass, Settings, TrendingUp, Rocket, GraduationCap,
};

export function ServicePage({ pageKey }: { pageKey: keyof BrandTheme["servicePages"] }) {
  const { locale } = useI18n();
  const p = brandTheme.servicePages[pageKey];

  return (
    <div className="space-y-20 py-4">
      {/* Hero */}
      <div className="relative grid items-center gap-10 lg:grid-cols-2">
        <div className="pointer-events-none absolute -end-20 -top-16 -z-10 hidden size-[28rem] rounded-full bg-primary/[0.06] blur-2xl animate-blob lg:block" aria-hidden />
        <Reveal>
          <div className="mb-4 inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.22em] text-copper">
            <span className="h-px w-8 bg-copper/50" aria-hidden />
            {pickLocale(p.eyebrow, locale)}
          </div>
          <h1 className="font-serif text-4xl font-semibold leading-[1.05] tracking-tight sm:text-6xl">
            {pickLocale(p.title, locale)} <span className="italic text-copper">{pickLocale(p.emphasis, locale)}</span>
          </h1>
          <p className="mt-5 max-w-xl text-muted-foreground sm:text-lg">{pickLocale(p.subtitle, locale)}</p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Button asChild size="lg" className="shine relative overflow-hidden">
              <Link href={p.primaryCta.href}>
                {pickLocale(p.primaryCta.label, locale)}
                <ArrowRight className="size-4 rtl:rotate-180" aria-hidden />
              </Link>
            </Button>
            <Button asChild size="lg" variant="outline">
              <Link href={p.secondaryCta.href}>{pickLocale(p.secondaryCta.label, locale)}</Link>
            </Button>
          </div>
        </Reveal>
        <Reveal delay={120} className="hidden lg:block">
          <HeroArt className="mx-auto w-full max-w-md" />
        </Reveal>
      </div>

      {/* Features */}
      <div className="stagger-in grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {p.features.map((f) => {
          const Icon = ICONS[f.icon] ?? Users;
          return (
            <div key={f.icon + f.title.en} className="card-hover rounded-2xl border bg-card p-6 hover:border-primary/30 hover:shadow-lg">
              <span className="mb-4 flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <Icon className="size-5" aria-hidden />
              </span>
              <h3 className="font-serif text-lg font-semibold">{pickLocale(f.title, locale)}</h3>
              <p className="mt-1.5 text-sm text-muted-foreground">{pickLocale(f.desc, locale)}</p>
            </div>
          );
        })}
      </div>

      {/* Highlights (rounded teal panel with count-up) */}
      <Reveal>
        <div className="relative overflow-hidden rounded-3xl bg-primary px-6 py-12 text-primary-foreground">
          <div className="pointer-events-none absolute -end-10 -top-10 size-56 rounded-full bg-gold/10 blur-2xl" aria-hidden />
          <div className="grid gap-8 sm:grid-cols-3">
            {p.highlights.map((h) => (
              <div key={h.label.en} className="text-center">
                <div className="font-serif text-4xl font-semibold text-gold sm:text-5xl">
                  <CountUp to={h.num} suffix={h.suffix} />
                </div>
                <div className="mt-2 text-sm text-primary-foreground/75">{pickLocale(h.label, locale)}</div>
              </div>
            ))}
          </div>
        </div>
      </Reveal>

      {/* CTA */}
      <Reveal className="mx-auto max-w-2xl rounded-3xl border bg-card px-6 py-12 text-center shadow-sm">
        <h2 className="font-serif text-2xl font-semibold sm:text-3xl">
          {pickLocale(brandTheme.finalCta.title1, locale)}{" "}
          <span className="italic text-copper">{pickLocale(brandTheme.finalCta.title2, locale)}</span>
        </h2>
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <Button asChild size="lg">
            <Link href={brandTheme.finalCta.primary.href}>{pickLocale(brandTheme.finalCta.primary.label, locale)}</Link>
          </Button>
          <Button asChild size="lg" variant="outline">
            <Link href={p.primaryCta.href}>{pickLocale(p.primaryCta.label, locale)}</Link>
          </Button>
        </div>
      </Reveal>
    </div>
  );
}
