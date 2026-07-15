import { api } from "@/lib/api/client";

/**
 * Public feature-flag client. Flags gate PRESENTATION only and are DEFAULT-ENABLED: a missing or
 * unreachable flag resolves to `true`, so a flag outage can never hide a working feature. The
 * resolved map is fetched (auth-optional) from GET /api/v1/feature-flags — an authenticated request
 * gets the map resolved for that user, a guest gets the anonymous map.
 */

export type FeatureFlags = Record<string, boolean>;

/**
 * An "all enabled" flag map: a Proxy that returns `true` for ANY key. Used as the fallback when the
 * API is unreachable so the UI is never hidden by a flag failure (default-on semantics).
 */
export function allEnabled(): FeatureFlags {
  return new Proxy({} as FeatureFlags, {
    get: (_target, prop) => (typeof prop === "string" ? true : undefined),
    has: () => true,
  });
}

type FlagsPayload = { flags: FeatureFlags };

/**
 * Fetch the resolved flag map. Returns the all-enabled fallback on ANY failure (or a malformed
 * payload) so nothing is ever hidden by a missing/unreachable flag.
 */
export async function getFeatureFlags(): Promise<FeatureFlags> {
  try {
    const data = await api.data<FlagsPayload>("feature-flags", { auth: false, cache: "no-store" });
    return data && typeof data.flags === "object" && data.flags !== null ? data.flags : allEnabled();
  } catch {
    return allEnabled();
  }
}
