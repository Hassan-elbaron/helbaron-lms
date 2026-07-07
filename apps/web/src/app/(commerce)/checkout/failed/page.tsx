"use client";

import Link from "next/link";
import { XCircle } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { RequireAuth } from "@/lib/auth/guards";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

export default function CheckoutFailedPage() {
  const { t } = useI18n();
  return (
    <RequireAuth>
      <div className="mx-auto max-w-lg py-8">
        <Card>
          <CardContent className="flex flex-col items-center gap-4 p-8 text-center">
            <XCircle className="size-12 text-destructive" aria-hidden />
            <h1 className="text-2xl font-bold">{t("commerce.failed.title")}</h1>
            <p className="text-muted-foreground">{t("commerce.failed.body")}</p>
            <Button asChild><Link href="/cart">{t("commerce.failed.retry")}</Link></Button>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
