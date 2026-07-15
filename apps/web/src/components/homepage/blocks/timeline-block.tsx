"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { HomepageSection, TimelineContent } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS vertical timeline. Bilingual, RTL-safe (border sits on the inline-start edge). */
export function TimelineBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as TimelineContent;
  const items = content.items ?? [];
  if (items.length === 0) return null;

  return (
    <BlockShell section={section} id="timeline">
      <BlockHeading heading={content.heading} />
      <ol className="mx-auto max-w-3xl space-y-8 border-s-2 border-border ps-6 text-start">
        {items.map((item, i) => (
          <li key={`${item.title?.en ?? "step"}-${i}`} className="relative">
            <span className="absolute -start-[1.9rem] top-1 size-3 rounded-full bg-primary" aria-hidden />
            {item.date ? (
              <span className="text-xs font-semibold uppercase tracking-wide text-copper">{pickLocale(item.date, locale)}</span>
            ) : null}
            <h3 className="mt-1 font-serif text-lg font-semibold text-foreground">
              {item.title ? pickLocale(item.title, locale) : ""}
            </h3>
            {item.description ? (
              <p className="mt-1 text-sm text-muted-foreground">{pickLocale(item.description, locale)}</p>
            ) : null}
          </li>
        ))}
      </ol>
    </BlockShell>
  );
}
