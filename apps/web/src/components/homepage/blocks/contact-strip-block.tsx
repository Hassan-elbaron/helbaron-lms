"use client";

import Link from "next/link";
import { Mail, MapPin, Phone } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { ContactStripContent, HomepageSection } from "@/lib/homepage/api";
import { BlockShell } from "@/components/homepage/block-shell";
import { Button } from "@/components/ui/button";

/** CMS contact strip: heading + phone/email/address + CTA. Bilingual, RTL-safe. */
export function ContactStripBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as ContactStripContent;
  if (!content.heading) return null;

  return (
    <BlockShell section={section} id="contact-strip">
      <div className="flex flex-col items-center gap-6 rounded-3xl border border-border bg-card p-8 text-center md:flex-row md:justify-between md:text-start">
        <div>
          <h2 className="font-serif text-2xl font-semibold tracking-tight text-foreground">
            {pickLocale(content.heading, locale)}
          </h2>
          {content.subheading ? (
            <p className="mt-2 text-sm text-muted-foreground">{pickLocale(content.subheading, locale)}</p>
          ) : null}
          <ul className="mt-4 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-muted-foreground md:justify-start">
            {content.phone ? (
              <li className="inline-flex items-center gap-2">
                <Phone className="size-4 text-copper" aria-hidden />
                <a href={`tel:${content.phone}`} className="hover:text-foreground" dir="ltr">{content.phone}</a>
              </li>
            ) : null}
            {content.email ? (
              <li className="inline-flex items-center gap-2">
                <Mail className="size-4 text-copper" aria-hidden />
                <a href={`mailto:${content.email}`} className="hover:text-foreground">{content.email}</a>
              </li>
            ) : null}
            {content.address ? (
              <li className="inline-flex items-center gap-2">
                <MapPin className="size-4 text-copper" aria-hidden />
                <span>{pickLocale(content.address, locale)}</span>
              </li>
            ) : null}
          </ul>
        </div>
        {content.cta?.href ? (
          <Button asChild size="lg" className="shrink-0">
            <Link href={content.cta.href}>{content.cta.label ? pickLocale(content.cta.label, locale) : ""}</Link>
          </Button>
        ) : null}
      </div>
    </BlockShell>
  );
}
