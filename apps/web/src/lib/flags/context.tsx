"use client";

import { createContext, type ReactNode } from "react";
import { allEnabled, type FeatureFlags } from "@/lib/flags/api";

/**
 * Client-side feature-flag context. The server layout fetches the resolved map once and provides it
 * here so client components can gate UI. Defaults to the all-enabled map, so components render
 * correctly even without a provider — the UI is never hidden by missing flags (default-on).
 */
export const FeatureFlagsContext = createContext<FeatureFlags>(allEnabled());

export function FeatureFlagsProvider({ flags, children }: { flags?: FeatureFlags; children: ReactNode }) {
  return <FeatureFlagsContext.Provider value={flags ?? allEnabled()}>{children}</FeatureFlagsContext.Provider>;
}
