"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import type { Localized } from "@/config/theme";
import { pickLocale } from "@/config/theme";
import { Reveal } from "@/components/landing/reveal";

export function LegalPage({ title, intro, sections }: { title: Localized; intro: Localized; sections: { h: Localized; p: Localized }[] }) {
  const { locale } = useI18n();
  return (
    <Reveal className="mx-auto max-w-3xl py-6">
      <p className="text-xs font-semibold uppercase tracking-[0.22em] text-copper">HElbaron</p>
      <h1 className="mt-2 font-serif text-4xl font-semibold tracking-tight">{pickLocale(title, locale)}</h1>
      <p className="mt-4 text-muted-foreground">{pickLocale(intro, locale)}</p>
      <div className="mt-8 space-y-8">
        {sections.map((s) => (
          <section key={s.h.en}>
            <h2 className="font-serif text-xl font-semibold">{pickLocale(s.h, locale)}</h2>
            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{pickLocale(s.p, locale)}</p>
          </section>
        ))}
      </div>
    </Reveal>
  );
}
