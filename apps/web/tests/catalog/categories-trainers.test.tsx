import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useCategories, useTrainers } = vi.hoisted(() => ({ useCategories: vi.fn(), useTrainers: vi.fn() }));
vi.mock("@/lib/catalog/hooks", () => ({ useCategories, useTrainers }));

import CategoriesPage from "@/app/(marketing)/(site)/categories/page";
import TrainersPage from "@/app/(marketing)/(site)/trainers/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("Categories + Trainers", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders nested categories", () => {
    useCategories.mockReturnValue(ok([{ id: "cat1", name: "Programming", slug: "prog", children: [{ id: "cat2", name: "Web", slug: "web" }] }]));
    renderWithI18n(<CategoriesPage />);
    expect(screen.getByText("Programming")).toBeInTheDocument();
    expect(screen.getByText("Web")).toBeInTheDocument();
  });

  it("renders trainers", () => {
    useTrainers.mockReturnValue(ok([{ id: "t1", name: "Sara Ali", headline: "Senior Engineer" }]));
    renderWithI18n(<TrainersPage />);
    expect(screen.getByText("Sara Ali")).toBeInTheDocument();
  });
});
