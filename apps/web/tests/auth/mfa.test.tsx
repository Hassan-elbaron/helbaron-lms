import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderAuth } from "./util";

const { verifyMfa } = vi.hoisted(() => ({ verifyMfa: vi.fn().mockResolvedValue({}) }));
vi.mock("next/navigation", () => ({ useRouter: () => ({ replace: vi.fn(), push: vi.fn() }) }));
vi.mock("@/lib/auth/api", () => ({ verifyMfa }));
vi.mock("@/lib/api/client", () => ({ getToken: () => "tok" }));

import MfaPage from "@/app/(marketing)/(auth)/mfa/page";

describe("MfaPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("submits the MFA/recovery code to mfa/verify", async () => {
    renderAuth(<MfaPage />);
    await userEvent.type(await screen.findByLabelText("Verification code"), "654321");
    await userEvent.click(screen.getByRole("button", { name: "Verify" }));
    expect(verifyMfa).toHaveBeenCalledWith("654321");
  });
});
