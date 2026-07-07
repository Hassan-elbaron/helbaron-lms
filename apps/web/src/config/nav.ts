import type { LucideIcon } from "lucide-react";
import {
  LayoutDashboard, GraduationCap, Award, Bell, User, Settings, Building2, Building, Headset,
  Contact, Users, BarChart3, FileText, LayoutGrid, PlayCircle, ShoppingCart, FileSignature,
  Presentation, BookOpen, CalendarClock, Wallet,
} from "lucide-react";

/** labelKey is a dot-path into the i18n dictionary (resolved via useI18n().t). */
export type NavItem = { labelKey: string; href: string; icon: LucideIcon };

export const learningNav: NavItem[] = [
  { labelKey: "nav.dashboard", href: "/dashboard", icon: LayoutDashboard },
  { labelKey: "nav.myLearning", href: "/my-learning", icon: GraduationCap },
  { labelKey: "nav.continueLearning", href: "/continue-learning", icon: PlayCircle },
  { labelKey: "nav.certificates", href: "/certificates", icon: Award },
];

export const accountNav: NavItem[] = [
  { labelKey: "nav.profile", href: "/account/profile", icon: User },
  { labelKey: "nav.notifications", href: "/account/notifications", icon: Bell },
  { labelKey: "nav.settings", href: "/account/settings", icon: Settings },
];

export const commerceNav: NavItem[] = [
  { labelKey: "nav.orders", href: "/orders", icon: ShoppingCart },
  { labelKey: "nav.contracts", href: "/contracts", icon: FileSignature },
];

export const instructorNav: NavItem[] = [
  { labelKey: "nav.teach", href: "/teach", icon: Presentation },
  { labelKey: "nav.courses", href: "/teach/courses", icon: BookOpen },
  { labelKey: "nav.sessions", href: "/teach/sessions", icon: CalendarClock },
  { labelKey: "nav.students", href: "/teach/students", icon: Users },
  { labelKey: "nav.earnings", href: "/teach/earnings", icon: Wallet },
];

export const organizationNav: NavItem[] = [
  { labelKey: "nav.organization", href: "/org", icon: Building2 },
  { labelKey: "nav.organizations", href: "/org/organizations", icon: Building },
  { labelKey: "nav.consulting", href: "/org/consulting", icon: Headset },
  { labelKey: "nav.settings", href: "/account/settings", icon: Settings },
];

export const crmNav: NavItem[] = [
  { labelKey: "nav.crm", href: "/crm", icon: LayoutDashboard },
  { labelKey: "nav.leads", href: "/crm/leads", icon: Contact },
  { labelKey: "nav.consulting", href: "/crm/consulting", icon: Headset },
  { labelKey: "nav.accounts", href: "/crm/accounts", icon: Users },
];

export const analyticsNav: NavItem[] = [
  { labelKey: "nav.analytics", href: "/analytics", icon: BarChart3 },
  { labelKey: "nav.reports", href: "/reports", icon: FileText },
  { labelKey: "nav.dashboards", href: "/dashboards", icon: LayoutGrid },
];