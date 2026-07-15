import type { Metadata } from "next";
import { getSeo } from "@/lib/seo/api";
import { resolveLocale } from "@/lib/seo/locale";
import { buildMetadata } from "@/lib/seo/metadata";
import { EventsPageClient } from "./events-page-client";

/** Static metadata is the fallback; a managed SEO override (marketing_page/events) wins. */
export async function generateMetadata(): Promise<Metadata> {
  const fallback: Metadata = {
    title: "Events",
    description:
      "Upcoming and past live events, workshops and sessions at HElbaron — browse, search and register for online events with expert speakers.",
    alternates: { canonical: "/events" },
    openGraph: {
      title: "Events",
      description: "Upcoming and past live events, workshops and sessions at HElbaron.",
      url: "/events",
    },
  };

  const [seo, locale] = await Promise.all([getSeo("marketing_page", "events"), resolveLocale()]);
  return buildMetadata(seo, fallback, locale);
}

export default function EventsPage() {
  return <EventsPageClient />;
}
