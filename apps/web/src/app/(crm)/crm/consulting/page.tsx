"use client";

import { Headset } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useConsulting } from "@/lib/org/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { ConsultingCard } from "@/components/org/consulting-card";

export default function CrmConsultingPage() {
  const { t } = useI18n();
  const query = useConsulting();

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="CONSULTING" icon="Headset" title={t("crm.consulting.title")} subtitle={t("crm.consulting.subtitle")} />
      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<Headset className="size-8" />} title={t("org.consulting.empty")} />}
      >
        {(list) => (
          <div className="grid gap-3 sm:grid-cols-2">
            {list.map((r) => (
              <ConsultingCard key={r.id} request={r} />
            ))}
          </div>
        )}
      </QueryState>
    </div>
  );
}
