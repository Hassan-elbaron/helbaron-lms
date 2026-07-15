import type { Meta, StoryObj } from "@storybook/react";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

const meta = {
  title: "Primitives/Accordion",
  component: Accordion,
  tags: ["autodocs"],
  argTypes: {
    type: {
      control: { type: "inline-radio" },
      options: ["single", "multiple"],
    },
    collapsible: { control: { type: "boolean" } },
  },
} satisfies Meta<typeof Accordion>;

export default meta;
type Story = StoryObj<typeof meta>;

const items = [
  {
    value: "item-1",
    q: "Is it accessible?",
    a: "Yes. Each trigger exposes aria-expanded and controls its region.",
  },
  {
    value: "item-2",
    q: "Is it styled with tokens?",
    a: "Yes. Colours, motion, and spacing all read from design-system CSS variables.",
  },
  {
    value: "item-3",
    q: "Does it support RTL?",
    a: "Yes. Text alignment uses logical properties so it mirrors automatically.",
  },
];

export const Single: Story = {
  render: () => (
    <Accordion type="single" collapsible defaultValue="item-1" className="w-96">
      {items.map((item) => (
        <AccordionItem key={item.value} value={item.value}>
          <AccordionTrigger>{item.q}</AccordionTrigger>
          <AccordionContent>{item.a}</AccordionContent>
        </AccordionItem>
      ))}
    </Accordion>
  ),
};

export const Multiple: Story = {
  render: () => (
    <Accordion type="multiple" defaultValue={["item-1", "item-2"]} className="w-96">
      {items.map((item) => (
        <AccordionItem key={item.value} value={item.value}>
          <AccordionTrigger>{item.q}</AccordionTrigger>
          <AccordionContent>{item.a}</AccordionContent>
        </AccordionItem>
      ))}
    </Accordion>
  ),
};
