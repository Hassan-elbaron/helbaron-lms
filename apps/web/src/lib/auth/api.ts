import { api } from "@/lib/api/client";
import type { ApiSuccess, AuthUser } from "@/types/api";
import type { Locale } from "@/lib/i18n/config";

export type RegisterInput = {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
  locale?: Locale;
};

/** POST /auth/register — creates the account and emits the email OTP. Returns the user (no token). */
export function registerUser(input: RegisterInput) {
  return api.post<ApiSuccess<AuthUser>>("auth/register", input, { auth: false });
}

/** POST /auth/forgot-password — always succeeds (no account enumeration). */
export function forgotPassword(email: string) {
  return api.post("auth/forgot-password", { email }, { auth: false });
}

/** POST /auth/reset-password */
export function resetPassword(input: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}) {
  return api.post("auth/reset-password", input, { auth: false });
}

/** POST /auth/verify-email — requires a bearer token (attached automatically). */
export function verifyEmail(code: string) {
  return api.post("auth/verify-email", { code });
}

/** POST /auth/mfa/verify — step-up verification for an authenticated session. */
export function verifyMfa(code: string) {
  return api.post("auth/mfa/verify", { code });
}
