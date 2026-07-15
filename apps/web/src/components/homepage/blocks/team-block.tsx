"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import type { HomepageSection, TeamContent } from "@/lib/homepage/api";
import { BlockShell, BlockHeading } from "@/components/homepage/block-shell";

/** CMS team grid. Bilingual roles, RTL-safe. Renders nothing without members. */
export function TeamBlock({ section }: { section: HomepageSection }) {
  const { locale } = useI18n();
  const content = section.content as TeamContent;
  const items = content.items ?? [];
  if (items.length === 0) return null;

  return (
    <BlockShell section={section} id="team">
      <BlockHeading heading={content.heading} />
      <ul className="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
        {items.map((member, i) => {
          const card = (
            <div className="flex h-full flex-col items-center rounded-2xl border border-border bg-card p-6 text-center">
              {member.avatar ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={member.avatar} alt="" width={80} height={80} className="size-20 rounded-full object-cover" loading="lazy" decoding="async" />
              ) : (
                <span className="flex size-20 items-center justify-center rounded-full bg-primary/10 font-serif text-2xl font-bold text-primary" aria-hidden>
                  {(member.name ?? "?").slice(0, 1)}
                </span>
              )}
              <span className="mt-4 font-serif text-base font-semibold text-foreground">{member.name}</span>
              {member.role ? (
                <span className="mt-1 text-xs text-muted-foreground">{pickLocale(member.role, locale)}</span>
              ) : null}
            </div>
          );
          return (
            <li key={`${member.name ?? "member"}-${i}`}>
              {member.href ? (
                <a href={member.href} target="_blank" rel="noopener noreferrer" className="block focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                  {card}
                </a>
              ) : (
                card
              )}
            </li>
          );
        })}
      </ul>
    </BlockShell>
  );
}
