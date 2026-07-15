"use client";

import Link from "next/link";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { CategoriesContent, HomepageSection } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS categories grid. Consumes the server-resolved categories. Bilingual, RTL-safe. */
export function CategoriesBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as CategoriesContent;
  const categories = section.resolved?.categories ?? [];
  if (categories.length === 0) return null;

  return (
    <BlockShell section={section} id="categories">
      <BlockHeading heading={content.heading} subheading={content.subheading} />
      <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {categories.map((cat) => (
          <li key={cat.id}>
            <Link
              href={cat.href}
              className="flex h-full flex-col rounded-2xl border border-border bg-card p-5 transition-shadow hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            >
              <span className="font-serif text-base font-semibold text-foreground">
                {cat.name ? pickLocale(cat.name, locale) : cat.slug}
              </span>
              {cat.description ? (
                <span className="mt-2 line-clamp-2 text-xs text-muted-foreground">{pickLocale(cat.description, locale)}</span>
              ) : null}
            </Link>
          </li>
        ))}
      </ul>
    </BlockShell>
  );
}
