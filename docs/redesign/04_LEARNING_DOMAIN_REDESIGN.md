# Learning Domain Redesign (Phase 4)

> Architecture phase only. No code, no file moves, no namespace changes, no API changes, no database changes.
> Companion to `01_CATALOG_...`, `02_CRM_ORGANIZATION_...`, `03_INSTRUCTOR_AUTHORING_...`.

---

# Executive Summary

Learning is the **learner-execution** bounded context. It owns *what a learner did* — enrollment, progress, attempts, grades, achievements, sessions, sync — and owns **none** of *what is being learned*. Authoring defines learning objects; Catalog publishes courses; Media Platform owns bytes; Certification issues credentials; Commerce grants entitlement. Learning **executes** those definitions and records the resulting learner state.

The current implementation is a thin, correct-but-narrow slice: `Enrollment`, `LessonProgress`, a "resume pointer" `LearningSession`, plus `Bookmark`/`Note` engagement and a `LearningMediaService` that issues playback tokens. It works, but it (a) reads content structure **directly** out of Authoring/Catalog Eloquent tables, (b) recomputes course completion **synchronously** on every progress write, and (c) is **missing** the entire assessment-execution, gamification, learning-path, competency, offline, and multi-device surface that the product requires.

This redesign keeps every existing behavior and public API intact, and defines the target: a set of **execution aggregates** (Enrollment, LearningSession, AssessmentAttempt, AssignmentSubmission, PathEnrollment, GamificationProfile), fed by **commands**, projected into **read models** (Student Dashboard, Leaderboard, Progress), talking to content only through a **published-language contract** (`CurriculumReadPort`) and to media only through a **`PlaybackPort`** — never through foreign Eloquent models. Offline and multi-device are first-class: every learner-state write is a **versioned, idempotent, mergeable checkpoint**.

The guiding invariant, enforced everywhere in this document:

> **Learning owns learner execution. Learning never owns content definitions. Authoring defines learning objects; Learning executes them.**

---

# Current Problems

Evidence is drawn from the live `App\Contexts\Learning\*` tree.

## Current responsibilities (as built)

| Area | Where | Verdict |
|------|-------|---------|
| Enrollment (status, %, source) | `Models\Enrollment`, `Actions\Enrollment\*` | correct ownership |
| Lesson progress + course % (derived) | `Services\ProgressService` | correct ownership, wrong coupling + scalability |
| Resume pointer | `Models\LearningSession`, `Listeners\UpdateLearningSession` | correct ownership, far too thin |
| Bookmarks / notes | `Models\LessonBookmark`, `LessonNote`, `Actions\Engagement\*` | correct ownership |
| Lesson access / prerequisite gating | `Services\LessonAccessService` | **wrong ownership** (content-sequencing rule executed in Learning) |
| Playback token issuance | `Services\LearningMediaService`, `Playback\*` | **wrong ownership** (Media bytes leak into Learning) |
| Next-lesson computation | `Services\ContinueLearningService` | correct intent, wrong coupling |

## Mixed responsibilities

- **Progress math reaches into content tables.** `ProgressService::publishedLessonIds()` runs `Section::where('course_id',…)->published()` and `Lesson::whereIn(…)->published()`. Learning is joining Authoring/Catalog tables via foreign Eloquent models (`App\Domains\Authoring\Models\Lesson`, `Section`, `App\Domains\Catalog\Models\Course`). Learning knows the **shape of the curriculum**, which is Authoring's business.
- **Prerequisite gating is a content rule in Learning.** `LessonAccessService::prerequisitesMet()` reads `$lesson->prerequisites()` — a sequencing rule authored in Authoring — and enforces it inside Learning.
- **Media bytes leak into Learning.** `LearningMediaService` reads `$lesson->media` and its `s3_key` / `mux_asset_id` via provider classes bundled inside Learning (`MuxPlaybackTokenProvider`, `CloudFrontPlaybackTokenProvider`, `S3PlaybackTokenProvider`). Per the redesign, **Media Platform owns bytes**; Learning should hold only asset **references** and ask a port for a token.

## Wrong ownership

| Symptom | Should belong to | Learning should hold |
|---------|------------------|----------------------|
| `publishedLessonIds`, section ordering | Catalog/Authoring (curriculum) | a **published-language read** of `{lessonId, sectionId, order, weight, isPreview, prereqIds}` |
| `$lesson->prerequisites()` enforcement | Authoring (sequencing definition) | an evaluated **unlock decision** from the curriculum contract |
| Playback provider implementations | Media Platform | a `PlaybackPort::issue(assetRef, ttl)` call |
| `is_preview` free-access rule | Catalog/Commerce entitlement | an entitlement decision from Commerce/Catalog |

## Missing abstractions (entirely absent today)

Assessment Attempts · Assignment Submissions · Grades · Certificate **references** (Certification owns issuance) · Learning Paths execution · Competency progress · Skill progress · Achievements · Gamification (XP / levels / badges / streaks / leaderboards / challenges / rewards) · Attendance / live-session participation · Discussion participation · a Student-Dashboard **read model** · Learning-owned analytics projections · Learning notifications · **Offline sync** · **Multi-device synchronization** · checkpointing / auto-save / recovery / session timeout · conflict detection & merge.

## Scalability issues

1. **Synchronous fan-in on every keystroke of progress.** `record()` recomputes the whole course percentage by re-querying all published lessons on every progress write. At scale (long courses, mobile heartbeats) this is O(lessons) per write, on the request path.
2. **No read model.** The student dashboard, "continue learning", and any future leaderboard are computed live by joining content + progress. There is nothing to serve reads cheaply or offline.
3. **No append-only attempt/event store.** Grades, streaks, and analytics need an immutable ledger of learner events; today there is only mutable current-state rows.
4. **Resume state is single-dimensional.** `LearningSession` = one `last_lesson_id` per course. It cannot express device, checkpoint version, pause, or an offline delta — so multi-device and offline are impossible on the current shape.

---

# Learning Boundary

```
        Commerce ──grant──▶ ┌──────────────────────────────────────────┐
        (entitlement)       │            LEARNING CONTEXT              │
                            │  owns: learner EXECUTION + state         │
  Catalog/Authoring         │                                          │
  ──CurriculumReadPort────▶ │  Enrollment · Session · Progress ·       │ ──events──▶ Certification
  (published language)      │  Attempts · Submissions · Grades ·       │             Analytics
                            │  Paths · Competency/Skill · Gamification │             Notifications
  Media Platform            │  · Engagement · Offline/Sync             │             Live / CRM
  ──PlaybackPort─────────▶  │                                          │
                            └──────────────────────────────────────────┘
```

Learning is **downstream** of content and entitlement (it consumes published definitions and entitlement grants) and **upstream** of Certification, Analytics, and Notifications (it emits learner-execution events they react to). It shares **no tables** with content contexts; every content fact enters through a contract.

---

# Context Ownership

## Learning owns ONLY (canonical list)

Enrollments · Learning Sessions · Lesson Progress · Course Progress · Learning State · Bookmarks · Notes · Highlights · History · Resume Position · Assessment Attempts · Assignment Submissions · Grades · Completion · **Certificate References** · Learning Paths Execution · Competencies Progress · Skills Progress · Achievements · Gamification · XP · Badges · Streaks · Leaderboards · Attendance · Live Session Participation · Discussion Participation · Student Dashboard · Learning Analytics (learner-execution projections) · Learning Notifications (triggers) · Offline Sync.

## Learning NEVER owns

Courses · Lessons · Sections · Quiz Definitions · Assignment Definitions · Question Banks · Media Assets · Publishing · SEO · Pricing · Instructor Profiles.

## Ownership seams (how "never owns" is honored)

| Foreign concept | Owner | Learning's handle | Contract |
|-----------------|-------|-------------------|----------|
| Course / Section / Lesson | Catalog / Authoring | `courseRef`, `lessonRef` (id + version) | `CurriculumReadPort` |
| Quiz / Assignment / Exam definition | Authoring | `AssessmentDefinitionRef` (id + version) | `AssessmentDefinitionPort` |
| Media bytes | Media Platform | `AssetRef` | `PlaybackPort` |
| Entitlement (paid/free/preview) | Commerce / Catalog | `EntitlementDecision` | `EntitlementPort` |
| Certificate issuance | Certification | `CertificateRef` | consumes `CertificateEarned`, stores ref |
| Instructor identity | Instructor | `instructorRef` (display only) | published event, no read into profile |

**Rule of thumb:** Learning stores **references and decisions**, never foreign aggregates. A learner-execution row may cite `lessonRef=(id, version)`; it may never `belongsTo(Authoring\Lesson)` for business logic. (The existing Eloquent `belongsTo` relations are retained for compatibility during migration and are demoted to *reference resolution only* — see Migration Strategy.)

## Modules (each with the full definition set)

Learning is decomposed into **12 modules**. Each is defined below with Mission, Responsibilities, Entities, Aggregate Roots, Value Objects, Commands, Queries, Events, Application Services, Repository Interfaces, Read Models, Policies, Permissions, API Ownership, Search Ownership, Cache Strategy, Offline Strategy, Synchronization Strategy, Conflict Resolution. Global cross-module sections (Entities, Aggregates, …) follow after the module table.

### Module 1 — Enrollment
- **Mission:** own the learner↔course relationship and its lifecycle.
- **Responsibilities:** create/grant/suspend/complete/unenroll; hold status, source, completion %.
- **Entities:** Enrollment. **Aggregate Root:** Enrollment. **Value Objects:** EnrollmentStatus, EnrollmentSource, CompletionPercentage, CourseRef.
- **Commands:** EnrollInCourse, GrantEnrollment, SuspendEnrollment, ResumeEnrollment, Unenroll, ExpireEnrollment.
- **Queries:** GetEnrollment, ListMyEnrollments, IsEnrolled.
- **Events:** EnrollmentCreated, EnrollmentSuspended, EnrollmentCompleted, EnrollmentExpired, Unenrolled.
- **App Services:** EnrollmentService (wraps existing `Enroll/Grant/Unenroll` actions).
- **Repositories:** EnrollmentRepository.
- **Read Models:** MyLearningRow (per enrollment: course ref, %, next step).
- **Policies:** own-enrollment only; grant requires `enrollment.grant` (Commerce/admin).
- **Permissions:** `enrollment.view.own`, `enrollment.self`, `enrollment.grant`, `enrollment.manage`.
- **API:** `POST /courses/{course}/enroll`, `GET /my-learning`. **Search:** own enrollments only. **Cache:** `enrollment:{user}:{course}` (short). **Offline:** enrollment list cached read-only. **Sync:** server-authoritative; **Conflict:** none (grant is server-side).

### Module 2 — Learning Session (deep-dived below)
- **Mission:** own "where/how the learner is working right now" across devices and offline.
- **Root:** LearningSession. **VOs:** DeviceId, ResumePosition, Checkpoint, SessionState, CheckpointVersion.
- **Commands:** StartSession, Heartbeat, Pause, Resume, Checkpoint, EndSession, RecoverSession.
- **Events:** LearningSessionStarted, LearningSessionPaused, LearningSessionResumed, LearningSessionCheckpointed, LearningSessionEnded.
- **API:** `GET /continue-learning`, `POST /sessions/*` (new). **Cache:** `session:{user}:{course}`. **Offline:** full (local checkpoints). **Sync:** latest-checkpoint-wins per version vector. **Conflict:** checkpoint version vector + resume-priority policy.

### Module 3 — Progress Engine (deep-dived below)
- **Mission:** derive completion from recorded activity — never assert it.
- **Entities:** LessonProgress, CourseProgress (projection), SectionProgress (derived). **Root:** LessonProgress (write), CourseProgress (read model). **VOs:** ProgressStatus, PositionSeconds, ProgressWeight, CompletionRule.
- **Commands:** RecordLessonProgress, MarkLessonComplete, ResetProgress.
- **Queries:** GetCourseProgress, GetSectionProgress, GetNextLesson.
- **Events:** LessonStarted, ProgressUpdated, LessonCompleted, SectionCompleted, CourseCompleted.
- **Read Models:** CourseProgressView, ContinueLearningView.
- **API:** `POST /lessons/{lesson}/progress`, `GET /courses/{course}/learn`. **Cache:** `progress:{enrollment}` (invalidated by projector). **Offline:** offline progress queued. **Sync:** monotonic max-position + union-of-completed. **Conflict:** monotonic merge (never regress completion).

### Module 4 — Assessment Execution (deep-dived below)
- **Mission:** run assessments defined by Authoring and record attempts/grades.
- **Entities:** AssessmentAttempt, AttemptResponse, AssignmentSubmission, Grade, PeerReviewAssignment. **Roots:** AssessmentAttempt, AssignmentSubmission. **VOs:** AttemptState, Score, DefinitionRef(id+version), RandomizationSeed, GradingMode, PassRule.
- **Commands:** StartAttempt, SaveAttemptDraft, SubmitAttempt, GradeAttempt (auto), RecordManualGrade, SubmitAssignment, RequestRetake, SubmitPeerReview.
- **Queries:** GetAttempt, ListAttempts, GetGrade, GetGradebook.
- **Events:** AssessmentStarted, AssessmentDraftSaved, AssessmentSubmitted, AssessmentGraded, AssessmentPassed/Failed, AssignmentSubmitted, ManualGradeRecorded.
- **Read Models:** GradebookView, AttemptHistoryView.
- **API:** `POST /assessments/{ref}/attempts`, `PATCH …/attempts/{id}`, `POST …/attempts/{id}/submit`, `POST /assignments/{ref}/submissions` (new). **Cache:** attempt state per learner. **Offline:** practice/graded attempts offline-capable; exams online-only. **Sync:** attempt is single-owner draft; **Conflict:** last-writer per attempt draft (single device holds the lock).

### Module 5 — Gamification (deep-dived below)
- **Mission:** motivate via XP, levels, badges, streaks, leaderboards, challenges.
- **Entities:** GamificationProfile, XPLedgerEntry, BadgeAward, Streak, ChallengeEnrollment, LeaderboardEntry. **Root:** GamificationProfile (+ append-only XPLedger). **VOs:** XP, Level, StreakCount, BadgeCode, RewardGrant.
- **Commands:** AwardXP, UnlockBadge, UpdateStreak, JoinChallenge, ClaimReward.
- **Events:** XPAwarded, LevelUp, BadgeUnlocked, StreakUpdated, StreakBroken, ChallengeCompleted, RewardGranted.
- **Read Models:** LeaderboardView (materialized), ProfileSummaryView.
- **API:** `GET /me/gamification`, `GET /leaderboards/{scope}` (new). **Search:** leaderboard scoped by tenant/cohort. **Cache:** leaderboard materialized + TTL. **Offline:** XP accrues offline into ledger, reconciled on sync (idempotent). **Sync:** additive ledger (commutative). **Conflict:** none — XP entries are idempotent, keyed by source event id.

### Module 6 — Engagement (Bookmarks / Notes / Highlights / History)
- **Mission:** own the learner's personal annotations and history.
- **Entities:** LessonBookmark, LessonNote, Highlight, ViewHistoryEntry. **Root:** per-user Engagement aggregate. **VOs:** TextRange, NoteBody, Timestamped.
- **Commands:** ToggleBookmark, UpsertNote, AddHighlight, RecordView.
- **Events:** BookmarkToggled, NoteSaved, HighlightAdded.
- **API:** `POST /lessons/{lesson}/bookmark`, `POST /lessons/{lesson}/notes`, `POST …/highlights` (new). **Cache:** per-user, per-lesson. **Offline:** full (personal data, CRDT-friendly). **Sync:** per-item last-writer-wins by updated_at + device tiebreak. **Conflict:** field-level LWW; notes keep an edit-history tail.

### Module 7 — Learning Path Execution
- **Mission:** execute a learner's journey across multiple courses/steps.
- **Entities:** PathEnrollment, PathStepProgress. **Root:** PathEnrollment. **VOs:** PathRef, StepRef, StepStatus, UnlockRule.
- **Commands:** EnrollInPath, AdvancePathStep, CompletePath.
- **Queries:** GetPathProgress, GetNextStep.
- **Events:** PathEnrolled, PathStepCompleted, PathCompleted.
- **API:** `POST /paths/{path}/enroll`, `GET /paths/{path}/progress` (new). **Cache:** `path:{user}:{path}`. **Offline:** read cached; step-advance queued. **Sync:** monotonic. **Conflict:** union-of-completed steps.

### Module 8 — Competency & Skill Progress
- **Mission:** track mastery of the Learning-owned competency/skill framework, measured from assessments.
- **Entities:** SkillProgress, CompetencyProgress, MasteryEvidence. **Root:** LearnerMasteryProfile. **VOs:** MasteryLevel, EvidenceRef(attemptId), DecayPolicy.
- **Commands:** RecordMasteryEvidence, RecomputeMastery.
- **Events:** SkillProgressed, SkillMastered, CompetencyAttained.
- **Read Models:** SkillMatrixView.
- **API:** `GET /me/skills`, `GET /me/competencies` (new). **Cache:** mastery profile per user. **Offline:** read-only. **Sync:** server-derived. **Conflict:** none (recomputed from attempts).

### Module 9 — Attendance & Participation (Live + Discussion)
- **Mission:** record participation execution (not the live session itself, which Live owns).
- **Entities:** LiveAttendance, DiscussionParticipation. **Root:** ParticipationRecord. **VOs:** AttendanceStatus, JoinLeaveWindow, ParticipationScore.
- **Commands:** RecordAttendance, RecordDiscussionActivity.
- **Events:** AttendanceRecorded, DiscussionParticipated.
- **API:** consumed via Live events; `GET /me/attendance` (new). **Cache:** per enrollment. **Offline:** attendance is server-timestamped (online). **Sync:** append-only. **Conflict:** dedupe by (session, user, window).

### Module 10 — Student Dashboard & Learning Analytics (read side)
- **Mission:** serve fast, learner-facing rollups; project learner-execution facts for Analytics.
- **Entities:** *(none — projections only)*. **Read Models:** StudentDashboardView, LearningAnalyticsProjection, ContinueLearningView, GradebookView.
- **Queries:** GetDashboard, GetMyProgressSummary.
- **App Services:** DashboardProjector (subscribes to all Learning events), AnalyticsProjector.
- **API:** `GET /me/dashboard` (new; supersedes ad-hoc joins). **Search:** own data. **Cache:** dashboard read model (rebuildable). **Offline:** dashboard snapshot cached. **Sync:** read-only, eventually consistent. **Conflict:** none.

### Module 11 — Offline & Sync (deep-dived below)
- **Mission:** make Learning writes durable offline and reconcilable on reconnect.
- **Entities:** SyncEnvelope, SyncQueueItem, ConflictRecord. **Root:** DeviceSyncState. **VOs:** ClientMutationId, VersionVector, MergeDecision.
- **Commands:** EnqueueMutation, PushSyncBatch, PullSyncBatch, ResolveConflict.
- **Events:** OfflineSyncStarted, OfflineSyncCompleted, SyncConflictDetected, SyncConflictResolved.
- **API:** `POST /sync/push`, `GET /sync/pull` (new). **Offline:** the whole point. **Sync:** idempotent by `clientMutationId`. **Conflict:** per-aggregate merge policy (below).

### Module 12 — Learning Notifications (triggers)
- **Mission:** raise learner-facing notification *intents* (delivery is Notifications context).
- **Commands:** RaiseLearningNotification (internal).
- **Events (consumed by Notifications):** LessonCompleted, CourseCompleted, CertificateEarned, BadgeUnlocked, StreakAtRisk, AssignmentGraded, LiveSessionStartingSoon.
- **API:** none exposed; Learning **emits**, Notifications **delivers**. **Ownership boundary:** Learning decides *when*, Notifications decides *how/where*.

---

# Entities

| Entity | Module | Key fields (conceptual) | Notes |
|--------|--------|-------------------------|-------|
| Enrollment | 1 | user, courseRef, status, source, completionPct, timestamps | exists today |
| LearningSession | 2 | user, courseRef, deviceId, resumePosition, checkpointVersion, state | **expanded** from today's pointer |
| LessonProgress | 3 | enrollmentId, lessonRef, status, positionSeconds, completedAt | exists today |
| CourseProgress | 3 | enrollmentId, pct, completedLessonRefs, version | **new read model** (was inline on Enrollment) |
| AssessmentAttempt | 4 | user, definitionRef(id+ver), seed, state, score, startedAt, submittedAt | **new** |
| AttemptResponse | 4 | attemptId, questionRef, answer, autoScore | **new**, child of attempt |
| AssignmentSubmission | 4 | user, definitionRef, artifacts(AssetRef[]), state, grade | **new** |
| Grade | 4 | subjectRef (attempt/submission), value, gradedBy, mode, rubricRef | **new** |
| CertificateReference | 1/4 | user, courseRef, certificateRef (Certification id) | **reference only** |
| PathEnrollment | 7 | user, pathRef, stepProgress[] | **new** |
| SkillProgress / CompetencyProgress | 8 | user, skillRef, masteryLevel, evidenceRefs | **new** |
| GamificationProfile | 5 | user, xpTotal, level, streaks | **new** |
| XPLedgerEntry | 5 | user, delta, sourceEventId, awardedAt | **new**, append-only |
| BadgeAward | 5 | user, badgeCode, awardedAt, sourceRef | **new** |
| Streak | 5 | user, kind(daily/weekly), count, lastActiveOn | **new** |
| LeaderboardEntry | 5 | scope, user, score, rank | **new**, materialized |
| LessonBookmark / LessonNote / Highlight | 6 | user, lessonRef, payload | bookmark/note exist; highlight new |
| ViewHistoryEntry | 6 | user, lessonRef, viewedAt | **new** |
| LiveAttendance / DiscussionParticipation | 9 | user, sessionRef/threadRef, status | **new** |
| SyncEnvelope / SyncQueueItem / ConflictRecord | 11 | clientMutationId, aggregate, payload, versionVector | **new** |

---

# Aggregates

| Aggregate Root | Consistency boundary | Invariants |
|----------------|----------------------|------------|
| **Enrollment** | status + completion% + its LessonProgress children (referenced, not embedded) | one active enrollment per (user, course); completion only reached via Progress Engine |
| **LearningSession** | per (user, course) — device checkpoints | at most one *authoritative* resume position; checkpoint version monotonic |
| **AssessmentAttempt** | attempt + its responses | attempt pins one definition version; state machine (Created→InProgress→Submitted→Graded); no edits after Submitted |
| **AssignmentSubmission** | submission + artifacts + grade | version-pinned; late flag immutable once set; grade transitions only Draft→Final |
| **PathEnrollment** | path + step progress | step unlock respects UnlockRule; completion monotonic |
| **LearnerMasteryProfile** | all skill/competency progress for a user | mastery derived only from EvidenceRef (attempts), never hand-set |
| **GamificationProfile** | XP total + level + streaks (+ append-only ledger) | XP total = Σ ledger; ledger entries idempotent by sourceEventId |
| **DeviceSyncState** | per (user, device) sync cursor + queue | each clientMutationId applied at most once |

**Design stance:** aggregates reference each other and content **by id/ref**, never by embedding. Cross-aggregate reactions happen through **events**, not synchronous writes — this is what removes the current synchronous fan-in.

---

# Value Objects

`CourseRef(id, version)` · `LessonRef(id, version)` · `AssetRef(id)` · `AssessmentDefinitionRef(id, version)` · `EnrollmentStatus` · `EnrollmentSource` · `CompletionPercentage(0–100)` · `ProgressStatus{NotStarted,InProgress,Completed}` · `PositionSeconds` · `ProgressWeight` · `CompletionRule{allLessons, weighted, requiredOnly}` · `AttemptState{Created,InProgress,Submitted,Graded,Expired}` · `Score(value, max)` · `GradingMode{Auto,Manual,Peer,Hybrid}` · `PassRule(threshold)` · `RandomizationSeed` · `DeviceId` · `DeviceKind{Phone,Tablet,Desktop,Web,SmartTV}` · `ResumePosition(lessonRef, positionSeconds)` · `Checkpoint(payload, version, takenAt)` · `CheckpointVersion` · `VersionVector` · `ClientMutationId` · `XP` · `Level` · `StreakCount` · `BadgeCode` · `MasteryLevel` · `RewardGrant` · `EvidenceRef(attemptId)`.

All VOs are immutable, equality-by-value, and carry no persistence identity.

---

# Commands

Grouped by module (write side). Each command is validated, authorized, and produces one or more events.

- **Enrollment:** EnrollInCourse · GrantEnrollment · SuspendEnrollment · ResumeEnrollment · Unenroll · ExpireEnrollment
- **Session:** StartSession · Heartbeat · PauseSession · ResumeSession · Checkpoint · EndSession · RecoverSession
- **Progress:** RecordLessonProgress · MarkLessonComplete · ResetProgress
- **Assessment:** StartAttempt · SaveAttemptDraft · SubmitAttempt · AutoGradeAttempt · RecordManualGrade · SubmitAssignment · RequestRetake · SubmitPeerReview
- **Path:** EnrollInPath · AdvancePathStep · CompletePath
- **Mastery:** RecordMasteryEvidence · RecomputeMastery
- **Gamification:** AwardXP · UnlockBadge · UpdateStreak · JoinChallenge · ClaimReward
- **Engagement:** ToggleBookmark · UpsertNote · AddHighlight · RecordView
- **Participation:** RecordAttendance · RecordDiscussionActivity
- **Sync:** EnqueueMutation · PushSyncBatch · PullSyncBatch · ResolveConflict

Every command carries an optional `clientMutationId` so it is **idempotent** and offline-replayable.

---

# Queries

`GetEnrollment` · `ListMyEnrollments` · `IsEnrolled` · `GetCourseProgress` · `GetSectionProgress` · `GetNextLesson` · `GetSession` · `GetAttempt` · `ListAttempts` · `GetGrade` · `GetGradebook` · `GetPathProgress` · `GetNextStep` · `GetSkillMatrix` · `GetCompetencies` · `GetGamificationProfile` · `GetLeaderboard` · `ListBookmarks` · `ListNotes` · `GetHistory` · `GetAttendance` · `GetDashboard` · `GetMyProgressSummary` · `PullSyncBatch`.

Queries read **read models**, not write aggregates, wherever a projection exists (dashboard, gradebook, leaderboard, progress).

---

# Events

**Emitted by Learning (published language — carried as DTOs, never Eloquent models):**

`EnrollmentCreated` · `EnrollmentSuspended` · `EnrollmentCompleted` · `Unenrolled` · `LearningSessionStarted` · `LearningSessionPaused` · `LearningSessionResumed` · `LearningSessionCheckpointed` · `LearningSessionEnded` · `LessonStarted` · `ProgressUpdated` · `LessonCompleted` · `SectionCompleted` · `CourseCompleted` · `AssessmentStarted` · `AssessmentDraftSaved` · `AssessmentSubmitted` · `AssessmentGraded` · `AssessmentPassed` · `AssessmentFailed` · `AssignmentSubmitted` · `ManualGradeRecorded` · `PathEnrolled` · `PathStepCompleted` · `PathCompleted` · `SkillProgressed` · `SkillMastered` · `CompetencyAttained` · `XPAwarded` · `LevelUp` · `BadgeUnlocked` · `StreakUpdated` · `StreakBroken` · `ChallengeCompleted` · `RewardGranted` · `AttendanceRecorded` · `DiscussionParticipated` · `OfflineSyncStarted` · `OfflineSyncCompleted` · `SyncConflictDetected` · `SyncConflictResolved`.

**Consumed by Learning (from other contexts):**

| Event | Source | Learning reaction |
|-------|--------|-------------------|
| `EnrollmentGranted` / order paid | Commerce | GrantEnrollment |
| `CoursePublished` / `CourseVersionPublished` | Catalog | refresh `CurriculumReadPort` cache; re-pin progress denominators |
| `LessonPublished` / `AssessmentPublished` | Authoring | invalidate curriculum/definition cache |
| `CertificateIssued` | Certification | store `CertificateReference` |
| `LiveSessionAttended` | Live | RecordAttendance |
| `MediaAssetReady` | Media Platform | mark lesson playable in dashboard |

## Subscribers (who listens to Learning events)

| Learning event | Subscriber(s) | Purpose |
|----------------|---------------|---------|
| `CourseCompleted` | **Certification** (issue cert), **Analytics** (completion KPI), **Notifications** (congrats), **Commerce** (drip/upsell) | today `Certification` already listens to `CourseCompleted` |
| `LessonCompleted` | Gamification (AwardXP), DashboardProjector, Notifications, Analytics | fan-out replaces inline recompute |
| `ProgressUpdated` | DashboardProjector, LearningSession (checkpoint), Analytics | |
| `AssessmentPassed` | Certification (exam-gated certs), Mastery (RecordMasteryEvidence), Gamification | |
| `AssignmentSubmitted` | Instructor/Authoring (grading queue), Notifications | grading UI is Learning; queue signal to instructor |
| `BadgeUnlocked` / `LevelUp` / `StreakUpdated` | Notifications, DashboardProjector | |
| `CertificateEarned`→`CertificateReference` | Notifications, DashboardProjector | |
| `OfflineSyncCompleted` | DashboardProjector, Analytics | reconcile projections |

All subscribers receive **event DTOs** and never reach back into Learning tables (Deptrac-enforced).

---

# Read Models

| Read model | Fed by | Serves | Rebuildable? |
|------------|--------|--------|--------------|
| **StudentDashboardView** | all Learning events | `/me/dashboard` | yes (replay) |
| **ContinueLearningView** | ProgressUpdated, SessionCheckpointed | `/continue-learning` | yes |
| **CourseProgressView** | LessonCompleted, ProgressUpdated | `/courses/{c}/learn` header | yes |
| **GradebookView** | AssessmentGraded, ManualGradeRecorded | learner + instructor gradebook | yes |
| **LeaderboardView** | XPAwarded (materialized ranking) | `/leaderboards/{scope}` | yes |
| **SkillMatrixView** | SkillProgressed, CompetencyAttained | `/me/skills` | yes |
| **LearningAnalyticsProjection** | all events | Analytics context ingestion | yes |

Read models are **derived and disposable** — the source of truth is the write aggregates + event ledger, so any projection can be dropped and rebuilt. This is the structural fix for the current "join content live on every read" problem.

---

# Services

**Application services (orchestration):** EnrollmentService · LearningSessionService · ProgressService *(retained, refactored to read via port)* · AssessmentService · AssignmentService · GradingService · PathService · MasteryService · GamificationService · EngagementService · ParticipationService · SyncService · DashboardProjector · AnalyticsProjector.

**Ports (outbound interfaces Learning depends on, implemented by other contexts):**

| Port | Implemented by | Replaces today's direct access |
|------|----------------|-------------------------------|
| `CurriculumReadPort` | Catalog/Authoring | `Section::published()`, `Lesson::published()`, ordering, prereq graph, weights |
| `AssessmentDefinitionPort` | Authoring | quiz/exam/assignment definitions, question pools, rubrics |
| `PlaybackPort` | Media Platform | `LearningMediaService` provider stack (Mux/CloudFront/S3) |
| `EntitlementPort` | Commerce/Catalog | `is_preview` / paid-access decision inside `LessonAccessService` |
| `CertificationPort` (consume) | Certification | store `CertificateReference` |

**Domain services (pure):** ProgressCalculator (weighted % from a curriculum snapshot + completed set), UnlockEvaluator (given curriculum prereqs + completed set → unlocked?), AttemptGrader (auto-grade given definition + responses), StreakCalculator, LeaderboardRanker, MergeResolver.

> Note: `LessonAccessService` and `LearningMediaService` are **kept** but their content/media reads are redirected through `CurriculumReadPort` / `EntitlementPort` / `PlaybackPort`. Public behavior is unchanged; the coupling is severed.

---

# Repository Interfaces

`EnrollmentRepository` · `LearningSessionRepository` · `LessonProgressRepository` · `CourseProgressRepository` · `AssessmentAttemptRepository` · `AssignmentSubmissionRepository` · `GradeRepository` · `PathEnrollmentRepository` · `MasteryRepository` · `GamificationProfileRepository` · `XPLedgerRepository` · `BadgeRepository` · `EngagementRepository` · `ParticipationRepository` · `DeviceSyncStateRepository` · `LeaderboardRepository`.

Each is an **interface** owned by Learning; Eloquent implementations live in Learning's infrastructure. Read models have their own read-optimized repositories (`DashboardReadRepository`, etc.) separate from write repositories (CQRS split).

---

# API Ownership

Learning owns exactly the learner-execution surface. **No content, media-byte, pricing, or publishing endpoint is Learning's.**

| Method + path | Module | Status |
|---------------|--------|--------|
| `GET /api/v1/my-learning` | Enrollment | exists |
| `GET /api/v1/continue-learning` | Session | exists |
| `POST /api/v1/courses/{course}/enroll` | Enrollment | exists |
| `GET /api/v1/courses/{course}/learn` | Progress | exists |
| `GET /api/v1/lessons/{lesson}` | Progress/Playback | exists (playback via `PlaybackPort`) |
| `POST /api/v1/lessons/{lesson}/progress` | Progress | exists |
| `POST /api/v1/lessons/{lesson}/bookmark` | Engagement | exists |
| `POST /api/v1/lessons/{lesson}/notes` | Engagement | exists |
| `POST /api/v1/lessons/{lesson}/highlights` | Engagement | **new** |
| `POST /api/v1/sessions/{course}/{start,heartbeat,pause,resume,checkpoint,end}` | Session | **new** |
| `POST /api/v1/assessments/{ref}/attempts` · `PATCH/POST attempts/{id}[/submit]` | Assessment | **new** |
| `POST /api/v1/assignments/{ref}/submissions` | Assessment | **new** |
| `GET /api/v1/me/gradebook` | Assessment | **new** |
| `POST /api/v1/paths/{path}/enroll` · `GET /paths/{path}/progress` | Path | **new** |
| `GET /api/v1/me/{skills,competencies,gamification,attendance,dashboard}` | 8/5/9/10 | **new** |
| `GET /api/v1/leaderboards/{scope}` | Gamification | **new** |
| `POST /api/v1/sync/push` · `GET /api/v1/sync/pull` | Offline/Sync | **new** |

Existing routes and payloads are **unchanged** (no API modification in this phase — new routes are future work). Media playback continues to return a signed, expiring token; raw `s3_key`/`mux_asset_id` never leave the server.

---

# Search Strategy

- **Scope:** Learning search is always **learner-scoped** — a user searches only their own enrollments, notes, history, gradebook. There is no cross-learner search except **leaderboards**, which are scoped by tenant/cohort and served from a materialized ranking (not a live scan).
- **Ownership:** Learning indexes **its own** state (notes text, course-progress, attempt history). It does **not** index course/lesson catalog content — that search is Catalog's. A "search my courses" query joins Catalog's catalog search results with Learning's enrollment filter via `CurriculumReadPort`, without Learning owning the content index.
- **Index tech:** notes/highlights full-text via Postgres FTS (small, per-user); leaderboards via materialized read model; dashboard via read-model lookups (no search engine needed).

# Cache Strategy

| Data | Key | Invalidation | TTL |
|------|-----|--------------|-----|
| Curriculum snapshot (from port) | `curriculum:{courseId}:{version}` | `CourseVersionPublished` / `LessonPublished` | long (version-keyed → immutable) |
| Course progress | `progress:{enrollmentId}` | projector on ProgressUpdated/LessonCompleted | until event |
| Continue-learning | `continue:{userId}` | SessionCheckpointed / ProgressUpdated | short |
| Dashboard | `dashboard:{userId}` | projector | short + rebuildable |
| Leaderboard | `leaderboard:{scope}` | periodic + XPAwarded batch | seconds–minutes |
| Playback token | **not cached** (short-lived, per-request) | n/a | ≤ token TTL |

The **version-keyed curriculum cache** is the core scalability win: because `CourseRef`/`LessonRef` carry a version, a cached curriculum snapshot is immutable and can be held aggressively; a new content version simply produces a new key. Progress denominators are computed against a **pinned** snapshot, so re-computation is O(cached) not O(query).

# Offline Strategy

Offline is a first-class mode, not an afterthought.

| Capability | Offline behavior |
|------------|------------------|
| Offline lessons | curriculum snapshot + media pre-fetched (via `PlaybackPort` offline-license); lessons playable without network |
| Offline progress | `RecordLessonProgress` written to a **local queue** with `clientMutationId`; UI reflects immediately |
| Offline notes/bookmarks/highlights | written locally, CRDT/LWW-friendly, queued |
| Offline assessments | **practice + graded** quizzes attemptable offline; **exams and proctored/certification** require online (integrity) |
| Conflict detection | on reconnect, each queued mutation carries the base `VersionVector` it was made against |
| Conflict merge | applied by per-aggregate `MergeResolver` (below) |
| Sync queue | ordered, durable, per-device; drained on reconnect |
| Retry queue | failed mutations retried with backoff; poison messages surfaced as `SyncConflictDetected` |

**Idempotency** is the backbone: every mutation is keyed by `clientMutationId`, so replay (from retry or duplicate push) is safe and exactly-once in effect.

# Synchronization Strategy

Multi-device sync across **Phone, Tablet, Desktop, Web, Smart TV**.

- **Model:** each Learning aggregate carries a `VersionVector` (or monotonic `CheckpointVersion` where a total order suffices). Devices push batches of mutations and pull server changes since their cursor.
- **Resume priority:** when several devices report a resume position, the winner is chosen by policy: **latest checkpoint by server-accepted version**, tiebroken by `last_activity_at`, tiebroken by device trust (Desktop/Web > Tablet > Phone > TV) — configurable per tenant.
- **Latest checkpoint wins** for *position* (you resume where you most recently were); **union/monotonic merge** for *completion* (you never lose a completed lesson by switching devices).
- **Pull/push:** `GET /sync/pull?since={cursor}` returns changed aggregates; `POST /sync/push` accepts a batch, returns per-item accepted/conflicted with the authoritative version.

# Conflict Resolution

Per-aggregate merge policy — the merge is **deterministic** and **never regresses learner achievement**:

| Aggregate | Merge policy |
|-----------|--------------|
| LessonProgress | position = **max**(positions); status = **max**(NotStarted<InProgress<Completed) — completion is sticky |
| CourseProgress | recomputed from merged LessonProgress against pinned curriculum |
| LearningSession resume | **latest checkpoint** by version + resume-priority tiebreak |
| Notes / Highlights | **field-level LWW** by updated_at + device tiebreak; notes retain edit-history tail |
| Bookmarks | set-union (toggle resolved to most-recent state) |
| AssessmentAttempt draft | **single-writer**: an in-progress attempt is locked to one device; a second device must take over explicitly (server issues a new lease) — no silent merge of exam answers |
| XPLedger / Badges | **commutative append**, deduped by `sourceEventId` — no conflict possible |
| Streaks | recomputed from activity dates (idempotent) |

Unresolvable cases (e.g., two devices submitted the *same* attempt offline) raise `SyncConflictDetected`, are quarantined in a `ConflictRecord`, and surfaced to the learner/instructor rather than silently dropped.

---

# Progress Engine

**Principle:** completion is **derived from evidence**, never asserted by a client. The engine consumes activity and computes rollups; it is the single authority for "done".

**Inputs:** a **pinned curriculum snapshot** from `CurriculumReadPort` — `{lessonRef, sectionRef, order, weight, isPreview, prereqRefs}` for a `CourseRef(id, version)` — plus the learner's `LessonProgress` set. It does **not** query Authoring/Catalog tables (this replaces `ProgressService::publishedLessonIds()`).

**Levels of completion:**

| Level | Rule | Emits |
|-------|------|-------|
| Lesson | status reaches Completed (video threshold / mark-complete / assessment pass, per lesson's CompletionRule) | LessonCompleted |
| Section | all *required* lessons in section complete | SectionCompleted |
| Course | weighted % ≥ `completion_percentage` (config, default 100) over pinned snapshot | CourseCompleted |
| Path | all required steps complete | PathCompleted |
| Skill | mastery ≥ threshold from EvidenceRefs | SkillMastered |
| Competency | all mapped skills mastered | CompetencyAttained |
| Certificate eligibility | course/exam completion satisfies Certification's requirement (evaluated by Certification, signaled by Learning's `CourseCompleted`/`AssessmentPassed`) | (Certification issues) → `CertificateReference` stored |

**Execution model (scalability fix):** `RecordLessonProgress` writes the leaf and emits `ProgressUpdated`. Rollup recomputation happens in a **projector** against the cached snapshot, not synchronously on the write path. The current synchronous `recomputeCoursePercentage()` becomes a projector reaction; the write returns immediately. Completion detection stays **idempotent** (re-recording a completed lesson is a no-op) exactly as today.

**Weighting & rules:** `CompletionRule ∈ {allLessons, weighted, requiredOnly}` comes from the curriculum snapshot (authored in Authoring), evaluated in Learning. Prerequisites (`UnlockEvaluator`) are evaluated from the snapshot's `prereqRefs` + completed set — the rule is authored upstream, the *decision* is Learning's.

---

# Assessment Execution

Learning runs assessments **defined by Authoring** (Phase 3). **Authoring owns the definition; Learning owns every attempt.** An attempt pins the definition version, so re-versioning a quiz never corrupts historical grades.

| Type | Attempts | Grading | Offline | Passing | Notes |
|------|----------|---------|---------|---------|-------|
| Practice quiz | unlimited | auto | yes | formative | no gradebook impact |
| Graded quiz | n (config) | auto (+manual override) | yes | score ≥ threshold | best/last/avg per definition |
| Exam | 1–2 | auto | **online only** | strict cut | heavy randomization/pool; lockout |
| Certification exam | strict + cooldown | auto (audited) | **online only** | official cut | result → Certification |
| Survey | 1 | none | yes | n/a | feedback only |
| Programming lab | n | auto (test runner) | partial | test pass % | runtime via infra port |
| Peer review | 1 submit + n reviews | peer + calibrated | mixed | reviews complete | reviewer assignment in Learning |
| Assignment submission | n (window) | manual/auto | draft offline | rubric/manual | artifacts as `AssetRef[]` |

**Attempt state machine:** `Created → InProgress → (SaveDraft*) → Submitted → Graded` (+`Expired` on timeout). No response edits after `Submitted`. **Randomization:** the pool/shuffle is an Authoring definition; the **seed and the exact question set the learner saw** are recorded in the attempt. **Manual grading:** happens in Learning's grading UI over the Authoring-defined rubric, emitting `ManualGradeRecorded`. **Late submission / retries / version pinning:** all recorded on the attempt; late flag immutable once set. `AssessmentPassed` feeds Certification (exam-gated certs) and Mastery (evidence).

---

# Gamification

Motivation layer, fully event-sourced so it is offline-safe and replayable.

| Feature | Design |
|---------|--------|
| **XP** | append-only `XPLedgerEntry(delta, sourceEventId)`; total = Σ ledger. Idempotent by source event → offline accrual reconciles cleanly |
| **Levels** | pure function of XP total (thresholds in config); `LevelUp` emitted when crossing |
| **Achievements / Badges** | rule engine subscribing to Learning events (e.g., `CourseCompleted`→ "Finisher"); `BadgeAward` idempotent by (user, badgeCode, sourceRef) |
| **Leaderboards** | materialized `LeaderboardView` per scope (global/tenant/cohort/course); ranked from XP ledger on a cadence + on XP batch; never a live scan |
| **Daily streaks** | `Streak(kind=daily)` recomputed from activity dates; `StreakUpdated`/`StreakBroken`; `StreakAtRisk` notification trigger |
| **Weekly streaks** | `Streak(kind=weekly)` same model, weekly bucket |
| **Challenges** | `ChallengeEnrollment` with goal + window; completion emits `ChallengeCompleted` → reward |
| **Rewards** | `RewardGrant` (badge, XP boost, unlockable) via `ClaimReward`; economic rewards (coupons) delegate to Commerce via event, Learning only records the grant |

**Boundary:** Gamification consumes Learning events and **never** drives content or pricing. Rewards that touch money/entitlement are *requested* from Commerce by event; Learning records only the gamification-side grant.

---

# Learning Session

Replaces today's single `last_lesson_id` pointer with a real, multi-device, offline-capable session aggregate.

| Capability | Design |
|------------|--------|
| **Resume** | `ResumePosition(lessonRef, positionSeconds)`; served to `/continue-learning`; chosen by resume-priority across devices |
| **Pause** | `PauseSession` sets state=Paused, freezes timers (exam time limits honor pause rules per definition) |
| **Offline** | session runs from local checkpoints; mutations queued with `clientMutationId` |
| **Multi-device** | one logical session per (user, course); each device contributes checkpoints tagged with `DeviceId`/`DeviceKind` |
| **Synchronization** | version-vectored checkpoints; latest-checkpoint-wins for position, monotonic for completion |
| **Recovery** | `RecoverSession` reconstructs state from the last accepted checkpoint after a crash/disconnect |
| **Auto-save** | periodic `Heartbeat`/`Checkpoint` (config interval) persists position without user action |
| **Checkpointing** | `Checkpoint(payload, version, takenAt)` — an atomic, restorable snapshot of session state |
| **Session timeout** | inactivity beyond `session.ttl` ends the session (`LearningSessionEnded`); resume position preserved; exam attempts expire per their own stricter rule |

The existing `UpdateLearningSession` listener (updates the resume pointer on `LessonProgressRecorded`) is **retained** and generalized into `Checkpoint` on `ProgressUpdated`.

---

# Event Flow

Representative end-to-end flows (all events are DTOs; no Eloquent crosses a context boundary).

**Enroll → learn → complete → certify:**
```
Commerce: OrderPaid ─▶ Learning.GrantEnrollment ─▶ EnrollmentCreated
Learner opens course ─▶ LearningSessionStarted
Plays lesson ─▶ RecordLessonProgress ─▶ ProgressUpdated
   └▶ (projector) CourseProgressView updated
   └▶ Gamification.AwardXP ─▶ XPAwarded ─▶ (maybe) LevelUp
Finishes lesson ─▶ LessonCompleted ─▶ SectionCompleted (when section done)
Last required lesson ─▶ CourseCompleted
   ├▶ Certification: issue ─▶ CertificateIssued ─▶ Learning stores CertificateReference (CertificateEarned)
   ├▶ Analytics: completion KPI
   └▶ Notifications: "You finished!"
```

**Assessment:**
```
StartAttempt ─▶ AssessmentStarted (pins definition version, seed)
SaveAttemptDraft* ─▶ AssessmentDraftSaved
SubmitAttempt ─▶ AssessmentSubmitted ─▶ AutoGradeAttempt ─▶ AssessmentGraded
   └▶ AssessmentPassed ─▶ Mastery.RecordMasteryEvidence ─▶ SkillProgressed/SkillMastered
   └▶ AssessmentPassed ─▶ Certification (if exam-gated)
   └▶ Gamification.AwardXP
```

**Offline → sync:**
```
(offline) RecordLessonProgress, UpsertNote  ─▶ local queue (clientMutationId)
reconnect ─▶ OfflineSyncStarted ─▶ POST /sync/push
   server applies idempotently ─▶ per-item accepted | SyncConflictDetected
   merges via MergeResolver ─▶ SyncConflictResolved
   ─▶ OfflineSyncCompleted ─▶ DashboardProjector reconciles
```

**Multi-device resume:**
```
Phone: LearningSessionCheckpointed(v5)
Desktop: LearningSessionCheckpointed(v6)  ← later version
GET /continue-learning ─▶ resume-priority picks v6 (latest checkpoint)
completion sets are union-merged (no lesson lost)
```

## Subscribers (summary table)

| Publisher event | Certification | Analytics | Notifications | Gamification | Mastery | Instructor/Authoring | Commerce | Live |
|-----------------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| CourseCompleted | ✔ | ✔ | ✔ | ✔ | | | ✔ | |
| LessonCompleted | | ✔ | ✔ | ✔ | | | | |
| AssessmentPassed | ✔ | ✔ | ✔ | ✔ | ✔ | | | |
| AssignmentSubmitted | | ✔ | ✔ | | | ✔ (grade queue) | | |
| BadgeUnlocked/LevelUp | | ✔ | ✔ | | | | | |
| AttendanceRecorded | | ✔ | | ✔ | | | | ✔ |
| OfflineSyncCompleted | | ✔ | | | | | | |

---

# Future Evolution

- **Adaptive learning:** a recommendation port that reorders next-steps from mastery + performance — Learning already owns the evidence (attempts, mastery, progress) needed to drive it, behind an `AdaptivePolicyPort`.
- **Cohort / social learning:** study groups, shared progress, group leaderboards — extends Gamification scopes and Participation without new ownership.
- **Proctoring & integrity:** an `IntegrityPort` for exam proctoring providers; attempts already record device, seed, and timing to support it.
- **xAPI / LRS export:** `LearningAnalyticsProjection` is the natural source for an xAPI statement stream to an external LRS.
- **Real-time collaboration:** live co-watching / shared sessions — the checkpoint/version-vector model generalizes to presence.
- **Spaced repetition:** a review-scheduler over mastery decay — Mastery already carries a `DecayPolicy`.
- **Wallet / rewards economy:** richer `RewardGrant` types, still delegating anything monetary to Commerce by event.

All future work stays inside the invariant: Learning adds *execution* capabilities; content/pricing/media/publishing ownership never migrates in.

---

# Learning Record Store (LRS)

Learning is, at its core, an **event-producing system**: every learner interaction is an immutable experience record. The LRS is the append-only ledger behind the read models — the source of truth from which every projection (dashboard, gradebook, leaderboard, analytics) is rebuilt. It is Learning-owned and standards-aligned (xAPI / SCORM / cmi5) without letting any standard leak into content ownership.

## Record types

| Record | What it captures | Producer |
|--------|------------------|----------|
| **Experience Event** | a single verb-object statement ("learner *completed* lessonRef v3") | every Learning command |
| **Learning Timeline** | per-learner ordered stream of experience events | LRS projection |
| **Activity Stream** | tenant/cohort feed of events (privacy-filtered) | LRS projection |
| **Evidence Record** | an experience event flagged as mastery/assessment proof (`EvidenceRef`) | Assessment + Mastery |
| **Learning History** | the durable, exportable record of everything a learner did | LRS store |
| **SCORM tracking record** | cmi.* runtime data (score, status, suspend_data, bookmark) from a SCORM package | Playback/SCORM runtime |
| **cmi5 statement** | AU launch/pass/fail/completed statements | cmi5 runtime |
| **xAPI statement** | actor-verb-object-result-context statement | LRS emitter |

## xAPI / SCORM / cmi5 compatibility

- **xAPI (Tin Can):** every Experience Event maps to an xAPI statement `{actor, verb, object, result, context}`. `actor` = learner (pseudonymized id), `object` = `lessonRef`/`AssessmentDefinitionRef` (an **IRI derived from the ref**, never embedding content), `result` = score/completion, `context` = enrollment/session/device. Learning is the **LRS**; it can also push statements to an **external LRS** via an `LrsExportPort`.
- **SCORM 1.2 / 2004:** a SCORM package (owned by Authoring/Media as bytes) runs in a runtime; its `cmi.*` calls are captured as SCORM tracking records and **normalized into Experience Events** (SCORM `completion_status` → `LessonCompleted`, `cmi.score` → attempt score). SCORM's `suspend_data`/`bookmark` become session checkpoint payload — reusing the Learning Session model.
- **cmi5:** AU statements are xAPI statements with cmi5 profile constraints; the LRS validates the `moveOn` criteria and emits the same internal events. cmi5 sits **on top of** xAPI, so no separate store.

**Ownership boundary:** the LRS records *that a learner interacted with* `object=IRI(ref)`; it never stores the content itself. SCORM/cmi5 **packages** are Media/Authoring assets; the LRS stores only the **runtime results**.

## Per-record policy

| Record | Owner | Retention | Replay strategy | Privacy | GDPR | Export | Archiving |
|--------|-------|-----------|-----------------|---------|------|--------|-----------|
| Experience Event | Learning | hot: 24 mo; then cold | full replay → rebuild any projection | pseudonymized actor id | erasure via crypto-shred of actor key; right-to-access via export | xAPI statement JSON | cold object store after hot window |
| Learning Timeline | Learning (projection) | rebuildable (no independent retention) | derived from events | learner-scoped | follows events (rebuild excludes shredded) | timeline JSON/CSV | n/a (projection) |
| Activity Stream | Learning (projection) | 90 days rolling | derived | tenant-scoped, privacy-filtered | aggregate only after window | feed export | n/a |
| Evidence Record | Learning (+ Certification consumes) | life-of-credential + legal min | replay from attempts | linked to grades → restricted | retained for credential integrity even after account close (legal basis) | evidence bundle (PDF/JSON) | long-term archive |
| Learning History | Learning | account life + statutory tail | canonical (not derived) | learner PII | export on request; erasure honored except legally-retained evidence | full learner export (xAPI + CSV) | archived on account close |
| SCORM/cmi5 records | Learning (runtime results) | with attempt | normalized then retained | as attempts | as attempts | xAPI conversion | with attempts |

**Replay** is the backbone: because the event ledger is immutable and append-only, any read model can be dropped and rebuilt, any audit question answered, and any point-in-time state reconstructed. **Auditability** = every state change traces to an event with actor, timestamp, device, and source command id. **GDPR erasure** is implemented by **crypto-shredding the actor key** (rendering events unlinkable to a person) while preserving aggregate/statistical integrity and legally-required evidence records under a documented lawful basis.

---

# Adaptive Learning Engine

An engine that personalizes *sequencing and support* from the evidence Learning already owns — **without owning content or changing what content means**. It recommends; Authoring's curriculum and the human learner/instructor stay authoritative. It sits behind an `AdaptivePolicyPort` so the policy implementation (rules today, ML later) is swappable.

## Inputs (all Learning-owned evidence)

Progress (position, completion, velocity) · assessment attempts + item-level responses · mastery profile (skills/competencies) · engagement (time-on-task, revisits, drop-off) · streaks/consistency · confidence signals (self-report + hesitation/retry patterns) · session/device context · curriculum snapshot (prereq graph, difficulty tags — read via `CurriculumReadPort`, **not** owned).

## Outputs (advisory signals, never silent content edits)

Personalized next-step ordering · dynamic prerequisite suggestions · knowledge-gap list · weak-topic flags · content/revision recommendations · difficulty adjustment hint · pace/velocity estimate · confidence estimate · "review now" (spaced-repetition) prompts · AI-tutoring signal payloads. Every output is a **suggestion** surfaced to the learner (or instructor), overridable.

## Capabilities

| Capability | How it's derived | Boundary |
|------------|------------------|----------|
| Personalized sequencing | rank unlocked next-steps by gap × mastery × velocity | reorders *within* authored constraints; never invents lessons |
| Dynamic prerequisites | suggest extra prereqs when gap detected | **suggestion**; authored prereqs (Authoring) remain hard gates |
| Knowledge gaps | skills below mastery threshold with recent failure evidence | from Mastery + attempts |
| Weak topic detection | topic-tagged item performance below cohort/percentile | topic tags authored upstream |
| Recommendation engine | content/next-course suggestions | consumes Catalog catalog via port; Catalog owns the content |
| Revision recommendations | spaced-repetition schedule over mastery decay | Learning owns the schedule |
| Difficulty adjustment | pick harder/easier variant from an authored pool | variants defined by Authoring; **selection** is Learning's |
| Learning velocity | lessons/mastery per unit time | pure Learning metric |
| Learning confidence | blend of self-report + behavioral signals | pure Learning metric |
| AI tutoring signals | structured context packet for an AI tutor | via `AIProvider` port; human-in-the-loop |
| Human override | learner/instructor can dismiss/override any recommendation | **always available; overrides logged as events** |
| Adaptive policies | per-tenant/course rules for how aggressive adaptation is | capability/flag-driven (Phase 2) |

## Decision engine

A pluggable `AdaptivePolicyPort` with two interchangeable implementations: **(1) rules/heuristics** (thresholds, weighted scores — ships first, fully explainable) and **(2) ML model** (future, behind the same port). Inputs → feature vector → policy → ranked suggestions + rationale. Every decision emits an `AdaptiveRecommendationMade` event (auditable) and records whether it was accepted/overridden — closing the loop for offline evaluation.

## Feature ownership & future AI

**Learning owns** all behavioral features and the decision loop. **Authoring owns** content, difficulty variants, and topic tags (read via port). **AI integration** is strictly human-in-the-loop and behind `AIProvider`/`AdaptivePolicyPort`: the AI *suggests*, the human *decides*; no AI recommendation silently reorders a published course or alters a grade. Adaptation intensity is governed by tenant capability (some orgs disable it entirely).

---

# Competency & Skill Framework

Learning owns the **learner's mastery** of competencies/skills; it does not own the *content* that teaches them (Authoring) nor the *credential* that certifies them (Certification). This section defines the framework Learning maintains and how it synchronizes with Certification.

## Structure

| Element | Definition | Owner |
|---------|------------|-------|
| **Framework** | a named taxonomy (e.g., "Frontend Engineering v2", or an external standard like SFIA) | Learning (framework registry) |
| **Competency** | a broad capability composed of skills | Learning |
| **Skill** | a measurable ability | Learning |
| **Sub-skill** | a decomposition of a skill | Learning |
| **Evidence** | an `EvidenceRef` (attempt/submission/assessment pass) proving progress | Learning (from Assessment) |
| **Mastery Level** | Novice→Developing→Proficient→Advanced→Expert (configurable) | Learning |
| **Decay** | mastery erosion over time without reinforcement (`DecayPolicy`) | Learning |
| **Recertification** | re-proving mastery after decay/expiry | Learning proves → Certification re-issues |
| **Competency Map** | graph linking competencies↔skills↔content↔assessments | Learning owns learner-side; Authoring owns content mapping |
| **Role-based competencies** | competency set required for an org role | Organization/CRM defines role; Learning tracks attainment |
| **Career paths** | ordered role/competency progression | Learning (execution) over Catalog/Org-defined paths |
| **Certification mapping** | which competencies a credential attests | Certification owns; Learning feeds evidence |

## Lifecycle

```
Framework defined ─▶ Skills/competencies declared ─▶ mapped to content+assessments (Authoring)
Learner attempts assessment ─▶ EvidenceRecord ─▶ Mastery recomputed ─▶ SkillProgressed/SkillMastered
Mastery ≥ threshold across mapped skills ─▶ CompetencyAttained
DecayPolicy applies over time ─▶ mastery downgrades ─▶ SkillDecayed ─▶ (recertification prompt)
Recertification passed ─▶ mastery restored ─▶ Certification re-issues credential
```

Mastery is **only ever derived from evidence** — never hand-set. Decay is a scheduled recompute (a review-scheduler reaction), aligned with the Adaptive engine's revision recommendations.

## Synchronization with Certification

- Learning emits `CompetencyAttained` / `AssessmentPassed`; **Certification decides** whether a credential is warranted (it owns requirements) and emits `CertificateIssued`; Learning stores a `CertificateReference` (never the certificate itself).
- **Recertification:** Certification signals expiry (`CredentialExpiring`); Learning schedules re-proof; on renewed mastery, Certification re-issues. Learning owns the *proof*, Certification owns the *credential*.
- **Mapping direction:** Certification's requirement→competency mapping is Certification-owned; Learning consumes it read-only to know which evidence matters for a given credential. No context writes the other's tables — all via events + refs.

---

# Learning Analytics Framework

Analytics **produced by** Learning from its own execution ledger. Learning computes learner-execution metrics and **projects** them; the Analytics context is a **consumer/aggregator** of these projections, not the producer of learner-level truth. (This resolves the current arrangement where the Analytics context subscribes directly to concrete domain events — it will subscribe to Learning's published analytics projection instead.)

| Metric | Owner | Calculation | Projection | Refresh | Retention | Consumers |
|--------|-------|-------------|------------|---------|-----------|-----------|
| Learning velocity | Learning | lessons/skills mastered ÷ active time | per-learner rollup | near-real-time (on ProgressUpdated) | 24 mo hot | learner, instructor, adaptive engine |
| Drop-off points | Learning | last-position histogram per lesson | per-lesson projection | hourly batch | 12 mo | instructor, Authoring (content fix), Product |
| Heatmaps | Learning | position density / interaction density per lesson | per-lesson matrix | hourly batch | 12 mo | instructor, Authoring |
| Watch time | Learning | Σ session active seconds | per-lesson & per-learner | near-real-time | 24 mo | instructor, org, billing-insight |
| Completion funnel | Learning | enroll→start→mid→complete counts | per-course funnel | hourly | 24 mo | instructor, org, marketing |
| Assessment performance | Learning | score dist, item difficulty, discrimination | per-assessment | on AssessmentGraded | life-of-item | instructor, Authoring (item analysis) |
| Skill progression | Learning | mastery over time | per-learner + cohort | on SkillProgressed | 24 mo | learner, org, adaptive |
| Competency progression | Learning | competency attainment over time | per-learner + role | on CompetencyAttained | 24 mo | org, Certification |
| Engagement score | Learning | weighted(recency, frequency, depth, streaks) | per-learner | daily | 24 mo | learner, org, churn/retention |
| Retention score | Learning | continued-activity probability | per-learner/cohort | daily | 24 mo | org, Product, Notifications (nudge) |
| Learning health score | Learning | composite(velocity, engagement, retention, mastery) | per-learner/org | daily | 24 mo | org exec, dashboards |
| Organization analytics | Learning→Org | tenant rollups of the above | per-tenant | daily | per-tenant policy | org admins (via Analytics context) |
| Instructor analytics | Learning→Instructor | per-instructor course/cohort rollups | per-instructor | daily | 24 mo | instructors |
| Predictive analytics | Learning (features) + model | at-risk / completion-likelihood | per-learner scores | daily/near-real-time | 12 mo | instructor, Notifications, org |
| AI analytics | Learning + `AIProvider` | narrative insights, anomaly summaries | on demand | on request | ephemeral | instructor, org (advisory) |

**Ownership rule:** Learning owns **learner-level** analytics (it has the ledger); the **Analytics context** owns cross-domain BI, dashboards, and export — it *consumes* Learning's published projections and blends them with Commerce/CRM data. Every projection is **rebuildable from the LRS**. Predictive/AI outputs are advisory and human-overridable, behind ports.

---

# Accessibility & Inclusive Learning

Accessibility spans three owners with clear seams: **Authoring** owns accessible *content* (captions, transcripts, alt-text, sign-language assets, reading level); **Media Platform** owns accessible *media delivery* (caption tracks, audio-description tracks, adaptive bitrate); **Learning** owns the *learner's accommodations, preferences, and their effect on execution* (extra time, alt formats, progress rules). Learning never authors accessible content — it **applies** the learner's needs to the experience.

## Capabilities & ownership

| Capability | Primary owner | Learning's role |
|------------|---------------|-----------------|
| Screen readers / keyboard navigation | Frontend (Next.js) + WCAG | Learning exposes semantic, per-learner state; no content ownership |
| Captions / subtitles | Authoring (authored) + Media (delivered) | Learning stores learner's caption preference + on/off/language |
| Transcripts | Authoring | Learning surfaces per lesson; tracks "read transcript" as an alt completion path |
| Audio descriptions | Authoring + Media | Learning stores preference; may accept AD track as completion evidence |
| Sign-language assets | Authoring + Media | Learning references + preference |
| Color accessibility / dyslexia mode / reading preferences | Frontend + learner profile | **Learning owns the AccessibilityProfile** (font, spacing, contrast, dyslexia mode) |
| Low-bandwidth mode / offline accessibility | Media (delivery) + Learning (offline) | Learning ensures captions/transcripts are packaged in the **offline bundle** so a11y survives offline |
| Learning accommodations | Learning | e.g., alt navigation, reduced-motion, simplified UI flags |
| Progress accommodations | Learning | alt completion rules (transcript-read counts as watched; relaxed thresholds) |
| Assessment accommodations | Learning | **extra time, extra attempts, alt formats, pause allowances** applied to attempts — recorded on the attempt, honored by the timer/grader |
| Accessibility profiles | Learning | the learner's canonical `AccessibilityProfile`, synced across devices |

## AccessibilityProfile (Learning-owned)

A per-learner value object synced like any other Learning state (multi-device, offline): `{ captions: on/off/lang, audioDescription, signLanguage, reducedMotion, dyslexiaMode, fontScale, contrastMode, extraTimeFactor, extraAttempts, altNavigation, lowBandwidth }`. It is applied at execution time: the Progress Engine honors alt completion rules; the Assessment engine honors time/attempt accommodations; the offline bundler includes required a11y assets.

## Synchronization with Authoring & Media Platform

- **Authoring → Learning:** the curriculum snapshot (`CurriculumReadPort`) carries **availability flags** — which a11y assets exist for a lesson (hasCaptions, hasTranscript, hasAD, hasSignLanguage, readingLevel). Learning uses these to know what it *can* offer; it never stores the assets.
- **Media Platform → Learning:** `PlaybackPort` returns the available **caption/AD/sign-language tracks** with the signed playback token, and honors `lowBandwidth`. Offline licenses include the a11y tracks the learner's profile requires.
- **Quality gate tie-in:** Authoring's **Accessibility quality gate** (Phase 3) ensures the assets exist before publish; Learning then guarantees they're **applied and available offline**. If a required accommodation's asset is missing, Learning surfaces it (and may offer an alt completion path) rather than silently degrading.
- **Accommodations as evidence:** accommodation grants (extra time, alt format) are recorded as Experience Events in the LRS — auditable, and honored consistently across devices and offline.

---

# Migration Strategy (no code in this phase)

Sequenced, each step independently shippable and reversible, and each preserving the current public API and DB.

1. **Introduce ports without moving data.** Define `CurriculumReadPort`, `PlaybackPort`, `EntitlementPort`, `AssessmentDefinitionPort` as interfaces; implement them as thin adapters over the *existing* Authoring/Catalog/Media reads. Point `ProgressService`, `LessonAccessService`, `LearningMediaService`, `ContinueLearningService` at the ports. **Behavior identical; coupling severed.** (No schema change — adapters read the same tables, just behind an interface.)
2. **Version-key the curriculum snapshot.** Add a cached, version-keyed read model for `{lessonRef, order, weight, isPreview, prereqRefs}`. Progress math consumes the snapshot, not live queries.
3. **Move rollup off the write path.** Convert synchronous `recomputeCoursePercentage()` into a projector reaction on `ProgressUpdated`. Introduce `CourseProgressView`. Enrollment.`progress_percentage` becomes a projection cache, not the source of truth (column retained → no DB migration required to start).
4. **Add the event ledger + read models** (Dashboard, Gradebook, Leaderboard) as *new* tables, populated by projectors; existing endpoints keep working, new `/me/dashboard` supersedes ad-hoc joins.
5. **Add Assessment Execution** (attempts, submissions, grades) as new aggregates + new routes — additive; nothing existing changes.
6. **Add Gamification, Paths, Mastery, Participation** as new modules subscribing to existing events.
7. **Add Offline/Sync** last: idempotent commands (`clientMutationId`), `/sync/push|pull`, `MergeResolver`. Existing online writes are a special case (empty version vector).
8. **Demote foreign Eloquent relations** (`Enrollment→Course`, `LessonProgress→Lesson`) to reference-resolution helpers once all business logic reads via ports — keeps the columns/FKs, removes the logic coupling. Enforce with Deptrac (Learning may not depend on `Authoring\Models` / `Catalog\Models` for logic).

Aligns with backend refactor chunks: Learning already lives under `App\Contexts\Learning` (STEP 5E). This redesign is the **internal** re-architecture that follows the relocation; it requires no further namespace move.

---

# Acceptance Criteria

1. **Ownership invariant holds:** Learning contains zero content/pricing/media-byte/publishing definitions; grep shows no business-logic dependency on `Authoring\Models` / `Catalog\Models` — only refs and ports.
2. **Every "Learning owns" item** has a home: an aggregate, read model, or module maps to each of the 30 canonical items.
3. **Every "Learning never owns" item** is accessed only via a port/ref (Curriculum, AssessmentDefinition, Playback, Entitlement, Certificate).
4. **Progress is derived, not asserted;** completion detection is idempotent; rollup is off the write path (projector).
5. **Assessment attempts pin definition versions;** historical grades are stable across content re-versioning; exams are online-only, practice/graded offline-capable.
6. **Gamification is event-sourced and idempotent** (XP by sourceEventId); leaderboards are materialized, not live scans.
7. **Learning Session supports** resume, pause, offline, multi-device, synchronization, recovery, auto-save, checkpointing, and timeout.
8. **Offline supports** offline lessons, progress, notes, and (practice/graded) assessments, with a durable sync queue, retry queue, conflict detection, and merge.
9. **Multi-device sync** across phone/tablet/desktop/web/smart-TV with defined resume-priority and latest-checkpoint semantics; completion never regresses on merge.
10. **Every conflict has a deterministic merge policy;** unresolvable conflicts are quarantined and surfaced, never silently dropped.
11. **All emitted events are DTOs;** no Eloquent model crosses a context boundary; subscribers are enumerated and Deptrac-enforced.
12. **All existing public APIs and DB schema are unchanged** by this design; new capability is strictly additive.
13. **Every read view is rebuildable** from write aggregates + event ledger (drop-and-replay verified).
14. **Certificates are references:** Learning stores `CertificateReference`; Certification remains the sole issuer.
15. **Cache keys are version-scoped** for curriculum; playback tokens are never cached and never expose raw storage identifiers.
