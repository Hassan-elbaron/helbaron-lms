"use client";

import { DollarSign, GraduationCap, CheckCircle2, Video, Headset, UserPlus, ShoppingBag, Award } from "lucide-react";
import type { LucideIcon } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useKpis } from "@/lib/analytics/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { KpiCard } from "@/components/analytics/kpi-card";
import { Badge } from "@/components/ui/badge";

const METRICS = [
  "revenue",
  "enrollments",
  "completions",
  "live_sessions_completed",
  "consulting_requests",
  "signups",
  "orders_paid",
  "certificates_issued",
];

const META: Record<string, { labelKey: string; icon: LucideIcon }> = {
  revenue: { labelKey: "analytics.dashboard.revenue", icon: DollarSign },
  enrollments: { labelKey: "analytics.dashboard.enrollments", icon: GraduationCap },
  completions: { labelKey: "analytics.dashboard.completions", icon: CheckCircle2 },
  live_sessions_completed: { labelKey: "analytics.dashboard.liveSessions", icon: Video },
  consulting_requests: { labelKey: "analytics.dashboard.crm", icon: Headset },
  signups: { labelKey: "analytics.dashboard.signups", icon: UserPlus },
  orders_paid: { labelKey: "analytics.dashboard.ordersPaid", icon: ShoppingBag },
  certificates_issued: { labelKey: "analytics.dashboard.certificates", icon: Award },
};

export default function AnalyticsDashboardPage() {
  const { t } = useI18n();
  const query = useKpis(METRICS);

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="ANALYTICS" icon="BarChart3"
        title={t("analytics.dashboard.title")}
        subtitle={t("analytics.dashboard.subtitle")}
        action={<Badge variant="secondary">{t("analytics.dashboard.range")}</Badge>}
      />

      <QueryState
        query={query}
        isEmpty={(d) => d.kpis.length === 0}
        empty={<EmptyState title={t("analytics.dashboard.empty")} />}
      >
        {(data) => (
          <div className="stagger-in grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {data.kpis.map((kpi) => {
              const meta = META[kpi.metric];
              return (
                <KpiCard
                  key={kpi.metric}
                  kpi={kpi}
                  label={meta ? t(meta.labelKey) : kpi.metric}
                  icon={meta?.icon}
                />
              );
            })}
          </div>
        )}
      </QueryState>
    </div>
  );
}
