import type { Meta, StoryObj } from "@storybook/react";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const meta = {
  title: "Primitives/Select",
  component: Select,
  tags: ["autodocs"],
} satisfies Meta<typeof Select>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: () => (
    <Select defaultValue="banana">
      <SelectTrigger className="w-56">
        <SelectValue placeholder="Select a fruit" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="apple">Apple</SelectItem>
        <SelectItem value="banana">Banana</SelectItem>
        <SelectItem value="cherry">Cherry</SelectItem>
      </SelectContent>
    </Select>
  ),
};

export const WithPlaceholder: Story = {
  render: () => (
    <Select>
      <SelectTrigger className="w-56">
        <SelectValue placeholder="Choose an option" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="one">Option one</SelectItem>
        <SelectItem value="two">Option two</SelectItem>
        <SelectItem value="three">Option three</SelectItem>
      </SelectContent>
    </Select>
  ),
};

export const Disabled: Story = {
  render: () => (
    <Select disabled>
      <SelectTrigger className="w-56">
        <SelectValue placeholder="Disabled select" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="one">Option one</SelectItem>
      </SelectContent>
    </Select>
  ),
};

export const WithGroups: Story = {
  render: () => (
    <Select defaultValue="cat">
      <SelectTrigger className="w-56">
        <SelectValue placeholder="Select an animal" />
      </SelectTrigger>
      <SelectContent>
        <SelectGroup>
          <SelectItem value="cat">Cat</SelectItem>
          <SelectItem value="dog">Dog</SelectItem>
        </SelectGroup>
        <SelectGroup>
          <SelectItem value="eagle">Eagle</SelectItem>
          <SelectItem value="parrot">Parrot</SelectItem>
          <SelectItem value="owl" disabled>
            Owl (unavailable)
          </SelectItem>
        </SelectGroup>
      </SelectContent>
    </Select>
  ),
};
