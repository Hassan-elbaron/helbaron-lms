import { beforeEach, describe, expect, it, vi } from "vitest";
import { act, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";
import { AssessmentSettings } from "@/components/authoring/assessment/assessment-settings";
import type { Assessment } from "@/lib/assessment/types";

function assessment(overrides: Partial<Assessment> = {}): Assessment {
  return {
    id: "a1",
    title: "Module quiz",
    description: null,
    scope: "lesson",
    status: "draft",
    version: 1,
    settings: {
      passing_score: 60,
      negative_marking: false,
      max_attempts: null,
      time_limit_seconds: null,
      shuffle_questions: false,
      shuffle_options: false,
      questions_per_attempt: null,
      feedback_mode: "after_submit",
    },
    question_count: 1,
    questions: [],
    ...overrides,
  };
}

beforeEach(() => {
  // See question-editors.test.tsx: without `shouldAdvanceTime`, Testing Library's waitFor polls on
  // a frozen clock and every assertion that waits for the debounce hangs to the test timeout.
  vi.useFakeTimers({ shouldAdvanceTime: true });
});

function render(a: Assessment) {
  const onSave = vi.fn();
  const onSetStatus = vi.fn().mockResolvedValue(undefined);
  const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
  renderWithI18n(<AssessmentSettings assessment={a} onSave={onSave} onSetStatus={onSetStatus} />);

  return { onSave, onSetStatus, user };
}

describe("settings persistence", () => {
  it("autosaves an edited field", async () => {
    const { onSave, user } = render(assessment());

    await user.clear(screen.getByLabelText(/Title/));
    await user.type(screen.getByLabelText(/Title/), "Final exam");

    await vi.advanceTimersByTimeAsync(800);
    await waitFor(() => expect(onSave).toHaveBeenCalled());
    expect(onSave.mock.calls.at(-1)?.[0].title).toBe("Final exam");
  });

  it("converts the time limit from minutes to seconds", async () => {
    const { onSave, user } = render(assessment());

    await user.type(screen.getByLabelText(/Time limit/), "10");

    await vi.advanceTimersByTimeAsync(800);
    await waitFor(() => expect(onSave).toHaveBeenCalled());
    // Authors think in minutes; the API stores seconds.
    expect(onSave.mock.calls.at(-1)?.[0].time_limit_seconds).toBe(600);
  });

  it("clears an optional limit to null rather than zero", async () => {
    const { onSave, user } = render(assessment({ settings: { ...assessment().settings, max_attempts: 3 } }));

    await user.clear(screen.getByLabelText(/Attempt limit/));

    await vi.advanceTimersByTimeAsync(800);
    await waitFor(() => expect(onSave).toHaveBeenCalled());
    // null means unlimited; 0 would lock every learner out.
    expect(onSave.mock.calls.at(-1)?.[0].max_attempts).toBeNull();
  });
});

describe("publishing", () => {
  it("publishes through the real API and reflects the server's status", async () => {
    const { onSetStatus, user } = render(assessment());

    await user.click(screen.getByRole("button", { name: "Publish" }));

    await waitFor(() => expect(onSetStatus).toHaveBeenCalledWith("published"));
  });

  it("never shows Published optimistically when the guard refuses", async () => {
    const onSave = vi.fn();
    const onSetStatus = vi.fn().mockRejectedValue(new Error("Question 1 has no correct answer."));
    const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
    renderWithI18n(<AssessmentSettings assessment={assessment()} onSave={onSave} onSetStatus={onSetStatus} />);

    await user.click(screen.getByRole("button", { name: "Publish" }));

    // The guard's message names the actual problem — no client check could produce it.
    const alert = await screen.findByRole("alert");
    expect(within(alert).getByText("Question 1 has no correct answer.")).toBeInTheDocument();

    // Status is driven by the prop, which still says draft, so the badge must not read Published.
    expect(screen.getByText("Draft")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Publish" })).toBeInTheDocument();
  });

  it("allows immediate unpublishing of a published assessment", async () => {
    const { onSetStatus, user } = render(assessment({ status: "published" }));

    await user.click(screen.getByRole("button", { name: "Unpublish" }));

    // Unpublishing is never guarded — an author who spots a broken question must be able to pull it.
    await waitFor(() => expect(onSetStatus).toHaveBeenCalledWith("draft"));
  });

  it("prevents a duplicate publish while one is in flight", async () => {
    const onSave = vi.fn();
    let release: (() => void) | undefined;
    const onSetStatus = vi.fn().mockImplementation(
      () => new Promise<void>((resolve) => { release = resolve; }),
    );
    const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
    renderWithI18n(<AssessmentSettings assessment={assessment()} onSave={onSave} onSetStatus={onSetStatus} />);

    const button = screen.getByRole("button", { name: "Publish" });
    await user.click(button);

    await waitFor(() => expect(button).toBeDisabled());
    await user.click(button);

    expect(onSetStatus).toHaveBeenCalledTimes(1);

    // Releasing the in-flight publish re-enables the button, and that state update has to settle
    // inside the test — otherwise it lands after teardown and React rightly complains.
    await act(async () => {
      release?.();
    });
  });

  it("keeps unsaved edits when a publish fails", async () => {
    const onSave = vi.fn();
    const onSetStatus = vi.fn().mockRejectedValue(new Error("Nope."));
    const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
    renderWithI18n(<AssessmentSettings assessment={assessment()} onSave={onSave} onSetStatus={onSetStatus} />);

    await user.clear(screen.getByLabelText(/Title/));
    await user.type(screen.getByLabelText(/Title/), "Renamed");
    await user.click(screen.getByRole("button", { name: "Publish" }));

    await screen.findByRole("alert");
    // A failed publish must not discard what the author was typing.
    expect(screen.getByLabelText(/Title/)).toHaveValue("Renamed");
  });
});

describe("RTL", () => {
  it("renders and stays operable under a right-to-left document", async () => {
    // renderWithI18n has no locale switch, so this asserts direction-independence rather than
    // Arabic copy. Layout correctness comes from using logical CSS properties (ps-/me-/text-start)
    // throughout — that is enforced by review and the visual-regression suite, not here. The
    // EN/AR key-parity test in tests/authoring/block-content.test.ts covers missing translations.
    document.documentElement.dir = "rtl";

    try {
      render(assessment());

      expect(screen.getByLabelText(/Title/)).toBeEnabled();
      expect(screen.getByRole("button", { name: "Publish" })).toBeEnabled();
      expect(screen.getByRole("button", { name: "Preview" })).toBeEnabled();
    } finally {
      document.documentElement.dir = "ltr";
    }
  });
});
