import type { Meta, StoryObj } from "@storybook/react";
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
  TableCaption,
  type TableDensity,
} from "@/components/ui/table";

interface Enrollment {
  id: string;
  learner: string;
  course: string;
  progress: string;
  status: string;
}

const rows: Enrollment[] = [
  { id: "1", learner: "Amina Farouk", course: "Intro to Data Science", progress: "82%", status: "Active" },
  { id: "2", learner: "Youssef Nabil", course: "React for Beginners", progress: "45%", status: "Active" },
  { id: "3", learner: "Sara Mansour", course: "Advanced SQL", progress: "100%", status: "Completed" },
  { id: "4", learner: "Omar Khaled", course: "UX Foundations", progress: "12%", status: "At risk" },
  { id: "5", learner: "Lina Habib", course: "Cloud Fundamentals", progress: "67%", status: "Active" },
];

const meta = {
  title: "Data/Table",
  component: Table,
  parameters: { layout: "padded" },
  argTypes: {
    density: {
      control: "select",
      options: ["comfortable", "compact"] satisfies TableDensity[],
      description: "Row density. `compact` opts into the tighter [data-density=compact] spacing.",
    },
  },
} satisfies Meta<typeof Table>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Realistic enrollment table at the default (comfortable) density. */
export const Default: Story = {
  args: { density: "comfortable" },
  render: (args: import("react").ComponentProps<typeof Table>) => (
    <Table {...args}>
      <TableCaption>A list of recent course enrollments.</TableCaption>
      <TableHeader>
        <TableRow>
          <TableHead>Learner</TableHead>
          <TableHead>Course</TableHead>
          <TableHead className="text-end">Progress</TableHead>
          <TableHead>Status</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {rows.map((row) => (
          <TableRow key={row.id}>
            <TableCell className="font-medium">{row.learner}</TableCell>
            <TableCell>{row.course}</TableCell>
            <TableCell className="text-end tabular-nums">{row.progress}</TableCell>
            <TableCell>{row.status}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  ),
};

/** The tighter `compact` density variant driven by the `data-density` attribute. */
export const Compact: Story = {
  args: { density: "compact" },
  render: Default.render,
};

/** Header pinned to the top of a scrolling body via `TableHeader sticky`. */
export const StickyHeader: Story = {
  args: { density: "comfortable" },
  render: (args: import("react").ComponentProps<typeof Table>) => (
    <div className="max-h-64 overflow-auto rounded-md border">
      <Table {...args}>
        <TableHeader sticky>
          <TableRow>
            <TableHead>Learner</TableHead>
            <TableHead>Course</TableHead>
            <TableHead className="text-end">Progress</TableHead>
            <TableHead>Status</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {[...rows, ...rows, ...rows].map((row, i) => (
            <TableRow key={`${row.id}-${i}`}>
              <TableCell className="font-medium">{row.learner}</TableCell>
              <TableCell>{row.course}</TableCell>
              <TableCell className="text-end tabular-nums">{row.progress}</TableCell>
              <TableCell>{row.status}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  ),
};
