"use client";

import { FolderTree } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useCategories } from "@/lib/catalog/hooks";
import { PageHero } from "@/components/marketing/page-hero";
import { QueryState } from "@/components/student/query-state";
import { CategoryCard } from "@/components/catalog/category-card";
import { EmptyState } from "@/components/states/empty-state";

export function CategoriesPageClient() {
  const { t } = useI18n();
  const query = useCategories();

  return (
    <div>
      <PageHero page="categories" />
      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<FolderTree className="size-8" />} title={t("catalog.categories.empty")} />}
      >
        {(cats) => (
          <div className="stagger-in grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {cats.map((c) => <CategoryCard key={c.id} category={c} />)}
          </div>
        )}
      </QueryState>
    </div>
  );
}
