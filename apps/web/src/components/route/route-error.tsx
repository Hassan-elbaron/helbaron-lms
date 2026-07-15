"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { Button } from "@/components/ui/button";

export default function RouteError({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  const { t } = useI18n();
  return (
    <div
      role="alert"
      className="flex min-h-[40vh] w-full flex-col items-center justify-center gap-4 p-8 text-center"
    >
      <p className="font-medium">{t("common.routeError.title")}</p>
      <p className="max-w-md text-sm text-muted-foreground">
        {error?.message || t("common.routeError.description")}
      </p>
      <Button onClick={reset}>{t("common.retry")}</Button>
    </div>
  );
}
