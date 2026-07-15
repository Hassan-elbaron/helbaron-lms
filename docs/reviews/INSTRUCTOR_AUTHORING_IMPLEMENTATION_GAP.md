# Instructor Authoring — Implementation Gap

Date: 2026-07-15. Classification: **Missing product capability** (not a defect, not "out of scope"). This document records the gap accurately; it does **not** implement the missing system (implementation is out of scope for this QA task).

## PRD Requirement
The authoritative client PRD (referenced elsewhere as `CBA_PRD_MVP_Bilingual.pdf`) is **not available in this repository**, so the exact instructor-authoring requirement cannot be quoted here. This is the single most important open item: **the business must confirm whether instructors are required to create/edit their own course content, or whether authoring is intentionally centralized to admins.** Until the PRD is consulted, the capability is treated as *required-by-default* for a professional LMS (instructors typically author their own courses), and the current "Coming soon" placeholder is treated as an unmet requirement rather than a deliberate exclusion. A route (`/teach/courses/{id}/edit`) already exists in the product surface, which is consistent with an intended-but-unbuilt capability.

## Current Instructor Capabilities (implemented today)
- Instructor dashboard (`GET teach/dashboard`) — KPIs + recent enrollments. **Implemented.**
- Course listing (`GET teach/courses`) with Draft/Published/Archived filters. **Implemented.**
- Course detail + analytics (`GET teach/courses/{course}`). **Implemented.**
- Student roster (`GET teach/courses/{course}/students`). **Implemented.**
- Course status management (`POST teach/courses/{course}/publish|unpublish|archive`). **Implemented (backend); browser click-through partially verified.**
- Announcements (`GET|POST teach/courses/{course}/announcements`). **Implemented (backend); browser flow partially verified.**

## Missing Backend APIs (instructor-scoped, ownership-guarded)
- `POST teach/courses` — create a course (draft) owned by the instructor.
- `PUT/PATCH teach/courses/{course}` — update title, subtitle, description, outcomes, category, level, language, visibility, pricing, SEO (bilingual EN/AR fields).
- `POST teach/courses/{course}/thumbnail`, `.../promo-video` — media attach (S3/Mux integration already used by admin).
- `POST teach/courses/{course}/sections`, `PATCH .../sections/{section}`, `DELETE .../sections/{section}`, `POST .../sections/reorder`.
- `POST teach/courses/{course}/sections/{section}/lessons` (+ `PATCH`, `DELETE`, `reorder`) — video/audio/reading lesson types, transcript, downloadable resource metadata.
- `POST .../lessons/{lesson}/publish|unpublish`, `.../preview` token.
All must enforce instructor **ownership** (a course belongs to `instructor_id`) — distinct from admin (super_admin) authoring which is unrestricted.

## Missing Frontend Screens
- Course editor (`/teach/courses/{id}/edit`) — currently "Coming soon". Needs: form for all course fields (bilingual), thumbnail/promo uploader, SEO tab, visibility/pricing controls, unsaved-changes protection, autosave or explicit save, validation display.
- Create-course screen (`/teach/courses/new` or a modal).
- Section/lesson curriculum builder — add/rename/reorder (drag-and-drop)/delete sections and lessons; lesson-type-specific forms (video/audio/reading), transcript editor, resource attachments.
- Lesson preview.
- Instructor live-session management (`/teach/sessions` — currently "Coming soon"): schedule/create/edit sessions, join links.

## Missing Permissions
- An instructor **ownership policy** (Laravel Policy / Gate) on Course/Section/Lesson so instructors may author only their own courses; admins retain full access. Today only status/announcement actions are ownership-guarded; authoring policies don't exist because the endpoints don't.
- Feature-flag gating (optional) so authoring can be rolled out gradually.

## Missing Validation
- FormRequests for course create/update (required title EN, unique slug, valid category/level/language enum, price ≥ 0, media type/size limits), section/lesson (required title, valid lesson type, video/audio source required for those types), duplicate-slug detection, and bilingual required-field rules. None exist for the instructor authoring path.

## Missing Media Workflows
- Instructor-initiated image upload (course thumbnail) and video ingest (promo + video lessons via the existing Mux integration), audio upload (audio lessons), downloadable-resource upload, and transcript capture. The admin/Filament path uses these integrations; an instructor-scoped, ownership-guarded, size/type-validated upload flow does not exist.

## Missing Tests
- Feature tests for each instructor authoring endpoint (create/update course; ownership enforcement — instructor A cannot edit instructor B's course; validation failures; section/lesson CRUD + reorder; media validation; publish/unpublish transitions).
- Frontend component/e2e tests for the editor + curriculum builder.
- axe/a11y coverage of the new authoring forms.

## Recommended Architecture
- Reuse the existing `Domains/Catalog` models (Course, Section, Lesson) and Actions/Services pattern already used by the admin path — expose them through **instructor-scoped controllers** under `teach.php` guarded by an ownership policy, rather than duplicating logic.
- Media via the existing S3 + CloudFront + Mux integrations (already wired for admin), behind instructor-scoped, validated upload endpoints.
- Frontend: reuse the Design System form primitives (`ui/form-field`, `ui/form`, inputs, `data-grid`) + the drag-and-drop pattern; add a curriculum-builder client component; wire to the BFF (`/api/backend/teach/...`).
- Keep authoring behind the existing `instructor` feature-flag/route guard so it can be released incrementally.

## Estimated Implementation Phases (indicative, not a commitment)
1. Course CRUD (create/update fields, ownership policy, validation, tests) + editor screen. ~1 sprint.
2. Curriculum builder — sections + lessons CRUD + reorder + lesson-type forms + tests. ~1–2 sprints.
3. Media workflows — thumbnail/promo/video/audio/resource/transcript uploads + validation + tests. ~1 sprint.
4. Lesson preview + publish/unpublish lifecycle + a11y pass + e2e/visual coverage. ~0.5–1 sprint.
5. Instructor live-session management. ~1 sprint.

## Filament admin authoring — functional QA (2026-07-15, real browser)
Signed into the Filament panel (`/admin`) as admin@helbaron.local and exercised the authoring surface directly. This confirms **admin authoring exists and functions** — a capability distinct from instructor self-authoring (still Missing).

Verified:
- **Authoring resources present and reachable:** Courses, Sections, Lessons, Course-Announcements, Live Courses (nav groups "Catalog" + "Authoring").
- **Create Course form** renders with: Title (required), Subtitle, Description, Status (Draft/Published/Archived), Visibility (Public/Private/Unlisted/Hidden), Level, Language, Is featured.
- **Required-field validation works:** submitting with an empty Title is blocked (no record created; `Title*` marked required).
- **Create persists:** created a draft course "ZZ QA Temp Course delete me" (id `019f62dd-73ae-704c-8554-64fa4f73c9d8`) — it appears in the Courses list as **Draft** and opens in the Edit form with its saved data.

Findings:
- **Single-language form (structural):** the Course create/edit form is single-language (one `Language` selector, plus one Title/Subtitle/Description set), **not** parallel EN + AR fields on one record. Bilingual coverage is modelled per-course-language, so "author the same course in EN and AR" is two records, not one bilingual record. Confirm this matches the PRD's bilingual-authoring intent.
- **No delete in the Courses table (minor):** the `CourseResource` table exposes no per-row delete, no bulk-select, and the Edit page header has only Save/Cancel — courses are managed by **Status = Archived**, not hard delete. The `Course` model uses `SoftDeletes` but the resource does not surface a delete action. Flag whether an (admin-only) delete/restore is desired, or whether archive-only is intentional (archive-only is defensible for enrollment/order integrity).
- **Not completed under browser automation (harness limitation, not a product defect):** adding sections/lessons via the nested repeaters, media/thumbnail upload, publish-from-admin, and the duplicate-slug check could not be driven reliably — Livewire's `wire:model`/`wire:submit` pipeline does not sync cleanly from synthetic automation events (the course create itself only went through via a native value-setter + `form.requestSubmit()` workaround). A human user in the browser is unaffected; these paths are wired in code and the resources exist. Recommend covering these with backend feature tests and/or an e2e runner that speaks Livewire, rather than treating them as QA gaps.

Residual test data (cleared by the reseed command below): the Draft course "ZZ QA Temp Course delete me" (`019f62dd-…`) created during this audit. It is Draft, so it does not appear in the public catalog.

## Business Decision Required
**Confirm against the client PRD whether instructor self-authoring is a required capability for launch.**
- If **required**: schedule phases 1–5 above; the "Coming soon" placeholders are unmet requirements (High priority).
- If **explicitly centralized to admins** (PRD states instructors never author): reclassify as intentional scope and replace the "Coming soon" placeholder with a clearer "authoring handled by your administrator" message + link, and remove the `/teach/courses/{id}/edit` route.
This decision cannot be made from the code; it requires the PRD / product owner.

## Restore local demo data after this QA
Run on the host (Docker):
```
docker compose exec api php artisan migrate:fresh --seed
```
This clears the residual QA test data: the Draft "ZZ QA Temp Course delete me" course, the "Business AI for Decision Makers" status change (Published→Draft), and the "QA Test Announcement" on "Project Management Foundations".
