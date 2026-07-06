"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { organizationNav } from "@/config/nav";

/** Authenticated organization/B2B admin workspace. */
export default function OrganizationLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth roles={["admin", "super_admin"]}>
      <AppShell nav={organizationNav}>{children}</AppShell>
    </RequireAuth>
  );
}
