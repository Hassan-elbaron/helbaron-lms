import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderAuth } from "./util";

const { registerUser, login } = vi.hoisted(() => ({
  registerUser: vi.fn().mockResolvedValue({ data: {} }),
  login: vi.fn().mockResolvedValue(undefined),
}));
vi.mock("next/navigation", () => ({ useRouter: () => ({ replace: vi.fn(), push: vi.fn() }) }));
vi.mock("@/lib/auth/api", () => ({ registerUser }));
vi.mock("@/lib/auth/auth-context", () => ({
  useAuth: () => ({ login, user: null, status: "guest", logout: vi.fn(), refresh: vi.fn() }),
}));

import RegisterPage from "@/app/(auth)/register/page";

describe("RegisterPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    Object.defineProperty(window, "location", {
      configurable: true,
      value: { ...window.location, assign: vi.fn() },
    });
  });

  it("validates password confirmation and terms", async () => {
    renderAuth(<RegisterPage />);
    await userEvent.type(screen.getByLabelText("Full name"), "Sara");
    await userEvent.type(screen.getByLabelText("Email"), "sara@example.com");
    await userEvent.type(screen.getByLabelText("Password"), "secret123");
    await userEvent.type(screen.getByLabelText("Confirm password"), "different");
    await userEvent.click(screen.getByRole("button", { name: "Create account" }));
    expect(await screen.findByText("Passwords do not match.")).toBeInTheDocument();
    expect(registerUser).not.toHaveBeenCalled();
  });

  it("submits registration when valid", async () => {
    renderAuth(<RegisterPage />);
    await userEvent.type(screen.getByLabelText("Full name"), "Sara");
    await userEvent.type(screen.getByLabelText("Email"), "sara@example.com");
    await userEvent.type(screen.getByLabelText("Password"), "secret123");
    await userEvent.type(screen.getByLabelText("Confirm password"), "secret123");
    await userEvent.click(screen.getByLabelText(/I agree/i));
    await userEvent.click(screen.getByRole("button", { name: "Create account" }));
    expect(registerUser).toHaveBeenCalledWith(
      expect.objectContaining({ name: "Sara", email: "sara@example.com", password: "secret123", password_confirmation: "secret123" }),
    );
  });
});
