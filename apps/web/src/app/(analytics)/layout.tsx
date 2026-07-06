"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { analyticsNav } from "@/config/nav";

/** Authenticated analytics workspace (admin/super_admin gate the UI; API enforces analytics permissions). */
export default function AnalyticsLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth roles={["admin", "super_admin"]}>
      <AppShell nav={analyticsNav}>{children}</AppShell>
    </RequireAuth>
  );
}
