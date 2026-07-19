"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { AlertCircle, Eye } from "lucide-react";
import { AssessmentPreview } from "./assessment-preview";
import { Button } from "@/components/ui/button";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import type { Assessment, AssessmentInput, FeedbackMode } from "@/lib/assessment/types";

const AUTOSAVE_DELAY_MS = 700;
const FEEDBACK_MODES: readonly FeedbackMode[] = ["immediate", "after_submit", "never"];

/**
 * Right rail: assessment-level settings.
 *
 * Settings autosave; publish does not. Publishing is a deliberate act with a server-side guard
 * behind it, so it stays an explicit button whose failure message comes straight from the backend
 * rather than being guessed at here.
 */
export function AssessmentSettings({
  assessment,
  onSave,
  onSetStatus,
}: {
  assessment: Assessment;
  onSave: (input: AssessmentInput) => void;
  onSetStatus: (status: "draft" | "published") => Promise<void>;
}) {
  const { t } = useAuthoringI18n();
  const [draft, setDraft] = useState<AssessmentInput>(() => fromAssessment(assessment));
  const [publishError, setPublishError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [previewOpen, setPreviewOpen] = useState(false);

  const draftJson = JSON.stringify(draft);
  const savedJson = useRef(JSON.stringify(fromAssessment(assessment)));

  useEffect(() => {
    if (draftJson === savedJson.current) return;

    const timer = setTimeout(() => {
      savedJson.current = draftJson;
      onSave(JSON.parse(draftJson) as AssessmentInput);
    }, AUTOSAVE_DELAY_MS);

    return () => clearTimeout(timer);
  }, [draftJson, onSave]);

  const patch = useCallback((next: Partial<AssessmentInput>) => {
    setDraft((prev) => ({ ...prev, ...next }));
  }, []);

  async function toggleStatus() {
    const next = assessment.status === "published" ? "draft" : "published";
    setBusy(true);
    setPublishError(null);
    try {
      await onSetStatus(next);
    } catch (error) {
      // The publish guard's message is the useful one — it names the actual problem
      // ("Question 3 has no correct answer"), which no client-side check could produce.
      setPublishError(error instanceof Error ? error.message : String(error));
    } finally {
      setBusy(false);
    }
  }

  const published = assessment.status === "published";

  return (
    <div className="space-y-5 p-4">
      <div className="space-y-2">
        <div className="flex items-center justify-between gap-2">
          <h2 className="text-sm font-semibold">{t("assessment.settings")}</h2>
          <span className="rounded-full border border-border px-2 py-0.5 text-xs">
            {t(`assessment.status.${assessment.status}`)}
          </span>
        </div>

        <div className="grid grid-cols-2 gap-2">
          <Button variant="outline" size="sm" onClick={() => setPreviewOpen(true)}>
            <Eye className="size-4" aria-hidden />
            {t("preview.open")}
          </Button>
          <Button
            size="sm"
            variant={published ? "outline" : "default"}
            // Guards against a double-submit creating two publish requests; the guard runs
            // server-side either way, but a second in-flight request would race the first.
            disabled={busy}
            loading={busy}
            onClick={toggleStatus}
          >
            {published ? t("assessment.unpublish") : t("assessment.publish")}
          </Button>
        </div>

        {publishError ? (
          <p role="alert" className="flex items-start gap-2 text-xs text-destructive">
            <AlertCircle className="mt-0.5 size-3.5 shrink-0" aria-hidden />
            {publishError}
          </p>
        ) : null}
      </div>

      <FormField label={t("assessment.field.title")} required>
        <Input value={draft.title ?? ""} onChange={(e) => patch({ title: e.target.value })} />
      </FormField>

      <FormField label={t("assessment.field.description")}>
        <Textarea
          rows={3}
          value={draft.description ?? ""}
          onChange={(e) => patch({ description: e.target.value })}
        />
      </FormField>

      <FormField label={t("assessment.field.passingScore")} hint={t("assessment.field.passingHint")}>
        <Input
          type="number"
          inputMode="numeric"
          min={0}
          max={100}
          value={draft.passing_score ?? ""}
          onChange={(e) => patch({ passing_score: toNullableInt(e.target.value) })}
        />
      </FormField>

      <FormField label={t("assessment.field.timeLimit")} hint={t("assessment.field.timeLimitHint")}>
        <Input
          type="number"
          inputMode="numeric"
          min={1}
          value={draft.time_limit_seconds == null ? "" : Math.round(draft.time_limit_seconds / 60)}
          // Authors think in minutes; the API stores seconds.
          onChange={(e) => {
            const minutes = toNullableInt(e.target.value);
            patch({ time_limit_seconds: minutes === null ? null : minutes * 60 });
          }}
        />
      </FormField>

      <FormField label={t("assessment.field.maxAttempts")} hint={t("assessment.field.maxAttemptsHint")}>
        <Input
          type="number"
          inputMode="numeric"
          min={1}
          value={draft.max_attempts ?? ""}
          onChange={(e) => patch({ max_attempts: toNullableInt(e.target.value) })}
        />
      </FormField>

      <FormField label={t("assessment.field.perAttempt")} hint={t("assessment.field.perAttemptHint")}>
        <Input
          type="number"
          inputMode="numeric"
          min={1}
          value={draft.questions_per_attempt ?? ""}
          onChange={(e) => patch({ questions_per_attempt: toNullableInt(e.target.value) })}
        />
      </FormField>

      <FormField label={t("assessment.field.feedbackMode")}>
        {(field) => (
          <Select
            value={draft.feedback_mode ?? "after_submit"}
            onValueChange={(v) => patch({ feedback_mode: v as FeedbackMode })}
          >
            <SelectTrigger id={field.id}>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {FEEDBACK_MODES.map((mode) => (
                <SelectItem key={mode} value={mode}>
                  {t(`assessment.feedback.${mode}`)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        )}
      </FormField>

      <SettingToggle
        label={t("assessment.field.shuffleQuestions")}
        checked={draft.shuffle_questions ?? false}
        onChange={(next) => patch({ shuffle_questions: next })}
      />
      <SettingToggle
        label={t("assessment.field.shuffleOptions")}
        checked={draft.shuffle_options ?? false}
        onChange={(next) => patch({ shuffle_options: next })}
      />
      <SettingToggle
        label={t("assessment.field.negativeMarking")}
        checked={draft.negative_marking ?? false}
        onChange={(next) => patch({ negative_marking: next })}
      />

      {/* Reads the assessment straight from the query cache, so the preview always reflects the
          questions as last saved rather than a snapshot taken when the panel mounted. */}
      <AssessmentPreview assessment={assessment} open={previewOpen} onOpenChange={setPreviewOpen} />
    </div>
  );
}

function SettingToggle({
  label,
  checked,
  onChange,
}: {
  label: string;
  checked: boolean;
  onChange: (next: boolean) => void;
}) {
  return (
    <div className="flex items-center justify-between gap-3">
      <span className="text-sm">{label}</span>
      <Switch checked={checked} onCheckedChange={onChange} aria-label={label} />
    </div>
  );
}

function fromAssessment(assessment: Assessment): AssessmentInput {
  return {
    title: assessment.title,
    description: assessment.description,
    ...assessment.settings,
  };
}

/** "" means "cleared", which is a real value here — not the same as leaving a field untouched. */
function toNullableInt(raw: string): number | null {
  const trimmed = raw.trim();
  if (trimmed === "") return null;
  const parsed = Number(trimmed);

  return Number.isFinite(parsed) ? Math.round(parsed) : null;
}
