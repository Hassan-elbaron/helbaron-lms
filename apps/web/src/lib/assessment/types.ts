/**
 * Assessment domain types.
 *
 * These mirror the backend Resources EXACTLY (AssessmentResource, QuestionResource,
 * QuestionOptionResource, AttemptResource). No field is invented and none is renamed — where the
 * API says `points` is a float and `option_ids` is the response key, so does this file. The backend
 * is the source of truth; everything here is a read of that contract.
 */

/** Implemented question types. Mirrors the backend QuestionType enum exactly. */
export type QuestionType =
  | "single_choice"
  | "multiple_choice"
  | "true_false"
  | "short_answer"
  | "fill_in_blank";

export type AssessmentStatus = "draft" | "published" | "archived";
export type FeedbackMode = "immediate" | "after_submit" | "never";
export type Difficulty = "easy" | "medium" | "hard";

export type AttemptStatus =
  | "in_progress"
  | "submitted"
  | "awaiting_review"
  | "graded"
  | "expired"
  | "abandoned";

/**
 * Per-question settings. Deliberately open: the backend stores `config` as free-form JSON so new
 * question types need no migration, and the keys below are the ones V1 graders actually read.
 */
export interface QuestionConfig {
  /** multiple_choice, fill_in_blank — score partially instead of all-or-nothing. */
  partial_credit?: boolean;
  /** short_answer, fill_in_blank — compare case-sensitively. */
  case_sensitive?: boolean;
  /** short_answer, fill_in_blank — fold Arabic orthographic variants. Defaults to true. */
  normalize_arabic?: boolean;
  [key: string]: unknown;
}

/** AUTHOR view of an option — carries the answer key. Never rendered to a learner. */
export interface QuestionOption {
  id: string;
  label: string | null;
  value: string | null;
  is_correct: boolean;
  /** Which blank / sub-part this belongs to. 0 for single-part questions. */
  group_index: number;
  feedback: string | null;
  position: number;
}

/** AUTHOR view of a question. */
export interface Question {
  id: string;
  type: QuestionType;
  prompt: string;
  config: QuestionConfig | null;
  explanation: string | null;
  hint: string | null;
  points: number;
  negative_points: number;
  difficulty: Difficulty | null;
  position: number;
  options: QuestionOption[];
}

export interface AssessmentSettings {
  passing_score: number | null;
  negative_marking: boolean;
  max_attempts: number | null;
  time_limit_seconds: number | null;
  shuffle_questions: boolean;
  shuffle_options: boolean;
  questions_per_attempt: number | null;
  feedback_mode: FeedbackMode;
}

export interface Assessment {
  id: string;
  title: string;
  description: string | null;
  scope: string;
  status: AssessmentStatus;
  version: number;
  settings: AssessmentSettings;
  question_count?: number;
  questions?: Question[];
}

/** The compact reference a Quiz lesson carries in the curriculum tree. */
export interface LessonAssessmentRef {
  id: string;
  title: string;
  status: AssessmentStatus;
  question_count: number;
  version: number;
}

// ── Learner side ───────────────────────────────────────────────────────────

/**
 * LEARNER view of an option. `is_correct` and `feedback` are absent until the backend decides the
 * learner is entitled to them — their absence is the security guarantee, so they are optional here
 * rather than nullable, and the UI must treat `undefined` as "not revealed".
 */
export interface LearnerOption {
  id: string;
  label: string | null;
  group_index: number;
  is_correct?: boolean;
  feedback?: string | null;
}

export interface LearnerQuestion {
  id: string;
  type: QuestionType;
  prompt: string;
  config: QuestionConfig | null;
  hint: string | null;
  points: number;
  /** Null until the attempt is graded AND feedback mode permits it. */
  explanation: string | null;
  options: LearnerOption[];
}

/** The response envelope, shaped by question type. Mirrors AssessmentAnswer's documented shapes. */
export interface AnswerResponse {
  option_ids?: string[];
  text?: string;
  /** Keyed by blank index as a string, matching the JSON the backend accepts. */
  blanks?: Record<string, string>;
}

export interface LearnerAnswer {
  response: AnswerResponse | null;
  is_correct: boolean | null;
  points_awarded: number | null;
  feedback: string | null;
}

export interface AttemptResult {
  score: number | null;
  max_score: number | null;
  percentage: number | null;
  passed: boolean | null;
}

export interface AttemptQuestion {
  question: LearnerQuestion;
  answer: LearnerAnswer | null;
}

export interface Attempt {
  id: string;
  status: AttemptStatus;
  attempt_number: number;
  started_at: string | null;
  expires_at: string | null;
  submitted_at: string | null;
  /** Null while the attempt is still open — the backend withholds it deliberately. */
  result: AttemptResult | null;
  questions: AttemptQuestion[];
  feedback_mode: FeedbackMode | null;
}

// ── Write payloads ─────────────────────────────────────────────────────────

export interface AssessmentInput {
  title?: string;
  description?: string | null;
  passing_score?: number | null;
  negative_marking?: boolean;
  max_attempts?: number | null;
  time_limit_seconds?: number | null;
  shuffle_questions?: boolean;
  shuffle_options?: boolean;
  questions_per_attempt?: number | null;
  feedback_mode?: FeedbackMode;
}

export interface OptionInput {
  label?: string | null;
  value?: string | null;
  is_correct?: boolean;
  group_index?: number;
  feedback?: string | null;
}

export interface QuestionInput {
  type?: QuestionType;
  prompt?: string;
  config?: QuestionConfig | null;
  explanation?: string | null;
  hint?: string | null;
  points?: number;
  negative_points?: number;
  difficulty?: Difficulty | null;
  /** Saved as a complete SET — the backend replaces every option in one transaction. */
  options?: OptionInput[];
}
