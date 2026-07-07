"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { errorMessage } from "@/lib/api/errors";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useAddToCart, useProducts } from "@/lib/commerce/hooks";
import { PageHero } from "@/components/marketing/page-hero";
import { QueryState } from "@/components/student/query-state";
import { ProductCard } from "@/components/commerce/product-card";
import { Pagination } from "@/components/ui/pagination";
import { EmptyState } from "@/components/states/empty-state";
import { toast } from "@/components/ui/toast";

export default function ProductsPage() {
  const { t } = useI18n();
  const router = useRouter();
  const { status } = useAuth();
  const [page, setPage] = useState(1);
  const query = useProducts(page);
  const add = useAddToCart();

  const onAdd = (id: string) => {
    if (status !== "authenticated") {
      router.push("/login?redirect=/products");
      return;
    }
    add.mutate(
      { product: id },
      {
        onSuccess: () => toast.success(t("commerce.products.added")),
        onError: (e) => toast.error(errorMessage(e, t("common.error"))),
      },
    );
  };

  return (
    <div>
      <PageHero page="products" />
      <QueryState query={query} isEmpty={(d) => d.data.length === 0} empty={<EmptyState title={t("commerce.products.empty")} />}>
        {(data) => (
          <div className="space-y-6">
            <div className="stagger-in grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {data.data.map((p) => (
                <ProductCard key={p.id} product={p} onAdd={onAdd} adding={add.isPending && add.variables?.product === p.id} />
              ))}
            </div>
            <Pagination page={data.meta.current_page} lastPage={data.meta.last_page} onPageChange={setPage} />
          </div>
        )}
      </QueryState>
    </div>
  );
}
