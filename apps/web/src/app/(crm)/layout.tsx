"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { crmNav } from "@/config/nav";

/** Authenticated CRM workspace (admin/super_admin gate the UI; API enforces crm.* permissions). */
export default function CrmLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth roles={["admin", "super_admin"]}>
      <AppShell nav={crmNav}>{children}</AppShell>
    </RequireAuth>
  );
}
