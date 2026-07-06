"use client";

import Link from "next/link";
import { ArrowRight, Star } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import { Button } from "@/components/ui/button";
import { Reveal } from "./reveal";
import { HeroArt } from "./hero-art";

const AVATARS = ["var(--primary)", "var(--copper)", "var(--gold)", "oklch(0.45 0.08 30)"];

function Chip({ eyebrow, body, className }: { eyebrow: string; body: string; className?: string }) {
  return (
    <div className={`absolute rounded-2xl border border-border/70 bg-card/95 p-4 shadow-xl shadow-primary/10 backdrop-blur ${className ?? ""}`}>
      <span className="pointer-events-none absolute start-2.5 top-2.5 size-2.5 border-s-2 border-t-2 border-copper/60" aria-hidden />
      <p className="text-[0.6rem] font-semibold uppercase tracking-[0.16em] text-copper">{eyebrow}</p>
      <p className="mt-1 max-w-[10rem] text-xs leading-snug text-foreground">{body}</p>
    </div>
  );
}

export function Hero() {
  const { locale } = useI18n();
  const h = brandTheme.hero;

  return (
    <section className="relative overflow-hidden">
      <div className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(120%_120%_at_15%_0%,oklch(0.985_0.012_88)_0%,var(--background)_55%)]" aria-hidden />
      <div className="pointer-events-none absolute -end-24 top-10 -z-10 hidden size-[36rem] rounded-full bg-primary/[0.06] blur-2xl animate-blob lg:block" aria-hidden />

      <div className="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 sm:py-24 lg:grid-cols-2">
        {/* Left */}
        <Reveal>
          <div className="mb-5 inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-copper">
            <span className="h-px w-8 bg-copper/50" aria-hidden />
            {pickLocale(h.eyebrow, locale)}
          </div>
          <h1 className="font-serif text-[2.75rem] font-semibold leading-[1.02] tracking-tight sm:text-[4.25rem]">
            <span className="block text-primary">{pickLocale(h.headlineLine1, locale)}</span>
            <span className="block italic text-copper">{pickLocale(h.headlineEmphasis, locale)}</span>
            <span className="block text-primary">{pickLocale(h.headlineLine2, locale)}</span>
          </h1>
          <p className="mt-6 max-w-xl text-base text-muted-foreground sm:text-lg">{pickLocale(h.subtitle, locale)}</p>

          <div className="mt-8 flex flex-wrap items-center gap-3">
            <Button asChild size="lg" className="shine relative overflow-hidden">
              <Link href={h.primaryCta.href}>
                {pickLocale(h.primaryCta.label, locale)}
                <ArrowRight className="size-4 rtl:rotate-180" aria-hidden />
              </Link>
            </Button>
            <Button asChild size="lg" variant="outline">
              <Link href={h.secondaryCta.href}>{pickLocale(h.secondaryCta.label, locale)}</Link>
            </Button>
          </div>

          <div className="mt-8 flex items-center gap-3">
            <div className="flex -space-x-2 rtl:space-x-reverse">
              {AVATARS.map((c, i) => (
                <span key={i} className="inline-block size-8 rounded-full border-2 border-background" style={{ backgroundColor: c }} aria-hidden />
              ))}
            </div>
            <div className="flex items-center gap-2">
              <span className="flex text-gold">
                {[0, 1, 2, 3, 4].map((i) => <Star key={i} className="size-3.5 fill-current" aria-hidden />)}
              </span>
              <span className="text-sm font-semibold">{h.rating.value}</span>
              <span className="hidden text-sm text-muted-foreground sm:inline">· {pickLocale(h.rating.text, locale)}</span>
            </div>
          </div>
        </Reveal>

        {/* Right: editorial SVG art + floating chips */}
        <Reveal delay={120}>
          <div className="relative mx-auto max-w-lg">
            <HeroArt className="w-full drop-shadow-sm" />
            <Chip
              eyebrow={pickLocale(h.cards[0].eyebrow, locale)}
              body={pickLocale(h.cards[0].body, locale)}
              className="-start-2 top-6 animate-float sm:start-0"
            />
            <Chip
              eyebrow={pickLocale(h.cards[2].eyebrow, locale)}
              body={pickLocale(h.cards[2].body, locale)}
              className="-end-2 bottom-8 animate-float-slow sm:end-0"
            />
          </div>
        </Reveal>
      </div>
    </section>
  );
}
