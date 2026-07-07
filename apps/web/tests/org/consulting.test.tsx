import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useConsulting, requestMutate } = vi.hoisted(() => ({ useConsulting: vi.fn(), requestMutate: vi.fn() }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/lib/org/hooks", () => ({
  useConsulting,
  useRequestConsulting: () => ({ mutate: requestMutate, isPending: false }),
}));

import ConsultingPage from "@/app/(organization)/org/consulting/page";

const ok = (data: unknown) => ({ isPending: false, isError: false, refetch: vi.fn(), data });

describe("ConsultingPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("lists requests with status", () => {
    useConsulting.mockReturnValue(ok([{ id: "c1", subject: "Onboarding help", description: "Need help", status: "in_progress", sla_due_at: null, created_at: null }]));
    renderWithI18n(<ConsultingPage />);
    expect(screen.getByText("Onboarding help")).toBeInTheDocument();
    expect(screen.getByText("In progress")).toBeInTheDocument();
  });

  it("submits a new consulting request", async () => {
    useConsulting.mockReturnValue(ok([]));
    renderWithI18n(<ConsultingPage />);
    await userEvent.type(screen.getByLabelText("Subject"), "Data migration");
    await userEvent.click(screen.getByRole("button", { name: /Submit request/i }));
    expect(requestMutate).toHaveBeenCalledWith(
      expect.objectContaining({ subject: "Data migration" }),
      expect.anything(),
    );
  });
});
