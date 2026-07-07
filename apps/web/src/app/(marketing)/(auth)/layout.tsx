import type { ReactNode } from "react";
import { RequireGuest } from "@/lib/auth/guards";

export default function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <RequireGuest redirectTo="/">
      <div className="flex min-h-dvh items-center justify-center p-4">
        <div className="w-full max-w-md">{children}</div>
      </div>
    </RequireGuest>
  );
}