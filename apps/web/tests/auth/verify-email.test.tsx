import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderAuth } from "./util";

const { verifyEmail, refresh } = vi.hoisted(() => ({
  verifyEmail: vi.fn().mockResolvedValue({}),
  refresh: vi.fn().mockResolvedValue(undefined),
}));
vi.mock("next/navigation", () => ({ useRouter: () => ({ replace: vi.fn(), push: vi.fn() }) }));
vi.mock("@/lib/auth/api", () => ({ verifyEmail }));
vi.mock("@/lib/auth/auth-context", () => ({
  useAuth: () => ({ refresh, user: null, status: "authenticated", login: vi.fn(), logout: vi.fn() }),
}));
vi.mock("@/lib/api/client", () => ({ hasSession: () => true }));

import VerifyEmailPage from "@/app/(marketing)/(auth)/verify-email/page";

describe("VerifyEmailPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("submits the OTP code to verify-email", async () => {
    renderAuth(<VerifyEmailPage />);
    await userEvent.type(await screen.findByLabelText("Verification code"), "123456");
    await userEvent.click(screen.getByRole("button", { name: "Verify" }));
    expect(verifyEmail).toHaveBeenCalledWith("123456");
  });
});
