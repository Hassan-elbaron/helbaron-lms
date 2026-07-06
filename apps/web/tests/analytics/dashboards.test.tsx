import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useDashboards } = vi.hoisted(() => ({ useDashboards: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/analytics/hooks", () => ({ useDashboards }));

import DashboardsPage from "@/app/(analytics)/dashboards/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("DashboardsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders dashboards with widget previews", () => {
    useDashboards.mockReturnValue(ok([{ id: "d1", key: "exec", name: "Executive", is_default: true, widgets: [{ id: "w1", title: "Revenue", metric_key: "revenue", type: "kpi", config: null }] }]));
    renderWithI18n(<DashboardsPage />);
    expect(screen.getByText("Executive")).toBeInTheDocument();
    expect(screen.getByText("Revenue")).toBeInTheDocument();
    expect(screen.getByText("Default")).toBeInTheDocument();
  });

  it("shows empty state", () => {
    useDashboards.mockReturnValue(ok([]));
    renderWithI18n(<DashboardsPage />);
    expect(screen.getByText("No dashboards found.")).toBeInTheDocument();
  });
});
