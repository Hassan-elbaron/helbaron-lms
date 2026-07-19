"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { AlertCircle } from "lucide-react";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import {
  QUESTION_TYPES,
  allowsMultipleCorrect,
  defaultQuestion,
  fixedOptionCount,
  isMultiPart,
  toInput,
  usesOptions,
  usesTextMatching,
  validateQuestion,
} from "@/lib/assessment/question-model";
import type { Difficulty, Question, QuestionInput, QuestionType } from "@/lib/assessment/types";
import { ChoiceEditor, FillInBlankEditor, ShortAnswerEditor } from "./editors/answer-editors";

const AUTOSAVE_DELAY_MS = 700;

/**
 * Centre pane: edits one question.
 *
 * Autosave is debounced and mirrors the Course Builder's lesson editor — the author never presses
 * save. The draft is keyed by question id upstream, so switching questions remounts and cannot
 * carry a half-typed prompt across.
 *
 * Validation shown here is UX only. The backend's QuestionShapeGuard remains the source of truth
 * and will reject an invalid question regardless; this just surfaces the same reasons sooner.
 */
export function QuestionEditor({
  question,
  onSave,
  onValidityChange,
}: {
  question: Question;
  onSave: (input: QuestionInput) => void;
  onValidityChange: (questionId: string, valid: boolean) => void;
}) {
  const { t } = useAuthoringI18n();
  const [draft, setDraft] = useState<QuestionInput>(() => toInput(question));

  const issues = useMemo(() => validateQuestion(draft), [draft]);
  const draftJson = JSON.stringify(draft);
  const savedJson = useRef(JSON.stringify(toInput(question)));

  // Report validity upward so the question list can flag problems without re-validating.
  useEffect(() => {
    onValidityChange(question.id, issues.length === 0);
  }, [question.id, issues.length, onValidityChange]);

  useEffect(() => {
    if (draftJson === savedJson.current) return;

    const timer = setTimeout(() => {
      savedJson.current = draftJson;
      onSave(JSON.parse(draftJson) as QuestionInput);
    }, AUTOSAVE_DELAY_MS);

    return () => clearTimeout(timer);
  }, [draftJson, onSave]);

  function patch(next: Partial<QuestionInput>) {
    setDraft((prev) => ({ ...prev, ...next }));
  }

  /**
   * Changing type rebuilds the answer key from that type's defaults. Carrying choices over into a
   * short-answer question (or vice versa) would produce a key the new type cannot interpret, so
   * the prompt and scoring are kept and only the answer shape is reset.
   */
  function changeType(type: QuestionType) {
    const fresh = defaultQuestion(type, t("options.true"), t("options.false"));
    setDraft((prev) => ({
      ...fresh,
      prompt: prev.prompt,
      points: prev.points,
      negative_points: prev.negative_points,
      difficulty: prev.difficulty,
      explanation: prev.explanation,
      hint: prev.hint,
    }));
  }

  const type = draft.type ?? question.type;

  return (
    <div className="mx-auto max-w-3xl space-y-6 p-6">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <FormField label={t("question.type.change")}>
          {(field) => (
            <Select value={type} onValueChange={(v) => changeType(v as QuestionType)}>
              <SelectTrigger id={field.id} aria-describedby={field["aria-describedby"]}>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {QUESTION_TYPES.map((option) => (
                  <SelectItem key={option} value={option}>
                    {t(`qtype.${option}`)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
        </FormField>

        <FormField label={t("question.field.points")} required>
          <Input
            type="number"
            inputMode="decimal"
            min={0.01}
            step={0.5}
            value={draft.points ?? 1}
            onChange={(e) => patch({ points: Number(e.target.value) })}
          />
        </FormField>

        <FormField label={t("question.field.penalty")} hint={t("question.field.penaltyHint")}>
          <Input
            type="number"
            inputMode="decimal"
            min={0}
            step={0.5}
            value={draft.negative_points ?? 0}
            // Stored as a positive magnitude; the backend scorer applies the sign, and only when
            // the assessment has negative_marking switched on.
            onChange={(e) => patch({ negative_points: Math.max(0, Number(e.target.value)) })}
          />
        </FormField>

        <FormField label={t("question.field.difficulty")}>
          {(field) => (
            <Select
              value={draft.difficulty ?? "medium"}
              onValueChange={(v) => patch({ difficulty: v as Difficulty })}
            >
              <SelectTrigger id={field.id}>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {(["easy", "medium", "hard"] as const).map((level) => (
                  <SelectItem key={level} value={level}>
                    {t(`question.difficulty.${level}`)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
        </FormField>
      </div>

      <FormField label={t("question.field.prompt")} required>
        <Textarea
          rows={3}
          value={stripTags(draft.prompt ?? "")}
          // Prompts are stored as HTML and sanitized server-side. The editor writes a paragraph so
          // the stored value stays valid markup rather than a bare text node.
          onChange={(e) => patch({ prompt: e.target.value.trim() === "" ? "" : `<p>${escapeHtml(e.target.value)}</p>` })}
        />
      </FormField>

      {usesOptions(type) ? (
        <ChoiceEditor
          value={draft}
          onChange={patch}
          allowMultiple={allowsMultipleCorrect(type)}
          fixedOptions={fixedOptionCount(type) !== null}
        />
      ) : isMultiPart(type) ? (
        <FillInBlankEditor value={draft} onChange={patch} />
      ) : usesTextMatching(type) ? (
        <ShortAnswerEditor value={draft} onChange={patch} />
      ) : null}

      <FormField label={t("question.field.hint")} hint={t("question.field.hintHint")}>
        <Input value={stripTags(draft.hint ?? "")} onChange={(e) => patch({ hint: e.target.value })} />
      </FormField>

      <FormField label={t("question.field.explanation")} hint={t("question.field.explanationHint")}>
        <Textarea
          rows={3}
          value={stripTags(draft.explanation ?? "")}
          onChange={(e) => patch({ explanation: e.target.value })}
        />
      </FormField>

      {/* Attachments have no backing table — `assessment_questions` has no media relation and the
          save endpoint accepts no attachment field. Shown as an explicit unavailable state rather
          than hidden, so an author looking for the feature learns why it is missing instead of
          assuming they cannot find it. */}
      <div className="rounded-md border border-dashed border-border p-3">
        <p className="text-sm font-medium text-muted-foreground">{t("question.attachments.title")}</p>
        <p className="mt-1 text-xs text-muted-foreground">{t("question.attachments.unavailable")}</p>
      </div>

      {issues.length > 0 ? (
        <div role="alert" className="space-y-1 rounded-md border border-destructive/40 bg-destructive/5 p-3">
          {issues.map((issue) => (
            <p key={issue.key} className="flex items-center gap-2 text-sm text-destructive">
              <AlertCircle className="size-4 shrink-0" aria-hidden />
              {t(issue.key)}
            </p>
          ))}
        </div>
      ) : null}
    </div>
  );
}

/** Prompts round-trip through a textarea; tags are added back on save. */
function stripTags(html: string): string {
  return html.replace(/<[^>]*>/g, "");
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}
