import type { Meta, StoryObj } from "@storybook/react";
import type { UseQueryResult } from "@tanstack/react-query";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { QueryState } from "@/components/student/query-state";

type Course = { id: number; title: string; learners: number };

/**
 * Build a minimal `UseQueryResult` shaped just enough for `QueryState`, which only reads
 * `isPending`, `isError`, `error`, `data`, and `refetch`. Cast through `unknown` because the
 * real type is a large discriminated union we don't need to satisfy field-by-field for a story.
 */
function makeQuery(state: {
  isPending?: boolean;
  isError?: boolean;
  error?: unknown;
  data?: Course[];
}): UseQueryResult<Course[]> {
  return {
    isPending: state.isPending ?? false,
    isError: state.isError ?? false,
    error: state.error ?? null,
    data: state.data,
    refetch: () => {
      alert("refetch()");
      return Promise.resolve({} as never);
    },
  } as unknown as UseQueryResult<Course[]>;
}

const sampleCourses: Course[] = [
  { id: 1, title: "Introduction to Islamic Finance", learners: 1240 },
  { id: 2, title: "Data Science with Python", learners: 3180 },
  { id: 3, title: "Arabic Calligraphy Fundamentals", learners: 860 },
];

const renderList = (data: Course[]) => (
  <ul className="w-80 space-y-2">
    {data.map((c) => (
      <li key={c.id} className="flex items-center justify-between rounded-lg border bg-card p-3 text-sm">
        <span className="font-medium">{c.title}</span>
        <span className="tabular-nums text-muted-foreground">{c.learners.toLocaleString()}</span>
      </li>
    ))}
  </ul>
);

const meta = {
  title: "States/QueryState",
  component: QueryState,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  parameters: {
    docs: {
      description: {
        component:
          "Wraps loading / error / empty / content branches around a TanStack `useQuery` result with consistent, localized states.",
      },
    },
  },
} satisfies Meta<typeof QueryState<Course[]>>;

export default meta;
type Story = StoryObj<typeof meta>;

/** `isPending: true` → the shared LoadingState. */
export const Loading: Story = {
  render: () => (
    <QueryState query={makeQuery({ isPending: true })}>{renderList}</QueryState>
  ),
};

/** `isError: true` → ErrorState with a retry button wired to `refetch()`. */
export const Error: Story = {
  render: () => (
    <QueryState query={makeQuery({ isError: true, error: new Error("Network request failed") })}>
      {renderList}
    </QueryState>
  ),
};

/** Data present but `isEmpty` returns true → EmptyState. */
export const Empty: Story = {
  render: () => (
    <QueryState query={makeQuery({ data: [] })} isEmpty={(d) => d.length === 0}>
      {renderList}
    </QueryState>
  ),
};

/** Populated data → the render-prop children. */
export const Populated: Story = {
  render: () => (
    <QueryState query={makeQuery({ data: sampleCourses })} isEmpty={(d) => d.length === 0}>
      {renderList}
    </QueryState>
  ),
};
