import { api } from "@/lib/api/client";
import type { Locale } from "@/config/theme";

/**
 * Public Navigation client. Menus are managed in the admin Navigation Builder and exposed per
 * location (GET /api/v1/navigation/{location}). This module fetches the enabled, ordered, tree of
 * items and NEVER lets nav break: on any failure — or an empty menu — it returns null so the caller
 * renders its built-in hardcoded config (nav.ts / brandTheme) instead. All labels are bilingual.
 */

export type NavLocalized = { en: string; ar: string };

export type NavAuth = "any" | "guest" | "authenticated";

export type NavVisibility = {
  roles: string[] | null;
  auth: NavAuth;
  locales: string[] | null;
  feature_flag: string | null;
};

export type NavNode = {
  id: string;
  label: NavLocalized;
  url: string;
  url_type: "internal" | "external";
  icon: string | null;
  open_new_tab: boolean;
  target: "_blank" | "_self";
  rel: string | null;
  badge: NavLocalized | null;
  description: NavLocalized | null;
  image: string | null;
  visibility: NavVisibility;
  children: NavNode[];
};

export type NavPayload = { location: string; items: NavNode[] };

/** Location slugs — must match App\Platform\Navigation\Enums\MenuLocation. */
export type MenuLocation =
  | "public-header"
  | "public-footer"
  | "learner-sidebar"
  | "instructor-sidebar"
  | "organization-sidebar"
  | "admin-quick-links"
  | "mobile-menu"
  | "utility-menu"
  | "mega-menu"
  | "legal-menu";

/**
 * Fetch the item tree for a location. Returns null on ANY failure OR when the menu is empty, so
 * consumers fall back to their hardcoded config — navigation never disappears.
 */
export async function getNavigation(location: MenuLocation): Promise<NavNode[] | null> {
  try {
    const data = await api.data<NavPayload>(`navigation/${location}`, { auth: false });
    return data.items && data.items.length > 0 ? data.items : null;
  } catch {
    return null;
  }
}

/** The current visitor context used to filter items client-side. */
export type NavResolveContext = {
  locale: Locale;
  authed: boolean;
  roles: string[];
  flags?: Record<string, boolean>;
};

/** Whether a single node is visible to the current visitor (auth / locale / roles / feature flag). */
export function isNavNodeVisible(node: NavNode, ctx: NavResolveContext): boolean {
  const v = node.visibility;

  if (v.auth === "guest" && ctx.authed) return false;
  if (v.auth === "authenticated" && !ctx.authed) return false;

  if (v.locales && v.locales.length > 0 && !v.locales.includes(ctx.locale)) return false;

  if (v.roles && v.roles.length > 0 && !v.roles.some((r) => ctx.roles.includes(r))) return false;

  // A flagged item is only shown when the flag is explicitly on (no flag source ⇒ hidden). Seeded
  // and migrated items carry no flag, so this never affects the hardcoded-parity fallback.
  if (v.feature_flag && !(ctx.flags?.[v.feature_flag] ?? false)) return false;

  return true;
}

/** Recursively filter a tree to the nodes visible for the current visitor. */
export function resolveNav(nodes: NavNode[], ctx: NavResolveContext): NavNode[] {
  return nodes
    .filter((n) => isNavNodeVisible(n, ctx))
    .map((n) => ({ ...n, children: resolveNav(n.children ?? [], ctx) }));
}

/** rel that guarantees noopener/noreferrer for external / new-tab links (defense-in-depth). */
export function safeRel(node: NavNode): string | undefined {
  if (node.target === "_blank" || node.url_type === "external") {
    const tokens = new Set((node.rel ?? "").split(/\s+/).filter(Boolean));
    tokens.add("noopener");
    tokens.add("noreferrer");
    return Array.from(tokens).join(" ");
  }
  return node.rel ?? undefined;
}
