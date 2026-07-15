"use client";

import { QueryClientProvider } from "@tanstack/react-query";
import { ThemeProvider } from "next-themes";
import { useState, type ReactNode } from "react";
import { createQueryClient } from "@/lib/api/query-client";
import { AuthProvider } from "@/lib/auth/auth-context";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { BrandingProvider } from "@/lib/branding/context";
import { FeatureFlagsProvider } from "@/lib/flags/context";
import { Toaster } from "@/components/ui/toast";
import type { Locale } from "@/lib/i18n/config";
import type { Branding } from "@/lib/branding/api";
import type { FeatureFlags } from "@/lib/flags/api";

/** Single client provider tree mounted at the root. Order: theme → branding → flags → i18n → query → auth. */
export function Providers({
  children,
  initialLocale,
  branding,
  flags,
}: {
  children: ReactNode;
  initialLocale?: Locale;
  branding?: Branding;
  flags?: FeatureFlags;
}) {
  const [queryClient] = useState(() => createQueryClient());

  return (
    <ThemeProvider attribute="class" defaultTheme="system" enableSystem disableTransitionOnChange>
      <BrandingProvider branding={branding}>
        <FeatureFlagsProvider flags={flags}>
          <I18nProvider initialLocale={initialLocale}>
            <QueryClientProvider client={queryClient}>
              <AuthProvider>
                {children}
                <Toaster />
              </AuthProvider>
            </QueryClientProvider>
          </I18nProvider>
        </FeatureFlagsProvider>
      </BrandingProvider>
    </ThemeProvider>
  );
}
