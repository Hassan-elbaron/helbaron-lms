"use client";

import { useCallback, useMemo, useState } from "react";
import { AlertTriangle } from "lucide-react";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Spinner } from "@/components/ui/spinner";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useAssessment, useAssessmentActions } from "@/lib/assessment/hooks";
import { defaultQuestion, toInput } from "@/lib/assessment/question-model";
import type { AssessmentInput, QuestionInput, QuestionType } from "@/lib/assessment/types";
import { AssessmentSettings } from "./assessment-settings";
import { QuestionEditor } from "./question-editor";
import { QuestionList } from "./question-list";

/**
 * Three-pane assessment builder, rendered inside the Course Builder's centre column when a Quiz
 * lesson is selected. It is not a separate page — a quiz lesson is edited exactly where every
 * other lesson type is.
 *
 * Layout mirrors the Course Builder shell: list · editor · settings, collapsing to a single
 * column on small screens where the panes stack rather than compete for width.
 */
export function AssessmentBuilder({ assessmentId }: { assessmentId: string }) {
  const { t } = useAuthoringI18n();
  const { data: assessment, isPending, isError, refetch } = useAssessment(assessmentId);
  const actions = useAssessmentActions(assessmentId);

  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [pendingDelete, setPendingDelete] = useState<string | null>(null);
  const [invalidIds, setInvalidIds] = useState<ReadonlySet<string>>(new Set());
  const [actionError, setActionError] = useState<string | null>(null);

  /**
   * Every mutation goes through here. Without it a rejected action — a 403 from the ownership
   * gate, a 422 from the shape guard — becomes an unhandled promise rejection and the author sees
   * nothing at all: the optimistic patch rolls back and the UI silently reverts. Surfacing the
   * server's own message is the only way they learn why.
   */
  const run = useCallback(async (action: () => Promise<unknown>) => {
    setActionError(null);
    try {
      await action();
    } catch (error) {
      setActionError(error instanceof Error ? error.message : String(error));
    }
  }, []);

  const questions = useMemo(() => assessment?.questions ?? [], [assessment]);

  /**
   * The selection is DERIVED, not synced. Storing it and repairing it in an effect would mean a
   * render where `selectedId` points at a question that no longer exists — and an extra render to
   * correct it. Falling back here means the pane never shows a stale or empty state after a delete.
   */
  const effectiveId =
    selectedId !== null && questions.some((q) => q.id === selectedId)
      ? selectedId
      : (questions[0]?.id ?? null);

  const handleValidity = useCallback((questionId: string, valid: boolean) => {
    setInvalidIds((prev) => {
      const has = prev.has(questionId);
      if (valid === !has) return prev;

      const next = new Set(prev);
      if (valid) next.delete(questionId);
      else next.add(questionId);

      return next;
    });
  }, []);

  const selected = questions.find((q) => q.id === effectiveId) ?? null;

  function addQuestion(type: QuestionType) {
    void run(async () => {
      const created = await actions.addQuestion(
        defaultQuestion(type, t("options.true"), t("options.false")),
      );
      setSelectedId(created.id);
    });
  }

  function duplicateQuestion(questionId: string) {
    const source = questions.find((q) => q.id === questionId);
    if (!source) return;

    void run(async () => {
      // Duplicated through the same create path as any other question, so the shape guard applies
      // and the copy gets its own option ids — reusing them would corrupt in-flight attempts.
      const created = await actions.addQuestion(toInput(source));
      setSelectedId(created.id);
    });
  }

  const saveQuestion = useCallback(
    (questionId: string, input: QuestionInput) => {
      void actions.saveQuestion(questionId, input);
    },
    [actions],
  );

  if (isPending) {
    return (
      <div className="flex min-h-[24rem] items-center justify-center" role="status" aria-live="polite">
        <Spinner />
      </div>
    );
  }

  if (isError || !assessment) {
    return (
      <div className="flex min-h-[24rem] flex-col items-center justify-center gap-3 p-8 text-center">
        <AlertTriangle className="size-8 text-destructive" aria-hidden />
        <p className="text-sm text-muted-foreground">{t("builder.loadError")}</p>
        <Button variant="outline" onClick={() => void refetch()}>
          {t("builder.retry")}
        </Button>
      </div>
    );
  }

  return (
    <div className="grid min-h-[32rem] grid-cols-1 gap-0 rounded-lg border border-border lg:grid-cols-[minmax(14rem,18rem)_1fr_minmax(15rem,20rem)]">
      <aside
        className="border-b border-border lg:border-b-0 lg:border-e"
        aria-label={t("assessment.questions")}
      >
        <QuestionList
          questions={questions}
          selectedId={effectiveId}
          invalidIds={invalidIds}
          onSelect={setSelectedId}
          onAdd={addQuestion}
          onDuplicate={duplicateQuestion}
          onDelete={setPendingDelete}
          onReorder={(ids) => void run(() => actions.reorder(ids))}
        />
      </aside>

      <main className="min-w-0">
        {selected ? (
          <QuestionEditor
            // Remount per question so a debounced draft can never leak into the next one.
            key={selected.id}
            question={selected}
            onSave={(input) => saveQuestion(selected.id, input)}
            onValidityChange={handleValidity}
          />
        ) : (
          <p className="p-8 text-center text-sm text-muted-foreground">{t("assessment.noQuestions")}</p>
        )}
      </main>

      <aside
        className="border-t border-border lg:border-s lg:border-t-0"
        aria-label={t("assessment.settings")}
      >
        <AssessmentSettings
          assessment={assessment}
          onSave={(input: AssessmentInput) => void actions.updateSettings(input)}
          onSetStatus={async (status) => {
            await actions.setStatus(status);
          }}
        />
      </aside>

      {actionError ? (
        // role=alert so the failure is announced, not just drawn — an author who deleted a
        // question and saw it reappear needs to be told the server refused.
        <p
          role="alert"
          className="col-span-full border-t border-border bg-destructive/5 px-4 py-2 text-sm text-destructive"
        >
          {actionError}
        </p>
      ) : null}

      <ConfirmDialog
        open={pendingDelete !== null}
        onOpenChange={(open) => {
          if (!open) setPendingDelete(null);
        }}
        title={t("question.delete")}
        description={t("question.deleteConfirm")}
        confirmLabel={t("question.delete")}
        onConfirm={async () => {
          if (pendingDelete) await run(() => actions.removeQuestion(pendingDelete));
          setPendingDelete(null);
        }}
      />
    </div>
  );
}
