import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18nAsync } from "../render";

const { useReport, runMutate, createMutate } = vi.hoisted(() => ({ useReport: vi.fn(), runMutate: vi.fn(), createMutate: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/analytics/hooks", () => ({
  useReport,
  useRunReport: () => ({ mutate: runMutate, isPending: false }),
  useCreateExport: () => ({ mutate: createMutate, isPending: false }),
  useExportStatus: () => ({ data: undefined }),
}));

import ReportDetailsPage from "@/app/(analytics)/reports/[public_id]/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("ReportDetailsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("runs the report and renders the result rows in a table", async () => {
    useReport.mockReturnValue(ok({ id: "rep_1", name: "Revenue by metric", type: "metric", metric_keys: ["revenue"], visibility: "shared" }));
    runMutate.mockImplementation((_body, opts) =>
      opts.onSuccess({ data: { run_id: "run_1", ran_at: "2026-07-06T10:00:00Z", result: { type: "metric", rows: [{ metric: "revenue", total: 1000 }] } } }),
    );
    await renderWithI18nAsync(<ReportDetailsPage params={Promise.resolve({ public_id: "rep_1" })} />);
    expect(await screen.findByText("Revenue by metric")).toBeInTheDocument();
    await userEvent.click(screen.getByRole("button", { name: /Run report/i }));
    expect(runMutate).toHaveBeenCalledWith({ report: "rep_1" }, expect.anything());
    expect(screen.getByText("metric")).toBeInTheDocument(); // column header
    expect(screen.getByText("1000")).toBeInTheDocument();
  });

  it("queues an export when clicking Export CSV", async () => {
    useReport.mockReturnValue(ok({ id: "rep_1", name: "R", type: "metric", metric_keys: [], visibility: "private" }));
    await renderWithI18nAsync(<ReportDetailsPage params={Promise.resolve({ public_id: "rep_1" })} />);
    await userEvent.click(await screen.findByRole("button", { name: /Export CSV/i }));
    expect(createMutate).toHaveBeenCalledWith({ report: "rep_1", format: "csv" }, expect.anything());
  });
});
