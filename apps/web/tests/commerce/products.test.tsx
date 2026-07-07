import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useProducts, addMutate } = vi.hoisted(() => ({ useProducts: vi.fn(), addMutate: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn(), replace: vi.fn() }) }));
vi.mock("@/lib/auth/auth-context", () => ({ useAuth: () => ({ status: "authenticated" }) }));
vi.mock("@/lib/commerce/hooks", () => ({ useProducts, useAddToCart: () => ({ mutate: addMutate, isPending: false, variables: undefined }) }));

import ProductsPage from "@/app/(marketing)/(site)/products/page";

describe("ProductsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("shows price + sale and adds to cart", async () => {
    useProducts.mockReturnValue({
      isPending: false, isError: false, refetch: vi.fn(),
      data: { data: [{ id: "p1", type: "course", title: "Pro Plan", slug: "pro", description: "All courses", prices: [{ currency: "USD", amount_minor: 5000, sale_amount_minor: 3000, on_sale: true, effective_minor: 3000 }] }], meta: { current_page: 1, per_page: 15, total: 1, last_page: 1 }, links: { first: null, last: null, prev: null, next: null } },
    });
    renderWithI18n(<ProductsPage />);
    expect(screen.getByText("Pro Plan")).toBeInTheDocument();
    await userEvent.click(screen.getByRole("button", { name: /Add to cart/i }));
    expect(addMutate).toHaveBeenCalledWith({ product: "p1" }, expect.anything());
  });
});
