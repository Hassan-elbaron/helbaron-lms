import { describe, expect, it } from "vitest";
import {
  allowsMultipleCorrect,
  blankIndices,
  buildBlanksPayload,
  defaultQuestion,
  fixedOptionCount,
  isMultiPart,
  previewIsCorrect,
  previewNormalized,
  renumberBlanks,
  usesOptions,
  usesTextMatching,
  validateQuestion,
} from "@/lib/assessment/question-model";
import type { Question } from "@/lib/assessment/types";

/**
 * MIRROR-DRIFT GUARDS.
 *
 * Each block below pins a frontend helper against the SAME cases its PHP counterpart is tested
 * with, so a backend rule change that is not reflected here fails the suite instead of silently
 * showing an author the wrong thing.
 *
 *   previewNormalized  ← tests/Unit/Assessment/GradingTest.php (Arabic, digits, case, whitespace)
 *   validateQuestion   ← app/Domains/Assessment/Services/QuestionShapeGuard.php
 *   capability flags   ← app/Domains/Assessment/Enums/QuestionType.php
 */

function question(overrides: Partial<Question> = {}): Question {
  return {
    id: "q1",
    type: "single_choice",
    prompt: "<p>Q</p>",
    config: null,
    explanation: null,
    hint: null,
    points: 1,
    negative_points: 0,
    difficulty: null,
    position: 0,
    options: [],
    ...overrides,
  };
}

describe("capability flags mirror QuestionType", () => {
  it("classifies every V1 type the same way the enum does", () => {
    expect(usesOptions("single_choice")).toBe(true);
    expect(usesOptions("multiple_choice")).toBe(true);
    expect(usesOptions("true_false")).toBe(true);
    expect(usesOptions("short_answer")).toBe(false);
    expect(usesOptions("fill_in_blank")).toBe(false);

    expect(usesTextMatching("short_answer")).toBe(true);
    expect(usesTextMatching("fill_in_blank")).toBe(true);

    // Only single_choice and true_false are single-answer.
    expect(allowsMultipleCorrect("single_choice")).toBe(false);
    expect(allowsMultipleCorrect("true_false")).toBe(false);
    expect(allowsMultipleCorrect("multiple_choice")).toBe(true);

    expect(isMultiPart("fill_in_blank")).toBe(true);
    expect(isMultiPart("short_answer")).toBe(false);

    expect(fixedOptionCount("true_false")).toBe(2);
    expect(fixedOptionCount("single_choice")).toBeNull();
  });
});

describe("previewNormalized mirrors AnswerNormalizer", () => {
  it("folds case and collapses whitespace", () => {
    expect(previewNormalized("  PHOTO   synthesis ")).toBe("photo synthesis");
  });

  it("keeps case when the author asks for it", () => {
    expect(previewNormalized("DNA", true)).toBe("DNA");
    expect(previewNormalized("DNA", false)).toBe("dna");
  });

  it("treats Arabic orthographic variants as the same answer", () => {
    // The exact case from GradingTest: a bare alef must match a hamza-carrying one.
    expect(previewNormalized("إجابة")).toBe(previewNormalized("اجابة"));
  });

  it("strips harakat", () => {
    expect(previewNormalized("مَدْرَسَة")).toBe(previewNormalized("مدرسه"));
  });

  it("maps Arabic-Indic digits onto ASCII", () => {
    expect(previewNormalized("٤٢")).toBe("42");
  });

  it("leaves Arabic alone when normalisation is switched off", () => {
    expect(previewNormalized("إجابة", false, false)).not.toBe(previewNormalized("اجابة", false, false));
  });
});

describe("validateQuestion mirrors QuestionShapeGuard", () => {
  const keys = (q: Parameters<typeof validateQuestion>[0]) => validateQuestion(q).map((i) => i.key);

  it("requires a prompt and positive points", () => {
    expect(keys({ type: "short_answer", prompt: "", points: 1 })).toContain("validation.promptRequired");
    expect(keys({ type: "short_answer", prompt: "<p>Q</p>", points: 0 })).toContain("validation.pointsPositive");
  });

  it("requires at least two options and one correct for choice types", () => {
    expect(
      keys({ type: "single_choice", prompt: "<p>Q</p>", points: 1, options: [{ label: "A" }] }),
    ).toContain("validation.needsTwoOptions");

    expect(
      keys({
        type: "single_choice",
        prompt: "<p>Q</p>",
        points: 1,
        options: [{ label: "A" }, { label: "B" }],
      }),
    ).toContain("validation.needsCorrect");
  });

  it("rejects two correct answers on a single-answer type", () => {
    const issues = keys({
      type: "single_choice",
      prompt: "<p>Q</p>",
      points: 1,
      options: [
        { label: "A", is_correct: true },
        { label: "B", is_correct: true },
      ],
    });

    expect(issues).toContain("validation.singleCorrectOnly");
  });

  it("allows two correct answers on multiple choice", () => {
    expect(
      keys({
        type: "multiple_choice",
        prompt: "<p>Q</p>",
        points: 1,
        options: [
          { label: "A", is_correct: true },
          { label: "B", is_correct: true },
        ],
      }),
    ).toEqual([]);
  });

  it("requires an accepted answer for text-matched types", () => {
    expect(
      keys({
        type: "short_answer",
        prompt: "<p>Q</p>",
        points: 1,
        options: [{ value: "photosynthesis", is_correct: false }],
      }),
    ).toContain("validation.needsAccepted");

    // Whitespace-only is not an accepted answer.
    expect(
      keys({ type: "short_answer", prompt: "<p>Q</p>", points: 1, options: [{ value: "   ", is_correct: true }] }),
    ).toContain("validation.needsAccepted");
  });
});

describe("blank indexes", () => {
  it("lists distinct indexes ascending", () => {
    expect(blankIndices([{ group_index: 2 }, { group_index: 0 }, { group_index: 2 }])).toEqual([0, 2]);
  });

  it("closes gaps so indexes stay contiguous from zero", () => {
    // Removing the middle blank must shift the last one down; the backend rejects a gap because a
    // missing index means the learner sees a blank the key cannot score.
    const renumbered = renumberBlanks([{ group_index: 0 }, { group_index: 2 }]);

    expect(renumbered.map((o) => o.group_index)).toEqual([0, 1]);
  });

  it("never produces duplicate indexes when renumbering", () => {
    const renumbered = renumberBlanks([{ group_index: 5 }, { group_index: 5 }, { group_index: 9 }]);
    const indexes = renumbered.map((o) => o.group_index);

    // Two answers for the same blank stay on the same blank — deduping them would delete an
    // accepted answer.
    expect(indexes).toEqual([0, 0, 1]);
  });
});

describe("buildBlanksPayload — regression for the integer-key defect", () => {
  it("emits stringified integer keys", () => {
    const payload = buildBlanksPayload(
      new Map([
        [0, "carbon"],
        [1, "dioxide"],
      ]),
    );

    expect(payload).toEqual({ "0": "carbon", "1": "dioxide" });
  });

  it("drops non-integer and negative indexes", () => {
    // PHP casts a non-numeric key to 0, so a payload with a junk key would silently answer blank 0.
    // AssessmentAnswer::blanks() drops those server-side; this stops the client generating them.
    const payload = buildBlanksPayload(
      new Map([
        [0, "kept"],
        [1.5, "dropped"],
        [-1, "dropped"],
      ]),
    );

    expect(payload).toEqual({ "0": "kept" });
  });
});

describe("defaultQuestion", () => {
  it("ships true/false with both options already correct-marked", () => {
    const payload = defaultQuestion("true_false", "True", "False");

    expect(payload.options).toHaveLength(2);
    expect(payload.options?.[0]).toMatchObject({ label: "True", is_correct: true });
    // A brand-new question of every type must already satisfy the shape guard's option-count rule.
    expect(validateQuestion({ ...payload, prompt: "<p>Q</p>" })).toEqual([]);
  });

  it("gives choice types two blank options and text types one accepted answer", () => {
    expect(defaultQuestion("single_choice", "T", "F").options).toHaveLength(2);
    expect(defaultQuestion("short_answer", "T", "F").options).toHaveLength(1);
    expect(defaultQuestion("fill_in_blank", "T", "F").options?.[0]?.group_index).toBe(0);
  });
});

describe("previewIsCorrect", () => {
  it("requires the exact correct set for choice questions", () => {
    const q = question({
      type: "multiple_choice",
      options: [
        { id: "a", label: "A", value: null, is_correct: true, group_index: 0, feedback: null, position: 0 },
        { id: "b", label: "B", value: null, is_correct: true, group_index: 0, feedback: null, position: 1 },
        { id: "c", label: "C", value: null, is_correct: false, group_index: 0, feedback: null, position: 2 },
      ],
    });

    expect(previewIsCorrect(q, { option_ids: ["a", "b"] })).toBe(true);
    expect(previewIsCorrect(q, { option_ids: ["a"] })).toBe(false);
    expect(previewIsCorrect(q, { option_ids: ["a", "b", "c"] })).toBe(false);
    expect(previewIsCorrect(q, null)).toBe(false);
  });

  it("matches short answers through the normaliser", () => {
    const q = question({
      type: "short_answer",
      options: [
        { id: "o1", label: null, value: "إجابة", is_correct: true, group_index: 0, feedback: null, position: 0 },
      ],
    });

    expect(previewIsCorrect(q, { text: "اجابة" })).toBe(true);
    expect(previewIsCorrect(q, { text: "" })).toBe(false);
  });

  it("requires every blank, counting from the answer key not the response", () => {
    const q = question({
      type: "fill_in_blank",
      options: [
        { id: "o1", label: null, value: "carbon", is_correct: true, group_index: 0, feedback: null, position: 0 },
        { id: "o2", label: null, value: "dioxide", is_correct: true, group_index: 1, feedback: null, position: 1 },
      ],
    });

    expect(previewIsCorrect(q, { blanks: { "0": "carbon", "1": "dioxide" } })).toBe(true);
    // Omitting a blank must not shrink the denominator and pass.
    expect(previewIsCorrect(q, { blanks: { "0": "carbon" } })).toBe(false);
    expect(previewIsCorrect(q, { blanks: {} })).toBe(false);
  });

  it("is never correct when the question has no answer key", () => {
    expect(previewIsCorrect(question({ options: [] }), { option_ids: ["x"] })).toBe(false);
  });
});
