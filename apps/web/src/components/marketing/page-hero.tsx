"use client";

import {
  GraduationCap, LayoutGrid, Users, Tag, ShoppingCart, Receipt, FileSignature,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import { pageHeroes } from "@/config/page-heroes";
import { Reveal } from "@/components/landing/reveal";

const ICONS: Record<string, LucideIcon> = {
  GraduationCap, LayoutGrid, Users, Tag, ShoppingCart, Receipt, FileSignature,
};

export function PageHero({ page }: { page: keyof typeof pageHeroes }) {
  const { locale } = useI18n();
  const h = pageHeroes[page];
  const Icon = ICONS[h.icon] ?? GraduationCap;

  return (
    <Reveal
      as="section"
      className="mb-10 overflow-hidden rounded-3xl border bg-[radial-gradient(130%_150%_at_100%_0%,oklch(0.985_0.012_88)_0%,var(--card)_58%)] p-8 sm:p-10"
    >
      <div className="relative grid items-center gap-6 lg:grid-cols-[1.7fr_1fr]">
        <div className="pointer-events-none absolute -end-10 -top-14 -z-0 size-56 rounded-full bg-primary/[0.06] blur-2xl" aria-hidden />
        <div className="relative">
          <div className="mb-3 inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.22em] text-copper">
            <span className="h-px w-8 bg-copper/50" aria-hidden />
            {pickLocale(h.eyebrow, locale)}
          </div>
          <h1 className="font-serif text-3xl font-semibold leading-[1.05] tracking-tight sm:text-5xl">
            {pickLocale(h.title, locale)} <span className="italic text-copper">{pickLocale(h.emphasis, locale)}</span>
          </h1>
          <p className="mt-4 max-w-xl text-muted-foreground">{pickLocale(h.subtitle, locale)}</p>
        </div>

        <div className="hidden min-w-0 shrink-0 items-center justify-self-end gap-4 lg:flex">
          {h.stat ? (
            <div className="whitespace-nowrap rounded-2xl border bg-background/70 px-5 py-4 text-center shadow-sm backdrop-blur">
              <div className="font-serif text-3xl font-semibold text-primary">{h.stat.value}</div>
              <div className="text-xs text-muted-foreground">{pickLocale(h.stat.label, locale)}</div>
            </div>
          ) : null}
          <span className="flex size-20 items-center justify-center rounded-3xl bg-gradient-to-br from-primary to-[oklch(0.30_0.04_190)] text-primary-foreground shadow-lg shadow-primary/20 animate-float-slow">
            <Icon className="size-9" aria-hidden />
          </span>
        </div>
      </div>
    </Reveal>
  );
}
