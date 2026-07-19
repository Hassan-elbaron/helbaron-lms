"use client";

import { ChevronDown, ChevronUp, Plus, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { blankIndices, previewNormalized, renumberBlanks } from "@/lib/assessment/question-model";
import type { OptionInput, QuestionConfig, QuestionInput } from "@/lib/assessment/types";

/**
 * The answer-key editors. One per shape of answer, not one per question type — single_choice,
 * multiple_choice and true_false all edit a list of choices and differ only in how many may be
 * correct, so they share ChoiceEditor rather than duplicating it three times.
 */

interface EditorProps {
  value: QuestionInput;
  onChange: (patch: Partial<QuestionInput>) => void;
}

function setConfig(value: QuestionInput, key: keyof QuestionConfig, next: boolean): Partial<QuestionInput> {
  return { config: { ...(value.config ?? {}), [key]: next } };
}

// ── Choice-based: single_choice, multiple_choice, true_false ────────────────

export function ChoiceEditor({
  value,
  onChange,
  allowMultiple,
  fixedOptions,
}: EditorProps & { allowMultiple: boolean; fixedOptions: boolean }) {
  const { t } = useAuthoringI18n();
  const options = value.options ?? [];

  function update(index: number, patch: Partial<OptionInput>) {
    onChange({ options: options.map((o, i) => (i === index ? { ...o, ...patch } : o)) });
  }

  function setCorrect(index: number, correct: boolean) {
    onChange({
      options: options.map((o, i) => {
        if (i === index) return { ...o, is_correct: correct };
        // Single-answer types: selecting one choice must clear the others, otherwise the author
        // creates a question the backend will reject and no learner could answer.
        return allowMultiple ? o : { ...o, is_correct: false };
      }),
    });
  }

  return (
    <fieldset className="space-y-3">
      <legend className="text-sm font-medium">{t("options.title")}</legend>

      <ul className="space-y-2">
        {options.map((option, index) => (
          <li key={index} className="flex items-start gap-2">
            {allowMultiple ? (
              <Checkbox
                // A native input, not a Radix primitive — it takes onChange, not onCheckedChange.
                checked={option.is_correct ?? false}
                onChange={(e) => setCorrect(index, e.target.checked)}
                aria-label={`${t("options.markCorrect")} — ${option.label || t("options.placeholder")}`}
                className="mt-2.5"
              />
            ) : (
              <input
                type="radio"
                // A real radio input, not a styled div: one group per question, arrow-key
                // navigable, and announced as a radio group by screen readers.
                name="correct-option"
                checked={option.is_correct ?? false}
                onChange={() => setCorrect(index, true)}
                aria-label={`${t("options.markCorrect")} — ${option.label || t("options.placeholder")}`}
                className="mt-3 size-4 accent-[var(--color-primary)]"
              />
            )}

            <div className="flex-1 space-y-1">
              <Input
                value={option.label ?? ""}
                onChange={(e) => update(index, { label: e.target.value })}
                placeholder={t("options.placeholder")}
                aria-label={t("options.placeholder")}
                // True/False labels are fixed by the type; editing them would break the question.
                readOnly={fixedOptions}
              />
              <Input
                value={option.feedback ?? ""}
                onChange={(e) => update(index, { feedback: e.target.value })}
                placeholder={t("options.feedback")}
                aria-label={t("options.feedback")}
                className="text-xs"
              />
            </div>

            {fixedOptions ? null : (
              <div className="mt-1 flex shrink-0">
                {/* Buttons, not drag: an option list is short, and up/down is keyboard-operable
                    with no extra affordance. The order is persisted as `position` by index. */}
                <Button
                  variant="ghost"
                  size="icon"
                  className="size-8"
                  aria-label={t("options.moveUp")}
                  disabled={index === 0}
                  onClick={() => onChange({ options: swap(options, index, index - 1) })}
                >
                  <ChevronUp className="size-4" aria-hidden />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  className="size-8"
                  aria-label={t("options.moveDown")}
                  disabled={index === options.length - 1}
                  onClick={() => onChange({ options: swap(options, index, index + 1) })}
                >
                  <ChevronDown className="size-4" aria-hidden />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  className="size-8"
                  aria-label={t("options.remove")}
                  // Never drop below two choices — a one-option question is unanswerable, and the
                  // backend shape guard would reject it anyway.
                  disabled={options.length <= 2}
                  onClick={() => onChange({ options: options.filter((_, i) => i !== index) })}
                >
                  <Trash2 className="size-4" aria-hidden />
                </Button>
              </div>
            )}
          </li>
        ))}
      </ul>

      {fixedOptions ? null : (
        <Button
          variant="outline"
          size="sm"
          onClick={() => onChange({ options: [...options, { label: "", is_correct: false, group_index: 0 }] })}
        >
          <Plus className="size-4" aria-hidden />
          {t("options.add")}
        </Button>
      )}

      {allowMultiple ? (
        <ToggleRow
          label={t("options.partialCredit")}
          hint={t("options.partialCreditHint")}
          checked={value.config?.partial_credit === true}
          onChange={(next) => onChange(setConfig(value, "partial_credit", next))}
        />
      ) : null}
    </fieldset>
  );
}

// ── short_answer ────────────────────────────────────────────────────────────

export function ShortAnswerEditor({ value, onChange }: EditorProps) {
  const { t } = useAuthoringI18n();
  const options = value.options ?? [];
  const caseSensitive = value.config?.case_sensitive === true;
  const normalizeArabic = value.config?.normalize_arabic !== false;

  return (
    <fieldset className="space-y-3">
      <legend className="text-sm font-medium">{t("answer.accepted")}</legend>
      <p className="text-xs text-muted-foreground">{t("answer.acceptedHint")}</p>

      <ul className="space-y-2">
        {options.map((option, index) => {
          const raw = option.value ?? "";
          const normalized = previewNormalized(raw, caseSensitive, normalizeArabic);

          return (
            <li key={index} className="space-y-1">
              <div className="flex items-center gap-2">
                <Input
                  value={raw}
                  onChange={(e) =>
                    onChange({
                      options: options.map((o, i) =>
                        i === index ? { ...o, value: e.target.value, is_correct: true, group_index: 0 } : o,
                      ),
                    })
                  }
                  placeholder={t("answer.acceptedPlaceholder")}
                  aria-label={t("answer.acceptedPlaceholder")}
                />
                <Button
                  variant="ghost"
                  size="icon"
                  className="size-8"
                  aria-label={t("options.remove")}
                  disabled={options.length <= 1}
                  onClick={() => onChange({ options: options.filter((_, i) => i !== index) })}
                >
                  <Trash2 className="size-4" aria-hidden />
                </Button>
              </div>

              {/* Shows the author exactly what the grader will compare, so surprises surface here
                  rather than in a learner's score. Preview only — the server does the real match. */}
              {normalized !== "" && normalized !== raw ? (
                <p className="ps-1 text-xs text-muted-foreground">
                  {t("answer.normalizedAs", { value: normalized })}
                </p>
              ) : null}
            </li>
          );
        })}
      </ul>

      <Button
        variant="outline"
        size="sm"
        onClick={() => onChange({ options: [...options, { value: "", is_correct: true, group_index: 0 }] })}
      >
        <Plus className="size-4" aria-hidden />
        {t("answer.addAccepted")}
      </Button>

      <ToggleRow
        label={t("answer.caseSensitive")}
        checked={caseSensitive}
        onChange={(next) => onChange(setConfig(value, "case_sensitive", next))}
      />
      <ToggleRow
        label={t("answer.normalizeArabic")}
        hint={t("answer.normalizeArabicHint")}
        checked={normalizeArabic}
        onChange={(next) => onChange(setConfig(value, "normalize_arabic", next))}
      />
    </fieldset>
  );
}

// ── fill_in_blank ───────────────────────────────────────────────────────────

export function FillInBlankEditor({ value, onChange }: EditorProps) {
  const { t } = useAuthoringI18n();
  const options = value.options ?? [];
  const blanks = blankIndices(options);
  const caseSensitive = value.config?.case_sensitive === true;
  const normalizeArabic = value.config?.normalize_arabic !== false;

  function addBlank() {
    // Blanks must stay contiguous from 0 — the backend rejects a gap, because a gap means the
    // learner is shown a blank the answer key has no entry for.
    onChange({ options: [...options, { value: "", is_correct: true, group_index: blanks.length }] });
  }

  function removeBlank(index: number) {
    // renumberBlanks closes the gap. A hole in the sequence is rejected by the backend, because a
    // missing index means the learner sees a blank the answer key cannot score.
    onChange({ options: renumberBlanks(options.filter((o) => (o.group_index ?? 0) !== index)) });
  }

  return (
    <fieldset className="space-y-4">
      <legend className="text-sm font-medium">{t("blank.title")}</legend>
      <p className="text-xs text-muted-foreground">{t("blank.hint")}</p>

      {blanks.map((blankIndex) => {
        const answers = options.filter((o) => (o.group_index ?? 0) === blankIndex);

        return (
          <div key={blankIndex} className="space-y-2 rounded-md border border-border p-3">
            <div className="flex items-center justify-between">
              <h4 className="text-sm font-medium">{t("blank.label", { n: blankIndex + 1 })}</h4>
              <Button
                variant="ghost"
                size="icon"
                className="size-8"
                aria-label={t("blank.remove")}
                disabled={blanks.length <= 1}
                onClick={() => removeBlank(blankIndex)}
              >
                <Trash2 className="size-4" aria-hidden />
              </Button>
            </div>

            {answers.map((answer) => {
              const position = options.indexOf(answer);
              const raw = answer.value ?? "";
              const normalized = previewNormalized(raw, caseSensitive, normalizeArabic);

              return (
                <div key={position} className="space-y-1">
                  <div className="flex items-center gap-2">
                    <Input
                      value={raw}
                      onChange={(e) =>
                        onChange({
                          options: options.map((o, i) =>
                            i === position ? { ...o, value: e.target.value } : o,
                          ),
                        })
                      }
                      placeholder={t("answer.acceptedPlaceholder")}
                      aria-label={`${t("blank.label", { n: blankIndex + 1 })} — ${t("answer.acceptedPlaceholder")}`}
                    />
                    <Button
                      variant="ghost"
                      size="icon"
                      className="size-8"
                      aria-label={t("options.remove")}
                      disabled={answers.length <= 1}
                      onClick={() => onChange({ options: options.filter((_, i) => i !== position) })}
                    >
                      <Trash2 className="size-4" aria-hidden />
                    </Button>
                  </div>
                  {normalized !== "" && normalized !== raw ? (
                    <p className="ps-1 text-xs text-muted-foreground">
                      {t("answer.normalizedAs", { value: normalized })}
                    </p>
                  ) : null}
                </div>
              );
            })}

            <Button
              variant="ghost"
              size="sm"
              onClick={() =>
                onChange({ options: [...options, { value: "", is_correct: true, group_index: blankIndex }] })
              }
            >
              <Plus className="size-4" aria-hidden />
              {t("answer.addAccepted")}
            </Button>
          </div>
        );
      })}

      <Button variant="outline" size="sm" onClick={addBlank}>
        <Plus className="size-4" aria-hidden />
        {t("blank.add")}
      </Button>

      <ToggleRow
        label={t("blank.partialCredit")}
        checked={value.config?.partial_credit === true}
        onChange={(next) => onChange(setConfig(value, "partial_credit", next))}
      />
      <ToggleRow
        label={t("answer.caseSensitive")}
        checked={caseSensitive}
        onChange={(next) => onChange(setConfig(value, "case_sensitive", next))}
      />
      <ToggleRow
        label={t("answer.normalizeArabic")}
        hint={t("answer.normalizeArabicHint")}
        checked={normalizeArabic}
        onChange={(next) => onChange(setConfig(value, "normalize_arabic", next))}
      />
    </fieldset>
  );
}

/** Immutable positional swap. Option order is persisted as `position` by array index. */
function swap(options: OptionInput[], from: number, to: number): OptionInput[] {
  if (to < 0 || to >= options.length) return options;

  const next = [...options];
  [next[from], next[to]] = [next[to], next[from]];

  return next;
}

function ToggleRow({
  label,
  hint,
  checked,
  onChange,
}: {
  label: string;
  hint?: string;
  checked: boolean;
  onChange: (next: boolean) => void;
}) {
  return (
    <div className="flex items-start justify-between gap-3 rounded-md border border-border p-3">
      <div className="min-w-0">
        <p className="text-sm font-medium">{label}</p>
        {hint ? <p className="mt-0.5 text-xs text-muted-foreground">{hint}</p> : null}
      </div>
      <Switch checked={checked} onCheckedChange={onChange} aria-label={label} />
    </div>
  );
}
