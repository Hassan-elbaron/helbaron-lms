import { describe, expect, it, vi } from "vitest";
import { screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";
import { AssessmentPreview } from "@/components/authoring/assessment/assessment-preview";
import type { Assessment, Question, QuestionOption } from "@/lib/assessment/types";

function option(overrides: Partial<QuestionOption> = {}): QuestionOption {
  return {
    id: "o1",
    label: null,
    value: null,
    is_correct: false,
    group_index: 0,
    feedback: null,
    position: 0,
    ...overrides,
  };
}

function choiceQuestion(id: string, prompt: string, correctId: string): Question {
  return {
    id,
    type: "single_choice",
    prompt: `<p>${prompt}</p>`,
    config: null,
    explanation: "Because.",
    hint: null,
    points: 1,
    negative_points: 0,
    difficulty: null,
    position: 0,
    options: [
      option({ id: correctId, label: "Right", is_correct: true }),
      option({ id: `${id}-wrong`, label: "Wrong", position: 1 }),
    ],
  };
}

function assessment(questions: Question[], passingScore: number | null = 50): Assessment {
  return {
    id: "a1",
    title: "Module quiz",
    description: null,
    scope: "lesson",
    status: "draft",
    version: 1,
    settings: {
      passing_score: passingScore,
      negative_marking: false,
      max_attempts: null,
      time_limit_seconds: null,
      shuffle_questions: false,
      shuffle_options: false,
      questions_per_attempt: null,
      feedback_mode: "after_submit",
    },
    question_count: questions.length,
    questions,
  };
}

function open(a: Assessment) {
  const user = userEvent.setup();
  renderWithI18n(<AssessmentPreview assessment={a} open onOpenChange={vi.fn()} />);

  return { user };
}

describe("AssessmentPreview", () => {
  it("labels itself a preview and says nothing is saved", () => {
    open(assessment([choiceQuestion("q1", "First", "c1")]));

    expect(screen.getByText("Preview")).toBeInTheDocument();
    // An instructor must never be unsure whether they just consumed a real attempt.
    expect(screen.getByText("This is a preview. Nothing is saved and no attempt is recorded.")).toBeInTheDocument();
  });

  it("never exposes the answer key before submission", () => {
    open(assessment([choiceQuestion("q1", "First", "c1")]));

    // The preview strips the key into a LearnerQuestion, so the presenter has nothing to leak —
    // no correctness marker and no explanation until the preview is submitted.
    expect(screen.queryByText("Correct")).not.toBeInTheDocument();
    expect(screen.queryByText("Because.")).not.toBeInTheDocument();
  });

  it("navigates between questions and tracks progress", async () => {
    const { user } = open(
      assessment([choiceQuestion("q1", "First", "c1"), { ...choiceQuestion("q2", "Second", "c2"), position: 1 }]),
    );

    expect(screen.getByText("Question 1 of 2")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Previous" })).toBeDisabled();

    await user.click(screen.getByRole("button", { name: "Next" }));
    expect(screen.getByText("Question 2 of 2")).toBeInTheDocument();

    await user.click(screen.getByRole("button", { name: "Previous" }));
    expect(screen.getByText("Question 1 of 2")).toBeInTheDocument();
  });

  it("jumps to a question from the navigator", async () => {
    const { user } = open(
      assessment([choiceQuestion("q1", "First", "c1"), { ...choiceQuestion("q2", "Second", "c2"), position: 1 }]),
    );

    // The accessible name carries the answered state too — that announcement is the point of the
    // navigator, so the query asserts the whole label rather than matching loosely.
    await user.click(screen.getByRole("button", { name: "Go to question 2, not answered" }));

    expect(screen.getByText("Question 2 of 2")).toBeInTheDocument();
  });

  it("flags and unflags a question", async () => {
    const { user } = open(assessment([choiceQuestion("q1", "First", "c1")]));

    const flag = screen.getByRole("button", { name: "Flag for review" });
    expect(flag).toHaveAttribute("aria-pressed", "false");

    await user.click(flag);
    expect(screen.getByRole("button", { name: "Remove flag" })).toHaveAttribute("aria-pressed", "true");
  });

  it("asks for confirmation when questions are unanswered", async () => {
    const { user } = open(assessment([choiceQuestion("q1", "First", "c1")]));

    await user.click(screen.getByRole("button", { name: "Submit" }));

    const dialog = await screen.findByRole("dialog", { name: "Submit this preview?" });
    expect(within(dialog).getByText(/1 question\(s\) are still unanswered/)).toBeInTheDocument();
  });

  it("submits without confirmation once everything is answered, and scores the result", async () => {
    const { user } = open(assessment([choiceQuestion("q1", "First", "c1")]));

    // The label wraps the input, so the option text is the radio's accessible name.
    await user.click(screen.getByRole("radio", { name: "Right" }));
    await user.click(screen.getByRole("button", { name: "Submit" }));

    await waitFor(() => expect(screen.getByText("Preview result")).toBeInTheDocument());
    expect(screen.getByText("1 of 1 points")).toBeInTheDocument();
    expect(screen.getByText("Would pass")).toBeInTheDocument();
    // The score is an author-facing approximation; the real one comes from the server.
    expect(
      screen.getByText("Scored in the browser for preview only. Real attempts are graded on the server."),
    ).toBeInTheDocument();
  });

  it("reports a failing score against the pass mark", async () => {
    const { user } = open(assessment([choiceQuestion("q1", "First", "c1")], 100));

    // The wrong option is still an answer, so nothing is unanswered and there is no confirmation
    // step — the preview goes straight to the result.
    await user.click(screen.getAllByRole("radio")[1]);
    await user.click(screen.getByRole("button", { name: "Submit" }));

    await waitFor(() => expect(screen.getByText("Would not pass")).toBeInTheDocument());
    expect(screen.queryByRole("button", { name: "Submit anyway" })).not.toBeInTheDocument();
  });

  it("says nothing about passing when the assessment has no pass mark", async () => {
    const { user } = open(assessment([choiceQuestion("q1", "First", "c1")], null));

    await user.click(screen.getAllByRole("radio")[0]);
    await user.click(screen.getByRole("button", { name: "Submit" }));

    await waitFor(() => expect(screen.getByText("No pass mark set")).toBeInTheDocument());
  });

  it("restarts cleanly", async () => {
    const { user } = open(assessment([choiceQuestion("q1", "First", "c1")]));

    await user.click(screen.getAllByRole("radio")[0]);
    await user.click(screen.getByRole("button", { name: "Submit" }));
    await screen.findByText("Preview result");

    await user.click(screen.getByRole("button", { name: "Restart preview" }));

    // Back to the paper, with the previous answers cleared.
    expect(screen.getByText("Question 1 of 1")).toBeInTheDocument();
    expect(screen.getAllByRole("radio")[0]).not.toBeChecked();
  });

  it("tells the author when there is nothing to preview", () => {
    open(assessment([]));

    expect(screen.getByText("Add a question to preview this assessment.")).toBeInTheDocument();
  });
});
