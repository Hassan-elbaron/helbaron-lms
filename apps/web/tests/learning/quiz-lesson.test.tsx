import { beforeEach, describe, expect, it, vi } from "vitest";
import { screen } from "@testing-library/react";
import { renderWithI18n } from "../render";
import type { LessonPayload } from "@/lib/learning/api";

/**
 * The learner integration seam: a quiz lesson must reach QuizPlayer through the existing lesson
 * renderer, and every other lesson type must be untouched by that change.
 *
 * QuizPlayer itself is mocked here — its behaviour is covered in tests/assessment/quiz-player.test.tsx.
 * What matters at this seam is WHICH component renders and WHAT reference it receives.
 */

const { QuizPlayer } = vi.hoisted(() => ({
  QuizPlayer: vi.fn(({ assessmentId }: { assessmentId: string }) => (
    <div data-testid="quiz-player">player:{assessmentId}</div>
  )),
}));
const { recordProgress } = vi.hoisted(() => ({ recordProgress: vi.fn() }));

vi.mock("@/components/learning/quiz-player", () => ({ QuizPlayer }));
vi.mock("@/lib/learning/api", async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  recordProgress,
}));

import { LessonContent } from "@/components/learning/lesson-content";

function lesson(overrides: Partial<LessonPayload> = {}): LessonPayload {
  return {
    id: "l1",
    title: "Knowledge check",
    type: "quiz",
    content: null,
    is_preview: false,
    playback: null,
    progress: { status: "not_started", position_seconds: null },
    bookmarked: false,
    note: null,
    navigation: { previous: null, next: null },
    assessment: { id: "a1", title: "Module quiz", question_count: 3, version: 1 },
    ...overrides,
  };
}

beforeEach(() => {
  vi.clearAllMocks();
});

describe("quiz lessons", () => {
  it("renders QuizPlayer with the real assessment reference", () => {
    renderWithI18n(<LessonContent lesson={lesson()} />);

    expect(screen.getByTestId("quiz-player")).toHaveTextContent("player:a1");
    expect(QuizPlayer).toHaveBeenCalledWith(
      expect.objectContaining({ assessmentId: "a1" }),
      undefined,
    );
  });

  it("shows an unavailable state when no assessment is attached", () => {
    renderWithI18n(<LessonContent lesson={lesson({ assessment: null })} />);

    expect(screen.queryByTestId("quiz-player")).not.toBeInTheDocument();
    // A draft assessment arrives as null too, deliberately — the learner sees the same honest
    // "not available" rather than a broken player or a draft id.
    expect(screen.getByText("This quiz is not available yet.")).toBeInTheDocument();
  });

  it("does not record progress merely because the player mounted", () => {
    renderWithI18n(<LessonContent lesson={lesson()} />);

    // Opening a quiz is not completing it. Completion stays with the existing lesson flow.
    expect(recordProgress).not.toHaveBeenCalled();
  });

  it("renders and stays valid under RTL", () => {
    document.documentElement.dir = "rtl";

    try {
      renderWithI18n(<LessonContent lesson={lesson()} />);
      expect(screen.getByTestId("quiz-player")).toBeInTheDocument();
    } finally {
      document.documentElement.dir = "ltr";
    }
  });
});

describe("other lesson types are unaffected", () => {
  it("keeps rendering an article", () => {
    renderWithI18n(
      <LessonContent lesson={lesson({ type: "article", assessment: null, content: { html: "<p>Body text</p>" } })} />,
    );

    expect(screen.getByText("Body text")).toBeInTheDocument();
    expect(screen.queryByTestId("quiz-player")).not.toBeInTheDocument();
  });

  it("keeps rendering an external link", () => {
    renderWithI18n(
      <LessonContent
        lesson={lesson({ type: "external_link", assessment: null, content: { url: "https://example.org" } })}
      />,
    );

    expect(screen.getByRole("link")).toHaveAttribute("href", "https://example.org");
    expect(screen.queryByTestId("quiz-player")).not.toBeInTheDocument();
  });

  it("keeps the legacy quiz placeholder separate from a real quiz", () => {
    renderWithI18n(<LessonContent lesson={lesson({ type: "quiz_placeholder", assessment: null })} />);

    // quiz_placeholder is inert authored content and must never reach the player.
    expect(screen.queryByTestId("quiz-player")).not.toBeInTheDocument();
  });

  it("never renders the player for a non-quiz lesson that somehow carries a reference", () => {
    // Defence in depth: the backend already nulls this, but the branch keys on type.
    renderWithI18n(<LessonContent lesson={lesson({ type: "article", content: { html: "<p>Body</p>" } })} />);

    expect(screen.queryByTestId("quiz-player")).not.toBeInTheDocument();
  });
});
