import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useOrganizations } = vi.hoisted(() => ({ useOrganizations: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/org/hooks", () => ({ useOrganizations }));

import OrganizationsPage from "@/app/(organization)/org/organizations/page";

const paginated = (items: unknown[]) => ({
  isPending: false, isError: false, refetch: vi.fn(),
  data: { data: items, meta: { current_page: 1, per_page: 15, total: items.length, last_page: 1 }, links: { first: null, last: null, prev: null, next: null } },
});

describe("OrganizationsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders organizations with member counts", () => {
    useOrganizations.mockReturnValue(paginated([{ id: "org_1", name: "Acme Inc", slug: "acme", status: "active", size: "large", website: null, members_count: 12 }]));
    renderWithI18n(<OrganizationsPage />);
    expect(screen.getByText("Acme Inc")).toBeInTheDocument();
    expect(screen.getByText(/12/)).toBeInTheDocument();
  });

  it("shows empty state when there are no organizations", () => {
    useOrganizations.mockReturnValue(paginated([]));
    renderWithI18n(<OrganizationsPage />);
    expect(screen.getByText("No organizations found.")).toBeInTheDocument();
  });
});
