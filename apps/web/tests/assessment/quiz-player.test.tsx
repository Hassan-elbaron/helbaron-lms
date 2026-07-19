import { beforeEach, describe, expect, it, vi } from "vitest";
import { act, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";
import type { Attempt, AttemptQuestion, LearnerQuestion } from "@/lib/assessment/types";

/**
 * `useStartAttempt` is mocked, so its `onSuccess` has to be invoked by hand to simulate the attempt
 * being created. That callback sets state, and an unwrapped state update is how a test starts
 * passing for the wrong reason — the assertion happens to catch up rather than being guaranteed to.
 * Every simulated success therefore goes through act().
 */
function completeStart(mutate: ReturnType<typeof vi.fn>, attemptId = "att1") {
  act(() => {
    mutate.mock.calls[0][1].onSuccess({ id: attemptId });
  });
}

/**
 * Advancing the clock fires the debounce, which resolves (or rejects) the saveAnswer promise, which
 * moves the save indicator. That is a React state update driven from outside an event handler, so
 * the whole advance has to happen inside act() — otherwise the indicator assertions are racing the
 * render rather than observing it.
 */
async function advanceBy(ms: number) {
  await act(async () => {
    await vi.advanceTimersByTimeAsync(ms);
  });
}

const { useAttempt, useStartAttempt, useSubmitAttempt } = vi.hoisted(() => ({
  useAttempt: vi.fn(),
  useStartAttempt: vi.fn(),
  useSubmitAttempt: vi.fn(),
}));
const { saveAnswer } = vi.hoisted(() => ({ saveAnswer: vi.fn() }));

vi.mock("@/lib/assessment/hooks", () => ({ useAttempt, useStartAttempt, useSubmitAttempt }));
vi.mock("@/lib/assessment/api", () => ({ saveAnswer }));

import { QuizPlayer } from "@/components/learning/quiz-player";

function learnerQuestion(id: string, prompt: string): LearnerQuestion {
  return {
    id,
    type: "single_choice",
    prompt: `<p>${prompt}</p>`,
    config: null,
    hint: null,
    points: 1,
    explanation: null,
    options: [
      { id: `${id}-a`, label: "Alpha", group_index: 0 },
      { id: `${id}-b`, label: "Beta", group_index: 0 },
    ],
  };
}

function item(id: string, prompt: string, answer: AttemptQuestion["answer"] = null): AttemptQuestion {
  return { question: learnerQuestion(id, prompt), answer };
}

function attempt(overrides: Partial<Attempt> = {}): Attempt {
  return {
    id: "att1",
    status: "in_progress",
    attempt_number: 1,
    started_at: new Date().toISOString(),
    expires_at: null,
    submitted_at: null,
    result: null,
    questions: [item("q1", "First"), item("q2", "Second")],
    feedback_mode: "after_submit",
    ...overrides,
  };
}

let startMutate: ReturnType<typeof vi.fn>;
let submitMutate: ReturnType<typeof vi.fn>;

beforeEach(() => {
  vi.clearAllMocks();
  // See question-editors.test.tsx: without `shouldAdvanceTime`, Testing Library's waitFor polls on
  // a frozen clock and every assertion that waits for the debounce hangs to the test timeout.
  vi.useFakeTimers({ shouldAdvanceTime: true });
  startMutate = vi.fn();
  submitMutate = vi.fn();
  useStartAttempt.mockReturnValue({ mutate: startMutate, isPending: false, error: null });
  useSubmitAttempt.mockReturnValue({ mutate: submitMutate, isPending: false, error: null });
  useAttempt.mockReturnValue({ data: undefined, isPending: false, isError: false, refetch: vi.fn() });
  saveAnswer.mockResolvedValue(undefined);
});

function render() {
  const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
  renderWithI18n(<QuizPlayer assessmentId="a1" />);

  return { user };
}

describe("starting and resuming", () => {
  it("starts an attempt through the API", async () => {
    const { user } = render();

    await user.click(screen.getByRole("button", { name: "Start the quiz" }));

    expect(startMutate).toHaveBeenCalledWith("a1", expect.anything());
  });

  it("surfaces an attempt-limit refusal verbatim", async () => {
    useStartAttempt.mockReturnValue({
      mutate: startMutate,
      isPending: false,
      error: new Error("You have used all available attempts for this assessment."),
    });
    render();

    expect(await screen.findByRole("alert")).toHaveTextContent("You have used all available attempts");
  });

  it("seeds the inputs from answers already saved on the server", async () => {
    const resumed = attempt({
      questions: [
        item("q1", "First", { response: { option_ids: ["q1-b"] }, is_correct: null, points_awarded: null, feedback: null }),
        item("q2", "Second"),
      ],
    });
    useAttempt.mockReturnValue({ data: resumed, isPending: false, isError: false, refetch: vi.fn() });

    const { user } = render();
    await user.click(screen.getByRole("button", { name: "Start the quiz" }));
    completeStart(startMutate);

    // Resuming must restore what the learner already answered, not present a blank paper.
    await waitFor(() => expect(screen.getByRole("radio", { name: "Beta" })).toBeChecked());
  });
});

describe("answering and autosave", () => {
  async function startedPlayer() {
    useAttempt.mockReturnValue({ data: attempt(), isPending: false, isError: false, refetch: vi.fn() });
    const { user } = render();
    await user.click(screen.getByRole("button", { name: "Start the quiz" }));
    completeStart(startMutate);
    await screen.findByText("Question 1 of 2");

    return { user };
  }

  it("debounces the save and writes through the real endpoint", async () => {
    const { user } = await startedPlayer();

    await user.click(screen.getByRole("radio", { name: "Alpha" }));
    expect(screen.getByText("Unsaved changes")).toBeInTheDocument();
    expect(saveAnswer).not.toHaveBeenCalled();

    await advanceBy(800);

    await waitFor(() =>
      expect(saveAnswer).toHaveBeenCalledWith("att1", "q1", { option_ids: ["q1-a"] }),
    );
    expect(await screen.findByText("Saved")).toBeInTheDocument();
  });

  it("coalesces rapid changes into a single request", async () => {
    const { user } = await startedPlayer();

    await user.click(screen.getByRole("radio", { name: "Alpha" }));
    await user.click(screen.getByRole("radio", { name: "Beta" }));
    await advanceBy(800);

    // One request carrying the latest value — not one per click.
    await waitFor(() => expect(saveAnswer).toHaveBeenCalledTimes(1));
    expect(saveAnswer).toHaveBeenCalledWith("att1", "q1", { option_ids: ["q1-b"] });
  });

  it("shows a save failure after retries are exhausted", async () => {
    saveAnswer.mockRejectedValue(Object.assign(new Error("Server error."), { status: 500 }));
    const { user } = await startedPlayer();

    await user.click(screen.getByRole("radio", { name: "Alpha" }));
    await advanceBy(800);
    // Two automatic retries at 3s each before the learner is told.
    await advanceBy(3200);
    await advanceBy(3200);

    await waitFor(() => expect(screen.getByText("Server error.")).toBeInTheDocument());
    expect(saveAnswer).toHaveBeenCalledTimes(3);
  });

  it("does not retry a rejection the server will repeat", async () => {
    saveAnswer.mockRejectedValue(Object.assign(new Error("This attempt is no longer accepting answers."), { status: 422 }));
    const { user } = await startedPlayer();

    await user.click(screen.getByRole("radio", { name: "Alpha" }));
    await advanceBy(800);

    await waitFor(() => expect(screen.getByText(/no longer accepting answers/)).toBeInTheDocument());
    expect(saveAnswer).toHaveBeenCalledTimes(1);
  });
});

describe("navigation", () => {
  async function startedPlayer() {
    useAttempt.mockReturnValue({ data: attempt(), isPending: false, isError: false, refetch: vi.fn() });
    const { user } = render();
    await user.click(screen.getByRole("button", { name: "Start the quiz" }));
    completeStart(startMutate);
    await screen.findByText("Question 1 of 2");

    return { user };
  }

  it("moves with Previous and Next", async () => {
    const { user } = await startedPlayer();

    expect(screen.getByRole("button", { name: "Previous" })).toBeDisabled();
    await user.click(screen.getByRole("button", { name: "Next" }));
    expect(screen.getByText("Question 2 of 2")).toBeInTheDocument();
  });

  it("pages with arrow keys", async () => {
    const { user } = await startedPlayer();

    await user.keyboard("{ArrowRight}");
    expect(screen.getByText("Question 2 of 2")).toBeInTheDocument();

    await user.keyboard("{ArrowLeft}");
    expect(screen.getByText("Question 1 of 2")).toBeInTheDocument();
  });

  it("jumps from the palette, announcing answered state in the label", async () => {
    const { user } = await startedPlayer();

    await user.click(screen.getByRole("button", { name: /Go to question 2.*not answered/ }));

    expect(screen.getByText("Question 2 of 2")).toBeInTheDocument();
  });

  it("filters the palette to unanswered and flagged questions", async () => {
    const { user } = await startedPlayer();

    await user.click(screen.getByRole("button", { name: "Flagged (0)" }));
    expect(screen.getByText("No questions flagged.")).toBeInTheDocument();

    await user.click(screen.getByRole("button", { name: "Flag for review" }));
    expect(screen.getByRole("button", { name: "Flagged (1)" })).toBeInTheDocument();
  });

  it("reports completion percentage", async () => {
    const { user } = await startedPlayer();

    expect(screen.getByText("0% complete")).toBeInTheDocument();
    await user.click(screen.getByRole("radio", { name: "Alpha" }));
    expect(screen.getByText("50% complete")).toBeInTheDocument();
  });
});

describe("submission", () => {
  it("confirms with unanswered and flagged counts before submitting", async () => {
    useAttempt.mockReturnValue({ data: attempt(), isPending: false, isError: false, refetch: vi.fn() });
    const { user } = render();
    await user.click(screen.getByRole("button", { name: "Start the quiz" }));
    completeStart(startMutate);

    await user.click(await screen.findByRole("button", { name: "Submit" }));

    const dialog = await screen.findByRole("dialog");
    expect(within(dialog).getByText(/2 unanswered, 0 flagged/)).toBeInTheDocument();
    expect(submitMutate).not.toHaveBeenCalled();

    await user.click(within(dialog).getByRole("button", { name: "Submit" }));
    await waitFor(() => expect(submitMutate).toHaveBeenCalledWith("att1"));
  });

  it("flushes a pending answer before submitting so nothing is lost", async () => {
    useAttempt.mockReturnValue({ data: attempt(), isPending: false, isError: false, refetch: vi.fn() });
    const { user } = render();
    await user.click(screen.getByRole("button", { name: "Start the quiz" }));
    completeStart(startMutate);

    await user.click(await screen.findByRole("radio", { name: "Alpha" }));
    await user.click(screen.getByRole("button", { name: "Submit" }));
    await user.click(within(await screen.findByRole("dialog")).getByRole("button", { name: "Submit" }));

    // The answer must reach the server before the attempt closes.
    await waitFor(() => expect(saveAnswer).toHaveBeenCalledWith("att1", "q1", { option_ids: ["q1-a"] }));
    expect(submitMutate).toHaveBeenCalled();
  });
});

describe("results and feedback modes", () => {
  function renderFinished(a: Attempt) {
    useAttempt.mockReturnValue({ data: a, isPending: false, isError: false, refetch: vi.fn() });
    const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
    renderWithI18n(<QuizPlayer assessmentId="a1" />);

    return { user };
  }

  async function openFinished(a: Attempt) {
    const { user } = renderFinished(a);
    await user.click(screen.getByRole("button", { name: "Start the quiz" }));
    completeStart(startMutate, a.id);
  }

  it("shows the server's score and pass state", async () => {
    await openFinished(
      attempt({
        status: "graded",
        result: { score: 1, max_score: 2, percentage: 50, passed: true },
        questions: [
          item("q1", "First", { response: { option_ids: ["q1-a"] }, is_correct: true, points_awarded: 1, feedback: null }),
        ],
      }),
    );

    expect(await screen.findByText("50%")).toBeInTheDocument();
    expect(screen.getByText("Passed")).toBeInTheDocument();
    expect(screen.getByText("Correct")).toBeInTheDocument();
  });

  it("hides all correctness when the backend withheld it", async () => {
    await openFinished(
      attempt({
        status: "graded",
        feedback_mode: "never",
        result: { score: 1, max_score: 2, percentage: 50, passed: false },
        // is_correct null = not revealed. It must never render as "Incorrect".
        questions: [
          item("q1", "First", { response: { option_ids: ["q1-a"] }, is_correct: null, points_awarded: null, feedback: null }),
        ],
      }),
    );

    expect(await screen.findByText("Answers are not shown for this quiz.")).toBeInTheDocument();
    expect(screen.queryByText("Correct")).not.toBeInTheDocument();
    expect(screen.queryByText("Incorrect")).not.toBeInTheDocument();
    // The score is still shown — only the key is withheld.
    expect(screen.getByText("50%")).toBeInTheDocument();
  });

  it("explains an expired attempt", async () => {
    await openFinished(
      attempt({
        status: "expired",
        result: { score: 0, max_score: 2, percentage: 0, passed: false },
        questions: [item("q1", "First", { response: null, is_correct: false, points_awarded: 0, feedback: null })],
      }),
    );

    expect(await screen.findByText(/Your time ran out/)).toBeInTheDocument();
  });

  it("states that attempt history is unavailable rather than inventing it", async () => {
    await openFinished(attempt({ status: "graded", result: { score: 0, max_score: 1, percentage: 0, passed: null } }));

    expect(await screen.findByText("Previous attempts are not available yet.")).toBeInTheDocument();
  });
});
