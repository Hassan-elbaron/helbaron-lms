import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useProfile, mutate } = vi.hoisted(() => ({ useProfile: vi.fn(), mutate: vi.fn() }));
vi.mock("@/lib/student/hooks", () => ({
  useProfile,
  useUpdateProfile: () => ({ mutate, isPending: false }),
}));

import ProfilePage from "@/app/(account)/profile/page";

describe("ProfilePage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("submits profile changes to the update mutation", async () => {
    useProfile.mockReturnValue({
      isPending: false,
      isError: false,
      refetch: vi.fn(),
      data: { id: "u1", name: "Sara", email: "s@e.com", phone: null, locale: "en", email_verified: true, mfa_enabled: false, roles: [], profile: { first_name: "Sara", last_name: null, avatar_path: null, bio: null, gender: null, date_of_birth: null } },
    });
    renderWithI18n(<ProfilePage />);
    const bio = screen.getByLabelText("Bio");
    await userEvent.type(bio, "Hello");
    await userEvent.click(screen.getByRole("button", { name: "Save changes" }));
    expect(mutate).toHaveBeenCalledWith(expect.objectContaining({ bio: "Hello", locale: "en" }), expect.anything());
  });
});
