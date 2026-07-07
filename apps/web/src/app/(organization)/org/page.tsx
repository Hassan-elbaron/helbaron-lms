"use client";

import Link from "next/link";
import { Building, Users, Armchair, Headset } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useConsulting, useOrganizations } from "@/lib/org/hooks";
import { PageHeader } from "@/components/student/page-header";
import { StatCard } from "@/components/student/stat-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { ConsultingCard } from "@/components/org/consulting-card";

const OPEN_STATUSES = new Set(["new", "triaged", "in_progress"]);

export default function OrgDashboardPage() {
  const { t } = useI18n();
  const orgs = useOrganizations(1);
  const consulting = useConsulting();

  const orgList = orgs.data?.data ?? [];
  const totalOrgs = orgs.data?.meta.total ?? orgList.length;
  const totalMembers = orgList.reduce((sum, o) => sum + (o.members_count ?? 0), 0);
  const requests = consulting.data ?? [];
  const openRequests = requests.filter((r) => OPEN_STATUSES.has(r.status)).length;

  const loading = orgs.isPending || consulting.isPending;

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="ORGANIZATION" icon="Building2" title={t("org.dashboard.title")} subtitle={t("org.dashboard.subtitle")} />

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
            <StatCard label={t("org.dashboard.organizations")} value={totalOrgs} icon={Building} />
            <StatCard label={t("org.dashboard.members")} value={totalMembers} icon={Users} />
            <StatCard label={t("org.dashboard.seats")} value={totalMembers} icon={Armchair} />
            <StatCard label={t("org.dashboard.consulting")} value={requests.length} icon={Headset} />
          </>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>{t("org.dashboard.organizations")}</CardTitle>
            <Button asChild variant="ghost" size="sm">
              <Link href="/org/organizations">{t("org.dashboard.viewOrganizations")}</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-3">
            {orgs.isPending ? (
              <Skeleton className="h-16" />
            ) : orgList.length === 0 ? (
              <p className="text-sm text-muted-foreground">{t("org.list.empty")}</p>
            ) : (
              orgList.slice(0, 5).map((o) => (
                <div key={o.id} className="flex items-center justify-between gap-3 rounded-md border p-3">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium">{o.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {o.members_count ?? 0} {t("org.list.membersCount")}
                    </p>
                  </div>
                  <Button asChild size="sm" variant="outline">
                    <Link href={`/org/organizations/${o.id}`}>{t("org.list.view")}</Link>
                  </Button>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>{t("org.dashboard.consulting")}</CardTitle>
            <Button asChild variant="ghost" size="sm">
              <Link href="/org/consulting">{t("org.dashboard.viewConsulting")}</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm text-muted-foreground">
              {t("org.dashboard.openRequests")}: <span className="font-semibold text-foreground">{openRequests}</span>
            </p>
            {consulting.isPending ? (
              <Skeleton className="h-16" />
            ) : requests.length === 0 ? (
              <p className="text-sm text-muted-foreground">{t("org.consulting.empty")}</p>
            ) : (
              requests.slice(0, 2).map((r) => <ConsultingCard key={r.id} request={r} />)
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
