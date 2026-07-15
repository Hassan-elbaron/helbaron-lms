# Curriculum Read Contract (Official)

> Chief Enterprise Architect — official architecture contract for how the **Learning** context reads **Curriculum** data (Authoring lessons/sections, Catalog courses). Learning depends only on this Shared contract + DTOs; it holds no Authoring/Catalog model. This document is the canonical surface; it reconciles the contract with the two implementation-driven additions made during execution.

## Contract location

- Port interface: `App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort` (Shared kernel).
- DTOs: `App\Platform\Shared\Curriculum\Data\{CourseRef, SectionRef, LessonRef}` (immutable `final readonly`).
- Implementation (temporary): `App\Domains\Authoring\Curriculum\CurriculumReadAdapter`, bound in `AuthoringServiceProvider`. Reads Authoring's own `Lesson`/`Section` and (existing, baselined) Catalog `Course`/`CourseStatus`. **To be split** into a Catalog course provider + an Authoring section/lesson provider (see Technical Debt).

## Port surface (canonical)

Enrollability & course resolution:
- `isCourseEnrollable(int $courseId): bool`
- `findCourseByPublicId(string $publicId): ?CourseRef`
- `courseRefById(int $courseId): ?CourseRef`

Lesson resolution:
- `lessonRefById(int $lessonId): ?LessonRef`
- `findLessonByPublicId(string $publicId): ?LessonRef`  ← implementation-driven addition
- `courseIdForLesson(int $lessonId): ?int`

Structure & progress math:
- `curriculumTree(int $courseId, bool $publishedOnly): array{course: ?CourseRef, sections: list<array{section: SectionRef, lessons: list<LessonRef>}>}`
- `orderedPublishedLessonRefs(int $courseId): list<LessonRef>`
- `publishedLessonIdsForCourse(int $courseId): list<int>`
- `publishedLessonIdsForSection(int $sectionId): list<int>`

Model→DTO mappers (transitional; consumed by the resource compatibility layer until resources drop model input):
- `courseRef(\Illuminate\Database\Eloquent\Model $course): CourseRef`
- `sectionRef(\Illuminate\Database\Eloquent\Model $section): SectionRef`
- `lessonRef(\Illuminate\Database\Eloquent\Model $lesson): LessonRef`

## DTOs (fields)

- **CourseRef**: `id:int`, `publicId:string`, `title:string`, `slug:string`, `thumbnailPath:?string`.
- **SectionRef**: `id:int`, `publicId:string`, `title:string`.
- **LessonRef**: `id:int`, `publicId:string`, `title:string`, `type:string`, `isPreview:bool`, `hasMedia:?bool`, `sectionId:int`, `courseId:int`, `position:int`, `prerequisiteLessonIds:int[]`, `content:?array`  ← `content` is an implementation-driven addition. Structural/detail fields (`sectionId`, `courseId`, `position`, `prerequisiteLessonIds`, `content`) are populated only by the detail read methods (`findLessonByPublicId`/`lessonRefById`); list/tree/nav refs leave them at defaults.

## Implementation-driven additions — why they are now official contract

Two contract elements were added during execution (Phase 3A/4) rather than at design time. They are promoted to the official contract because they are **structural necessities of the HTTP layer**, not incidental helpers:

1. **`findLessonByPublicId(string): ?LessonRef`.** External URLs address lessons by their **public id** — `HasPublicId::getRouteKeyName()` returns `'public_id'`, so `GET /api/v1/lessons/{lesson}` (and progress/bookmark/notes) previously relied on implicit route-model binding resolving the lesson by public id. Removing route-model binding (Phase 4) meant the controller receives the public id as a string and must resolve it through the port. The original design provided only `findCourseByPublicId` (courses are likewise public-id addressed for `/courses/{course}/…`); the lesson equivalent was missing. Without it, the lesson controllers could not be decoupled from the `Lesson` model. It is therefore a first-class part of the read contract, symmetric with `findCourseByPublicId`.

2. **`LessonRef.content` (`?array`).** The lesson-player response (`LearnerLessonResource`) renders the lesson **body/content** (a JSON structure). To serve the player from a DTO instead of the `Lesson` model, the content must travel on the ref. `LessonRef` originally carried only render-tree fields (title/type/isPreview/hasMedia). `content` was added so the player is fully model-free. It is populated only by the detail read methods (single-lesson reads), never in bulk tree/nav refs, to avoid carrying large payloads across many lessons.

Both additions are backward-compatible: `findLessonByPublicId` is additive; `LessonRef.content` is a defaulted (`null`) trailing constructor parameter, so all prior `new LessonRef(...)` sites remain valid.

## Consumer rules

- Learning services/actions/controllers call **only** the port + DTOs; they must not import `Authoring\Models\Lesson`/`Section`, `Authoring\Services\CurriculumTreeService`, `Catalog\Models\Course`, or `Catalog\Enums\CourseStatus`.
- DTOs are read-only projections; they never expose Eloquent models or raw media identifiers.
- Cross-context integration that legitimately requires a course/user (Commerce entitlement, Certification, Notifications) uses events + the `Enrollment` model relations (published-model seam), not this read port.
