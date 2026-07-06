import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderAuth } from "./util";

const { forgotPassword } = vi.hoisted(() => ({ forgotPassword: vi.fn().mockResolvedValue({}) }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ replace: vi.fn(), push: vi.fn() }) }));
vi.mock("@/lib/auth/api", () => ({ forgotPassword }));

import ForgotPasswordPage from "@/app/(auth)/forgot-password/page";

describe("ForgotPasswordPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("rejects an invalid email", async () => {
    renderAuth(<ForgotPasswordPage />);
    await userEvent.type(screen.getByLabelText("Email"), "not-an-email");
    await userEvent.click(screen.getByRole("button", { name: "Send reset link" }));
    expect(await screen.findByText("Enter a valid email address.")).toBeInTheDocument();
    expect(forgotPassword).not.toHaveBeenCalled();
  });

  it("submits and shows the confirmation state", async () => {
    renderAuth(<ForgotPasswordPage />);
    await userEvent.type(screen.getByLabelText("Email"), "sara@example.com");
    await userEvent.click(screen.getByRole("button", { name: "Send reset link" }));
    expect(forgotPassword).toHaveBeenCalledWith("sara@example.com");
    expect(await screen.findByText(/reset link is on its way/i)).toBeInTheDocument();
  });
});
