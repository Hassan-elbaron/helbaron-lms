"use client";

import type { ReactNode } from "react";
import { usePathname } from "next/navigation";

/** Re-mounts on route change (keyed by pathname) so the `.page-enter` animation replays. */
export function PageTransition({ children, className }: { children: ReactNode; className?: string }) {
  const pathname = usePathname();
  return (
    <div key={pathname} className={`page-enter ${className ?? ""}`}>
      {children}
    </div>
  );
}
