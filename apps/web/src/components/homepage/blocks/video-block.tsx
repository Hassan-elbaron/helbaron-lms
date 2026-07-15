"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { HomepageSection, VideoContent } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS video block. Embeds the provided URL responsively. Bilingual caption, RTL-safe. */
export function VideoBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as VideoContent;
  if (!content.url) return null;

  const caption = content.caption ? pickLocale(content.caption, locale) : "";

  return (
    <BlockShell section={section} id="video">
      <BlockHeading heading={content.heading} />
      <div className="mx-auto max-w-4xl overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
        <div className="relative aspect-video w-full">
          <iframe
            src={content.url}
            title={caption || "Video"}
            className="absolute inset-0 h-full w-full"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowFullScreen
          />
        </div>
      </div>
      {caption ? <p className="mt-4 text-center text-sm text-muted-foreground">{caption}</p> : null}
    </BlockShell>
  );
}
