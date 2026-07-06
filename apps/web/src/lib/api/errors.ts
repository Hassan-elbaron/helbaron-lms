import type { FieldValues, Path, UseFormSetError } from "react-hook-form";
import { ApiRequestError } from "./client";

/**
 * Maps the standard error envelope's validation details
 * (`{ error: { code: "VALIDATION_ERROR", details: { fields: { email: ["…"] } } } }`)
 * onto React Hook Form field errors. Returns true if any field error was applied.
 */
export function applyApiFieldErrors<T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>,
): boolean {
  if (!(error instanceof ApiRequestError) || !error.details) return false;

  const fields = (error.details as { fields?: Record<string, string[] | string> }).fields;
  if (!fields || typeof fields !== "object") return false;

  let applied = false;
  for (const [name, messages] of Object.entries(fields)) {
    const message = Array.isArray(messages) ? messages[0] : String(messages);
    setError(name as Path<T>, { type: "server", message });
    applied = true;
  }
  return applied;
}

/** Human-readable message for any thrown error, falling back to a translated default. */
export function errorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiRequestError) return error.message || fallback;
  if (error instanceof Error) return error.message || fallback;
  return fallback;
}

/** True when the backend signalled that MFA is required for this login. */
export function isMfaRequired(error: unknown): boolean {
  return error instanceof ApiRequestError && error.code === "AUTH_MFA_REQUIRED";
}
