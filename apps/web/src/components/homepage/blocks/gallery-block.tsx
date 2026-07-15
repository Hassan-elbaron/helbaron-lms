"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { GalleryContent, HomepageSection } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS image gallery. Bilingual captions, RTL-safe. Renders nothing without images. */
export function GalleryBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as GalleryContent;
  const items = (content.items ?? []).filter((it) => it.image);
  if (items.length === 0) return null;

  return (
    <BlockShell section={section} id="gallery">
      <BlockHeading heading={content.heading} />
      <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        {items.map((item, i) => (
          <li key={`${item.caption?.en ?? "img"}-${i}`} className="overflow-hidden rounded-2xl border border-border bg-card">
            <figure>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={item.image as string}
                alt={item.caption ? pickLocale(item.caption, locale) : ""}
                className="aspect-square w-full object-cover"
                loading="lazy"
              />
              {item.caption ? (
                <figcaption className="p-3 text-xs text-muted-foreground">{pickLocale(item.caption, locale)}</figcaption>
              ) : null}
            </figure>
          </li>
        ))}
      </ul>
    </BlockShell>
  );
}
