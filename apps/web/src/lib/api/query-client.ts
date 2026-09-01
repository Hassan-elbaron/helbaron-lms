import { MutationCache, QueryClient } from "@tanstack/react-query";
import { ApiRequestError } from "./client";
import { recoveryPathFor } from "./errors";

/**
 * Send an unverified caller to the page that fixes it.
 *
 * `RequireVerifiedEmail` gates the ENTIRE api group, so `403 EMAIL_VERIFICATION_REQUIRED` can come
 * back from any authenticated mutation in the app. Only the course purchase panel handled it, which
 * meant every other surface showed a generic "something went wrong" toast with no route out — and
 * the client-side `user.email_verified` flag it checked first is cached, so it goes stale exactly
 * when the server starts refusing.
 *
 * Handled here rather than per-component so a surface added later inherits it for free. A hard
 * navigation is used because this module is not inside the router: it runs for every mutation,
 * including ones fired from outside a React tree.
 */
function routeToRecovery(error: unknown): void {
  if (typeof window === "undefined") return;

  const target = recoveryPathFor(error, window.location.pathname + window.location.search);

  if (target === null || window.location.pathname.startsWith("/verify-email")) return;

  window.location.assign(target);
}

/** Shared TanStack Query configuration. Does not retry on auth/validation errors. */
export function createQueryClient(): QueryClient {
  return new QueryClient({
    mutationCache: new MutationCache({
      onError: (error) => routeToRecovery(error),
    }),
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        retry: (failureCount, error) => {
          if (error instanceof ApiRequestError && [401, 403, 404, 422].includes(error.status)) {
            return false;
          }
          return failureCount < 2;
        },
        refetchOnWindowFocus: false,
      },
      mutations: { retry: false },
    },
  });
}
