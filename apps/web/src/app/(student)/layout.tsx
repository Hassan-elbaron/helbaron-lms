"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { studentNav } from "@/config/nav";

/** Authenticated learner workspace. */
export default function StudentLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <AppShell nav={studentNav}>{children}</AppShell>
    </RequireAuth>
  );
}
