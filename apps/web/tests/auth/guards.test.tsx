import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";

const { replace, authState } = vi.hoisted(() => ({
  replace: vi.fn(),
  authState: {
    status: "guest" as "guest" | "loading" | "authenticated",
    user: null as { id: string; roles: string[] } | null,
  },
}));

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace, push: vi.fn() }),
  usePathname: () => "/crm/leads",
  useSearchParams: () => new URLSearchParams("page=2"),
}));
vi.mock("@/lib/auth/auth-context", () => ({ useAuth: () => authState }));

import { RequireAuth, RequireGuest } from "@/lib/auth/guards";

describe("RequireAuth", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    authState.status = "guest";
    authState.user = null;
  });

  it("redirects guests to login with the current path+search as ?redirect=", () => {
    renderWithI18n(
      <RequireAuth>
        <p>secret</p>
      </RequireAuth>,
    );
    expect(screen.queryByText("secret")).not.toBeInTheDocument();
    expect(replace).toHaveBeenCalledWith(`/login?redirect=${encodeURIComponent("/crm/leads?page=2")}`);
  });

  it("renders an access-denied panel with a go-home link on role mismatch", () => {
    authState.status = "authenticated";
    authState.user = { id: "u1", roles: ["student"] };
    renderWithI18n(
      <RequireAuth roles={["admin"]}>
        <p>secret</p>
      </RequireAuth>,
    );
    expect(screen.queryByText("secret")).not.toBeInTheDocument();
    expect(replace).not.toHaveBeenCalled();
    expect(screen.getByText("Access denied")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "Go to homepage" })).toHaveAttribute("href", "/");
  });

  it("renders children when authenticated with a matching role", () => {
    authState.status = "authenticated";
    authState.user = { id: "u1", roles: ["admin"] };
    renderWithI18n(
      <RequireAuth roles={["admin"]}>
        <p>secret</p>
      </RequireAuth>,
    );
    expect(screen.getByText("secret")).toBeInTheDocument();
    expect(replace).not.toHaveBeenCalled();
  });
});

describe("RequireGuest", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    authState.status = "guest";
    authState.user = null;
  });

  it("renders children for guests", () => {
    renderWithI18n(
      <RequireGuest>
        <p>login form</p>
      </RequireGuest>,
    );
    expect(screen.getByText("login form")).toBeInTheDocument();
  });

  it("redirects authenticated users away", () => {
    authState.status = "authenticated";
    authState.user = { id: "u1", roles: [] };
    renderWithI18n(
      <RequireGuest>
        <p>login form</p>
      </RequireGuest>,
    );
    expect(screen.queryByText("login form")).not.toBeInTheDocument();
    expect(replace).toHaveBeenCalledWith("/dashboard");
  });
});
