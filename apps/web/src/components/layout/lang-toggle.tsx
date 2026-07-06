"use client";

import { Languages } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Button } from "@/components/ui/button";

export function LangToggle() {
  const { locale, setLocale, t } = useI18n();
  const next = locale === "en" ? "ar" : "en";
  return (
    <Button variant="ghost" size="icon" aria-label={t("lang.switch")} onClick={() => setLocale(next)}>
      <Languages className="size-5" aria-hidden />
      <span className="sr-only">{next.toUpperCase()}</span>
    </Button>
  );
}
