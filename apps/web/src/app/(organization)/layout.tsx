"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { organizationNav } from "@/config/nav";

export default function OrganizationLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth roles={["org_manager", "admin", "super_admin"]}>
      <AppShell nav={organizationNav} location="organization-sidebar">{children}</AppShell>
    </RequireAuth>
  );
}