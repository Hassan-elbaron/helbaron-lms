import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useOrders, useContracts, acceptMutate } = vi.hoisted(() => ({ useOrders: vi.fn(), useContracts: vi.fn(), acceptMutate: vi.fn() }));
vi.mock("@/lib/auth/auth-context", () => ({ useAuth: () => ({ status: "authenticated" }) }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/commerce/hooks", () => ({
  useOrders, useContracts,
  useAcceptContract: () => ({ mutate: acceptMutate, isPending: false, variables: undefined }),
}));

import OrdersPage from "@/app/(commerce)/orders/page";
import ContractsPage from "@/app/(commerce)/contracts/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("Orders + Contracts", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders an order with status + total", () => {
    useOrders.mockReturnValue(ok({ data: [{ id: "o1", status: "paid", currency: "USD", subtotal_minor: 5000, discount_minor: 0, total_minor: 5000, placed_at: null, paid_at: null, fulfilled_at: null, items: [{ title: "Pro Plan", unit_amount_minor: 5000 }], invoice: null }], meta: { current_page: 1, per_page: 15, total: 1, last_page: 1 }, links: { first: null, last: null, prev: null, next: null } }));
    renderWithI18n(<OrdersPage />);
    expect(screen.getByText("paid")).toBeInTheDocument();
  });

  it("accepts a pending contract", async () => {
    useContracts.mockReturnValue(ok([{ id: "k1", status: "pending", accepted_at: null, template: { key: "terms", version: 1, title: "Terms", body: "Body text" } }]));
    renderWithI18n(<ContractsPage />);
    expect(screen.getByText("Terms")).toBeInTheDocument();
    await userEvent.click(screen.getByRole("button", { name: "Accept" }));
    expect(acceptMutate).toHaveBeenCalledWith("k1", expect.anything());
  });
});
