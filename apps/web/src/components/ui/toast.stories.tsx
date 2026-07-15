import type { Meta, StoryObj } from "@storybook/react";
import { Toaster, toast } from "@/components/ui/toast";
import { Button } from "@/components/ui/button";
import { I18nProvider } from "@/lib/i18n/i18n-context";

// The Toaster host reads text direction from useI18n(), so wrap in I18nProvider.
const meta = {
  title: "Primitives/Toast",
  component: Toaster,
  tags: ["autodocs"],
  decorators: [
    (Story: () => import("react").ReactElement) => (
      <I18nProvider>
        {Story()}
      </I18nProvider>
    ),
  ],
} satisfies Meta<typeof Toaster>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Playground: Story = {
  render: () => (
    <div className="flex flex-wrap gap-2">
      <Button variant="outline" onClick={() => toast("Event has been created")}>
        Default
      </Button>
      <Button
        variant="success"
        onClick={() => toast.success("Course published successfully")}
      >
        Success
      </Button>
      <Button
        variant="destructive"
        onClick={() => toast.error("Something went wrong. Please try again.")}
      >
        Error
      </Button>
      <Button variant="info" onClick={() => toast.info("A new version is available")}>
        Info
      </Button>
      <Button
        variant="secondary"
        onClick={() => toast.warning("Your session expires in 5 minutes")}
      >
        Warning
      </Button>
      <Button
        onClick={() =>
          toast("Draft saved", {
            description: "Your changes are stored locally.",
            action: { label: "Undo", onClick: () => toast("Reverted") },
          })
        }
      >
        With action
      </Button>
      {/* Mount the toast host once; toasts render into its portal. */}
      <Toaster />
    </div>
  ),
};
