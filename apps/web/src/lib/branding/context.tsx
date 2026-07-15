"use client";

import { createContext, useContext, type ReactNode } from "react";
import { defaultBranding, type Branding } from "@/lib/branding/api";

/**
 * Client-side branding context. The server layout fetches branding once and provides it here so
 * client components (landing header/footer, etc.) can white-label brand name, copyright and social
 * links. Always falls back to the built-in defaults, so components render correctly even without a
 * provider (the site is never broken by missing branding).
 */
const BrandingContext = createContext<Branding>(defaultBranding);

export function BrandingProvider({ branding, children }: { branding?: Branding; children: ReactNode }) {
  return <BrandingContext.Provider value={branding ?? defaultBranding}>{children}</BrandingContext.Provider>;
}

export function useBranding(): Branding {
  return useContext(BrandingContext);
}
