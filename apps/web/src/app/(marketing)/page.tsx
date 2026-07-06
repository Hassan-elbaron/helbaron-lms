import type { Metadata } from "next";
import { brandTheme } from "@/config/theme";
import { AnnouncementBar } from "@/components/landing/announcement-bar";
import { LandingHeader } from "@/components/landing/landing-header";
import { Hero } from "@/components/landing/hero";
import { TrustedBy } from "@/components/landing/trusted-by";
import { ServiceLines } from "@/components/landing/service-lines";
import { CategoriesSection } from "@/components/landing/categories-section";
import { FeaturedCourses } from "@/components/marketing/featured-courses";
import { StatsBand } from "@/components/landing/stats-band";
import { FinalCta } from "@/components/landing/final-cta";
import { LandingFooter } from "@/components/landing/landing-footer";

export const metadata: Metadata = {
  title: `${brandTheme.name} — ${brandTheme.tagline.en}`,
  description: brandTheme.footer.description.en,
};

export default function LandingPage() {
  return (
    <>
      <AnnouncementBar />
      <LandingHeader />
      <main className="flex-1">
        <Hero />
        <TrustedBy />
        <ServiceLines />
        <CategoriesSection />
        <FeaturedCourses />
        <StatsBand />
        <FinalCta />
      </main>
      <LandingFooter />
    </>
  );
}
