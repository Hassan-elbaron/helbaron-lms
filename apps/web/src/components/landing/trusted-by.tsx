"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import type { PartnersContent } from "@/lib/homepage/api";

/**
 * "Trusted by" / Partners marquee. Uses CMS-managed partner items when provided; otherwise falls
 * back to the built-in brand list.
 */
export function TrustedBy({ content }: { content?: PartnersContent }) {
  const { locale } = useI18n();
  const items = content?.items?.filter((p) => p.name) ?? null;
  const logos = items && items.length > 0 ? items.map((p) => p.name as string) : brandTheme.trustedBy.logos;

  return (
    <section className="border-y bg-card/60 py-9">
      <p className="mb-6 text-center text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
        {pickLocale(brandTheme.trustedBy.label, locale)}
      </p>
      <div className="marquee-mask overflow-hidden">
        <div className="marquee-track gap-12 px-6">
          {[...logos, ...logos].map((name, i) => (
            <span key={i} className="whitespace-nowrap font-serif text-lg font-medium text-muted-foreground">
              {name}
            </span>
          ))}
        </div>
      </div>
    </section>
  );
}
