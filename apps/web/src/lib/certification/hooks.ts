"use client";

import { useQuery } from "@tanstack/react-query";
import { verifyCertificate } from "./api";

/** Verify a certificate by its public code. Disabled until a code is provided; never retried
 *  (a 404 is a meaningful "not found" answer, not a transient failure). */
export const useVerifyCertificate = (code: string) =>
  useQuery({
    queryKey: ["certificate-verify", code],
    queryFn: () => verifyCertificate(code),
    enabled: Boolean(code),
    retry: false,
  });
