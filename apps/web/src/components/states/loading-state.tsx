"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { cn } from "@/lib/utils";
import { Spinner } from "@/components/ui/spinner";

export function LoadingState({ className, label }: { className?: string; label?: string }) {
  const { t } = useI18n();
  return (
    <div className={cn("flex flex-col items-center justify-center gap-3 p-8 text-muted-foreground", className)} role="status" aria-live="polite">
      <Spinner size="md" aria-hidden />
      <span className="text-sm">{label ?? t("common.loading")}</span>
    </div>
  );
}

/** Full-viewport loader used by route guards while auth resolves. */
export function PageLoading() {
  return (
    <div className="flex min-h-[60vh] w-full items-center justify-center">
      <LoadingState />
    </div>
  );
}
