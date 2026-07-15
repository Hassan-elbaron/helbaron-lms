"use client";

import { useContext } from "react";
import { FeatureFlagsContext } from "@/lib/flags/context";
import type { FeatureFlags } from "@/lib/flags/api";

/** The resolved flag map for the current visitor. */
export function useFeatureFlags(): FeatureFlags {
  return useContext(FeatureFlagsContext);
}

/**
 * Whether a single feature is enabled. DEFAULT-ENABLED: an unknown/undefined key resolves to `true`
 * so a feature is never hidden by a missing flag. Pass a known key to gate a UI surface.
 */
export function useFeatureFlag(key: string): boolean {
  return useFeatureFlags()[key] ?? true;
}
