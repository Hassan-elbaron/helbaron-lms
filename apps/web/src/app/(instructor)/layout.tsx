"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { instructorNav } from "@/config/nav";

export default function InstructorLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth roles={["instructor", "admin", "super_admin"]}>
      <AppShell nav={instructorNav} location="instructor-sidebar">{children}</AppShell>
    </RequireAuth>
  );
}