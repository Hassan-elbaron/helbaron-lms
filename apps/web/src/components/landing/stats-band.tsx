"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import { CountUp } from "./count-up";

export function StatsBand() {
  const { locale } = useI18n();
  return (
    <section className="relative overflow-hidden bg-primary px-4 py-20 text-primary-foreground">
      <div className="pointer-events-none absolute -start-16 -top-16 size-72 rounded-full bg-white/[0.04] blur-2xl" aria-hidden />
      <div className="pointer-events-none absolute -end-16 -bottom-20 size-80 rounded-full bg-gold/[0.06] blur-2xl" aria-hidden />
      <div className="mx-auto grid max-w-6xl gap-10 sm:grid-cols-2 lg:grid-cols-4">
        {brandTheme.stats.map((s) => (
          <div key={s.display} className="text-center">
            <div className="font-serif text-4xl font-semibold text-gold sm:text-5xl">
              <CountUp to={s.num} prefix={s.prefix} suffix={s.suffix} />
            </div>
            <div className="mt-2 text-sm text-primary-foreground/75">{pickLocale(s.label, locale)}</div>
          </div>
        ))}
      </div>
    </section>
  );
}
