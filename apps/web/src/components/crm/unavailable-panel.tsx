"use client";

import { Info } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";

/** Renders a labelled panel for CRM sub-resources that have no read API endpoint yet. */
export function UnavailablePanel({ note }: { note?: string }) {
  const { t } = useI18n();
  return (
    <div className="flex items-start gap-2 rounded-md border border-dashed p-4 text-sm text-muted-foreground">
      <Info className="mt-0.5 size-4 shrink-0" aria-hidden />
      <span>{note ?? t("crm.notAvailable")}</span>
    </div>
  );
}
