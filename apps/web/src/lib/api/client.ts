import { siteConfig } from "@/config/site";
import type { ApiError, ApiSuccess } from "@/types/api";

const TOKEN_KEY = "helbaron.token";

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string | null): void {
  if (typeof window === "undefined") return;
  if (token) window.localStorage.setItem(TOKEN_KEY, token);
  else window.localStorage.removeItem(TOKEN_KEY);
}

/** Error thrown for any non-2xx response, carrying the standard error envelope. */
export class ApiRequestError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    message: string,
    public readonly details?: Record<string, unknown>,
    public readonly correlationId?: string,
  ) {
    super(message);
    this.name = "ApiRequestError";
  }
}

type RequestOptions = Omit<RequestInit, "body"> & { body?: unknown; auth?: boolean };

/**
 * Typed fetch wrapper around the REST API (/api/v1). Attaches the Sanctum bearer token,
 * unwraps the standard envelope, and throws ApiRequestError on failure.
 */
export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { body, auth = true, headers, ...rest } = options;
  const token = auth ? getToken() : null;

  const res = await fetch(`${siteConfig.apiBaseUrl}/${path.replace(/^\//, "")}`, {
    ...rest,
    headers: {
      Accept: "application/json",
      ...(body !== undefined ? { "Content-Type": "application/json" } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...headers,
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const json = res.status === 204 ? null : await res.json().catch(() => null);

  if (!res.ok) {
    const err = (json as ApiError | null)?.error;
    throw new ApiRequestError(
      res.status,
      err?.code ?? "HTTP_ERROR",
      err?.message ?? res.statusText,
      err?.details,
      err?.correlation_id,
    );
  }

  return json as T;
}

/** Convenience helpers returning the unwrapped `data` for success envelopes. */
export const api = {
  get: <T>(path: string, opts?: RequestOptions) => apiFetch<T>(path, { ...opts, method: "GET" }),
  post: <T>(path: string, body?: unknown, opts?: RequestOptions) =>
    apiFetch<T>(path, { ...opts, method: "POST", body }),
  put: <T>(path: string, body?: unknown, opts?: RequestOptions) =>
    apiFetch<T>(path, { ...opts, method: "PUT", body }),
  del: <T>(path: string, opts?: RequestOptions) => apiFetch<T>(path, { ...opts, method: "DELETE" }),
  /** Unwrap `{ data }` for endpoints using the success envelope. */
  data: async <T>(path: string, opts?: RequestOptions) =>
    (await apiFetch<ApiSuccess<T>>(path, { ...opts, method: opts?.method ?? "GET" })).data,
};
