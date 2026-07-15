import { useState } from "react";
import { describe, expect, it, vi } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { renderWithI18n } from "../render";

function Harness({ onConfirm }: { onConfirm: () => void }) {
  const [open, setOpen] = useState(false);
  return (
    <>
      <button onClick={() => setOpen(true)}>Open</button>
      <ConfirmDialog
        open={open}
        onOpenChange={setOpen}
        title="Archive this course?"
        description="This can be undone later."
        confirmLabel="Archive"
        onConfirm={onConfirm}
      />
    </>
  );
}

describe("ConfirmDialog", () => {
  it("opens and shows the title, consequence, and confirm/cancel actions", async () => {
    renderWithI18n(<Harness onConfirm={vi.fn()} />);
    await userEvent.click(screen.getByRole("button", { name: "Open" }));

    const dialog = await screen.findByRole("dialog");
    expect(dialog).toHaveTextContent("Archive this course?");
    expect(dialog).toHaveTextContent("This can be undone later.");
    expect(screen.getByRole("button", { name: "Archive" })).toBeInTheDocument();
    // Falls back to the bilingual default cancel label.
    expect(screen.getByRole("button", { name: "Cancel" })).toBeInTheDocument();
  });

  it("invokes onConfirm when the confirm button is clicked", async () => {
    const onConfirm = vi.fn();
    renderWithI18n(<Harness onConfirm={onConfirm} />);
    await userEvent.click(screen.getByRole("button", { name: "Open" }));
    await userEvent.click(await screen.findByRole("button", { name: "Archive" }));
    expect(onConfirm).toHaveBeenCalledTimes(1);
  });
});
