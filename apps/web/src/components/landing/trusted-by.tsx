"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";

export function TrustedBy() {
  const { locale } = useI18n();
  const logos = brandTheme.trustedBy.logos;
  return (
    <section className="border-y bg-card/60 py-9">
      <p className="mb-6 text-center text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
        {pickLocale(brandTheme.trustedBy.label, locale)}
      </p>
      <div className="marquee-mask overflow-hidden">
        <div className="marquee-track gap-12 px-6">
          {[...logos, ...logos].map((name, i) => (
            <span key={i} className="whitespace-nowrap font-serif text-lg font-medium text-foreground/45">
              {name}
            </span>
          ))}
        </div>
      </div>
    </section>
  );
}
