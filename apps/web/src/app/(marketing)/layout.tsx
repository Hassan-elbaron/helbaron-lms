import type { ReactNode } from "react";

/** Full-bleed marketing shell — the landing page renders its own announcement/header/footer. */
export default function MarketingLayout({ children }: { children: ReactNode }) {
  return <div className="flex min-h-dvh flex-col">{children}</div>;
}
