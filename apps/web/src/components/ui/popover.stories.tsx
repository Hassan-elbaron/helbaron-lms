import type { Meta, StoryObj } from "@storybook/react";
import {
  Popover,
  PopoverClose,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { Button } from "@/components/ui/button";

const meta = {
  title: "Primitives/Popover",
  component: Popover,
  tags: ["autodocs"],
} satisfies Meta<typeof Popover>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: () => (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="outline">Open popover</Button>
      </PopoverTrigger>
      <PopoverContent align="start">
        <div className="space-y-2">
          <h4 className="text-sm font-medium">Dimensions</h4>
          <p className="text-sm text-muted-foreground">
            Set the width and height for the selected layer.
          </p>
          <div className="flex justify-end">
            <PopoverClose asChild>
              <Button size="sm">Done</Button>
            </PopoverClose>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  ),
};

export const Alignments: Story = {
  render: () => (
    <div className="flex gap-4">
      {(["start", "center", "end"] as const).map((align) => (
        <Popover key={align}>
          <PopoverTrigger asChild>
            <Button variant="outline">{align}</Button>
          </PopoverTrigger>
          <PopoverContent align={align}>
            <p className="text-sm">Aligned to <strong>{align}</strong>.</p>
          </PopoverContent>
        </Popover>
      ))}
    </div>
  ),
};
