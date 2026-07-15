"use client";

import { WifiOff } from "lucide-react";
import { useEffect, useState } from "react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { cn } from "@/lib/utils";

/** Reactive online/offline status. SSR-safe (assumes online until mounted). */
export function useOnlineStatus(): boolean {
  const [online, setOnline] = useState(true);
  useEffect(() => {
    const update = () => setOnline(navigator.onLine);
    update();
    window.addEventListener("online", update);
    window.addEventListener("offline", update);
    return () => {
      window.removeEventListener("online", update);
      window.removeEventListener("offline", update);
    };
  }, []);
  return online;
}

/** Slim banner shown while the browser reports no connectivity. Token-driven + bilingual. */
export function OfflineBanner({ className, message }: { className?: string; message?: string }) {
  const online = useOnlineStatus();
  const { t } = useI18n();
  if (online) return null;
  return (
    <div
      role="status"
      aria-live="polite"
      className={cn(
        "motion-slide-down flex items-center justify-center gap-2 bg-warning px-4 py-2 text-center text-sm font-medium text-warning-foreground",
        className,
      )}
    >
      <WifiOff className="size-4" aria-hidden />
      <span>{message ?? t("common.offline")}</span>
    </div>
  );
}
