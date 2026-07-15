import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { BarChart, ProgressRing, chartColor, CHART_SERIES } from "@/components/ui/charts";

describe("chart theme", () => {
  it("exposes a tokenized categorical colour sequence (no hardcoded hex)", () => {
    expect(CHART_SERIES[0]).toBe("var(--primary)");
    for (const c of CHART_SERIES) expect(c.startsWith("var(--")).toBe(true);
  });

  it("wraps and is negative-safe", () => {
    expect(chartColor(0)).toBe(chartColor(CHART_SERIES.length));
    expect(chartColor(-1)).toBe(chartColor(CHART_SERIES.length - 1));
  });
});

describe("BarChart a11y", () => {
  it("renders an accessible figure with a visually-hidden data-table alternative", () => {
    render(
      <BarChart
        label="Monthly signups"
        data={[
          { label: "Jan", value: 3 },
          { label: "Feb", value: 5 },
        ]}
      />,
    );
    expect(screen.getByRole("img", { name: "Monthly signups" })).toBeInTheDocument();
    // The data table mirrors the values for screen-reader users.
    const table = screen.getByRole("table");
    expect(table).toHaveTextContent("Feb");
    expect(table).toHaveTextContent("5");
  });
});

describe("ProgressRing a11y", () => {
  it("is an accessible figure showing the rounded percentage", () => {
    render(<ProgressRing value={40} label="Completion" />);
    expect(screen.getByRole("img", { name: "Completion" })).toBeInTheDocument();
    expect(screen.getByText("40%")).toBeInTheDocument();
  });
});
