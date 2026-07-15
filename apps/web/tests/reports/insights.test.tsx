import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n, renderWithI18nAsync } from "../render";

const { useReportCatalog, useReportInsight } = vi.hoisted(() => ({
  useReportCatalog: vi.fn(),
  useReportInsight: vi.fn(),
}));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/reports/hooks", () => ({ useReportCatalog, useReportInsight }));

import ReportInsightsHub from "@/app/(analytics)/reports/insights/page";
import ReportInsightPage from "@/app/(analytics)/reports/insights/[report]/page";
import { ReportView } from "@/components/reports/report-view";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("Report Insights", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders the report catalog on the hub", () => {
    useReportCatalog.mockReturnValue(
      ok([
        { key: "revenue", label: "Revenue", description: "Paid revenue and refunds." },
        { key: "crm", label: "CRM", description: "Pipeline and leads." },
      ]),
    );
    renderWithI18n(<ReportInsightsHub />);
    expect(screen.getByText("Revenue")).toBeInTheDocument();
    expect(screen.getByText("CRM")).toBeInTheDocument();
  });

  it("shows the hub empty state", () => {
    useReportCatalog.mockReturnValue(ok([]));
    renderWithI18n(<ReportInsightsHub />);
    expect(screen.getByText("No reports available.")).toBeInTheDocument();
  });

  it("renders a report's KPIs, chart and table via ReportView", () => {
    renderWithI18n(
      <ReportView
        meta={{ from: "2026-01-01", to: "2026-07-01" }}
        page={1}
        onPageChange={() => {}}
        payload={{
          summary: { net_minor: 2500000, orders: 12 },
          series: [
            { period: "2026-05", value: 3 },
            { period: "2026-06", value: 5 },
          ],
          by_course: [{ course: "Course A", revenue_minor: 30000 }],
        }}
      />,
    );
    expect(screen.getByText("Summary")).toBeInTheDocument();
    expect(screen.getByText("Orders")).toBeInTheDocument();
    expect(screen.getByText("12")).toBeInTheDocument();
    expect(screen.getByText("Course A")).toBeInTheDocument();
  });

  it("renders the funnel steps", () => {
    renderWithI18n(
      <ReportView
        meta={{ from: "2026-01-01", to: "2026-07-01" }}
        page={1}
        onPageChange={() => {}}
        payload={{
          steps: [
            { step: "enrolled", count: 10, percentage: 100 },
            { step: "completed", count: 4, percentage: 40 },
          ],
        }}
      />,
    );
    expect(screen.getByText("Enrolled")).toBeInTheDocument();
    expect(screen.getByText("Completed")).toBeInTheDocument();
  });

  it("renders a single report page for the given key", async () => {
    useReportInsight.mockReturnValue(
      ok({ data: { summary: { issued: 7 } }, meta: { from: "2026-01-01", to: "2026-07-01" } }),
    );
    await renderWithI18nAsync(<ReportInsightPage params={Promise.resolve({ report: "certificates" })} />);
    expect(screen.getByText("Certificates")).toBeInTheDocument();
    expect(screen.getByText("Issued")).toBeInTheDocument();
    expect(screen.getByText("7")).toBeInTheDocument();
  });
});
