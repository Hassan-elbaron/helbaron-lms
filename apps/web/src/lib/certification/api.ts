import { api } from "@/lib/api/client";

/**
 * Public certificate verification result (GET /api/v1/certificates/verify/{code}).
 * Issuance facts only — no ids or storage paths are exposed by the backend.
 */
export type CertificateVerification = {
  valid: boolean;
  /** Certificate status enum value: "issued" | "revoked". */
  status: string;
  number: string;
  holder_name: string | null;
  course_title: string | null;
  issued_at: string | null;
  revoked_at: string | null;
};

/** Public, unauthenticated verification lookup (throttled server-side per IP). */
export const verifyCertificate = (code: string) =>
  api.data<CertificateVerification>(`certificates/verify/${encodeURIComponent(code)}`, { auth: false });
