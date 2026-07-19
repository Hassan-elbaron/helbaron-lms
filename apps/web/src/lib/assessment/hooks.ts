"use client";

/**
 * React Query layer for assessment authoring.
 *
 * Mirrors the pattern the Course Builder already uses (lib/authoring/hooks.ts): optimistic cache
 * patches for edits that cannot fail structurally, plain invalidation for the ones that can.
 *
 * Publishing and question creation are NOT optimistic. Both can be rejected by server-side rules
 * (the publish guard; QuestionShapeGuard) that the client deliberately does not reimplement, so
 * showing success before the server agrees would mean showing a state that may never exist.
 */
import { useMemo } from "react";
import { useMutation, useQuery, useQueryClient, type QueryKey } from "@tanstack/react-query";
import * as client from "./api";
import type { Assessment, AssessmentInput, AssessmentStatus, Question, QuestionInput } from "./types";

export function assessmentKey(assessmentId: string): QueryKey {
  return ["assessment", assessmentId];
}

export function courseAssessmentsKey(coursePublicId: string): QueryKey {
  return ["assessments", "course", coursePublicId];
}

export function useAssessment(assessmentId: string | null) {
  return useQuery({
    queryKey: assessmentKey(assessmentId ?? "none"),
    queryFn: () => client.getAssessment(assessmentId as string),
    enabled: Boolean(assessmentId),
    staleTime: 15_000,
  });
}

export function useCourseAssessments(coursePublicId: string) {
  return useQuery({
    queryKey: courseAssessmentsKey(coursePublicId),
    queryFn: () => client.listAssessments(coursePublicId),
    staleTime: 30_000,
  });
}

export interface AssessmentActions {
  updateSettings: (input: AssessmentInput) => Promise<void>;
  setStatus: (status: AssessmentStatus) => Promise<Assessment>;
  addQuestion: (input: QuestionInput) => Promise<Question>;
  saveQuestion: (questionId: string, input: QuestionInput) => Promise<void>;
  removeQuestion: (questionId: string) => Promise<void>;
  reorder: (orderedIds: string[]) => Promise<void>;
}

export function useAssessmentActions(assessmentId: string): AssessmentActions {
  const qc = useQueryClient();
  const key = assessmentKey(assessmentId);

  return useMemo<AssessmentActions>(() => {
    const read = () => qc.getQueryData<Assessment>(key);
    const write = (next: Assessment) => qc.setQueryData<Assessment>(key, next);
    const invalidate = () => qc.invalidateQueries({ queryKey: key });

    /** Patch the cache, persist, roll back on failure, reconcile on success. */
    async function optimistic(
      patch: (a: Assessment) => Assessment,
      persist: () => Promise<unknown>,
    ): Promise<void> {
      const prev = read();
      if (prev) write(patch(prev));
      try {
        await persist();
        await invalidate();
      } catch (e) {
        if (prev) write(prev);
        throw e;
      }
    }

    return {
      updateSettings(input) {
        return optimistic(
          (a) => ({
            ...a,
            title: input.title ?? a.title,
            description: input.description !== undefined ? input.description : a.description,
            settings: {
              ...a.settings,
              // Only overwrite keys the caller actually sent — undefined means "unchanged",
              // whereas null is a real value (clearing a time limit or pass mark).
              ...(input.passing_score !== undefined ? { passing_score: input.passing_score } : {}),
              ...(input.negative_marking !== undefined ? { negative_marking: input.negative_marking } : {}),
              ...(input.max_attempts !== undefined ? { max_attempts: input.max_attempts } : {}),
              ...(input.time_limit_seconds !== undefined ? { time_limit_seconds: input.time_limit_seconds } : {}),
              ...(input.shuffle_questions !== undefined ? { shuffle_questions: input.shuffle_questions } : {}),
              ...(input.shuffle_options !== undefined ? { shuffle_options: input.shuffle_options } : {}),
              ...(input.questions_per_attempt !== undefined ? { questions_per_attempt: input.questions_per_attempt } : {}),
              ...(input.feedback_mode !== undefined ? { feedback_mode: input.feedback_mode } : {}),
            },
          }),
          () => client.updateAssessment(assessmentId, input),
        );
      },

      async setStatus(status) {
        // Not optimistic: the publish guard may refuse, and its message is what the author needs.
        const updated = await client.setAssessmentStatus(assessmentId, status);
        await invalidate();

        return updated;
      },

      async addQuestion(input) {
        // Not optimistic: QuestionShapeGuard decides whether the option set is coherent.
        const created = await client.createQuestion(assessmentId, input);
        await invalidate();

        return created;
      },

      saveQuestion(questionId, input) {
        return optimistic(
          (a) => ({
            ...a,
            questions: (a.questions ?? []).map((q) =>
              q.id === questionId ? { ...q, ...stripUndefined(input) } : q,
            ),
          }),
          () => client.updateQuestion(questionId, input),
        );
      },

      removeQuestion(questionId) {
        return optimistic(
          (a) => ({
            ...a,
            questions: reindex((a.questions ?? []).filter((q) => q.id !== questionId)),
            question_count: Math.max(0, (a.question_count ?? 1) - 1),
          }),
          () => client.deleteQuestion(questionId),
        );
      },

      reorder(orderedIds) {
        return optimistic(
          (a) => ({ ...a, questions: reindex(orderBy(a.questions ?? [], orderedIds)) }),
          () => client.reorderQuestions(assessmentId, orderedIds),
        );
      },
    };
  }, [qc, key, assessmentId]);
}

/** Drops undefined keys so a partial patch never blanks a field it did not mention. */
function stripUndefined(input: QuestionInput): Partial<Question> {
  const out: Record<string, unknown> = {};
  for (const [k, v] of Object.entries(input)) {
    if (v !== undefined) out[k] = v;
  }

  return out as Partial<Question>;
}

function reindex(questions: Question[]): Question[] {
  return questions.map((q, i) => ({ ...q, position: i }));
}

function orderBy(questions: Question[], orderedIds: string[]): Question[] {
  const byId = new Map(questions.map((q) => [q.id, q]));
  const ordered = orderedIds.map((id) => byId.get(id)).filter((q): q is Question => Boolean(q));
  const rest = questions.filter((q) => !orderedIds.includes(q.id));

  return [...ordered, ...rest];
}

// ── Learner ─────────────────────────────────────────────────────────────────

export function attemptKey(attemptId: string): QueryKey {
  return ["attempt", attemptId];
}

export function useAttempt(attemptId: string | null) {
  return useQuery({
    queryKey: attemptKey(attemptId ?? "none"),
    queryFn: () => client.getAttempt(attemptId as string),
    enabled: Boolean(attemptId),
    // The attempt carries a server-side clock; refetching stale data would fight the local timer.
    staleTime: Infinity,
  });
}

export function useStartAttempt() {
  const qc = useQueryClient();

  return useMutation({
    mutationFn: (assessmentId: string) => client.startAttempt(assessmentId),
    onSuccess: (attempt) => qc.setQueryData(attemptKey(attempt.id), attempt),
  });
}

export function useSubmitAttempt() {
  const qc = useQueryClient();

  return useMutation({
    mutationFn: (attemptId: string) => client.submitAttempt(attemptId),
    // The submit response IS the graded attempt, so seed the cache rather than refetching.
    onSuccess: (attempt) => qc.setQueryData(attemptKey(attempt.id), attempt),
  });
}
