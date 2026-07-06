"use client";

import { ShoppingCart } from "lucide-react";
import type { Product } from "@/lib/commerce/api";
import { formatMoney } from "@/lib/format";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export function ProductCard({ product, onAdd, adding }: { product: Product; onAdd: (id: string) => void; adding?: boolean }) {
  const { t, locale } = useI18n();
  const price = product.prices[0];

  return (
    <Card className="flex h-full flex-col">
      <CardContent className="flex flex-1 flex-col gap-3 p-5">
        <div className="flex items-start justify-between gap-2">
          <h3 className="font-semibold leading-tight">{product.title}</h3>
          {price?.on_sale ? <Badge variant="warning">{t("commerce.products.sale")}</Badge> : null}
        </div>
        {product.description ? <p className="line-clamp-3 text-sm text-muted-foreground">{product.description}</p> : null}
        <div className="mt-auto space-y-3">
          {price ? (
            <div className="flex items-baseline gap-2">
              <span className="text-xl font-bold">{formatMoney(price.effective_minor, price.currency, locale)}</span>
              {price.on_sale ? (
                <span className="text-sm text-muted-foreground line-through">{formatMoney(price.amount_minor, price.currency, locale)}</span>
              ) : null}
            </div>
          ) : null}
          <Button className="w-full" loading={adding} onClick={() => onAdd(product.id)}>
            <ShoppingCart className="size-4" aria-hidden /> {t("commerce.products.addToCart")}
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
