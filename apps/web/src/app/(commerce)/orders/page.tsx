"use client";

import { useState } from "react";
import { Receipt } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useOrders } from "@/lib/commerce/hooks";
import { RequireAuth } from "@/lib/auth/guards";
import { PageHero } from "@/components/marketing/page-hero";
import { QueryState } from "@/components/student/query-state";
import { OrderCard } from "@/components/commerce/order-card";
import { Pagination } from "@/components/ui/pagination";
import { EmptyState } from "@/components/states/empty-state";

export default function OrdersPage() {
  const { t } = useI18n();
  const [page, setPage] = useState(1);
  const query = useOrders(page);
  return (
    <RequireAuth>
      <PageHero page="orders" />
      <QueryState query={query} isEmpty={(d) => d.data.length === 0} empty={<EmptyState icon={<Receipt className="size-8" />} title={t("commerce.orders.empty")} />}>
        {(data) => (
          <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              {data.data.map((o) => <OrderCard key={o.id} order={o} />)}
            </div>
            <Pagination page={data.meta.current_page} lastPage={data.meta.last_page} onPageChange={setPage} />
          </div>
        )}
      </QueryState>
    </RequireAuth>
  );
}
