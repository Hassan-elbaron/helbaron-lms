import type { Meta, StoryObj } from "@storybook/react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

const meta = {
  title: "Primitives/Avatar",
  component: Avatar,
  tags: ["autodocs"],
} satisfies Meta<typeof Avatar>;

export default meta;
type Story = StoryObj<typeof meta>;

export const WithImage: Story = {
  render: () => (
    <Avatar>
      <AvatarImage src="https://i.pravatar.cc/150?img=12" alt="Jane Doe" />
      <AvatarFallback>JD</AvatarFallback>
    </Avatar>
  ),
};

export const Fallback: Story = {
  render: () => (
    <Avatar>
      {/* Broken src forces the initials fallback to render. */}
      <AvatarImage src="" alt="Sam Rivera" />
      <AvatarFallback>SR</AvatarFallback>
    </Avatar>
  ),
};

export const Sizes: Story = {
  render: () => (
    <div className="flex items-center gap-4">
      <Avatar className="size-8">
        <AvatarImage src="https://i.pravatar.cc/150?img=5" alt="Small" />
        <AvatarFallback className="text-xs">SM</AvatarFallback>
      </Avatar>
      <Avatar className="size-10">
        <AvatarImage src="https://i.pravatar.cc/150?img=8" alt="Medium" />
        <AvatarFallback>MD</AvatarFallback>
      </Avatar>
      <Avatar className="size-16">
        <AvatarImage src="https://i.pravatar.cc/150?img=15" alt="Large" />
        <AvatarFallback className="text-lg">LG</AvatarFallback>
      </Avatar>
    </div>
  ),
};

export const Group: Story = {
  render: () => (
    <div className="flex -space-x-3 rtl:space-x-reverse">
      {[12, 8, 5, 15].map((img, i) => (
        <Avatar key={i} className="ring-2 ring-background">
          <AvatarImage src={`https://i.pravatar.cc/150?img=${img}`} alt={`Member ${i + 1}`} />
          <AvatarFallback>{`U${i + 1}`}</AvatarFallback>
        </Avatar>
      ))}
    </div>
  ),
};
