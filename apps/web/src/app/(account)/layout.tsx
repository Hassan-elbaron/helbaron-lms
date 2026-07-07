"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { accountNav } from "@/config/nav";

export default function AccountLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <AppShell nav={accountNav}>{children}</AppShell>
    </RequireAuth>
  );
}