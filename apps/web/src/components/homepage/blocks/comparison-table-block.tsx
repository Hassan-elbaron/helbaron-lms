"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { ComparisonTableContent, HomepageSection } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS comparison table. Bilingual headers/cells, RTL-safe (logical text alignment). */
export function ComparisonTableBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as ComparisonTableContent;
  const columns = content.columns ?? [];
  const rows = content.rows ?? [];
  if (columns.length === 0 || rows.length === 0) return null;

  return (
    <BlockShell section={section} id="comparison-table">
      <BlockHeading heading={content.heading} />
      <div className="overflow-x-auto rounded-2xl border border-border">
        <table className="w-full border-collapse text-start text-sm">
          <thead>
            <tr className="bg-card">
              {columns.map((col, i) => (
                <th key={i} className="border-b border-border px-4 py-3 text-start font-serif font-semibold text-foreground">
                  {pickLocale(col, locale)}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.map((row, r) => (
              <tr key={r} className="even:bg-card/40">
                {(row.cells ?? []).map((cell, c) => (
                  <td
                    key={c}
                    className={c === 0 ? "px-4 py-3 font-medium text-foreground" : "px-4 py-3 text-muted-foreground"}
                  >
                    {pickLocale(cell, locale)}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </BlockShell>
  );
}
