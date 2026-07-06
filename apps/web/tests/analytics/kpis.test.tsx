import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useKpis } = vi.hoisted(() => ({ useKpis: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/analytics/hooks", () => ({ useKpis }));

import AnalyticsDashboardPage from "@/app/(analytics)/analytics/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("AnalyticsDashboardPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders KPI cards with formatted values", () => {
    useKpis.mockReturnValue(ok({
      from: "2026-06-06", to: "2026-07-06",
      kpis: [
        { metric: "revenue", unit: "currency_minor", total: 250000, series: [{ period: "2026-07-01", value: 250000 }] },
        { metric: "enrollments", unit: "count", total: 42, series: [] },
      ],
    }));
    renderWithI18n(<AnalyticsDashboardPage />);
    expect(screen.getByText("Revenue")).toBeInTheDocument();
    expect(screen.getByText("$2,500.00")).toBeInTheDocument();
    expect(screen.getByText("42")).toBeInTheDocument();
  });

  it("shows empty state when no KPIs", () => {
    useKpis.mockReturnValue(ok({ from: "", to: "", kpis: [] }));
    renderWithI18n(<AnalyticsDashboardPage />);
    expect(screen.getByText("No KPI data available.")).toBeInTheDocument();
  });
});
