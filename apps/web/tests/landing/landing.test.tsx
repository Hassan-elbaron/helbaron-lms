import { describe, expect, it, vi } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }), usePathname: () => "/" }));
vi.mock("@/lib/auth/auth-context", () => ({ useAuth: () => ({ status: "unauthenticated" }) }));

import { Hero } from "@/components/landing/hero";
import { ServiceLines } from "@/components/landing/service-lines";
import { CategoriesSection } from "@/components/landing/categories-section";
import { StatsBand } from "@/components/landing/stats-band";

describe("Landing sections", () => {
  it("renders the hero headline, eyebrow and CTAs", () => {
    renderWithI18n(<Hero />);
    expect(screen.getByText("FOR MENA'S BUSINESS BUILDERS")).toBeInTheDocument();
    expect(screen.getByText("Master")).toBeInTheDocument();
    expect(screen.getByText("the core.")).toBeInTheDocument();
    expect(screen.getByText("Lead the future.")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /Explore courses/i })).toBeInTheDocument();
  });

  it("renders the five service lines with per-card CTAs", () => {
    renderWithI18n(<ServiceLines />);
    expect(screen.getByText("Courses")).toBeInTheDocument();
    expect(screen.getByText("Live Cohorts")).toBeInTheDocument();
    expect(screen.getByText("HElbaron Advisory")).toBeInTheDocument();
    expect(screen.getByText("01")).toBeInTheDocument();
    expect(screen.getByText("05")).toBeInTheDocument();
    expect(screen.getByText("Browse catalog")).toBeInTheDocument();
    expect(screen.getByText("Talk to advisory")).toBeInTheDocument();
  });

  it("renders the twelve categories with HOT badges", () => {
    renderWithI18n(<CategoriesSection />);
    expect(screen.getByText("Project Management")).toBeInTheDocument();
    expect(screen.getByText("Investment & Trading")).toBeInTheDocument();
    expect(screen.getAllByText("HOT").length).toBe(3);
  });

  it("renders the stats band (count-up resolves to target in tests)", () => {
    renderWithI18n(<StatsBand />);
    expect(screen.getByText("25K+")).toBeInTheDocument();
    expect(screen.getByText("$25M")).toBeInTheDocument();
  });
});
