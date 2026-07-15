"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { HomepageSection, NumbersContent } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS KPI/numbers strip. Bilingual, RTL-safe. Renders nothing when empty. */
export function NumbersBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as NumbersContent;
  const items = content.items ?? [];
  if (items.length === 0) return null;

  return (
    <BlockShell section={section} id="numbers">
      <BlockHeading heading={content.heading} />
      <dl className="grid grid-cols-1 divide-y divide-border rounded-2xl border border-border bg-card sm:grid-cols-3 sm:divide-x sm:divide-y-0 rtl:sm:divide-x-reverse">
        {items.map((item, i) => (
          <div key={`${item.label?.en ?? "num"}-${i}`} className="p-6 text-center">
            <dt className="font-serif text-3xl font-bold text-foreground">{item.value}</dt>
            <dd className="mt-1 text-sm text-muted-foreground">
              {item.label ? pickLocale(item.label, locale) : ""}
            </dd>
          </div>
        ))}
      </dl>
    </BlockShell>
  );
}
