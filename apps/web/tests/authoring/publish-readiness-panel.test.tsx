import { beforeEach, describe, expect, it, vi } from "vitest";
import { screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";
import type { ReadinessReport, TeachCourse } from "@/lib/teach/api";

/**
 * The publish surface. What matters here is not layout but authority: the panel must take the
 * server's verdict rather than deriving its own, must never show a course as published before the
 * server says so, and must surface a guard refusal verbatim.
 */
const { useCourseReadiness, useTeachCourse, usePublishCourse, useUnpublishCourse, useArchiveCourse } =
  vi.hoisted(() => ({
    useCourseReadiness: vi.fn(),
    useTeachCourse: vi.fn(),
    usePublishCourse: vi.fn(),
    useUnpublishCourse: vi.fn(),
    useArchiveCourse: vi.fn(),
  }));

vi.mock("@/lib/teach/hooks", () => ({
  useCourseReadiness,
  useTeachCourse,
  usePublishCourse,
  useUnpublishCourse,
  useArchiveCourse,
}));

import { PublishReadinessPanel } from "@/components/authoring/publish-readiness-panel";

function report(overrides: Partial<ReadinessReport> = {}): ReadinessReport {
  return {
    is_publishable: true,
    score: 100,
    evaluated_at: new Date("2026-07-20T10:00:00Z").toISOString(),
    blockers: [],
    warnings: [],
    passed_checks: ["course.no_sections"],
    ...overrides,
  };
}

const blocker = {
  code: "course.no_published_lesson",
  severity: "blocker" as const,
  title: "The course has no published lessons.",
  explanation: "Draft lessons are invisible to learners.",
  recommended_action: "Publish at least one lesson in the Course Builder.",
  entity_type: "course" as const,
  entity_id: "c1",
};

let publishMutate: ReturnType<typeof vi.fn>;
let refetch: ReturnType<typeof vi.fn>;

function setup(data: ReadinessReport | undefined, status: TeachCourse["status"] = "draft", extra = {}) {
  refetch = vi.fn();
  useCourseReadiness.mockReturnValue({
    data,
    isPending: false,
    isError: false,
    isFetching: false,
    refetch,
    ...extra,
  });
  useTeachCourse.mockReturnValue({ data: { status } });
}

beforeEach(() => {
  vi.clearAllMocks();
  publishMutate = vi.fn();
  usePublishCourse.mockReturnValue({ mutate: publishMutate, isPending: false, error: null });
  useUnpublishCourse.mockReturnValue({ mutate: vi.fn(), isPending: false, error: null });
  useArchiveCourse.mockReturnValue({ mutate: vi.fn(), isPending: false, error: null });
});

function render() {
  const user = userEvent.setup();
  renderWithI18n(<PublishReadinessPanel courseId="c1" open onOpenChange={vi.fn()} />);

  return { user };
}

describe("readiness display", () => {
  it("shows the score and a ready state from the server", () => {
    setup(report());
    render();

    expect(screen.getByText("100% ready")).toBeInTheDocument();
    expect(screen.getByText("This course is ready to publish.")).toBeInTheDocument();
  });

  it("renders every field of a blocking issue", () => {
    setup(report({ is_publishable: false, score: 40, blockers: [blocker] }));
    render();

    const section = screen.getByRole("region", { name: "Blocking issues" });

    // Title, explanation and recommended action all appear: an issue the author cannot act on is
    // useless, so the action text is as load-bearing as the title.
    expect(within(section).getByText(blocker.title)).toBeInTheDocument();
    expect(within(section).getByText(blocker.explanation)).toBeInTheDocument();
    expect(within(section).getByText(blocker.recommended_action)).toBeInTheDocument();
  });

  it("deep-links a lesson-scoped issue to that lesson", () => {
    setup(
      report({
        is_publishable: false,
        blockers: [{ ...blocker, code: "lesson.empty_content", entity_type: "lesson", entity_id: "les9" }],
      }),
    );
    render();

    expect(screen.getByRole("link", { name: "Open the lesson" })).toHaveAttribute(
      "href",
      "/teach/courses/c1/edit?lesson=les9",
    );
  });

  it("does not offer a deep link for a course-scoped issue", () => {
    setup(report({ is_publishable: false, blockers: [blocker] }));
    render();

    // The author is already in the builder; a link back to where they are would be noise.
    expect(screen.queryByRole("link", { name: "Open the lesson" })).not.toBeInTheDocument();
  });

  it("separates warnings from blockers", () => {
    setup(
      report({
        warnings: [{ ...blocker, code: "course.missing_description", severity: "warning", title: "No description." }],
      }),
    );
    render();

    expect(within(screen.getByRole("region", { name: "Blocking issues" })).getByText("Nothing is blocking publication.")).toBeInTheDocument();
    expect(within(screen.getByRole("region", { name: "Recommended" })).getByText("No description.")).toBeInTheDocument();
  });

  it("lists completed checks in words rather than raw codes", () => {
    setup(report({ passed_checks: ["course.no_published_lesson"] }));
    render();

    expect(screen.getByText("At least one lesson is published.")).toBeInTheDocument();
  });
});

describe("publish authority", () => {
  it("disables publish when the server says the course is not publishable", () => {
    setup(report({ is_publishable: false, blockers: [blocker] }));
    render();

    // Read from is_publishable, never recomputed from the blocker list.
    expect(screen.getByRole("button", { name: "Publish" })).toBeDisabled();
  });

  it("enables publish only on the server's verdict, even with warnings outstanding", () => {
    setup(report({ score: 80, warnings: [{ ...blocker, severity: "warning" }] }));
    render();

    expect(screen.getByRole("button", { name: "Publish" })).toBeEnabled();
  });

  it("confirms before publishing and does not fire until confirmed", async () => {
    setup(report());
    const { user } = render();

    await user.click(screen.getByRole("button", { name: "Publish" }));
    expect(publishMutate).not.toHaveBeenCalled();

    const dialog = await screen.findByRole("dialog", { name: "Publish this course?" });
    await user.click(within(dialog).getByRole("button", { name: "Publish" }));

    await waitFor(() => expect(publishMutate).toHaveBeenCalledWith("c1", expect.anything()));
  });

  it("surfaces a guard refusal verbatim", () => {
    setup(report());
    usePublishCourse.mockReturnValue({
      mutate: publishMutate,
      isPending: false,
      error: new Error("The course has no published lessons."),
    });
    render();

    // The refusal names what to fix. Replacing it with a generic failure message throws away the
    // only useful part of the response.
    expect(screen.getByRole("alert")).toHaveTextContent("The course has no published lessons.");
  });

  it("offers unpublish instead of publish once the course is published", () => {
    setup(report(), "published");
    render();

    expect(screen.queryByRole("button", { name: "Publish" })).not.toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Unpublish" })).toBeInTheDocument();
  });

  it("hides archive for an already archived course", () => {
    setup(report(), "archived");
    render();

    expect(screen.queryByRole("button", { name: "Archive" })).not.toBeInTheDocument();
  });

  it("blocks a second submission while one is in flight", () => {
    setup(report());
    usePublishCourse.mockReturnValue({ mutate: publishMutate, isPending: true, error: null });
    render();

    expect(screen.getByRole("button", { name: "Publish" })).toBeDisabled();
  });
});

describe("states", () => {
  it("shows a loading state while the report is being fetched", () => {
    useCourseReadiness.mockReturnValue({ data: undefined, isPending: true, isError: false, refetch: vi.fn() });
    useTeachCourse.mockReturnValue({ data: { status: "draft" } });
    renderWithI18n(<PublishReadinessPanel courseId="c1" open onOpenChange={vi.fn()} />);

    expect(screen.getByRole("status")).toBeInTheDocument();
  });

  it("offers a retry when the report fails to load", async () => {
    const failedRefetch = vi.fn();
    useCourseReadiness.mockReturnValue({
      data: undefined,
      isPending: false,
      isError: true,
      refetch: failedRefetch,
    });
    useTeachCourse.mockReturnValue({ data: { status: "draft" } });
    const user = userEvent.setup();
    renderWithI18n(<PublishReadinessPanel courseId="c1" open onOpenChange={vi.fn()} />);

    expect(screen.getByText("Couldn't check publish readiness.")).toBeInTheDocument();
    await user.click(screen.getByRole("button", { name: "Retry" }));
    expect(failedRefetch).toHaveBeenCalled();
  });

  it("re-checks on demand", async () => {
    setup(report());
    const { user } = render();

    await user.click(screen.getByRole("button", { name: "Re-check" }));
    expect(refetch).toHaveBeenCalled();
  });

  it("renders under RTL", () => {
    document.documentElement.dir = "rtl";

    try {
      setup(report());
      render();
      expect(screen.getByText("100% ready")).toBeInTheDocument();
    } finally {
      document.documentElement.dir = "ltr";
    }
  });

  it("does not fetch readiness while the panel is closed", () => {
    setup(report());
    renderWithI18n(<PublishReadinessPanel courseId="c1" open={false} onOpenChange={vi.fn()} />);

    // Readiness is a multi-query evaluation server-side; the builder should not pay for it on
    // every mount, only when an author opens the panel.
    expect(useCourseReadiness).toHaveBeenCalledWith("c1", false);
  });
});
