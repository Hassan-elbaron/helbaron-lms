import { describe, expect, it, vi } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }), usePathname: () => "/" }));

import { BlockRenderer } from "@/components/homepage/registry";
import type { HomepageSection } from "@/lib/homepage/api";

function section(partial: Partial<HomepageSection> & Pick<HomepageSection, "type" | "content">): HomepageSection {
  return { key: partial.type, position: 10, ...partial } as HomepageSection;
}

describe("Homepage block registry", () => {
  it("renders a Statistics block through the registry", () => {
    renderWithI18n(
      <BlockRenderer
        section={section({
          type: "statistics",
          content: {
            heading: { en: "By the numbers", ar: "بالأرقام" },
            items: [{ value: "12", suffix: "+", label: { en: "Verticals", ar: "مجالات" } }],
          },
        })}
      />,
    );
    expect(screen.getByText("By the numbers")).toBeInTheDocument();
    expect(screen.getByText("Verticals")).toBeInTheDocument();
  });

  it("renders a CTA block honoring its accessibility label", () => {
    const { container } = renderWithI18n(
      <BlockRenderer
        section={section({
          type: "cta",
          accessibility_label: { en: "Sign up prompt", ar: "دعوة للتسجيل" },
          content: {
            headline: { en: "Ready?", ar: "جاهز؟" },
            cta_primary: { label: { en: "Get started", ar: "ابدأ" }, href: "/register" },
          },
        })}
      />,
    );
    expect(screen.getByText("Ready?")).toBeInTheDocument();
    expect(container.querySelector('section[aria-label="Sign up prompt"]')).not.toBeNull();
  });

  it("consumes server-resolved courses in the FeaturedCourses block", () => {
    renderWithI18n(
      <BlockRenderer
        section={section({
          type: "featured_courses",
          content: { heading: { en: "Featured", ar: "مختارة" } },
          resolved: {
            courses: [
              { id: "c1", title: { en: "Leadership 101", ar: "قيادة" }, slug: "lead", href: "/courses/c1", thumbnail: null, level: null },
            ],
          },
        })}
      />,
    );
    expect(screen.getByText("Leadership 101")).toBeInTheDocument();
  });

  it("sanitizes RichText HTML and strips scripts", () => {
    const { container } = renderWithI18n(
      <BlockRenderer
        section={section({
          type: "rich_text",
          content: { title: { en: "About", ar: "عن" }, body: { en: "<p>Safe</p><script>alert(1)</script>", ar: "<p>آمن</p>" } },
        })}
      />,
    );
    expect(screen.getByText("Safe")).toBeInTheDocument();
    expect(container.querySelector("script")).toBeNull();
  });

  it("hides a block on mobile when device visibility is off", () => {
    const { container } = renderWithI18n(
      <BlockRenderer
        section={section({
          type: "numbers",
          visibility: { desktop: true, tablet: true, mobile: false },
          content: { heading: { en: "Impact", ar: "الأثر" }, items: [{ value: "3", label: { en: "Hubs", ar: "مراكز" } }] },
        })}
      />,
    );
    expect(container.querySelector("section")?.className).toContain("max-md:hidden");
  });

  it("renders nothing for an unknown/unsupported block type", () => {
    const { container } = renderWithI18n(
      <BlockRenderer section={section({ type: "totally_unknown" as HomepageSection["type"], content: {} })} />,
    );
    expect(container.querySelector("section")).toBeNull();
    expect(container.textContent).toBe("");
  });
});
