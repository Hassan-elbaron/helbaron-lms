"use client";

import Link from "next/link";
import { ArrowRight, CalendarDays } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { FeaturedEventsContent, HomepageSection } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";
import { Button } from "@/components/ui/button";

/** CMS featured-events list. Consumes the server-resolved upcoming events. Bilingual, RTL-safe. */
export function FeaturedEventsBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as FeaturedEventsContent;
  const events = section.resolved?.events ?? [];
  if (events.length === 0) return null;

  const fmt = new Intl.DateTimeFormat(locale === "ar" ? "ar" : "en", { dateStyle: "medium", timeStyle: "short" });

  return (
    <BlockShell section={section} id="featured-events">
      <BlockHeading heading={content.heading} subheading={content.subheading} />
      <ul className="grid gap-4 sm:grid-cols-2">
        {events.map((event) => (
          <li key={event.id}>
            <Link
              href={event.href}
              className="flex h-full flex-col rounded-xl border border-border bg-card p-6 elevation-1 transition-shadow duration-[--duration-fast] hover:elevation-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            >
              {event.starts_at ? (
                <span className="mb-3 inline-flex items-center gap-2 text-xs font-semibold text-copper">
                  <CalendarDays className="size-4" aria-hidden />
                  {fmt.format(new Date(event.starts_at))}
                </span>
              ) : null}
              <span className="font-serif text-lg font-semibold text-foreground">
                {event.title ? pickLocale(event.title, locale) : ""}
              </span>
              {event.description ? (
                <span className="mt-2 line-clamp-2 text-sm text-muted-foreground">{pickLocale(event.description, locale)}</span>
              ) : null}
            </Link>
          </li>
        ))}
      </ul>
      {content.cta?.href ? (
        <div className="mt-10 text-center">
          <Button asChild size="lg" variant="outline">
            <Link href={content.cta.href}>
              {content.cta.label ? pickLocale(content.cta.label, locale) : ""}
              <ArrowRight className="size-4 rtl:rotate-180" aria-hidden />
            </Link>
          </Button>
        </div>
      ) : null}
    </BlockShell>
  );
}
