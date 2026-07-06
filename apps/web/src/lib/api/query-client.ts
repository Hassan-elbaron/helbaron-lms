import { QueryClient } from "@tanstack/react-query";
import { ApiRequestError } from "./client";

/** Shared TanStack Query configuration. Does not retry on auth/validation errors. */
export function createQueryClient(): QueryClient {
  return new QueryClient({
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
