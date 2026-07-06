import type { ReactNode } from "react";
import { AnnouncementBar } from "@/components/landing/announcement-bar";
import { LandingHeader } from "@/components/landing/landing-header";
import { LandingFooter } from "@/components/landing/landing-footer";
import { PageTransition } from "@/components/layout/page-transition";

/** Public catalog shell — shares the exact chrome (announcement + header + footer) with the landing. */
export default function PublicLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-dvh flex-col">
      <AnnouncementBar />
      <LandingHeader />
      <main className="flex-1">
        <PageTransition className="mx-auto w-full max-w-6xl px-4 py-10">{children}</PageTransition>
      </main>
      <LandingFooter />
    </div>
  );
}
