"use client";

import { CheckCircle2 } from "lucide-react";
import type { ReactNode } from "react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { cn } from "@/lib/utils";

export interface SuccessStateProps {
  title?: string;
  message?: string;
  action?: ReactNode;
  className?: string;
}

/** Canonical success surface. Mirrors EmptyState/ErrorState; polite live region for a11y. */
export function SuccessState({ title, message, action, className }: SuccessStateProps) {
  const { t } = useI18n();
  return (
    <div
      className={cn("flex flex-col items-center justify-center gap-3 p-8 text-center", className)}
      role="status"
      aria-live="polite"
    >
      <CheckCircle2 className="size-8 text-success" aria-hidden />
      <div className="space-y-1">
        <p className="font-medium">{title ?? t("common.success")}</p>
        {message ? <p className="text-sm text-muted-foreground">{message}</p> : null}
      </div>
      {action}
    </div>
  );
}
