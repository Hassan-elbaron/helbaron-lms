"use client";

import Link from "next/link";
import { Contact, Target, Headset, ListChecks } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useLeads } from "@/lib/crm/hooks";
import { useConsulting } from "@/lib/org/hooks";
import { PageHeader } from "@/components/student/page-header";
import { StatCard } from "@/components/student/stat-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { LeadRow } from "@/components/crm/lead-row";
import { UnavailablePanel } from "@/components/crm/unavailable-panel";

const OPEN_CONSULTING = new Set(["new", "triaged", "in_progress"]);

export default function CrmDashboardPage() {
  const { t } = useI18n();
  const leads = useLeads({ page: 1, per_page: 5 });
  const consulting = useConsulting();

  const leadItems = leads.data?.data ?? [];
  const totalLeads = leads.data?.meta.total ?? leadItems.length;
  // "Opportunities" has no endpoint; qualified/converted leads are shown as a proxy.
  const opportunities = leadItems.filter((l) => l.status === "qualified" || l.status === "converted").length;
  const requests = consulting.data ?? [];
  const openConsulting = requests.filter((r) => OPEN_CONSULTING.has(r.status)).length;

  const loading = leads.isPending || consulting.isPending;

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="CRM" icon="LayoutDashboard" title={t("crm.dashboard.title")} subtitle={t("crm.dashboard.subtitle")} />

      <div className="stagger-in grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {loading ? (
          <>
            <Skeleton className="h-24" />
            <Skeleton className="h-24" />
            <Skeleton className="h-24" />
            <Skeleton className="h-24" />
          </>
        ) : (
          <>
            <StatCard label={t("crm.dashboard.leads")} value={totalLeads} icon={Contact} />
            <StatCard label={t("crm.dashboard.opportunities")} value={opportunities} icon={Target} />
            <StatCard label={t("crm.dashboard.consulting")} value={requests.length} icon={Headset} />
            <StatCard label={t("crm.dashboard.tasks")} value="—" icon={ListChecks} />
          </>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>{t("crm.dashboard.recentLeads")}</CardTitle>
            <Button asChild variant="ghost" size="sm">
              <Link href="/crm/leads">{t("crm.dashboard.viewLeads")}</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-3">
            {leads.isPending ? (
              <Skeleton className="h-16" />
            ) : leadItems.length === 0 ? (
              <p className="text-sm text-muted-foreground">{t("crm.leads.empty")}</p>
            ) : (
              leadItems.map((l) => <LeadRow key={l.id} lead={l} />)
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>{t("crm.dashboard.consulting")}</CardTitle>
            <Button asChild variant="ghost" size="sm">
              <Link href="/crm/consulting">{t("crm.dashboard.viewConsulting")}</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm text-muted-foreground">
              {t("crm.dashboard.consulting")}: <span className="font-semibold text-foreground">{openConsulting}</span>
            </p>
            <UnavailablePanel />
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
