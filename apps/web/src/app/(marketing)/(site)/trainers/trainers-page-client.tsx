"use client";

import { Users } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useTrainers } from "@/lib/catalog/hooks";
import { PageHero } from "@/components/marketing/page-hero";
import { QueryState } from "@/components/student/query-state";
import { TrainerCard } from "@/components/catalog/trainer-card";
import { EmptyState } from "@/components/states/empty-state";

export function TrainersPageClient() {
  const { t } = useI18n();
  const query = useTrainers();

  return (
    <div>
      <PageHero page="trainers" />
      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<Users className="size-8" />} title={t("catalog.trainers.empty")} />}
      >
        {(trainers) => (
          <div className="stagger-in grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {trainers.map((tr) => <TrainerCard key={tr.id} trainer={tr} />)}
          </div>
        )}
      </QueryState>
    </div>
  );
}
