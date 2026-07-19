"use client";

import { useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { ClipboardList } from "lucide-react";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import { curriculumKey } from "@/lib/authoring/hooks";
import { createAssessment, setLessonAssessment } from "@/lib/assessment/api";
import type { Block } from "@/lib/authoring/types";
import { AssessmentBuilder } from "./assessment-builder";

/**
 * The Quiz lesson's centre pane.
 *
 * A quiz lesson REFERENCES an assessment rather than containing one, so this panel handles the
 * reference (create / detach) and delegates everything else to the builder. Creating is two calls
 * because the backend keeps the two concerns separate: create the assessment on the course, then
 * point the lesson at it.
 */
export function QuizLessonPanel({ block }: { block: Block }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const qc = useQueryClient();
  const [creating, setCreating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [confirmDetach, setConfirmDetach] = useState(false);

  const attached = block.assessment ?? null;

  async function createAndAttach() {
    setCreating(true);
    setError(null);
    try {
      const assessment = await createAssessment(builder.courseId, { title: block.title });
      await setLessonAssessment(block.id, assessment.id);
      // Refetch the curriculum so the lesson carries its new reference; the builder reads it from
      // there rather than holding a second copy.
      await qc.invalidateQueries({ queryKey: curriculumKey(builder.courseId) });
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setCreating(false);
    }
  }

  async function detach() {
    setError(null);
    try {
      // Detach only clears the reference — the assessment itself survives and can be re-attached,
      // which is the whole point of it being an independent entity.
      await setLessonAssessment(block.id, null);
      await qc.invalidateQueries({ queryKey: curriculumKey(builder.courseId) });
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }

  if (!attached) {
    return (
      <div className="mx-auto max-w-2xl p-6">
        <div className="rounded-lg border border-dashed border-border bg-muted/30 p-8 text-center">
          <ClipboardList className="mx-auto size-6 text-muted-foreground/60" aria-hidden />
          <p className="mt-3 text-sm text-muted-foreground">{t("quiz.attach.none")}</p>
          <Button className="mt-4" disabled={creating} onClick={() => void createAndAttach()}>
            {creating ? t("quiz.attach.creating") : t("quiz.attach.create")}
          </Button>
          {error ? (
            <p role="alert" className="mt-3 text-sm text-destructive">
              {error}
            </p>
          ) : null}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4 p-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="min-w-0">
          <h3 className="truncate text-sm font-medium">{attached.title}</h3>
          <p className="text-xs text-muted-foreground">
            {t("quiz.summary", {
              n: attached.question_count,
              status: t(`assessment.status.${attached.status}`),
            })}
          </p>
        </div>
        <Button variant="ghost" size="sm" onClick={() => setConfirmDetach(true)}>
          {t("quiz.attach.detach")}
        </Button>
      </div>

      {error ? (
        <p role="alert" className="text-sm text-destructive">
          {error}
        </p>
      ) : null}

      <AssessmentBuilder assessmentId={attached.id} />

      <ConfirmDialog
        open={confirmDetach}
        onOpenChange={setConfirmDetach}
        title={t("quiz.attach.detach")}
        description={t("quiz.attach.detachConfirm")}
        confirmLabel={t("quiz.attach.detach")}
        confirmVariant="default"
        onConfirm={async () => {
          await detach();
          setConfirmDetach(false);
        }}
      />
    </div>
  );
}
