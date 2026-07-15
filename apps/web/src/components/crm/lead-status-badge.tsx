"use client";

import { Sparkles, Loader, CheckCircle2, Trophy, XCircle } from "lucide-react";
import type { LucideIcon } from "lucide-react";
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

// A per-status icon so the state is conveyed by shape + text, never colour alone (WCAG 1.4.1).
const icon: Record<LeadStatus, LucideIcon> = {
  new: Sparkles,
  working: Loader,
  qualified: CheckCircle2,
  converted: Trophy,
  lost: XCircle,
};

export function LeadStatusBadge({ status }: { status: LeadStatus }) {
  const { t } = useI18n();
  const Icon = icon[status] ?? Sparkles;
  return (
    <Badge variant={variant[status] ?? "outline"} className="gap-1">
      <Icon className="size-3" aria-hidden />
      {t(`crm.leadStatus.${status}`)}
    </Badge>
  );
}
