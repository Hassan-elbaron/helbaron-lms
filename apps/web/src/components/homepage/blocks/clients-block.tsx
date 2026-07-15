"use client";

import type { ClientsContent, HomepageSection, LogoCloudContent } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/**
 * Shared logo-row renderer for the Clients and LogoCloud blocks (same { name, logo, href } shape).
 * Renders logos when provided, else the client name as a text mark. Bilingual heading, RTL-safe.
 */
export function ClientsBlock({ section }: { section: HomepageSection }) {
  const content = section.content as ClientsContent | LogoCloudContent;
  const items = content.items ?? [];
  if (items.length === 0) return null;

  return (
    <BlockShell section={section} id={section.type}>
      <BlockHeading heading={content.heading} />
      <ul className="flex flex-wrap items-center justify-center gap-x-10 gap-y-6">
        {items.map((item, i) => {
          const inner = item.logo ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={item.logo} alt={item.name ?? ""} width={120} height={32} className="h-8 w-auto opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0" loading="lazy" decoding="async" />
          ) : (
            <span className="font-serif text-lg font-semibold text-muted-foreground">{item.name}</span>
          );
          return (
            <li key={`${item.name ?? "logo"}-${i}`}>
              {item.href ? (
                <a href={item.href} target="_blank" rel="noopener noreferrer" className="inline-flex">
                  {inner}
                </a>
              ) : (
                inner
              )}
            </li>
          );
        })}
      </ul>
    </BlockShell>
  );
}
