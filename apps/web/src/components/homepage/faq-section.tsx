"use client";

import { Plus } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { FaqContent } from "@/lib/homepage/api";
import { Section, SectionHeading } from "@/components/landing/section";
import { Reveal } from "@/components/landing/reveal";

const HEADING = {
  eyebrow: { en: "FAQ", ar: "الأسئلة الشائعة" },
  title1: { en: "Questions?", ar: "أسئلة؟" },
  title2: { en: "Answered.", ar: "إجابات." },
};

/**
 * CMS-driven FAQ. Uses native <details>/<summary> so it is fully keyboard-accessible and works
 * without JavaScript. RTL-safe. Renders nothing when there are no items.
 */
export function FaqSection({ content }: { content?: FaqContent }) {
  const { locale } = useI18n();
  const items = content?.items?.filter((q) => q.question) ?? [];
  if (items.length === 0) return null;

  return (
    <Section id="faq" className="bg-background">
      <SectionHeading
        eyebrow={pickLocale(HEADING.eyebrow, locale)}
        title1={pickLocale(HEADING.title1, locale)}
        title2={pickLocale(HEADING.title2, locale)}
      />
      <Reveal className="mx-auto max-w-3xl">
        <ul className="divide-y divide-border overflow-hidden rounded-2xl border border-border bg-card">
          {items.map((q, i) => (
            <li key={`${q.question?.en ?? "q"}-${i}`}>
              <details className="group">
                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-start font-medium text-foreground focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                  <span>{q.question ? pickLocale(q.question, locale) : ""}</span>
                  <Plus className="size-4 shrink-0 text-copper transition-transform group-open:rotate-45" aria-hidden />
                </summary>
                {q.answer ? (
                  <div className="px-5 pb-5 text-sm leading-relaxed text-muted-foreground">
                    {pickLocale(q.answer, locale)}
                  </div>
                ) : null}
              </details>
            </li>
          ))}
        </ul>
      </Reveal>
    </Section>
  );
}
