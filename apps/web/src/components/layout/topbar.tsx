"use client";

import { Menu } from "lucide-react";
import type { ReactNode } from "react";
import { Button } from "@/components/ui/button";
import { LangToggle } from "./lang-toggle";
import { ThemeToggle } from "./theme-toggle";
import { UserMenu } from "./user-menu";

export interface TopbarProps {
  onMenuClick?: () => void;
  start?: ReactNode;
}

export function Topbar({ onMenuClick, start }: TopbarProps) {
  return (
    <header className="flex h-16 items-center justify-between border-b bg-background px-4 md:px-6">
      <div className="flex items-center gap-2">
        {onMenuClick ? (
          <Button variant="ghost" size="icon" className="md:hidden" aria-label="Menu" onClick={onMenuClick}>
            <Menu className="size-5" aria-hidden />
          </Button>
        ) : null}
        {start}
      </div>
      <div className="flex items-center gap-1">
        <LangToggle />
        <ThemeToggle />
        <UserMenu />
      </div>
    </header>
  );
}
