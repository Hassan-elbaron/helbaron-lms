import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useLeads, createMutate } = vi.hoisted(() => ({ useLeads: vi.fn(), createMutate: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/crm/hooks", () => ({
  useLeads,
  useCreateLead: () => ({ mutate: createMutate, isPending: false }),
}));

import LeadsPage from "@/app/(crm)/crm/leads/page";

const paginated = (items: unknown[]) => ({
  isPending: false, isError: false, refetch: vi.fn(),
  data: { data: items, meta: { current_page: 1, per_page: 15, total: items.length, last_page: 1 }, links: { first: null, last: null, prev: null, next: null } },
});

describe("LeadsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders leads with status", () => {
    useLeads.mockReturnValue(paginated([{ id: "lead_1", name: "Jane Buyer", email: "jane@co.test", phone: null, source: "website", status: "qualified", value_minor: 50000, currency: "USD", created_at: null }]));
    renderWithI18n(<LeadsPage />);
    expect(screen.getByText("Jane Buyer")).toBeInTheDocument();
    // "Qualified" appears both as a filter <option> and the row badge.
    expect(screen.getAllByText("Qualified").length).toBeGreaterThan(0);
  });

  it("shows empty state", () => {
    useLeads.mockReturnValue(paginated([]));
    renderWithI18n(<LeadsPage />);
    expect(screen.getByText("No leads found.")).toBeInTheDocument();
  });

  it("submits a new lead with cents conversion", async () => {
    useLeads.mockReturnValue(paginated([]));
    renderWithI18n(<LeadsPage />);
    await userEvent.type(screen.getByLabelText("Name"), "New Prospect");
    await userEvent.type(screen.getByLabelText("Estimated value"), "100");
    await userEvent.click(screen.getByRole("button", { name: /Create lead/i }));
    expect(createMutate).toHaveBeenCalledWith(
      expect.objectContaining({ name: "New Prospect", value_minor: 10000 }),
      expect.anything(),
    );
  });
});
