"use client";

import { useState, type ReactNode } from "react";
import type { NavItem } from "@/config/nav";
import { useMediaQuery } from "@/hooks/use-media-query";
import { Drawer, DrawerContent } from "@/components/ui/drawer";
import { Sidebar } from "./sidebar";
import { PageTransition } from "./page-transition";
import { Topbar } from "./topbar";

export interface AppShellProps {
  nav: NavItem[];
  children: ReactNode;
  brand?: string;
}

/** Responsive dashboard shell: persistent sidebar on desktop, drawer nav on mobile. */
export function AppShell({ nav, children, brand }: AppShellProps) {
  const isDesktop = useMediaQuery("(min-width: 768px)");
  const [open, setOpen] = useState(false);

  return (
    <div className="flex h-dvh w-full overflow-hidden">
      {isDesktop ? <Sidebar items={nav} brand={brand} /> : null}
      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar onMenuClick={isDesktop ? undefined : () => setOpen(true)} />
        <main className="flex-1 overflow-auto p-4 md:p-6"><PageTransition>{children}</PageTransition></main>
      </div>
      {!isDesktop ? (
        <Drawer open={open} onOpenChange={setOpen}>
          <DrawerContent className="h-[85dvh] p-0">
            <Sidebar items={nav} brand={brand} className="w-full border-e-0" />
          </DrawerContent>
        </Drawer>
      ) : null}
    </div>
  );
}
