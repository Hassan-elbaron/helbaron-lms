"use client";

import type { ReactNode } from "react";
import { AppShell } from "@/components/layout/app-shell";
import { learningNav } from "@/config/nav";

export default function LearningAppLayout({ children }: { children: ReactNode }) {
  return <AppShell nav={learningNav} location="learner-sidebar">{children}</AppShell>;
}