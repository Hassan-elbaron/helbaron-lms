"use client";

import { useRouter } from "next/navigation";
import { useEffect, type ReactNode } from "react";
import { useAuth } from "./auth-context";
import { PageLoading } from "@/components/states/loading-state";

/** Requires an authenticated session; redirects guests to the sign-in route. */
export function RequireAuth({ children, redirectTo = "/login", roles }: { children: ReactNode; redirectTo?: string; roles?: string[] }) {
  const { status, user } = useAuth();
  const router = useRouter();

  const authorized = status === "authenticated" && (!roles || roles.some((r) => user?.roles.includes(r)));

  useEffect(() => {
    if (status === "guest") router.replace(redirectTo);
  }, [status, router, redirectTo]);

  if (status === "loading") return <PageLoading />;
  if (status === "guest") return null;
  if (!authorized) return null;

  return <>{children}</>;
}

/** Requires a guest session; redirects authenticated users away (e.g. from /login). */
export function RequireGuest({ children, redirectTo = "/dashboard" }: { children: ReactNode; redirectTo?: string }) {
  const { status } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (status === "authenticated") router.replace(redirectTo);
  }, [status, router, redirectTo]);

  if (status === "loading") return <PageLoading />;
  if (status === "authenticated") return null;

  return <>{children}</>;
}
