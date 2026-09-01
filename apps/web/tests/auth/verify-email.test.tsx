import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderAuth } from "./util";

const { verifyEmail, resendEmailOtp, refresh, replace, search, authState } = vi.hoisted(() => ({
  verifyEmail: vi.fn().mockResolvedValue({}),
  resendEmailOtp: vi.fn().mockResolvedValue({}),
  refresh: vi.fn().mockResolvedValue(undefined),
  replace: vi.fn(),
  search: { params: new URLSearchParams() },
  authState: {
    status: "authenticated" as "guest" | "authenticated",
    user: null as { email_verified: boolean } | null,
  },
}));
vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace, push: vi.fn() }),
  useSearchParams: () => search.params,
}));
vi.mock("@/lib/auth/api", () => ({ verifyEmail, resendEmailOtp }));
vi.mock("@/lib/auth/auth-context", () => ({
  useAuth: () => ({ refresh, user: authState.user, status: authState.status, login: vi.fn(), logout: vi.fn() }),
}));
// Partial mock: only hasSession is stubbed. ApiRequestError must stay real, because errorMessage()
// narrows on it with `instanceof` when a mutation rejects.
vi.mock("@/lib/api/client", async (importOriginal) => ({
  ...(await importOriginal<typeof import("@/lib/api/client")>()),
  hasSession: () => true,
}));

import VerifyEmailPage from "@/app/(marketing)/(auth)/verify-email/page";

describe("VerifyEmailPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useRealTimers();
    authState.status = "authenticated";
    authState.user = null;
    search.params = new URLSearchParams();
  });

  it("submits the OTP code to verify-email", async () => {
    renderAuth(<VerifyEmailPage />);
    await userEvent.type(await screen.findByLabelText("Verification code"), "123456");
    await userEvent.click(screen.getByRole("button", { name: "Verify" }));
    expect(verifyEmail).toHaveBeenCalledWith("123456");
  });

  // The page is no longer guest-guarded, so an account with nothing left to verify must be sent on
  // rather than shown a code form it cannot use.
  it("sends an already-verified account to the dashboard", async () => {
    authState.user = { email_verified: true };

    renderAuth(<VerifyEmailPage />);

    expect(replace).toHaveBeenCalledWith("/dashboard");
  });

  it("keeps an unverified account on the form", async () => {
    authState.user = { email_verified: false };

    renderAuth(<VerifyEmailPage />);

    expect(await screen.findByLabelText("Verification code")).toBeInTheDocument();
    expect(replace).not.toHaveBeenCalled();
  });

  /*
   * `?redirect=` is threaded here by the auth guard, the course purchase panel and the sign-in flow,
   * and was then ignored — the page hardcoded "/" on success and "/dashboard" when already
   * verified. A learner who pressed "Enroll" on a course and verified landed on the homepage and
   * had to find the course again.
   */
  it("honours the redirect param after a successful verification", async () => {
    search.params = new URLSearchParams("redirect=/courses/abc");
    vi.useFakeTimers({ shouldAdvanceTime: true });

    renderAuth(<VerifyEmailPage />);
    await userEvent.type(await screen.findByLabelText("Verification code"), "123456");
    await userEvent.click(screen.getByRole("button", { name: "Verify" }));

    await waitFor(() => expect(verifyEmail).toHaveBeenCalled());
    await vi.advanceTimersByTimeAsync(1500);

    expect(replace).toHaveBeenCalledWith("/courses/abc");
  });

  it("honours the redirect param for an account that is already verified", async () => {
    search.params = new URLSearchParams("redirect=/courses/abc");
    authState.user = { email_verified: true };

    renderAuth(<VerifyEmailPage />);

    expect(replace).toHaveBeenCalledWith("/courses/abc");
  });

  /*
   * Open-redirect guard. `redirect` is attacker-controllable via a crafted link, and this page hands
   * it straight to router.replace on a session that has just become fully authenticated.
   */
  it("refuses to bounce a freshly verified session to an external origin", async () => {
    search.params = new URLSearchParams("redirect=https://evil.example/steal");
    authState.user = { email_verified: true };

    renderAuth(<VerifyEmailPage />);

    expect(replace).toHaveBeenCalledWith("/dashboard");
    expect(replace).not.toHaveBeenCalledWith("https://evil.example/steal");
  });

  it("refuses a protocol-relative redirect", async () => {
    search.params = new URLSearchParams("redirect=//evil.example/steal");
    authState.user = { email_verified: true };

    renderAuth(<VerifyEmailPage />);

    expect(replace).toHaveBeenCalledWith("/dashboard");
  });

  /*
   * The resend control. Without it, a user whose 10-minute code expired or landed in spam was locked
   * out of the entire API with no self-service recovery anywhere in the product.
   */
  it("requests a new code and then holds the button on a cooldown", async () => {
    renderAuth(<VerifyEmailPage />);

    await userEvent.click(await screen.findByRole("button", { name: "Resend code" }));

    await waitFor(() => expect(resendEmailOtp).toHaveBeenCalledTimes(1));
    expect(await screen.findByText("A new code has been sent.")).toBeInTheDocument();

    // Cooling down: the control must not invite a second immediate request.
    const cooling = await screen.findByRole("button", { name: /Resend code in \d+s/ });
    expect(cooling).toBeDisabled();

    await userEvent.click(cooling).catch(() => undefined);
    expect(resendEmailOtp).toHaveBeenCalledTimes(1);
  });

  it("starts the cooldown when the resend is rate limited", async () => {
    const { ApiRequestError } = await import("@/lib/api/client");
    resendEmailOtp.mockRejectedValueOnce(
      new ApiRequestError(429, "AUTH_OTP_RATE_LIMITED", "Too many code requests."),
    );

    renderAuth(<VerifyEmailPage />);
    await userEvent.click(await screen.findByRole("button", { name: "Resend code" }));

    // A genuine rate limit must stop the button inviting more attempts.
    expect(await screen.findByRole("button", { name: /Resend code in \d+s/ })).toBeDisabled();
  });

  it("does NOT cool down after a transient network failure", async () => {
    resendEmailOtp.mockRejectedValueOnce(new TypeError("Failed to fetch"));

    renderAuth(<VerifyEmailPage />);
    await userEvent.click(await screen.findByRole("button", { name: "Resend code" }));

    // Cooling down on any failure locked the button for 60s after a dropped connection — the
    // opposite of what someone locked out of the product needs. The control must stay usable.
    const button = await screen.findByRole("button", { name: "Resend code" });
    expect(button).not.toBeDisabled();

    await userEvent.click(button);
    expect(resendEmailOtp).toHaveBeenCalledTimes(2);
  });
});
