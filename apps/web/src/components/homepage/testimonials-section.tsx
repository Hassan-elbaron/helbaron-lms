"use client";

import { Quote } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { TestimonialsContent } from "@/lib/homepage/api";
import { Section, SectionHeading } from "@/components/landing/section";
import { Reveal } from "@/components/landing/reveal";

const HEADING = {
  eyebrow: { en: "TESTIMONIALS", ar: "شهادات" },
  title1: { en: "Trusted by", ar: "موثوق من" },
  title2: { en: "teams across MENA.", ar: "فرق في المنطقة." },
  subtitle: {
    en: "What professionals, founders, and enterprises say about learning with HElbaron.",
    ar: "ما يقوله المحترفون والروّاد والمؤسسات عن التعلّم مع HElbaron.",
  },
};

/** CMS-driven testimonials. Renders nothing when there are no items (page stays valid). */
export function TestimonialsSection({ content }: { content?: TestimonialsContent }) {
  const { locale } = useI18n();
  const items = content?.items?.filter((t) => t.quote) ?? [];
  if (items.length === 0) return null;

  return (
    <Section id="testimonials" className="bg-card/40">
      <SectionHeading
        eyebrow={pickLocale(HEADING.eyebrow, locale)}
        title1={pickLocale(HEADING.title1, locale)}
        title2={pickLocale(HEADING.title2, locale)}
        subtitle={pickLocale(HEADING.subtitle, locale)}
      />
      <ul className="grid gap-5 md:grid-cols-3">
        {items.map((t, i) => (
          <Reveal as="li" key={`${t.author ?? "quote"}-${i}`} delay={i * 60}>
            <figure className="flex h-full flex-col rounded-2xl border border-border bg-card p-6 shadow-sm">
              <Quote className="size-6 text-copper/70 rtl:-scale-x-100" aria-hidden />
              <blockquote className="mt-3 flex-1 text-sm leading-relaxed text-foreground">
                {t.quote ? pickLocale(t.quote, locale) : ""}
              </blockquote>
              <figcaption className="mt-4 border-t border-border pt-4">
                {t.author ? <span className="block text-sm font-semibold text-foreground">{t.author}</span> : null}
                {t.role ? <span className="block text-xs text-muted-foreground">{pickLocale(t.role, locale)}</span> : null}
              </figcaption>
            </figure>
          </Reveal>
        ))}
      </ul>
    </Section>
  );
}
