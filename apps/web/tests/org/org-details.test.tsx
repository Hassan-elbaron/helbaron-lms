import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18nAsync } from "../render";

const { useOrganization, inviteMutate } = vi.hoisted(() => ({ useOrganization: vi.fn(), inviteMutate: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/org/hooks", () => ({
  useOrganization,
  useInviteMember: () => ({ mutate: inviteMutate, isPending: false }),
}));

import OrganizationDetailsPage from "@/app/(organization)/org/organizations/[public_id]/page";

describe("OrganizationDetailsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders profile + members and submits an invite", async () => {
    useOrganization.mockReturnValue({
      isPending: false, isError: false, refetch: vi.fn(),
      data: { id: "org_1", name: "Acme Inc", slug: "acme", status: "active", size: "large", website: "https://acme.test", members_count: 1, members: [{ id: "m1", email: "lead@acme.test", role: "admin", status: "active", invited_at: null }] },
    });
    await renderWithI18nAsync(<OrganizationDetailsPage params={Promise.resolve({ public_id: "org_1" })} />);
    expect(await screen.findByText("Acme Inc")).toBeInTheDocument();
    expect(screen.getByText("lead@acme.test")).toBeInTheDocument();

    await userEvent.type(screen.getByLabelText("Email"), "new@acme.test");
    await userEvent.click(screen.getByRole("button", { name: /Send invite/i }));
    expect(inviteMutate).toHaveBeenCalledWith(
      expect.objectContaining({ email: "new@acme.test", role: "member" }),
      expect.anything(),
    );
  });
});
