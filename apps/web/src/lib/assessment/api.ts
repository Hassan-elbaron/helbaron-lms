/**
 * Assessment API client.
 *
 * A thin, honest wrapper over the endpoints Step 4a actually shipped. Every function here maps 1:1
 * to a real route — there are no placeholder calls and no client-side stand-ins for missing
 * backend behaviour. If something is not listed here, the backend does not expose it.
 *
 * Authoring routes live under /admin and are authorized by the `assessment.manage-assessment`
 * gate (super_admin / admin / the course's assigned trainer). Learner attempt routes are under /v1
 * and are ownership-checked against the attempt itself.
 */
import { api } from "@/lib/api/client";
import type {
  Assessment,
  AssessmentInput,
  AssessmentStatus,
  Attempt,
  AnswerResponse,
  Question,
  QuestionInput,
} from "./types";

// ── Authoring: assessments ──────────────────────────────────────────────────

export function listAssessments(coursePublicId: string): Promise<Assessment[]> {
  return api.data<Assessment[]>(`admin/courses/${coursePublicId}/assessments`);
}

export function createAssessment(coursePublicId: string, input: AssessmentInput): Promise<Assessment> {
  return api.data<Assessment>(`admin/courses/${coursePublicId}/assessments`, {
    method: "POST",
    body: input,
  });
}

/** Returns the assessment WITH its questions and full answer key (author view). */
export function getAssessment(assessmentId: string): Promise<Assessment> {
  return api.data<Assessment>(`admin/assessments/${assessmentId}`);
}

export function updateAssessment(assessmentId: string, input: AssessmentInput): Promise<Assessment> {
  return api.data<Assessment>(`admin/assessments/${assessmentId}`, { method: "PUT", body: input });
}

export function deleteAssessment(assessmentId: string): Promise<void> {
  return api.del(`admin/assessments/${assessmentId}`);
}

/**
 * Publishing is guarded server-side (at least one question, every question gradable and keyed,
 * a sane pass mark). A 422 here is the guard talking, and its message is meant to be shown.
 */
export function setAssessmentStatus(assessmentId: string, status: AssessmentStatus): Promise<Assessment> {
  return api.data<Assessment>(`admin/assessments/${assessmentId}/status`, {
    method: "POST",
    body: { status },
  });
}

// ── Authoring: questions ────────────────────────────────────────────────────

export function createQuestion(assessmentId: string, input: QuestionInput): Promise<Question> {
  return api.data<Question>(`admin/assessments/${assessmentId}/questions`, {
    method: "POST",
    body: input,
  });
}

export function updateQuestion(questionId: string, input: QuestionInput): Promise<Question> {
  return api.data<Question>(`admin/questions/${questionId}`, { method: "PUT", body: input });
}

export function deleteQuestion(questionId: string): Promise<void> {
  return api.del(`admin/questions/${questionId}`);
}

export function reorderQuestions(assessmentId: string, order: string[]): Promise<void> {
  return api.put(`admin/assessments/${assessmentId}/questions/order`, { order });
}

// ── Authoring: lesson ↔ assessment reference ────────────────────────────────

/** Point a quiz lesson at an assessment. Pass null to detach. */
export function setLessonAssessment(lessonId: string, assessmentId: string | null): Promise<unknown> {
  return api.put(`admin/lessons/${lessonId}/assessment`, { assessment_id: assessmentId });
}

// ── Learner: attempts ───────────────────────────────────────────────────────

/**
 * Starts an attempt, or resumes the learner's existing open one — the backend returns the same
 * attempt rather than consuming another, so a refreshed tab is safe.
 */
export function startAttempt(assessmentId: string): Promise<Attempt> {
  return api.data<Attempt>(`assessments/${assessmentId}/attempts`, { method: "POST" });
}

export function getAttempt(attemptId: string): Promise<Attempt> {
  return api.data<Attempt>(`attempts/${attemptId}`);
}

/** Saves one answer. Deliberately returns nothing gradeable: scoring happens at submission. */
export function saveAnswer(
  attemptId: string,
  questionId: string,
  response: AnswerResponse | null,
): Promise<unknown> {
  return api.put(`attempts/${attemptId}/answers`, { question_id: questionId, response });
}

export function submitAttempt(attemptId: string): Promise<Attempt> {
  return api.data<Attempt>(`attempts/${attemptId}/submit`, { method: "POST" });
}

/**
 * Backend capability the Assessment UI needs but Step 4a does not expose. Listed here rather than
 * worked around, because every workaround for these would mean inventing data.
 */
export const REMAINING_BACKEND: readonly string[] = [
  "Attempt history for a learner — GET /v1/assessments/{assessment}/attempts returning past attempts (number, status, score, submitted_at). The player can show the current attempt only; 'attempt history' cannot be built without it.",
  "Instructor results view — GET /admin/assessments/{assessment}/attempts with per-learner scores, for grading and item analysis.",
  "Question attachments — no media/attachment relation exists on assessment_questions, so the authoring UI offers no attachment field.",
  "Assessment duplication — POST /admin/assessments/{assessment}/duplicate, for reusing a quiz across lessons.",
];
