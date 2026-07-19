import { beforeEach, describe, expect, it, vi } from "vitest";
import { screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { renderWithI18n } from "../render";
import { QuestionEditor } from "@/components/authoring/assessment/question-editor";
import type { Question, QuestionOption, QuestionType } from "@/lib/assessment/types";

/**
 * Editor behaviour for all five V1 types. These assert what the editor SENDS, since that payload is
 * what QuestionShapeGuard will judge — rendering is secondary to producing a saveable question.
 */

function option(overrides: Partial<QuestionOption> = {}): QuestionOption {
  return {
    id: `o${Math.random().toString(36).slice(2, 8)}`,
    label: null,
    value: null,
    is_correct: false,
    group_index: 0,
    feedback: null,
    position: 0,
    ...overrides,
  };
}

function question(type: QuestionType, options: QuestionOption[] = []): Question {
  return {
    id: "q1",
    type,
    prompt: "<p>Existing prompt</p>",
    config: null,
    explanation: null,
    hint: null,
    points: 1,
    negative_points: 0,
    difficulty: "medium",
    position: 0,
    options,
  };
}

/** The editor autosaves on a 700ms debounce; fake timers keep the tests fast and deterministic. */
async function renderEditor(q: Question) {
  const onSave = vi.fn();
  const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
  renderWithI18n(<QuestionEditor question={q} onSave={onSave} onValidityChange={vi.fn()} />);

  return { onSave, user };
}

async function lastSave(onSave: ReturnType<typeof vi.fn>) {
  await vi.advanceTimersByTimeAsync(800);
  await waitFor(() => expect(onSave).toHaveBeenCalled());

  return onSave.mock.calls[onSave.mock.calls.length - 1][0];
}

beforeEach(() => {
  // `shouldAdvanceTime` matters: Testing Library only auto-advances Jest's fake clock, so with a
  // plain Vitest fake clock every `waitFor` / `findBy*` polls on a timer that never fires and hangs
  // until the 5s test timeout. Letting the clock also tick in real time keeps those utilities
  // working while `advanceTimersByTimeAsync` still jumps the 700ms debounce deterministically.
  vi.useFakeTimers({ shouldAdvanceTime: true });
});

describe("Single choice", () => {
  it("clears the previous answer when a new one is marked correct", async () => {
    const q = question("single_choice", [
      option({ id: "a", label: "A", is_correct: true }),
      option({ id: "b", label: "B", position: 1 }),
    ]);
    const { onSave, user } = await renderEditor(q);

    const radios = screen.getAllByRole("radio");
    await user.click(radios[1]);

    const payload = await lastSave(onSave);
    // Exactly one correct — two would make the question unanswerable and the guard would reject it.
    expect(payload.options.filter((o: { is_correct?: boolean }) => o.is_correct)).toHaveLength(1);
    expect(payload.options[1].is_correct).toBe(true);
  });

  it("refuses to delete below two options", async () => {
    const q = question("single_choice", [
      option({ id: "a", label: "A", is_correct: true }),
      option({ id: "b", label: "B", position: 1 }),
    ]);
    await renderEditor(q);

    for (const button of screen.getAllByRole("button", { name: "Remove option" })) {
      expect(button).toBeDisabled();
    }
  });

  it("reorders options and persists the new order", async () => {
    const q = question("single_choice", [
      option({ id: "a", label: "First", is_correct: true }),
      option({ id: "b", label: "Second", position: 1 }),
    ]);
    const { onSave, user } = await renderEditor(q);

    await user.click(screen.getAllByRole("button", { name: "Move option down" })[0]);

    const payload = await lastSave(onSave);
    // Order is persisted as `position` by array index, so the array order IS the answer.
    expect(payload.options[0].label).toBe("Second");
    expect(payload.options[1].label).toBe("First");
  });
});

describe("Multiple choice", () => {
  it("keeps several answers correct and exposes partial credit", async () => {
    const q = question("multiple_choice", [
      option({ id: "a", label: "A", is_correct: true }),
      option({ id: "b", label: "B", position: 1 }),
    ]);
    const { onSave, user } = await renderEditor(q);

    await user.click(screen.getAllByRole("checkbox")[1]);
    let payload = await lastSave(onSave);
    expect(payload.options.filter((o: { is_correct?: boolean }) => o.is_correct)).toHaveLength(2);

    await user.click(screen.getByLabelText("Award partial credit"));
    payload = await lastSave(onSave);
    // partial_credit is a real backend config key read by MultipleChoiceGrader.
    expect(payload.config.partial_credit).toBe(true);
  });
});

describe("True / False", () => {
  it("fixes both options and allows exactly one correct", async () => {
    const q = question("true_false", [
      option({ id: "t", label: "True", is_correct: true }),
      option({ id: "f", label: "False", position: 1 }),
    ]);
    const { onSave, user } = await renderEditor(q);

    // Neither option may be removed and neither label may be edited — the type mandates both.
    expect(screen.queryByRole("button", { name: "Remove option" })).not.toBeInTheDocument();
    for (const input of screen.getAllByDisplayValue(/True|False/)) {
      expect(input).toHaveAttribute("readonly");
    }

    await user.click(screen.getAllByRole("radio")[1]);
    const payload = await lastSave(onSave);
    expect(payload.options.filter((o: { is_correct?: boolean }) => o.is_correct)).toHaveLength(1);
  });
});

describe("Short answer", () => {
  it("accepts several answers and toggles the normalisation controls", async () => {
    const q = question("short_answer", [option({ id: "o1", value: "photosynthesis", is_correct: true })]);
    const { onSave, user } = await renderEditor(q);

    await user.click(screen.getByRole("button", { name: "Add an accepted answer" }));
    let payload = await lastSave(onSave);
    expect(payload.options).toHaveLength(2);

    await user.click(screen.getByLabelText("Case sensitive"));
    payload = await lastSave(onSave);
    expect(payload.config.case_sensitive).toBe(true);

    // Arabic normalisation defaults ON, so the toggle switches it off.
    await user.click(screen.getByLabelText("Normalise Arabic spelling"));
    payload = await lastSave(onSave);
    expect(payload.config.normalize_arabic).toBe(false);
  });

  it("shows the author how an answer will be normalised", async () => {
    const q = question("short_answer", [option({ id: "o1", value: "  Photosynthesis  ", is_correct: true })]);
    await renderEditor(q);

    // The preview exists so surprises surface at authoring time, not in a learner's score.
    expect(screen.getByText(/Matched as: photosynthesis/)).toBeInTheDocument();
  });
});

describe("Fill in the blank", () => {
  it("numbers blanks from zero and adds the next index", async () => {
    const q = question("fill_in_blank", [option({ id: "o1", value: "carbon", is_correct: true, group_index: 0 })]);
    const { onSave, user } = await renderEditor(q);

    expect(screen.getByText("Blank 1")).toBeInTheDocument();

    await user.click(screen.getByRole("button", { name: "Add a blank" }));
    const payload = await lastSave(onSave);

    const indexes = payload.options.map((o: { group_index?: number }) => o.group_index);
    // Contiguous from 0 — the backend rejects a gap.
    expect(indexes).toEqual([0, 1]);
  });

  it("closes the gap when a middle blank is removed", async () => {
    const q = question("fill_in_blank", [
      option({ id: "o1", value: "one", is_correct: true, group_index: 0 }),
      option({ id: "o2", value: "two", is_correct: true, group_index: 1, position: 1 }),
      option({ id: "o3", value: "three", is_correct: true, group_index: 2, position: 2 }),
    ]);
    const { onSave, user } = await renderEditor(q);

    await user.click(screen.getAllByRole("button", { name: "Remove blank" })[1]);
    const payload = await lastSave(onSave);

    const indexes = payload.options.map((o: { group_index?: number }) => o.group_index);
    expect(indexes).toEqual([0, 1]);
    // The surviving answers are the right ones — renumbering must not reassign values.
    expect(payload.options.map((o: { value?: string }) => o.value)).toEqual(["one", "three"]);
  });

  it("never emits a non-numeric blank index", async () => {
    const q = question("fill_in_blank", [option({ id: "o1", value: "carbon", is_correct: true, group_index: 0 })]);
    const { onSave, user } = await renderEditor(q);

    await user.click(screen.getByRole("button", { name: "Add a blank" }));
    const payload = await lastSave(onSave);

    for (const o of payload.options) {
      expect(Number.isInteger(o.group_index)).toBe(true);
      expect(o.group_index).toBeGreaterThanOrEqual(0);
    }
  });
});

describe("Shared fields", () => {
  it("sends the wrong-answer penalty as a positive magnitude", async () => {
    const { onSave, user } = await renderEditor(question("single_choice", [
      option({ id: "a", label: "A", is_correct: true }),
      option({ id: "b", label: "B", position: 1 }),
    ]));

    const penalty = screen.getByLabelText(/Penalty/);
    await user.clear(penalty);
    await user.type(penalty, "2");

    const payload = await lastSave(onSave);
    // The scorer applies the sign; a negative here would double-negate.
    expect(payload.negative_points).toBe(2);
  });

  it("rebuilds the answer key when the type changes", async () => {
    const q = question("single_choice", [
      option({ id: "a", label: "A", is_correct: true }),
      option({ id: "b", label: "B", position: 1 }),
    ]);
    const { onSave, user } = await renderEditor(q);

    await user.click(screen.getByRole("combobox", { name: /Question type/ }));
    await user.click(await screen.findByRole("option", { name: "Short answer" }));

    const payload = await lastSave(onSave);
    expect(payload.type).toBe("short_answer");
    // Carrying choices into a text question would leave a key the new type cannot interpret.
    expect(payload.options.every((o: { label?: string | null }) => !o.label)).toBe(true);
    expect(payload.prompt).toBe("<p>Existing prompt</p>");
  });

  it("states plainly that attachments are unavailable rather than hiding the gap", async () => {
    await renderEditor(question("short_answer", [option({ id: "o1", value: "x", is_correct: true })]));

    expect(
      screen.getByText("Attachments are not available yet — questions have no file storage on the server."),
    ).toBeInTheDocument();
  });

  it("surfaces validation inline without blocking the author", async () => {
    const q = question("single_choice", [option({ id: "a", label: "A" }), option({ id: "b", label: "B", position: 1 })]);
    await renderEditor(q);

    const alert = await screen.findByRole("alert");
    expect(within(alert).getByText("Mark which option is correct.")).toBeInTheDocument();
  });
});
