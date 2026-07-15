import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useCart, checkoutMutate } = vi.hoisted(() => ({ useCart: vi.fn(), checkoutMutate: vi.fn() }));
vi.mock("@/lib/auth/auth-context", () => ({ useAuth: () => ({ status: "authenticated" }) }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn(), replace: vi.fn() }), usePathname: () => "/cart", useSearchParams: () => new URLSearchParams() }));
vi.mock("@/lib/commerce/hooks", () => ({
  useCart,
  useCheckout: () => ({ mutate: checkoutMutate, isPending: false }),
  useContracts: () => ({ data: [] }),
  useAcceptContract: () => ({ mutate: vi.fn(), isPending: false, variables: undefined }),
}));

import CheckoutPage from "@/app/(commerce)/checkout/page";

describe("CheckoutPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("shows the order summary and places the order", async () => {
    useCart.mockReturnValue({
      isPending: false, isError: false, refetch: vi.fn(),
      data: { id: "cart1", currency: "USD", coupon: null, items: [{ id: "ci1", product_id: "p1", title: "Pro Plan", unit_amount_minor: 5000 }], subtotal_minor: 5000, discount_minor: 0, total_minor: 5000 },
    });
    renderWithI18n(<CheckoutPage />);
    expect(screen.getByText("Order summary")).toBeInTheDocument();
    await userEvent.click(screen.getByRole("button", { name: "Place order" }));
    expect(checkoutMutate).toHaveBeenCalled();
  });
});
