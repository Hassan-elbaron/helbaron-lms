import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderAuth } from "./util";

const { resetPassword } = vi.hoisted(() => ({ resetPassword: vi.fn().mockResolvedValue({}) }));
vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace: vi.fn(), push: vi.fn() }),
  useSearchParams: () => new URLSearchParams("token=tok123&email=sara@example.com"),
}));
vi.mock("@/lib/auth/api", () => ({ resetPassword }));

import ResetPasswordPage from "@/app/(marketing)/(auth)/reset-password/page";

describe("ResetPasswordPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("submits token + email from the query with the new password", async () => {
    renderAuth(<ResetPasswordPage />);
    await userEvent.type(screen.getByLabelText("Password"), "newsecret1");
    await userEvent.type(screen.getByLabelText("Confirm password"), "newsecret1");
    await userEvent.click(screen.getByRole("button", { name: "Reset password" }));
    expect(resetPassword).toHaveBeenCalledWith({
      token: "tok123",
      email: "sara@example.com",
      password: "newsecret1",
      password_confirmation: "newsecret1",
    });
  });
});
