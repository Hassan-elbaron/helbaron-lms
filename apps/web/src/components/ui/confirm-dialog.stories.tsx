import { useState } from "react";
import type { Meta, StoryObj } from "@storybook/react";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Button } from "@/components/ui/button";
import { I18nProvider } from "@/lib/i18n/i18n-context";

// ConfirmDialog calls useI18n() for its default labels, so it needs I18nProvider.
const meta = {
  title: "Primitives/ConfirmDialog",
  component: ConfirmDialog,
  tags: ["autodocs"],
  decorators: [
    (Story: () => import("react").ReactElement) => (
      <I18nProvider>
        {Story()}
      </I18nProvider>
    ),
  ],
} satisfies Meta<typeof ConfirmDialog>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Destructive: Story = {
  render: function DestructiveStory() {
    const [open, setOpen] = useState(false);
    return (
      <>
        <Button variant="destructive" onClick={() => setOpen(true)}>
          Delete course
        </Button>
        <ConfirmDialog
          open={open}
          onOpenChange={setOpen}
          title="Delete this course?"
          description="This permanently removes the course and all of its lessons. This action cannot be undone."
          confirmLabel="Delete"
          cancelLabel="Keep course"
          confirmVariant="destructive"
          onConfirm={() => {
            // Simulate an async mutation.
            return new Promise<void>((resolve) => setTimeout(resolve, 800));
          }}
        />
      </>
    );
  },
};

export const DefaultConfirm: Story = {
  render: function DefaultConfirmStory() {
    const [open, setOpen] = useState(false);
    return (
      <>
        <Button onClick={() => setOpen(true)}>Publish course</Button>
        <ConfirmDialog
          open={open}
          onOpenChange={setOpen}
          title="Publish this course?"
          description="Learners will be able to enroll immediately after publishing."
          confirmLabel="Publish"
          cancelLabel="Not yet"
          confirmVariant="default"
          onConfirm={() => setOpen(false)}
        />
      </>
    );
  },
};
