"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { dashboardNav } from "@/config/nav";

/** Authenticated admin/instructor workspace. */
export default function DashboardLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <AppShell nav={dashboardNav}>{children}</AppShell>
    </RequireAuth>
  );
}
