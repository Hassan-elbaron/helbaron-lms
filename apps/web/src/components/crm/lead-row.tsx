"use client";

import Link from "next/link";
import { useI18n } from "@/lib/i18n/i18n-context";
import type { Lead } from "@/lib/crm/api";
import { formatMoney } from "@/lib/format";
import { Button } from "@/components/ui/button";
import { LeadStatusBadge } from "./lead-status-badge";

export function LeadRow({ lead }: { lead: Lead }) {
  const { t, locale } = useI18n();
  return (
    <div className="card-hover flex flex-wrap items-center justify-between gap-3 rounded-md border p-3 hover:border-primary/30 hover:shadow-sm">
      <div className="min-w-0">
        <p className="truncate text-sm font-medium">{lead.name}</p>
        <p className="truncate text-xs text-muted-foreground">
          {lead.email ?? lead.phone ?? "—"}
          {lead.source ? ` · ${lead.source}` : ""}
        </p>
      </div>
      <div className="flex items-center gap-3">
        {lead.value_minor != null ? (
          <span className="text-sm tabular-nums text-muted-foreground">
            {formatMoney(lead.value_minor, lead.currency ?? "USD", locale)}
          </span>
        ) : null}
        <LeadStatusBadge status={lead.status} />
        <Button asChild size="sm" variant="outline">
          <Link href={`/crm/leads/${lead.id}`}>{t("crm.leads.view")}</Link>
        </Button>
      </div>
    </div>
  );
}
