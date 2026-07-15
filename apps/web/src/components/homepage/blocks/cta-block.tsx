"use client";

import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { CtaContent, HomepageSection } from "@/lib/homepage/api";
import { BlockShell } from "@/components/homepage/block-shell";
import { Button } from "@/components/ui/button";

/** CMS call-to-action banner. Bilingual, RTL-safe (arrow flips). Renders nothing without a headline. */
export function CtaBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as CtaContent;
  if (!content.headline) return null;

  const primary = content.cta_primary;
  const secondary = content.cta_secondary;

  return (
    <BlockShell section={section} id="cta">
      <div className="mx-auto max-w-3xl rounded-3xl border border-border bg-card p-10 text-center shadow-sm">
        <h2 className="font-serif text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
          {pickLocale(content.headline, locale)}
        </h2>
        {content.subheadline ? (
          <p className="mx-auto mt-4 max-w-xl text-muted-foreground">{pickLocale(content.subheadline, locale)}</p>
        ) : null}
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          {primary?.href ? (
            <Button asChild size="lg">
              <Link href={primary.href}>
                {primary.label ? pickLocale(primary.label, locale) : ""}
                <ArrowRight className="size-4 rtl:rotate-180" aria-hidden />
              </Link>
            </Button>
          ) : null}
          {secondary?.href ? (
            <Button asChild size="lg" variant="outline">
              <Link href={secondary.href}>{secondary.label ? pickLocale(secondary.label, locale) : ""}</Link>
            </Button>
          ) : null}
        </div>
      </div>
    </BlockShell>
  );
}
