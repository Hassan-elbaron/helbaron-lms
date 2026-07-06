"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import type { NavItem } from "@/config/nav";
import { useI18n } from "@/lib/i18n/i18n-context";
import { cn } from "@/lib/utils";

export interface SidebarProps {
  items: NavItem[];
  brand?: string;
  className?: string;
}

/** Direction-agnostic vertical nav. Icons + i18n labels; active state by path prefix. */
export function Sidebar({ items, brand = "HElbaron", className }: SidebarProps) {
  const pathname = usePathname();
  const { t } = useI18n();

  return (
    <aside className={cn("flex h-full w-64 flex-col border-e bg-card", className)}>
      <div className="flex h-16 items-center px-6 text-lg font-semibold">{brand}</div>
      <nav className="flex-1 space-y-1 px-3 py-2" aria-label="Primary">
        {items.map((item) => {
          const active = pathname === item.href || pathname.startsWith(`${item.href}/`);
          const Icon = item.icon;
          return (
            <Link
              key={item.href}
              href={item.href}
              aria-current={active ? "page" : undefined}
              className={cn(
                "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors",
                active ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
              )}
            >
              {Icon ? <Icon className="size-4 shrink-0" aria-hidden /> : null}
              <span>{t(item.labelKey)}</span>
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}
