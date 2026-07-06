"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import type { LeadStatus } from "@/lib/crm/api";
import { Badge } from "@/components/ui/badge";

const variant: Record<LeadStatus, "default" | "secondary" | "success" | "warning" | "outline" | "destructive"> = {
  new: "warning",
  working: "secondary",
  qualified: "default",
  converted: "success",
  lost: "destructive",
};

export function LeadStatusBadge({ status }: { status: LeadStatus }) {
  const { t } = useI18n();
  return <Badge variant={variant[status] ?? "outline"}>{t(`crm.leadStatus.${status}`)}</Badge>;
}
