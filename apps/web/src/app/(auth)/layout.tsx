import type { ReactNode } from "react";
import { RequireGuest } from "@/lib/auth/guards";

/** Centered card shell for sign-in / register. Guests only. */
export default function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <RequireGuest redirectTo="/">
      <div className="flex min-h-dvh items-center justify-center bg-muted/30 p-4">
        <div className="w-full max-w-md">{children}</div>
      </div>
    </RequireGuest>
  );
}
