"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { HomepageSection, NewsletterContent } from "@/lib/homepage/api";
import { BlockShell } from "@/components/homepage/block-shell";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

/**
 * CMS newsletter sign-up. Bilingual, RTL-safe. Posts to the admin-provided action URL when present;
 * otherwise the form is inert (no client submission). Accessible labelled input.
 */
export function NewsletterBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as NewsletterContent;
  if (!content.heading) return null;

  const placeholder = content.placeholder ? pickLocale(content.placeholder, locale) : "";
  const cta = content.cta ? pickLocale(content.cta, locale) : "";

  return (
    <BlockShell section={section} id="newsletter">
      <div className="mx-auto max-w-2xl rounded-3xl border border-border bg-card p-10 text-center shadow-sm">
        <h2 className="font-serif text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
          {pickLocale(content.heading, locale)}
        </h2>
        {content.subheading ? (
          <p className="mx-auto mt-3 max-w-lg text-muted-foreground">{pickLocale(content.subheading, locale)}</p>
        ) : null}
        <form
          className="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row"
          action={content.action_url ?? undefined}
          method={content.action_url ? "post" : undefined}
          onSubmit={content.action_url ? undefined : (e) => e.preventDefault()}
        >
          <label className="sr-only" htmlFor="newsletter-email">
            {placeholder || "Email"}
          </label>
          <Input
            id="newsletter-email"
            type="email"
            name="email"
            required
            placeholder={placeholder}
            className="h-auto flex-1 rounded-xl px-4 py-2.5"
          />
          <Button type="submit">{cta}</Button>
        </form>
      </div>
    </BlockShell>
  );
}
