"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { HomepageSection, StatisticsContent } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS "by the numbers" statistics grid. Bilingual, RTL-safe. Renders nothing when empty. */
export function StatisticsBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as StatisticsContent;
  const items = content.items ?? [];
  if (items.length === 0) return null;

  return (
    <BlockShell section={section} id="statistics">
      <BlockHeading heading={content.heading} />
      <dl className="grid grid-cols-2 gap-6 lg:grid-cols-4">
        {items.map((item, i) => (
          <div key={`${item.label?.en ?? "stat"}-${i}`} className="rounded-2xl border border-border bg-card p-6">
            <dt className="font-serif text-4xl font-bold text-primary">
              {item.value}
              {item.suffix ? <span className="text-2xl">{item.suffix}</span> : null}
            </dt>
            <dd className="mt-2 text-sm text-muted-foreground">
              {item.label ? pickLocale(item.label, locale) : ""}
            </dd>
          </div>
        ))}
      </dl>
    </BlockShell>
  );
}
