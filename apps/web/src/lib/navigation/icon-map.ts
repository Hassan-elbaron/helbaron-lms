import type { LucideIcon } from "lucide-react";
import {
  LayoutDashboard, GraduationCap, Award, Bell, User, Building2, Building, Headset,
  Contact, Users, BarChart3, FileText, LayoutGrid, PlayCircle, ShoppingCart, FileSignature,
  Presentation, LineChart,
} from "lucide-react";

/**
 * Maps a CMS icon KEY (stored on a nav item, e.g. "LayoutDashboard") to its Lucide component. Only
 * icons already used by the app sidebars are registered; an unknown key resolves to undefined and
 * the item simply renders without an icon (never an error).
 */
const ICONS: Record<string, LucideIcon> = {
  LayoutDashboard, GraduationCap, Award, Bell, User, Building2, Building, Headset,
  Contact, Users, BarChart3, FileText, LayoutGrid, PlayCircle, ShoppingCart, FileSignature,
  Presentation, LineChart,
};

export function navIcon(key: string | null | undefined): LucideIcon | undefined {
  return key ? ICONS[key] : undefined;
}
