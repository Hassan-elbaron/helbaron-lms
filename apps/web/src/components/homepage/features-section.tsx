"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import type { FeaturesContent } from "@/lib/homepage/api";
import { Section, SectionHeading } from "@/components/landing/section";
import { Reveal } from "@/components/landing/reveal";

/**
 * CMS-driven "what we offer" features grid. Falls back to brand service lines when no content is
 * supplied so the section is never empty. RTL-safe, keyboard/AT friendly.
 */
export function FeaturesSection({ content }: { content?: FeaturesContent }) {
  const { locale } = useI18n();
  const h = brandTheme.serviceHeading;

  const items =
    content?.items && content.items.length > 0
      ? content.items
      : brandTheme.serviceLines.map((s) => ({ title: s.name, description: s.desc, icon: s.icon }));

  return (
    <Section id="features" className="bg-background">
      <SectionHeading
        eyebrow={pickLocale(h.eyebrow, locale)}
        title1={pickLocale(h.title1, locale)}
        title2={pickLocale(h.title2, locale)}
        subtitle={pickLocale(h.subtitle, locale)}
      />
      <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {items.map((item, i) => (
          <Reveal as="li" key={`${item.title?.en ?? "feature"}-${i}`} delay={i * 60}>
            <div className="group h-full rounded-2xl border border-border bg-card p-6 shadow-sm transition-shadow hover:shadow-md">
              <span
                className="mb-4 inline-flex size-10 items-center justify-center rounded-xl bg-primary/10 font-serif text-sm font-bold text-primary"
                aria-hidden
              >
                {(item.title?.en ?? "•").slice(0, 1).toUpperCase()}
              </span>
              <h3 className="font-serif text-lg font-semibold text-foreground">
                {item.title ? pickLocale(item.title, locale) : ""}
              </h3>
              {item.description ? (
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                  {pickLocale(item.description, locale)}
                </p>
              ) : null}
            </div>
          </Reveal>
        ))}
      </ul>
    </Section>
  );
}
