import { describe, expect, it, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";

const { useMyCertificates, downloadMutate } = vi.hoisted(() => ({ useMyCertificates: vi.fn(), downloadMutate: vi.fn() }));
vi.mock("@/lib/student/hooks", () => ({
  useMyCertificates,
  useCertificateDownload: () => ({ mutate: downloadMutate, isPending: false, variables: undefined }),
  useCertificateShare: () => ({ mutate: vi.fn(), isPending: false, variables: undefined }),
}));

import CertificatesPage from "@/app/(student)/certificates/page";

describe("CertificatesPage", () => {
  beforeEach(() => vi.clearAllMocks());

  it("requests a download for a certificate", async () => {
    useMyCertificates.mockReturnValue({
      isPending: false,
      isError: false,
      refetch: vi.fn(),
      data: [{ id: "cert1", number: "HB-001", status: "issued", course_title: "Course A", issued_at: null }],
    });
    renderWithI18n(<CertificatesPage />);
    expect(screen.getByText("Course A")).toBeInTheDocument();
    await userEvent.click(screen.getByRole("button", { name: /Download/i }));
    expect(downloadMutate).toHaveBeenCalledWith("cert1", expect.anything());
  });
});
