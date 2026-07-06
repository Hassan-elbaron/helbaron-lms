"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";

export function AnnouncementBar() {
  const { locale } = useI18n();
  return (
    <div className="bg-[oklch(0.28_0.03_190)] text-primary-foreground">
      <div className="mx-auto max-w-6xl px-4 py-2 text-center text-[0.7rem] font-medium uppercase tracking-[0.18em] text-primary-foreground/85 sm:text-xs">
        {pickLocale(brandTheme.announcement, locale)}
      </div>
    </div>
  );
}
