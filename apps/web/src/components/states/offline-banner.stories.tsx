import type { Meta, StoryObj, Decorator } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { OfflineBanner } from "@/components/states/offline-banner";

/**
 * `OfflineBanner` reads `navigator.onLine` and renders `null` while the browser reports a
 * connection. Storybook always runs "online", so we force the offline signal in a decorator
 * to make the banner visible.
 */
const forceOffline: Decorator = (Story: () => import("react").ReactElement) => {
  if (typeof navigator !== "undefined") {
    Object.defineProperty(navigator, "onLine", { configurable: true, get: () => false });
    window.dispatchEvent(new Event("offline"));
  }
  return (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  );
};

const meta = {
  title: "States/OfflineBanner",
  component: OfflineBanner,
  tags: ["autodocs"],
  parameters: { layout: "fullscreen" },
  decorators: [forceOffline],
  argTypes: {
    message: { control: { type: "text" } },
    className: { control: false },
  },
} satisfies Meta<typeof OfflineBanner>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Default banner — uses the localized `common.offline` message (forced offline for preview). */
export const Default: Story = {};

/** Custom message override. */
export const CustomMessage: Story = {
  args: { message: "You're offline — changes will sync once you reconnect." },
};
