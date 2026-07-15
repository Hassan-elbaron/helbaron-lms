import { describe, expect, it, vi } from "vitest";
import { screen } from "@testing-library/react";
import { BookOpen } from "lucide-react";
import { renderWithI18n } from "../render";

// Active path drives the sidebar's aria-current assertion below.
vi.mock("next/navigation", () => ({ usePathname: () => "/courses" }));

import { Sidebar } from "@/components/layout/sidebar";
import { LeadStatusBadge } from "@/components/crm/lead-status-badge";

describe("Sidebar accessibility", () => {
  const items = [
    { label: "Dashboard", href: "/dashboard", icon: BookOpen },
    { label: "Courses", href: "/courses", icon: BookOpen },
  ];

  it("labels the nav landmark and marks the active link with aria-current=page", () => {
    renderWithI18n(<Sidebar items={items} navLabel="Primary" />);

    // Landmark carries the provided accessible name.
    expect(screen.getByRole("navigation", { name: "Primary" })).toBeInTheDocument();

    // The link matching the current path is the only one flagged as the current page.
    const current = screen.getByRole("link", { name: "Courses" });
    expect(current).toHaveAttribute("aria-current", "page");
    expect(screen.getByRole("link", { name: "Dashboard" })).not.toHaveAttribute("aria-current");
  });

  it("accepts a distinct nav label (desktop rail vs mobile drawer)", () => {
    renderWithI18n(<Sidebar items={items} navLabel="Mobile" />);
    expect(screen.getByRole("navigation", { name: "Mobile" })).toBeInTheDocument();
  });
});

describe("LeadStatusBadge does not rely on colour alone", () => {
  it("renders the status text alongside a decorative icon", () => {
    const { container } = renderWithI18n(<LeadStatusBadge status="qualified" />);
    // Text conveys the status (label), and an aria-hidden icon reinforces it by shape.
    expect(screen.getByText(/qualified/i)).toBeInTheDocument();
    expect(container.querySelector("svg")).not.toBeNull();
  });
});
