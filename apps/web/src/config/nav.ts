import type { LucideIcon } from "lucide-react";
import { LayoutDashboard, GraduationCap, Award, Bell, User, Building2, Building, Headset, Settings, Contact, Users, BarChart3, FileText, LayoutGrid } from "lucide-react";

/** labelKey is a dot-path into the i18n dictionary (resolved via useI18n().t). */
export type NavItem = { labelKey: string; href: string; icon: LucideIcon };

export const dashboardNav: NavItem[] = [
  { labelKey: "nav.dashboard", href: "/dashboard", icon: LayoutDashboard },
  { labelKey: "nav.settings", href: "/settings", icon: Settings },
];

export const studentNav: NavItem[] = [
  { labelKey: "nav.dashboard", href: "/dashboard", icon: LayoutDashboard },
  { labelKey: "nav.myLearning", href: "/my-learning", icon: GraduationCap },
  { labelKey: "nav.certificates", href: "/certificates", icon: Award },
  { labelKey: "nav.notifications", href: "/notifications", icon: Bell },
  { labelKey: "nav.profile", href: "/profile", icon: User },
];

export const organizationNav: NavItem[] = [
  { labelKey: "nav.organization", href: "/org", icon: Building2 },
  { labelKey: "nav.organizations", href: "/org/organizations", icon: Building },
  { labelKey: "nav.consulting", href: "/org/consulting", icon: Headset },
  { labelKey: "nav.settings", href: "/settings", icon: Settings },
];

export const crmNav: NavItem[] = [
  { labelKey: "nav.crm", href: "/crm", icon: LayoutDashboard },
  { labelKey: "nav.leads", href: "/crm/leads", icon: Contact },
  { labelKey: "nav.consulting", href: "/crm/consulting", icon: Headset },
  { labelKey: "nav.organizations", href: "/crm/organizations", icon: Users },
];

export const analyticsNav: NavItem[] = [
  { labelKey: "nav.analytics", href: "/analytics", icon: BarChart3 },
  { labelKey: "nav.reports", href: "/reports", icon: FileText },
  { labelKey: "nav.dashboards", href: "/dashboards", icon: LayoutGrid },
];
