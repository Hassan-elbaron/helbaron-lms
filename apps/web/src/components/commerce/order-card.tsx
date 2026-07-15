"use client";

import type { Order } from "@/lib/commerce/api";
import { formatMoney } from "@/lib/format";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

const statusVariant: Record<string, "success" | "warning" | "destructive" | "secondary"> = {
  fulfilled: "success",
  paid: "success",
  pending: "warning",
  failed: "destructive",
  refunded: "secondary",
};

export function OrderCard({ order }: { order: Order }) {
  const { t, locale } = useI18n();
  return (
    <Card className="card-hover hover:border-primary/30 hover:elevation-3">
      <CardContent className="space-y-3 p-5">
        <div className="flex items-center justify-between gap-2">
          <div>
            <p className="font-semibold">
              {t("commerce.orders.order")} · {formatMoney(order.total_minor, order.currency, locale)}
            </p>
            {order.placed_at ? (
              <p className="text-xs text-muted-foreground">{t("commerce.orders.placed")}: {new Date(order.placed_at).toLocaleDateString()}</p>
            ) : null}
          </div>
          <Badge variant={statusVariant[order.status] ?? "secondary"}>{order.status}</Badge>
        </div>
        {order.items?.length ? (
          <ul className="space-y-1 text-sm text-muted-foreground">
            {order.items.map((it, i) => (
              <li key={i} className="flex justify-between gap-2">
                <span className="truncate">{it.title}</span>
                <span className="tabular-nums">{formatMoney(it.unit_amount_minor, order.currency, locale)}</span>
              </li>
            ))}
          </ul>
        ) : null}
        {order.invoice ? (
          <p className="text-xs text-muted-foreground">{t("commerce.orders.invoice")}: {order.invoice.number} ({order.invoice.status})</p>
        ) : null}
      </CardContent>
    </Card>
  );
}
