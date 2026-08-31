import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderAuth } from "./util";

const login = vi.fn().mockResolvedValue(undefined);
const replace = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace, push: vi.fn() }),
  useSearchParams: () => new URLSearchParams(),
}));
vi.mock("@/lib/auth/auth-context", () => ({
  useAuth: () => ({ login, user: null, status: "guest", logout: vi.fn(), refresh: vi.fn() }),
}));

import LoginPage from "@/app/(marketing)/(auth)/login/page";

describe("LoginPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("renders the sign-in form", () => {
    renderAuth(<LoginPage />);
    expect(screen.getByRole("button", { name: "Sign in" })).toBeInTheDocument();
    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByLabelText("Password")).toBeInTheDocument();
  });

  it("shows validation errors on empty submit and does not call the API", async () => {
    renderAuth(<LoginPage />);
    await userEvent.click(screen.getByRole("button", { name: "Sign in" }));
    expect(await screen.findAllByText("This field is required.")).not.toHaveLength(0);
    expect(login).not.toHaveBeenCalled();
  });

  it("calls auth.login with the credentials on valid submit", async () => {
    renderAuth(<LoginPage />);
    await userEvent.type(screen.getByLabelText("Email"), "sara@example.com");
    await userEvent.type(screen.getByLabelText("Password"), "secret123");
    await userEvent.click(screen.getByRole("button", { name: "Sign in" }));
    // The fourth argument is "remember me", threaded end to end in E1. It defaults to FALSE, and
    // asserting that explicitly is the point: the checkbox was previously registered on the form and
    // never sent, so unticking it on a shared machine still produced a fortnight-long credential.
    expect(login).toHaveBeenCalledWith("sara@example.com", "secret123", undefined, false);
  });

  it("passes remember me through when the box is ticked", async () => {
    renderAuth(<LoginPage />);
    await userEvent.type(screen.getByLabelText("Email"), "sara@example.com");
    await userEvent.type(screen.getByLabelText("Password"), "secret123");
    await userEvent.click(screen.getByLabelText(/remember/i));
    await userEvent.click(screen.getByRole("button", { name: "Sign in" }));
    expect(login).toHaveBeenCalledWith("sara@example.com", "secret123", undefined, true);
  });
});
