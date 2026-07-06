"use client";

import Link from "next/link";
import { MapPin } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";

export function LandingFooter() {
  const { locale } = useI18n();
  const f = brandTheme.footer;
  return (
    <footer className="bg-primary text-primary-foreground">
      <div className="mx-auto max-w-6xl px-4 py-14">
        <div className="grid gap-10 lg:grid-cols-[1.5fr_1fr_1fr_1fr]">
          {/* Brand block */}
          <div>
            <Link href="/" className="flex items-center gap-2">
              <span className="flex size-8 items-center justify-center rounded-lg bg-gold font-serif text-sm font-bold text-gold-foreground">H</span>
              <span className="font-serif text-lg font-semibold">{brandTheme.name}</span>
            </Link>
            <p className="mt-4 max-w-sm text-sm text-primary-foreground/70">{pickLocale(f.description, locale)}</p>
            <div className="mt-5 flex flex-wrap gap-2">
              {f.locations.map((loc) => (
                <span key={loc} className="inline-flex items-center gap-1 rounded-full border border-white/20 px-3 py-1 text-xs font-medium text-primary-foreground/90">
                  <MapPin className="size-3 text-gold" aria-hidden /> {loc}
                </span>
              ))}
            </div>
          </div>

          {/* Link columns */}
          {f.columns.map((col) => (
            <div key={col.title.en}>
              <h3 className="mb-3 text-sm font-semibold">{pickLocale(col.title, locale)}</h3>
              <ul className="space-y-2">
                {col.links.map((l, i) => (
                  <li key={`${l.href}-${i}`}>
                    <Link href={l.href} className="text-sm text-primary-foreground/70 transition-colors hover:text-primary-foreground">
                      {pickLocale(l.label, locale)}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/15 pt-6 text-sm text-primary-foreground/70 sm:flex-row">
          <p>© {new Date().getFullYear()} {brandTheme.name}. {pickLocale({ en: "All rights reserved.", ar: "جميع الحقوق محفوظة." }, locale)}</p>
          <div className="flex flex-wrap items-center gap-4">
            {f.legal.map((l) => (
              <Link key={l.href} href={l.href} className="transition-colors hover:text-primary-foreground">
                {pickLocale(l.label, locale)}
              </Link>
            ))}
          </div>
        </div>
      </div>
    </footer>
  );
}
