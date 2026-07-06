import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useNotifications, markMutate } = vi.hoisted(() => ({ useNotifications: vi.fn(), markMutate: vi.fn() }));
vi.mock("@/lib/student/hooks", () => ({
  useNotifications,
  useMarkNotificationRead: () => ({ mutate: markMutate, isPending: false, variables: undefined }),
  useUpdatePreferences: () => ({ mutate: vi.fn(), isPending: false }),
}));
vi.mock("@/lib/auth/auth-context", () => ({ useAuth: () => ({ user: { locale: "en" } }) }));

import NotificationsPage from "@/app/(student)/notifications/page";

describe("NotificationsPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("marks an unread notification as read", async () => {
    useNotifications.mockReturnValue({
      isPending: false,
      isError: false,
      refetch: vi.fn(),
      data: {
        data: [{ id: "n1", category: "system", type: "x", title: "Welcome", body: "Hi there", locale: "en", read: false, archived: false, created_at: null }],
        meta: { current_page: 1, per_page: 20, total: 1, last_page: 1 },
        links: { first: null, last: null, prev: null, next: null },
      },
    });
    renderWithI18n(<NotificationsPage />);
    expect(screen.getByText("Welcome")).toBeInTheDocument();
    await userEvent.click(screen.getByRole("button", { name: /Mark as read/i }));
    expect(markMutate).toHaveBeenCalledWith("n1", expect.anything());
  });
});
