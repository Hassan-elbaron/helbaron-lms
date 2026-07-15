# Instructor Authoring — Browser QA Report

Date: 2026-07-15 · Method: real Chrome (Claude-in-Chrome extension) against the running local stack (Docker API + Postgres + Redis, Next.js dev on localhost:3000).

## Classification correction (important)

A prior version of this report incorrectly classified missing instructor-authoring features as "intentional scope / Not Applicable" by inferring product scope from the absence of backend code. **That inference was wrong.** Missing code proves the feature is **not implemented** — it does not prove it is out of scope. Business scope is defined by the client PRD, not by the repository. No PRD statement was available saying instructors must never create or edit their own content. Therefore instructor authoring is classified below as a **MISSING PRODUCT CAPABILITY**, and Filament admin authoring is treated as a **different capability** from instructor self-authoring (both are recorded independently).

A frontend route `/teach/courses/{id}/edit` already exists and renders a "Coming soon" placeholder — i.e., the product surface is present but the capability behind it is unbuilt.

## Instructor capability status

| Capability | Status | Evidence |
|---|---|---|
| Instructor dashboard | **Implemented** | `/teach` renders real KPIs (3 Courses / 1 Students / 0 Completions; Published:3/Drafts:0/Archived:0; Recent enrollments) |
| Instructor course listing | **Implemented** | `/teach/courses` renders All/Draft/Published/Archived tabs + course cards (enrollments/completions/avg-progress/sections/lessons) |
| Instructor course analytics | **Implemented** | course detail `/teach/courses/{id}` renders Analytics KPIs |
| Instructor student roster | **Implemented** | `/teach/students` renders roster selector + loader |
| Instructor status management (publish/unpublish/archive) | **Implemented — browser-verified** | Full click-through tested as trainer Yara Adel: unpublish, publish (success + validation-blocked), archive confirm dialog. See "Browser workflows" below. |
| Instructor announcements | **Implemented — browser-verified** | Create + empty-validation + persistence + list render tested in the browser. See "Browser workflows" below. |
| Instructor course authoring | **Missing** | `/teach/courses/{id}/edit` renders "Coming soon"; no create/update-course API |
| Instructor section authoring | **Missing** | no section create/rename/reorder/delete API or UI in the instructor portal |
| Instructor lesson authoring | **Missing** | no lesson create/edit/reorder/publish API or UI in the instructor portal |
| Instructor media/resource authoring | **Missing** | no thumbnail/promo-video/resource/transcript upload API or UI in the instructor portal |
| Instructor live-session management | **Missing** | `/teach/sessions` renders "Coming soon"; no instructor live-session management API |

## Backend evidence (what exists today for instructors)
`apps/api/app/Domains/Catalog/routes/teach.php`: `GET dashboard`, `GET courses`, `GET courses/{course}`, `POST courses/{course}/publish|unpublish|archive`, `GET courses/{course}/students`, `GET|POST courses/{course}/announcements`. No authoring endpoints (create/update course/section/lesson/media). This documents the current implementation — **not** the intended scope.

## Note on Filament
Course/section/lesson authoring exists in the **admin Filament panel**. That is admin authoring, a distinct product capability. It does **not** satisfy an instructor-self-authoring requirement and is not counted as "instructor authoring implemented." See INSTRUCTOR_AUTHORING_IMPLEMENTATION_GAP.md for the full gap analysis and the Filament authoring functional-QA results.

## Browser workflows tested for the implemented instructor mutations

Executed 2026-07-15 in the user's real Chrome against the running local stack, signed in as the course-owning instructor **Yara Adel (trainer@helbaron.local, role: instructor)**. Test courses: "Business AI for Decision Makers" (0 sections), "Project Management Foundations" (1 section, 2 lessons, 1 enrollment).

| Workflow | Result | Evidence |
|---|---|---|
| **Unpublish** a published course | ✅ Pass | Clicked Unpublish on "Business AI…" → success toast **"Course moved to draft."** → after refetch the card badge flipped to **Draft** with a Publish button; persisted across full reload. Also unpublished "Project Management Foundations" and the badge updated **in place without reload** (confirming the list refetch works). |
| **Publish** — success path | ✅ Pass | Publishing "Project Management Foundations" (has a section) → `POST …/publish` returns **200, status: "published"**; card returns to Published. |
| **Publish** — validation-blocked path | ✅ Pass (correct behavior) | Publishing "Business AI…" (0 sections) → `POST …/publish` returns **422 `CATALOG_COURSE_PUBLISH_BLOCKED` — "The course has no sections."** and the UI shows an **error toast "The course has no sections."** (captured via continuous polling). The course correctly stays Draft. |
| **Archive** — confirmation dialog | ✅ Pass | Clicking Archive opens a modal: title "Archive this course?", body "Archived courses are hidden from the catalog and can't be enrolled in. You can restore it later from the Archived tab.", destructive-styled **Archive** button + **Cancel** + close (X). |
| **Archive** — Cancel path | ✅ Pass | Clicking **Cancel** dismisses the dialog with no state change (non-destructive). Confirm path not exercised to avoid an unrecoverable demo-data change. |
| **Announcement** — create | ✅ Pass | On "Project Management Foundations": filled Title + Message → Post → the announcement **persists and renders in the list** ("QA Test Announcement", dated 7/15/2026), and the form **clears** on success (success toast wired via `toast.success`). |
| **Announcement** — empty validation | ✅ Pass | With empty fields the **Post button is disabled** (`disabled={!title.trim() || !body.trim()}`) and both fields carry the `required` attribute; the `onSubmit` handler also guards `if (!title.trim() || !body.trim()) return`. |

**Conclusion:** every implemented instructor mutation works correctly in the browser. Two behaviours I initially flagged — "list doesn't update in place after a mutation" and "publish failure is silent" — were **re-verified and found to be observation-timing artifacts, not defects**: the mutation hooks do `invalidateQueries(["teach","courses"])` on success (the badge flips in place once the refetch lands), and the publish error toast does fire (it is transient and had faded before the earlier screenshot). No repository defect was opened from this workflow.

### Demo-data note (low severity)
The seed leaves two courses ("Business AI for Decision Makers", "Essential Business Skills") **Published with 0 sections/0 lessons**, a state the publish action itself forbids (`CATALOG_COURSE_PUBLISH_BLOCKED`). This is a **demo-data quality inconsistency** (the seeder sets `status = published` directly, bypassing the domain guard), not an application bug. Consequence during testing: once "Business AI…" was unpublished it could not be re-published through the instructor UI. Recommend the seeder either attach at least one section to any course it marks Published, or seed those content-less courses as Draft.

### Residual test-data changes (restore instructions)
Two changes were left on the local demo DB and could not be reverted from the QA environment (the Postgres/Docker stack runs on the host; this QA sandbox has no host DB access):
1. "Business AI for Decision Makers" is now **Draft** (was Published) — cannot be re-published via the UI because it has no sections.
2. A test announcement **"QA Test Announcement"** exists on "Project Management Foundations" (the instructor portal exposes no announcement-delete action).

Restore both in one host command:
```
docker compose exec api php artisan migrate:fresh --seed
```
(or re-run the demo seeder). "Project Management Foundations" was already restored to Published during testing.
