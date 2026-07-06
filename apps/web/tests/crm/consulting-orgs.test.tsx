import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { useConsulting, useOrganizations } = vi.hoisted(() => ({ useConsulting: vi.fn(), useOrganizations: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/org/hooks", () => ({ useConsulting, useOrganizations }));

import CrmConsultingPage from "@/app/(crm)/crm/consulting/page";
import CrmOrganizationsPage from "@/app/(crm)/crm/organizations/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("CRM consulting + organizations", () => {
  beforeEach(() => vi.clearAllMocks());

  it("lists consulting requests with status", () => {
    useConsulting.mockReturnValue(ok([{ id: "c1", subject: "Rollout plan", description: null, status: "triaged", sla_due_at: null, created_at: null }]));
    renderWithI18n(<CrmConsultingPage />);
    expect(screen.getByText("Rollout plan")).toBeInTheDocument();
    expect(screen.getByText("Triaged")).toBeInTheDocument();
  });

  it("renders organizations with members + seats", () => {
    useOrganizations.mockReturnValue(ok({ data: [{ id: "org_1", name: "Acme Inc", slug: "acme", status: "active", size: "large", website: null, members_count: 8 }], meta: { current_page: 1, per_page: 15, total: 1, last_page: 1 }, links: { first: null, last: null, prev: null, next: null } }));
    renderWithI18n(<CrmOrganizationsPage />);
    expect(screen.getByText("Acme Inc")).toBeInTheDocument();
    expect(screen.getAllByText("8").length).toBeGreaterThan(0);
  });
});
