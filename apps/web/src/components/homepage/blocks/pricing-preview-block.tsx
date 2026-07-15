"use client";

import Link from "next/link";
import { Check } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import { cn } from "@/lib/utils";
import type { HomepageSection, PricingPreviewContent } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";
import { Button } from "@/components/ui/button";

/** CMS pricing preview. Bilingual, RTL-safe. Renders nothing without plans. */
export function PricingPreviewBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as PricingPreviewContent;
  const plans = content.plans ?? [];
  if (plans.length === 0) return null;

  return (
    <BlockShell section={section} id="pricing-preview">
      <BlockHeading heading={content.heading} subheading={content.subheading} />
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {plans.map((plan, i) => (
          <div
            key={`${plan.name?.en ?? "plan"}-${i}`}
            className={cn(
              "flex flex-col rounded-2xl border bg-card p-6 text-start",
              plan.highlighted ? "border-primary shadow-md ring-1 ring-primary/40" : "border-border",
            )}
          >
            <h3 className="font-serif text-lg font-semibold text-foreground">
              {plan.name ? pickLocale(plan.name, locale) : ""}
            </h3>
            <p className="mt-3 flex items-baseline gap-1">
              <span className="font-serif text-3xl font-bold text-foreground">{plan.price}</span>
              {plan.period ? <span className="text-sm text-muted-foreground">{pickLocale(plan.period, locale)}</span> : null}
            </p>
            <ul className="mt-5 flex-1 space-y-2">
              {(plan.features ?? []).map((f, j) => (
                <li key={j} className="flex items-start gap-2 text-sm text-muted-foreground">
                  <Check className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden />
                  <span>{pickLocale(f, locale)}</span>
                </li>
              ))}
            </ul>
            {plan.cta?.href ? (
              <Button asChild className="mt-6" variant={plan.highlighted ? "default" : "outline"}>
                <Link href={plan.cta.href}>{plan.cta.label ? pickLocale(plan.cta.label, locale) : ""}</Link>
              </Button>
            ) : null}
          </div>
        ))}
      </div>
    </BlockShell>
  );
}
