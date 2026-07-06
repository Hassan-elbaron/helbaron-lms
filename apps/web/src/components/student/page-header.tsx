import type { ReactNode } from "react";
import {
  LayoutDashboard, GraduationCap, PlayCircle, Award, Bell, User, Building2, Building,
  Headset, Contact, Users, BarChart3, FileText, LayoutGrid, ShoppingCart,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";

const ICONS: Record<string, LucideIcon> = {
  LayoutDashboard, GraduationCap, PlayCircle, Award, Bell, User, Building2, Building,
  Headset, Contact, Users, BarChart3, FileText, LayoutGrid, ShoppingCart,
};

/** Elegant page header band: soft gradient panel, serif title, copper eyebrow + icon tile. */
export function PageHeader({
  title,
  subtitle,
  action,
  eyebrow,
  icon,
}: {
  title: string;
  subtitle?: string;
  action?: ReactNode;
  eyebrow?: string;
  icon?: string;
}) {
  const Icon = icon ? ICONS[icon] : undefined;
  return (
    <div className="relative mb-8 overflow-hidden rounded-3xl border bg-[radial-gradient(130%_160%_at_100%_0%,oklch(0.985_0.012_88)_0%,var(--card)_60%)] p-6 sm:p-7">
      <div className="pointer-events-none absolute -end-10 -top-14 -z-0 size-52 rounded-full bg-primary/[0.06] blur-2xl animate-blob" aria-hidden />
      <div className="relative flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          {Icon ? (
            <span className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-[oklch(0.30_0.04_190)] text-primary-foreground shadow-lg shadow-primary/20 animate-float-slow">
              <Icon className="size-7" aria-hidden />
            </span>
          ) : null}
          <div className="space-y-1">
            {eyebrow ? (
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-copper">{eyebrow}</p>
            ) : null}
            <h1 className="font-serif text-2xl font-semibold tracking-tight sm:text-[1.9rem]">{title}</h1>
            {subtitle ? <p className="text-sm text-muted-foreground">{subtitle}</p> : null}
          </div>
        </div>
        {action}
      </div>
    </div>
  );
}
