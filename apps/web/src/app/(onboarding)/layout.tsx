import type { ReactNode } from "react";

/** Centered shell for post-credential steps (verify email, MFA). These require a token; the
 * pages themselves prompt sign-in if none is present, so no RequireGuest/RequireAuth here. */
export default function OnboardingLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-dvh items-center justify-center bg-muted/30 p-4">
      <div className="w-full max-w-md">{children}</div>
    </div>
  );
}
