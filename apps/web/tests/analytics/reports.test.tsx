import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useReports } = vi.hoisted(() => ({ useReports: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/analytics/hooks", () => ({ useReports }));

import ReportsPage from "@/app/(analytics)/reports/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("ReportsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders report definitions", () => {
    useReports.mockReturnValue(ok([{ id: "rep_1", name: "Revenue by month", type: "metric", metric_keys: ["revenue"], visibility: "shared" }]));
    renderWithI18n(<ReportsPage />);
    expect(screen.getByText("Revenue by month")).toBeInTheDocument();
  });

  it("shows empty state", () => {
    useReports.mockReturnValue(ok([]));
    renderWithI18n(<ReportsPage />);
    expect(screen.getByText("No reports found.")).toBeInTheDocument();
  });
});
