import { describe, expect, it } from "vitest";
import { screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";
import { DataGrid, type ColumnDef } from "@/components/ui/data-grid";

interface Row {
  id: string;
  name: string;
  score: number;
}

const rows: Row[] = [
  { id: "1", name: "Bravo", score: 2 },
  { id: "2", name: "Alpha", score: 9 },
];

const columns: ColumnDef<Row>[] = [
  { key: "name", header: "Name", sortable: true, sortValue: (r) => r.name, cell: (r) => r.name },
  { key: "score", header: "Score", sortable: true, sortValue: (r) => r.score, cell: (r) => String(r.score) },
];

function dataRowNames() {
  // Skip the header row; read the first cell of each body row.
  return screen
    .getAllByRole("row")
    .slice(1)
    .map((r) => within(r).getAllByRole("cell")[0]?.textContent);
}

describe("DataGrid sorting", () => {
  it("toggles aria-sort and reorders rows client-side", async () => {
    renderWithI18n(<DataGrid columns={columns} data={rows} rowKey={(r) => r.id} />);

    // Unsorted: original order.
    expect(dataRowNames()).toEqual(["Bravo", "Alpha"]);

    await userEvent.click(screen.getByRole("button", { name: /Name/ }));
    expect(screen.getByRole("columnheader", { name: /Name/ })).toHaveAttribute("aria-sort", "ascending");
    expect(dataRowNames()).toEqual(["Alpha", "Bravo"]);

    await userEvent.click(screen.getByRole("button", { name: /Name/ }));
    expect(screen.getByRole("columnheader", { name: /Name/ })).toHaveAttribute("aria-sort", "descending");
    expect(dataRowNames()).toEqual(["Bravo", "Alpha"]);
  });
});

describe("DataGrid selection", () => {
  it("selects rows and surfaces a bulk-action bar", async () => {
    renderWithI18n(
      <DataGrid
        columns={columns}
        data={rows}
        rowKey={(r) => r.id}
        selectable
        bulkActions={() => <button type="button">Delete</button>}
      />,
    );

    await userEvent.click(screen.getByLabelText("Select all rows"));
    expect(screen.getByText("2 selected")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Delete" })).toBeInTheDocument();

    await userEvent.click(screen.getByRole("button", { name: /Clear/ }));
    expect(screen.queryByText("2 selected")).not.toBeInTheDocument();
  });
});

describe("DataGrid states", () => {
  it("renders the empty state when there is no data", () => {
    renderWithI18n(<DataGrid columns={columns} data={[]} rowKey={(r) => r.id} />);
    expect(screen.getByText("Nothing here yet")).toBeInTheDocument();
  });
});
