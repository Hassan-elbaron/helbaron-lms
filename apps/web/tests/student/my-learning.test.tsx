import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useMyLearning } = vi.hoisted(() => ({ useMyLearning: vi.fn() }));
vi.mock("@/lib/student/hooks", () => ({ useMyLearning }));

import MyLearningPage from "@/app/(student)/my-learning/page";

const q = (over: Record<string, unknown>) => ({ isPending: false, isError: false, refetch: vi.fn(), ...over });

describe("MyLearningPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders enrolled course cards", () => {
    useMyLearning.mockReturnValue(
      q({ data: [{ enrollment_id: "e1", status: "active", progress_percentage: 40, course: { id: "c1", title: "Intro to X" } }] }),
    );
    renderWithI18n(<MyLearningPage />);
    expect(screen.getByText("Intro to X")).toBeInTheDocument();
    expect(screen.getByText("40%")).toBeInTheDocument();
  });

  it("shows the empty state when there are no courses", () => {
    useMyLearning.mockReturnValue(q({ data: [] }));
    renderWithI18n(<MyLearningPage />);
    expect(screen.getByText("You are not enrolled in any course yet.")).toBeInTheDocument();
  });
});
