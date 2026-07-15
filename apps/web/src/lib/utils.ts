import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

/** Merge Tailwind classes with conditional logic (shadcn convention). */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Sanitize a post-auth `redirect` target to prevent open redirects (CWE-601).
 * Only same-origin, absolute *internal* paths are allowed — a value must start with a single "/"
 * and must not be protocol-relative ("//host"), a "/\" backslash trick, or contain a scheme.
 * Anything else falls back to `fallback` (default "/").
 */
export function safeRedirect(target: string | null | undefined, fallback = "/"): string {
  if (!target) return fallback;
  // Must be a root-relative path, but not protocol-relative or backslash-escaped.
  if (!target.startsWith("/") || target.startsWith("//") || target.startsWith("/\\")) return fallback;
  // Reject anything that smuggles a scheme or control chars.
  if (/[\x00-\x1f]/.test(target) || /^\/[^/]*:/.test(target)) return fallback;
  return target;
}
