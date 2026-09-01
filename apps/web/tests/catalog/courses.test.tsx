import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useCourses, useCategories } = vi.hoisted(() => ({ useCourses: vi.fn(), useCategories: vi.fn() }));
vi.mock("next/navigation", () => ({
  useSearchParams: () => new URLSearchParams(),
  useRouter: () => ({ replace: vi.fn(), push: vi.fn(), prefetch: vi.fn(), back: vi.fn(), forward: vi.fn(), refresh: vi.fn() }),
  usePathname: () => "/courses",
}));
vi.mock("@/lib/catalog/hooks", () => ({ useCourses, useCategories }));

import CoursesPage from "@/app/(marketing)/(site)/courses/page";

const paged = (items: unknown[]) => ({
  isPending: false,
  isError: false,
  refetch: vi.fn(),
  data: { data: items, meta: { current_page: 1, per_page: 12, total: items.length, last_page: 1 }, links: { first: null, last: null, prev: null, next: null } },
});

describe("CoursesPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    useCategories.mockReturnValue({ isPending: false, isError: false, data: [], refetch: vi.fn() });
  });

  it("renders course cards from the API", () => {
    useCourses.mockReturnValue(paged([{ id: "c1", title: "React Basics", slug: "react", subtitle: null, thumbnail_path: null, is_featured: false, level: "Beginner", language: "English", published_at: null }]));
    renderWithI18n(<CoursesPage />);
    expect(screen.getByText("React Basics")).toBeInTheDocument();
  });

  it("shows the empty state with no results", () => {
    useCourses.mockReturnValue(paged([]));
    renderWithI18n(<CoursesPage />);
    expect(screen.getByText("No courses match your filters.")).toBeInTheDocument();
  });

  /*
   * Card-level half of the free-enrolment regression. The card labels a course "Free" from its
   * purchase summary, and it used to do so whenever `purchasable` was false — which is also true of
   * a course whose product is merely Draft or Archived. That advertised paid courses as free in the
   * catalogue listing, one click away from a lifetime grant.
   */
  const card = (id: string, title: string, purchase: unknown) => ({
    id, title, slug: id, subtitle: null, thumbnail_path: null,
    is_featured: false, level: null, language: null, published_at: null, purchase,
  });

  it("labels a course Free only when nothing sells it at any status", () => {
    useCourses.mockReturnValue(paged([card("c1", "Genuinely Free", { purchasable: false, free: true })]));
    renderWithI18n(<CoursesPage />);

    expect(screen.getByText("Free")).toBeInTheDocument();
    expect(screen.queryByText("Not available yet")).not.toBeInTheDocument();
  });

  it("labels a draft-product course Not available yet, never Free", () => {
    useCourses.mockReturnValue(paged([card("c2", "Paid But Drafted", { purchasable: false, free: false })]));
    renderWithI18n(<CoursesPage />);

    expect(screen.getByText("Not available yet")).toBeInTheDocument();
    expect(screen.queryByText("Free")).not.toBeInTheDocument();
  });

  it("shows neither label when the payload carries no purchase summary", () => {
    // Fails closed: an absent summary must not be read as free.
    useCourses.mockReturnValue(paged([card("c3", "No Summary", undefined)]));
    renderWithI18n(<CoursesPage />);

    expect(screen.queryByText("Free")).not.toBeInTheDocument();
    expect(screen.queryByText("Not available yet")).not.toBeInTheDocument();
  });
});
