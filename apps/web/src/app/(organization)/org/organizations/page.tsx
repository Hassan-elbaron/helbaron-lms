"use client";

import { useState } from "react";
import Link from "next/link";
import { Building } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useOrganizations } from "@/lib/org/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Pagination } from "@/components/ui/pagination";

export default function OrganizationsPage() {
  const { t } = useI18n();
  const [page, setPage] = useState(1);
  const query = useOrganizations(page);

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="ACCOUNTS" icon="Building" title={t("org.list.title")} subtitle={t("org.list.subtitle")} />

      <QueryState
        query={query}
        isEmpty={(d) => d.data.length === 0}
        empty={<EmptyState title={t("org.list.empty")} icon={<Building className="size-6" />} />}
      >
        {(data) => (
          <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              {data.data.map((o) => (
                <Card key={o.id}>
                  <CardContent className="space-y-3 p-5">
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <p className="truncate font-semibold">{o.name}</p>
                        <p className="truncate text-xs text-muted-foreground">{o.slug}</p>
                      </div>
                      <Badge variant="outline">{o.status}</Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">
                      {o.members_count ?? 0} {t("org.list.membersCount")}
                    </p>
                    <Button asChild size="sm" variant="outline" className="w-full">
                      <Link href={`/org/organizations/${o.id}`}>{t("org.list.view")}</Link>
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
            {data.meta.last_page > 1 ? (
              <Pagination page={page} lastPage={data.meta.last_page} onPageChange={setPage} />
            ) : null}
          </div>
        )}
      </QueryState>
    </div>
  );
}
