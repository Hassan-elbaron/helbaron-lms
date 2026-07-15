import { useState } from "react";
import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { DataGrid, type ColumnDef } from "@/components/ui/data-grid";
import { Button } from "@/components/ui/button";

interface Course {
  id: string;
  title: string;
  category: string;
  learners: number;
  rating: number;
  status: "Published" | "Draft" | "Archived";
}

const courses: Course[] = [
  { id: "c1", title: "Intro to Data Science", category: "Data", learners: 1284, rating: 4.7, status: "Published" },
  { id: "c2", title: "React for Beginners", category: "Web", learners: 942, rating: 4.5, status: "Published" },
  { id: "c3", title: "Advanced SQL", category: "Data", learners: 512, rating: 4.8, status: "Draft" },
  { id: "c4", title: "UX Foundations", category: "Design", learners: 733, rating: 4.3, status: "Published" },
  { id: "c5", title: "Cloud Fundamentals", category: "Infra", learners: 421, rating: 4.1, status: "Archived" },
  { id: "c6", title: "Machine Learning 101", category: "Data", learners: 1876, rating: 4.9, status: "Published" },
];

const columns: ColumnDef<Course>[] = [
  {
    key: "title",
    header: "Course",
    cell: (row) => <span className="font-medium">{row.title}</span>,
    sortable: true,
    sortValue: (row) => row.title,
  },
  {
    key: "category",
    header: "Category",
    cell: (row) => row.category,
    hideable: true,
  },
  {
    key: "learners",
    header: "Learners",
    align: "end",
    cell: (row) => <span className="tabular-nums">{row.learners.toLocaleString()}</span>,
    sortable: true,
    sortValue: (row) => row.learners,
  },
  {
    key: "rating",
    header: "Rating",
    align: "end",
    cell: (row) => <span className="tabular-nums">{row.rating.toFixed(1)}</span>,
    sortable: true,
    sortValue: (row) => row.rating,
  },
  {
    key: "status",
    header: "Status",
    cell: (row) => row.status,
    hideable: true,
  },
];

const CourseGrid = DataGrid<Course>;

const meta = {
  title: "Data/DataGrid",
  component: CourseGrid,
  parameters: { layout: "padded" },
  decorators: [
    (Story: () => import("react").ReactElement) => (
      <I18nProvider>
        {Story()}
      </I18nProvider>
    ),
  ],
  args: {
    columns,
    data: courses,
    rowKey: (row: Course) => row.id,
  },
  argTypes: {
    density: { control: "select", options: ["comfortable", "compact"] },
    selectable: { control: "boolean" },
    stickyHeader: { control: "boolean" },
    columnToggle: { control: "boolean" },
    loading: { control: "boolean" },
  },
} satisfies Meta<typeof CourseGrid>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Baseline grid: static columns + rows, no interactive affordances. */
export const Default: Story = {};

/** Sortable columns (Course, Learners, Rating). Click a header to cycle asc → desc → none. */
export const Sortable: Story = {
  args: {
    columns,
  },
};

/** Row selection with a bulk-action bar rendered above the table. */
export const Selectable: Story = {
  args: {
    selectable: true,
    bulkActions: ({ selectedRows, clear }: { selectedRows: Course[]; clear: () => void }) => (
      <Button
        size="sm"
        variant="outline"
        onClick={() => {
          window.alert(`Archiving ${selectedRows.length} course(s)`);
          clear();
        }}
      >
        Archive
      </Button>
    ),
  },
};

/** Sticky header pinned while the constrained body scrolls (`stickyHeader` + `maxHeight`). */
export const StickyHeader: Story = {
  args: {
    stickyHeader: true,
    maxHeight: 220,
    data: [...courses, ...courses, ...courses].map((c, i) => ({ ...c, id: `${c.id}-${i}` })),
  },
};

/** Column visibility toggle uses each column's `hideable` flag. */
export const WithColumnToggle: Story = {
  args: {
    columnToggle: true,
    toolbar: <p className="text-sm text-muted-foreground">6 courses</p>,
  },
};

/** Empty state rendered when `data` is empty (falls back to the default EmptyState). */
export const Empty: Story = {
  args: {
    data: [],
  },
};

/** Loading state renders skeleton rows while `loading` is true. */
export const Loading: Story = {
  args: {
    loading: true,
  },
};

/** Controlled pagination wired through the `pagination` prop (uses local state). */
function PaginatedGrid(args: import("react").ComponentProps<typeof CourseGrid>) {
  const [page, setPage] = useState(1);
  return <CourseGrid {...args} pagination={{ page, lastPage: 5, onPageChange: setPage }} />;
}

export const WithPagination: Story = {
  render: (args: import("react").ComponentProps<typeof CourseGrid>) => <PaginatedGrid {...args} />,
};
