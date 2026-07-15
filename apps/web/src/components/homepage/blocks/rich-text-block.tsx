"use client";

import DOMPurify from "isomorphic-dompurify";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { HomepageSection, RichTextContent } from "@/lib/homepage/api";
import { BlockShell } from "@/components/homepage/block-shell";

/**
 * CMS rich-text block. The body HTML is sanitized server-side on save AND re-sanitized here
 * (defense in depth) via DOMPurify before injection, mirroring the static-page renderer. Bilingual.
 */
function sanitize(dirty: string): string {
  return DOMPurify.sanitize(dirty, {
    ALLOWED_TAGS: [
      "a", "b", "blockquote", "br", "caption", "code", "div", "em", "h1", "h2", "h3", "h4", "h5", "h6",
      "hr", "i", "img", "li", "ol", "p", "pre", "s", "span", "strong", "sub", "sup", "table", "tbody",
      "td", "th", "thead", "tr", "u", "ul",
    ],
    ALLOWED_ATTR: ["href", "src", "alt", "title", "target", "rel", "class", "dir", "lang", "colspan", "rowspan", "width", "height"],
    FORBID_TAGS: ["script", "style", "iframe", "object", "embed", "form"],
  });
}

export function RichTextBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as RichTextContent;
  const html = sanitize(content.body?.[locale] ?? content.body?.en ?? "");
  if (!content.title && !html) return null;

  return (
    <BlockShell section={section} id="rich-text">
      <div className="mx-auto max-w-3xl">
        {content.title ? (
          <h2 className="mb-6 font-serif text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
            {pickLocale(content.title, locale)}
          </h2>
        ) : null}
        <div
          className="prose max-w-none dark:prose-invert"
          // Sanitized server-side and re-sanitized above (DOMPurify) before injection.
          dangerouslySetInnerHTML={{ __html: html }}
        />
      </div>
    </BlockShell>
  );
}
