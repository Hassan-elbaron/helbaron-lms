/**
 * Client-side question helpers: defaults, capability flags, and UX validation.
 *
 * ┌─────────────────────────────────────────────────────────────────────────────────────────────┐
 * │ MIRROR-DRIFT WARNING                                                                        │
 * │                                                                                             │
 * │ Three things in this file deliberately restate backend behaviour:                           │
 * │                                                                                             │
 * │   1. the capability flags  ← App\Domains\Assessment\Enums\QuestionType                      │
 * │   2. validateQuestion()    ← App\Domains\Assessment\Services\QuestionShapeGuard             │
 * │   3. previewNormalized()   ← App\Domains\Assessment\Grading\AnswerNormalizer                │
 * │                                                                                             │
 * │ They exist so an author sees a problem while typing rather than after saving. None of them  │
 * │ decides anything: the backend re-validates every write and does all real grading. If the    │
 * │ two ever disagree the BACKEND IS RIGHT and this file is the bug.                            │
 * │                                                                                             │
 * │ Everything mirrored lives HERE and nowhere else, so updating a backend rule means changing  │
 * │ exactly one frontend file. `tests/assessment/question-model.test.ts` pins each mirrored rule │
 * │ against the same cases as its PHP counterpart's test, so drift fails the suite rather than  │
 * │ silently misleading an author.                                                              │
 * │                                                                                             │
 * │ Do NOT add scoring here. There is one grading engine and it is on the server.               │
 * └─────────────────────────────────────────────────────────────────────────────────────────────┘
 */
import type { AnswerResponse, OptionInput, Question, QuestionInput, QuestionType } from "./types";

export const QUESTION_TYPES: readonly QuestionType[] = [
  "single_choice",
  "multiple_choice",
  "true_false",
  "short_answer",
  "fill_in_blank",
];

export function usesOptions(type: QuestionType): boolean {
  return type === "single_choice" || type === "multiple_choice" || type === "true_false";
}

export function usesTextMatching(type: QuestionType): boolean {
  return type === "short_answer" || type === "fill_in_blank";
}

export function allowsMultipleCorrect(type: QuestionType): boolean {
  return type !== "single_choice" && type !== "true_false";
}

export function isMultiPart(type: QuestionType): boolean {
  return type === "fill_in_blank";
}

/** true_false is always exactly two options; every other type is author-defined. */
export function fixedOptionCount(type: QuestionType): number | null {
  return type === "true_false" ? 2 : null;
}

/**
 * A minimal valid payload for a new question of the given type. True/False ships with its two
 * options already present because the type mandates them — an author should never have to create
 * "True" and "False" by hand.
 */
export function defaultQuestion(type: QuestionType, trueLabel: string, falseLabel: string): QuestionInput {
  const base: QuestionInput = { type, prompt: "", points: 1, options: [] };

  if (type === "true_false") {
    return {
      ...base,
      options: [
        { label: trueLabel, is_correct: true, group_index: 0 },
        { label: falseLabel, is_correct: false, group_index: 0 },
      ],
    };
  }

  if (usesOptions(type)) {
    return {
      ...base,
      options: [
        { label: "", is_correct: false, group_index: 0 },
        { label: "", is_correct: false, group_index: 0 },
      ],
    };
  }

  // Text-matched types start with one empty accepted answer for blank 0.
  return { ...base, options: [{ value: "", is_correct: true, group_index: 0 }] };
}

/** Strips a question down to the payload shape, for duplicating it as a new question. */
export function toInput(question: Question): QuestionInput {
  return {
    type: question.type,
    prompt: question.prompt,
    config: question.config,
    explanation: question.explanation,
    hint: question.hint,
    points: question.points,
    negative_points: question.negative_points,
    difficulty: question.difficulty,
    options: question.options.map((o) => ({
      label: o.label,
      value: o.value,
      is_correct: o.is_correct,
      group_index: o.group_index,
      feedback: o.feedback,
    })),
  };
}

/** Blank indices present in an option set, ascending. Used by the fill-in-blank editor. */
export function blankIndices(options: OptionInput[]): number[] {
  const seen = new Set<number>();
  for (const o of options) seen.add(o.group_index ?? 0);

  return [...seen].sort((a, b) => a - b);
}

/**
 * Renumber blanks so they run 0..n-1 with no gaps, preserving relative order.
 *
 * The backend rejects a gap: a missing index means the learner is shown a blank the answer key has
 * no entry for, which is silently ungradable. Removing "blank 1" of three therefore has to shift
 * blank 2 down, not leave a hole.
 */
export function renumberBlanks(options: OptionInput[]): OptionInput[] {
  const order = blankIndices(options);
  const remap = new Map(order.map((original, index) => [original, index]));

  return options.map((option) => ({
    ...option,
    group_index: remap.get(option.group_index ?? 0) ?? 0,
  }));
}

/**
 * Build the `blanks` envelope for a learner answer.
 *
 * Keys are stringified integers. This looks redundant — JS object keys are strings anyway — but it
 * is the exact shape the backend narrows: `AssessmentAnswer::blanks()` DROPS any key that is not
 * numeric rather than coercing it, because PHP casts a non-numeric string to 0 and a payload of
 * `{"abc": "guess"}` would otherwise answer blank 0. Anything non-numeric is filtered here too, so
 * the client cannot generate a payload the server will silently discard.
 *
 * @param answers keyed by blank index
 */
export function buildBlanksPayload(answers: Map<number, string>): Record<string, string> {
  const payload: Record<string, string> = {};

  for (const [index, value] of answers) {
    if (Number.isInteger(index) && index >= 0) {
      payload[String(index)] = value;
    }
  }

  return payload;
}

export interface ValidationIssue {
  /** i18n key, resolved by the caller. */
  key: string;
}

/**
 * Mirrors QuestionShapeGuard's rules so the author is warned inline. Returns [] when the backend
 * would accept the question. Deliberately expresses the SAME reasons the server gives, because a
 * client that warns about different things than the server enforces is worse than one that stays
 * quiet.
 */
export function validateQuestion(input: QuestionInput): ValidationIssue[] {
  const issues: ValidationIssue[] = [];
  const type = input.type;
  const options = input.options ?? [];

  if (!input.prompt || input.prompt.replace(/<[^>]*>/g, "").trim() === "") {
    issues.push({ key: "validation.promptRequired" });
  }

  if ((input.points ?? 0) <= 0) {
    issues.push({ key: "validation.pointsPositive" });
  }

  if (!type) return issues;

  const correct = options.filter((o) => o.is_correct);

  if (usesOptions(type)) {
    const fixed = fixedOptionCount(type);
    if (fixed === null && options.length < 2) issues.push({ key: "validation.needsTwoOptions" });
    if (correct.length === 0) issues.push({ key: "validation.needsCorrect" });
    if (!allowsMultipleCorrect(type) && correct.length > 1) {
      issues.push({ key: "validation.singleCorrectOnly" });
    }
  }

  if (usesTextMatching(type)) {
    const usable = correct.filter((o) => ((o.value ?? o.label) ?? "").trim() !== "");
    if (usable.length === 0) issues.push({ key: "validation.needsAccepted" });
  }

  return issues;
}

/**
 * Approximate correctness for the INSTRUCTOR PREVIEW only.
 *
 * This lives here, under the mirror-drift banner, rather than in the preview component, so every
 * piece of logic that restates backend behaviour sits in one tested file.
 *
 * It is deliberately NOT the grading engine. It does not implement partial credit, negative
 * marking, per-question weighting or manual review; it answers one question — "would this response
 * be counted right?" — so an author can sanity-check their own answer key. A real attempt is
 * always scored by AttemptScorer on the server, and the preview UI says so on screen.
 *
 * @param question the AUTHOR's view, which carries the answer key
 */
export function previewIsCorrect(question: Question, response: AnswerResponse | null): boolean {
  if (response === null) return false;

  const correct = question.options.filter((o) => o.is_correct);
  if (correct.length === 0) return false;

  const caseSensitive = question.config?.case_sensitive === true;
  const normalizeArabic = question.config?.normalize_arabic !== false;

  if (usesOptions(question.type)) {
    const selected = [...new Set(response.option_ids ?? [])];
    const correctIds = correct.map((o) => o.id);

    return (
      selected.length === correctIds.length && selected.every((id) => correctIds.includes(id))
    );
  }

  if (question.type === "short_answer") {
    const submitted = previewNormalized(response.text ?? "", caseSensitive, normalizeArabic);

    return (
      submitted !== "" &&
      correct.some(
        (o) => previewNormalized(o.value ?? o.label ?? "", caseSensitive, normalizeArabic) === submitted,
      )
    );
  }

  if (question.type === "fill_in_blank") {
    // Grouped from the ANSWER KEY, never from the response — otherwise omitting a blank would
    // shrink the denominator and inflate the result, the same trap the backend guards against.
    const byBlank = new Map<number, string[]>();
    for (const option of correct) {
      const index = option.group_index ?? 0;
      byBlank.set(index, [...(byBlank.get(index) ?? []), option.value ?? option.label ?? ""]);
    }

    if (byBlank.size === 0) return false;

    return [...byBlank.entries()].every(([index, accepted]) => {
      const submitted = previewNormalized(
        response.blanks?.[String(index)] ?? "",
        caseSensitive,
        normalizeArabic,
      );

      return (
        submitted !== "" &&
        accepted.some((value) => previewNormalized(value, caseSensitive, normalizeArabic) === submitted)
      );
    });
  }

  return false;
}

/**
 * Mirrors the backend AnswerNormalizer so the author can see how a learner's answer will be
 * compared. Preview only — the server does the real comparison, and this must never be used to
 * decide correctness.
 */
export function previewNormalized(value: string, caseSensitive = false, normalizeArabic = true): string {
  let out = value;

  // Arabic-Indic and Eastern Arabic-Indic digits → ASCII.
  out = out.replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660));
  out = out.replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0));

  // Typographic punctuation that word processors substitute silently.
  out = out.replace(/[‘’]/g, "'").replace(/[“”]/g, '"').replace(/[–—]/g, "-");

  if (normalizeArabic) {
    out = out.replace(/[ؐ-ًؚ-ٰٟۖ-ۜـ]/g, "");
    out = out.replace(/[آأإٱ]/g, "ا").replace(/ى/g, "ي").replace(/ة/g, "ه");
  }

  out = out.replace(/ /g, " ").replace(/\s+/g, " ").trim();

  return caseSensitive ? out : out.toLowerCase();
}
