"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import { api, setToken, getToken, type ApiRequestError } from "@/lib/api/client";
import type { ApiSuccess, AuthUser } from "@/types/api";

type AuthState = {
  user: AuthUser | null;
  status: "loading" | "authenticated" | "guest";
  login: (email: string, password: string, mfaCode?: string) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

const CACHE_KEY = "helbaron.user";

/** Optimistic cache so reloads don't flash a full-page loader while /profile revalidates. */
function readCachedUser(): AuthUser | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(CACHE_KEY);
    return raw ? (JSON.parse(raw) as AuthUser) : null;
  } catch {
    return null;
  }
}
function writeCachedUser(user: AuthUser | null): void {
  if (typeof window === "undefined") return;
  try {
    if (user) window.localStorage.setItem(CACHE_KEY, JSON.stringify(user));
    else window.localStorage.removeItem(CACHE_KEY);
  } catch {
    /* ignore quota/private-mode errors */
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [status, setStatus] = useState<AuthState["status"]>("loading");

  const refresh = useCallback(async () => {
    if (!getToken()) {
      writeCachedUser(null);
      setStatus("guest");
      setUser(null);
      return;
    }
    try {
      const me = await api.data<AuthUser>("profile");
      setUser(me);
      writeCachedUser(me);
      setStatus("authenticated");
    } catch {
      setToken(null);
      writeCachedUser(null);
      setUser(null);
      setStatus("guest");
    }
  }, []);

  useEffect(() => {
    // Hydrate optimistically from cache (avoids the full-page loading flash), then revalidate.
    if (getToken()) {
      const cached = readCachedUser();
      if (cached) {
        setUser(cached);
        setStatus("authenticated");
      }
    }
    void refresh();
  }, [refresh]);

  const login = useCallback(async (email: string, password: string, mfaCode?: string) => {
    const res = await api.post<ApiSuccess<{ user: AuthUser; token: string }>>(
      "auth/login",
      { email, password, mfa_code: mfaCode, device_name: "web" },
      { auth: false },
    );
    setToken(res.data.token);
    setUser(res.data.user);
    writeCachedUser(res.data.user);
    setStatus("authenticated");
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post("auth/logout");
    } finally {
      setToken(null);
      writeCachedUser(null);
      setUser(null);
      setStatus("guest");
    }
  }, []);

  const value = useMemo<AuthState>(() => ({ user, status, login, logout, refresh }), [user, status, login, logout, refresh]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}

export type { ApiRequestError };
