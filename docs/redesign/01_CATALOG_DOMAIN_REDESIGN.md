# Catalog Domain Redesign (Phase 1) — Architecture Only

**Role:** Principal Domain Architect. **Type:** documentation only — no code, no moves, no namespace/API/DB changes.
**Grounding:** current `App\...\Catalog` implementation (models, actions, events, services, Filament, API) as built and verified in audits 04/05 and refactor 01–07.
**Thesis:** Catalog is the LMS's **central reference (published-language) context**. It owns the canonical definition and lifecycle of a Course and its taxonomy — and **nothing else**. Marketing, Learning, Instructor, Administration and Commerce become **consumers** that read Catalog's published read models or drive it through commands, never reach into its internals.

---

## Executive Summary

Today Catalog is a single Eloquent-centric module that simultaneously serves four masters: it is the **public storefront read** (Marketing), the **admin write/moderation** surface (Administration), the **instructor's course workspace** (Instructor), and the **course reference** for enrollment (Learning). This is the "feature leakage" flagged in refactor 01. The redesign keeps Catalog as the **single source of truth for the Course aggregate and taxonomy**, but splits it internally into a **write side** (commands + aggregate invariants + lifecycle) and a **read side** (per-consumer read models), and exposes a **public contract** so no other context imports Catalog models.

Concretely: Catalog owns `Course`, `Category`, and the taxonomy value types (`Level`, `Language`, `Tag`), plus the publish/visibility/featured lifecycle and the course's canonical `slug`/SEO fields. It does **not** own pricing (Commerce), curriculum/lessons (Instructor/Authoring), enrollment/progress (Learning), or trainer identity (Identity). It **publishes events** (`CoursePublished`, `CourseUnpublished`, `CourseArchived`, `CourseUpdated`, `CourseVisibilityChanged`, `CourseFeaturedToggled`, `CategoryChanged`) that those contexts subscribe to. Publish-readiness (is the curriculum complete?) is an **inbound port** (`CoursePublishGuard`) implemented by Instructor/Authoring — Catalog asks, it does not inspect lessons.

The result: one owner per concern, a cache-friendly read side with event-driven invalidation, a clean instructor→moderation→publish workflow, and SEO/URL ownership that lives with the canonical slug.

---

## Current Problems

1. **Four consumers, one module.** `CourseController` serves public browse; `CourseResource` (Filament) serves admin; `CreateCourse/UpdateCourse` actions serve both admin and (intended) instructors; Learning reads `Course` directly. No boundary separates read from write or public from admin. *(refactor 01, C4 leakage)*
2. **No read/write separation.** The same `Course` Eloquent model is projected for the storefront, the admin table, and enrollment. List endpoints risk N+1 and over-fetching; there is no dedicated read model. *(audit 05/PERF-1)*
3. **Direct cross-context coupling.** Other contexts (`Learning`, `Commerce`, `Certification`, `Live`) import `App\...\Catalog\Models\Course` concretely rather than depending on a published contract. *(audit 04/DA-2)*
4. **Publish-readiness leaks in.** Publishing a course requires curriculum completeness, which lives in Authoring. The current `CoursePublishGuard`/`NullCoursePublishGuard` contract is a good seed, but the boundary and event flow around it are not formalized.
5. **Trainer ownership is ambiguous.** `course_trainer` links Courses to `User`s; `TrainerController` renders "trainers" publicly. Trainer *identity* is Identity's; Catalog only owns the **assignment** (which trainers teach a course).
6. **SEO/URL ownership undefined.** `slug` + `seoColumns` live on `Course`, but who renders `<title>`/canonical/OG and who guarantees slug uniqueness/immutability is not stated.
7. **No read caching.** Hot public reads (catalog listing, course detail) hit the DB every time. *(audit 04/G6)*
8. **Pricing confusion.** The brief lists `CoursePricingChanged` as a candidate Catalog event — but **pricing is Commerce's**, not Catalog's. This must be corrected: Catalog emits course *lifecycle* events; Commerce emits pricing events for products that reference courses.

---

## Proposed Catalog Boundary

**Catalog OWNS (write + canonical state):**
- The **Course** aggregate: identity (`public_id`), `title`, `slug`, `subtitle`, `description`, taxonomy assignments (level, language, categories, tags), **lifecycle state** (`status`: draft|published|archived), `visibility` (public|unlisted|private), `is_featured`, `position`, `thumbnail_path` reference, `published_at`, and the course's **SEO bag** + canonical slug.
- The **Category** aggregate: hierarchy, ordering, slug.
- Taxonomy reference data: **Level**, **Language**, **Tag** (as value objects / small reference entities).
- **Trainer assignment** to a course (the `course_trainer` link) — *not* trainer identity.
- The **publish workflow** (draft → published → archived) and its invariants.

**Catalog does NOT own (consumers / other contexts):**
- **Pricing, products, coupons, checkout** → Commerce (a `Product` references a `courseId`).
- **Curriculum, sections, lessons, media, publish-readiness rule** → Instructor/Authoring (implements the `CoursePublishGuard` port).
- **Enrollment, progress, completion** → Learning (references `courseId`).
- **Certificates** → Certification (reacts to Learning's `CourseCompleted`).
- **Live sessions** → Live (references `courseId`).
- **Trainer/user identity, roles** → Identity.
- **SEO rendering, storefront pages, marketing chrome** → Marketing (consumes Catalog's public read model + SEO fields).
- **Moderation UI + platform administration** → Administration (drives Catalog commands via Filament).

**Rule:** *Everything that is not the definition, taxonomy, or lifecycle of a course/category is a consumer.*

---

## Context Ownership (who does what with Catalog)

| Concern | Owner | Interaction with Catalog |
|--------|-------|--------------------------|
| Public course browse/detail | **Marketing** | reads **Public Read Model** (published+public only) via query/contract |
| Course authoring (curriculum) | **Instructor** (Authoring) | owns lessons; implements `CoursePublishGuard`; drives `SubmitForReview`/`Publish` commands for own courses |
| Course lifecycle (draft/publish/archive/feature/visibility) | **Catalog (write)** | commands + invariants + events |
| Moderation / platform admin | **Administration** | drives Catalog commands via Filament (moderate, feature, force-unpublish) |
| Enrollment reference | **Learning** | reads course by `courseId` via contract; subscribes to lifecycle events |
| Pricing / product | **Commerce** | `Product -> courseId`; owns pricing; subscribes to `CourseArchived`/`CourseUnpublished` |
| Trainer identity | **Identity** | Catalog stores assignment; renders trainer via Identity read model |
| SEO fields | **Catalog owns data**, **Marketing renders** | slug/seo on Course; Marketing builds `<title>`/canonical/OG |

---

## Entities

- **Course** *(aggregate root)* — `public_id`, `slug` (unique, immutable after first publish), `title`, `subtitle`, `description`, `level_id?`, `language_id?`, `status`, `visibility`, `is_featured`, `position`, `thumbnail_path?`, `published_at?`, `seo`, timestamps, soft-deleted. Holds collections of category-ids, tag-ids, trainer-ids (assignments).
- **Category** *(aggregate root)* — `public_id`, `slug`, `name`, `parent_id?`, `position`, `seo`.
- **CourseLevel** — reference entity (`slug`, `name`, `rank`).
- **CourseLanguage** — reference entity (`code`, `name`).
- **CourseTag** — reference entity (`slug`, `name`).

Trainers, Lessons, Products, Enrollments are **NOT** Catalog entities — they are foreign references owned elsewhere.

---

## Aggregates

| Aggregate | Root | Invariants enforced inside | Consistency boundary |
|-----------|------|----------------------------|----------------------|
| **Course** | `Course` | slug unique+immutable-post-publish; cannot publish unless `CoursePublishGuard` allows; archived is terminal-ish (unarchive = explicit command); featured implies published+public; visibility transitions legal; category/tag/trainer assignments reference existing ids | one Course + its assignment links (transactional) |
| **Category** | `Category` | slug unique; no cyclic parent; position within siblings | one Category subtree node |
| **Taxonomy** (Level/Language/Tag) | each root | slug/code unique | reference data (rarely changes) |

Cross-aggregate references are **by id only** (courseId, categoryId), never by object graph.

---

## Value Objects

- **Slug** — normalized, URL-safe, uniqueness-checked; immutable once the course has been published (URL stability / SEO).
- **CourseStatus** — enum `Draft | Published | Archived` (+ transition rules).
- **Visibility** — enum `Public | Unlisted | Private`.
- **SeoMeta** — `title?`, `description?`, `ogImage?`, `canonicalOverride?` (a bag; rendering is Marketing's).
- **LevelRef / LanguageRef / TagRef** — small immutable references (id + display).
- **TrainerAssignment** — `{ userId, role? }` (assignment only; identity is Identity's).
- **Position** — ordering integer within a scope (courses list, category siblings).

---

## Commands (write side)

Course lifecycle:
- `CreateCourse(draft)` · `UpdateCourseDetails` · `SetCourseTaxonomy(level, language, categories[], tags[])` · `AssignTrainers(userIds[])`
- `SubmitCourseForReview` *(Instructor)* · `PublishCourse` *(requires `CoursePublishGuard::canPublish`)* · `UnpublishCourse` · `ArchiveCourse` · `RestoreCourse`
- `ToggleFeatured` · `ChangeVisibility` · `ReorderCourses` · `SetThumbnail`

Category:
- `CreateCategory` · `UpdateCategory` · `ReorderCategories` · `MoveCategory(parent)`

Each command → one Application Service method → one transaction → domain event(s). (Maps cleanly onto today's `CreateCourseAction`, `UpdateCourseAction`, `PublishCourseAction`, `UnpublishCourseAction`, `ArchiveCourseAction`, `ToggleFeaturedAction`, `ReorderCoursesAction`, `CreateCategoryAction`, `ReorderCategoriesAction` — reframed as commands.)

**Authorization:** every command is gated by `CoursePolicy`/`CategoryPolicy` + `CatalogPermission`; instructor commands additionally scoped to owned/assigned courses.

---

## Queries (read side)

- `GetPublicCourseList(filters, page)` — published + public only. *(Marketing)*
- `GetPublicCourseDetail(slug)` — published + public. *(Marketing)*
- `GetPublicCategories()` / `GetPublicTrainers()` *(Marketing)*
- `GetInstructorCourseList(userId)` — all statuses for owned/assigned courses. *(Instructor)*
- `GetInstructorCourseDetail(userId, id)` *(Instructor)*
- `GetAdminCourseList(filters incl. status/visibility)` — everything. *(Administration)*
- `GetCourseForEnrollmentRef(courseId)` — minimal projection for Learning. *(Learning)*
- `GetRelatedCourses(courseId)` *(Marketing)*

Queries return **read models**, never Eloquent aggregates, and are the only cacheable surface.

---

## Events (domain events Catalog publishes)

| Event | Emitted when | Payload (DTO) |
|-------|--------------|---------------|
| `CoursePublished` | draft/unpublished → published | courseId, slug, categoryIds, visibility, publishedAt |
| `CourseUnpublished` | published → draft | courseId, slug |
| `CourseArchived` | any → archived | courseId, slug |
| `CourseRestored` | archived → draft | courseId |
| `CourseUpdated` | details/taxonomy changed on a published course | courseId, changed fields |
| `CourseVisibilityChanged` | visibility changed | courseId, from, to |
| `CourseFeaturedToggled` | featured flag changed | courseId, isFeatured |
| `CourseTrainersChanged` | assignment changed | courseId, userIds |
| `CategoryCreated` / `CategoryChanged` | category created/updated/moved | categoryId, slug, parentId |

**Not Catalog events (boundary correction):** `CoursePricingChanged` is **Commerce's** (`ProductPriceChanged`), not Catalog's — Catalog carries no price. Any consumer needing price subscribes to Commerce.

Events carry **DTOs (ids + primitives)**, never Eloquent models — this is what lets Analytics/Notifications consume without importing Catalog (audit 04/DA-1).

---

## Read Models (projections per consumer)

| Read model | For | Fields | Freshness |
|-----------|-----|--------|-----------|
| `PublicCourseCard` | Marketing list | slug, title, subtitle, thumbnail, level, language, featured, trainerNames, categorySlugs | cached; invalidated on lifecycle events |
| `PublicCourseDetail` | Marketing detail | + description, seo, categories, tags, trainers(ref), relatedIds | cached |
| `InstructorCourseRow` | Instructor | id, title, status, visibility, updatedAt, publishReadiness | live/short cache |
| `AdminCourseRow` | Administration | + owner, flags, moderation state | live |
| `CourseEnrollmentRef` | Learning | courseId, title, slug, status, visibility | live/short cache |

Read models are built by **projectors** subscribing to Catalog's own events (or computed on read with cache). They are the seam that removes N+1 and over-fetching.

---

## Services (application layer)

- **CourseLifecycleService** — orchestrates publish/unpublish/archive/restore/feature/visibility; calls `CoursePublishGuard`; emits events.
- **CourseAuthoringFacadeService** *(thin)* — create/update details+taxonomy+trainers (used by Instructor and Admin via policy).
- **CategoryService** — CRUD + reorder + move.
- **SlugService** — normalization + uniqueness + immutability rule. *(exists)*
- **CourseSearchService** — query/filter for public + admin lists. *(exists, reframed as read side)*
- **RelatedCoursesService** — related-course read model. *(exists)*
- **CatalogReadModelProjector** — maintains cached read models from events. *(new seam)*

Domain (invariant) logic stays in the `Course`/`Category` aggregates; services orchestrate + persist + emit.

---

## Repository Interfaces (ports)

Write side (aggregate persistence):
- `CourseRepository` — `find(id)`, `findBySlug(slug)`, `save(Course)`, `nextIdentity()`.
- `CategoryRepository` — `find(id)`, `save(Category)`, `tree()`.

Read side (projections):
- `PublicCatalogReadRepository` — `cards(filters,page)`, `detail(slug)`, `categories()`, `related(courseId)`.
- `AdminCatalogReadRepository` / `InstructorCatalogReadRepository` — status-aware lists.

Inbound port (implemented by another context):
- `CoursePublishGuard` — `canPublish(courseId): PublishReadiness` *(implemented by Instructor/Authoring; today's contract, formalized)*.

Outbound ports (Catalog needs from others, via contract not concrete class):
- `TrainerDirectory` — `resolve(userIds[]): TrainerRef[]` *(Identity)*.

(Interfaces replace the currently-unused generic `Repository` contract from audit 04/BE-1 with purpose-built ports.)

---

## API Ownership

Catalog owns these HTTP surfaces (URLs unchanged):
- **Public (read):** `GET /api/v1/courses`, `GET /api/v1/courses/{public_id}`, `GET /api/v1/categories`, `GET /api/v1/trainers` — served from **public read models** (published+public).
- **Admin/Instructor (write):** course/category create/update/publish/etc. under the authenticated admin API, gated by policy.

Not Catalog's API: pricing/checkout (Commerce), curriculum editing (Instructor/Authoring admin API), enrollment (Learning).

**OpenAPI ownership:** `catalog.yaml` is owned by Catalog and describes only catalog endpoints. Pricing fields shown on a public course page are **composed at the edge/read model** from Commerce's contract, not defined in `catalog.yaml`.

**Public course URL:** canonical `/(marketing)/(site)/courses/{slug}` (Marketing route) backed by `GET /courses/{public_id|slug}`. Slug is Catalog-owned and immutable post-publish → stable SEO URLs.

---

## Filament Ownership

- `CourseResource`, `CategoryResource` are **Catalog write-side admin resources**, surfaced in the Administration panel (discovered via the resource map introduced in refactor 5E — `Contexts/Catalog/Filament/Resources` once Catalog moves).
- Moderation actions (feature, force-unpublish, archive) are Filament actions that call **Catalog commands** (not direct model saves) so invariants + events fire.
- Instructor course editing is **not** Filament — it is the `(instructor)` web app calling the write API.

---

## Search Strategy

- **Now:** `CourseSearchService` over Postgres with existing indexes: `slug` (unique), composite `(status, visibility)`, `is_featured`; category/tag via pivots; `ILIKE`/trigram for title/subtitle.
- **Public search** always constrained to `status = published AND visibility = public`.
- **Next:** optional external index (Meilisearch/Typesense) fed by Catalog events (`CoursePublished`/`CourseUpdated`/`CourseUnpublished`/`CourseArchived`) as the projection source; read side queries the index for public search, DB for admin.
- Facets: category, level, language, tag, featured.

## Indexing Strategy

- Keep the existing DB indexes; add covering indexes for the public card projection if needed (`(status, visibility, is_featured, position)`), and FK indexes on `course_category`, `course_tag`, `course_trainer` (Postgres does not auto-index FK columns — audit 05/MIG-1).
- Slug unique index remains the URL integrity guarantee.

## Cache Strategy

- **Read models are the cache unit.** `PublicCourseCard`/`PublicCourseDetail`/categories cached under tags `catalog:course:{id}`, `catalog:list`, `catalog:categories`.
- **Invalidation is event-driven:** listeners on `CoursePublished/Unpublished/Archived/Updated/VisibilityChanged/FeaturedToggled/CategoryChanged/TrainersChanged` flush the matching tags. (Implements audit 04/G6 for the hottest surface first.)
- Admin/instructor reads are uncached (freshness) or very short TTL.
- CDN `Cache-Control` on public GET course JSON with stale-while-revalidate.

## Permissions

- `CatalogPermission` enum drives: `catalog.course.view|create|update|publish|unpublish|archive|feature|moderate`, `catalog.category.manage`.
- **Instructor:** create/update/submit + publish (own courses) **only if** publish-readiness passes; cannot moderate/feature.
- **Administration/super_admin:** full incl. force-unpublish, feature, moderate, category management.
- **Marketing/public:** read published+public only (no permission needed).
- `CoursePolicy`/`CategoryPolicy` enforce object-level scope (owner/assignee vs admin).

---

## Event Flow (subscribers per event)

```
PublishCourse (cmd)
  -> CoursePublishGuard.canPublish (Instructor/Authoring)   [inbound port]
  -> Course.publish() invariant
  -> CoursePublished (event) ─────────────┬─> Marketing: invalidate public read cache / rebuild card+detail
                                          ├─> Learning: mark course enrollable (ref projection)
                                          ├─> Search index: upsert public doc
                                          ├─> Analytics: metric (courses_published) [DTO only]
                                          └─> Notifications: notify subscribers/instructor [DTO only]

CourseUnpublished / CourseArchived ───────┬─> Marketing: remove from public read/search
                                          ├─> Learning: block new enrollment (existing access unaffected)
                                          ├─> Commerce: flag products referencing courseId (stop new sales)
                                          └─> Analytics/Notifications: react [DTO]

CourseUpdated / VisibilityChanged / FeaturedToggled ─> Marketing: refresh read/search
CourseTrainersChanged ─> Marketing: refresh trainer projection (via Identity TrainerDirectory)
CategoryCreated / CategoryChanged ─> Marketing: refresh category nav/read
```

Commerce's `ProductPriceChanged` flows the **other way**: Marketing's course page composes price from Commerce; Catalog neither emits nor consumes it.

---

## Dependency Rules

**Inbound (who may call Catalog):** Marketing (read only), Instructor (write own), Administration (write all), Learning (read ref), Commerce (read ref) — **all via public contracts/queries**, never Catalog Eloquent models.

**Outbound (what Catalog may call):**
- `CoursePublishGuard` (Instructor/Authoring) — inbound port, interface only.
- `TrainerDirectory` (Identity) — interface only.
- `Platform\Shared` base + `Platform\Identity` for `auth()`/policy.

**Allowed:** Catalog → Platform (Shared/Identity) via interfaces; Catalog → its own read/write repos.
**Forbidden:**
- Catalog → Commerce/Learning/Certification/Live (Catalog must not know about pricing, enrollment, certificates, sessions).
- Any consumer → `App\...\Catalog\Models\*` (must use the `CatalogContext` contract + read models).
- Catalog → Authoring **models** (only the `CoursePublishGuard` port).
- Events carrying Eloquent models (DTOs only).

Enforced by **Deptrac** rules (audit 04/DA-3) once contexts are physically split.

---

## Migration Strategy (no code in this phase)

Aligns with the ongoing STEP 5 chunked backend refactor:
1. **Relocate** `App\Domains\Catalog` → `App\Contexts\Catalog` (its own gated chunk, same mechanism as 5E; add its map line to the Filament `RESOURCE_PATHS` — already present).
2. **Introduce the publish port**: formalize `CoursePublishGuard` as `Contexts\Catalog\Contracts\CoursePublishGuard`, implemented in Instructor/Authoring; bind in providers (the current binding exists).
3. **Introduce read models + projector** behind the existing controllers (no URL change): `CourseController@index/show` returns the public read model; add cache tags + event invalidation listeners.
4. **Publish a `CatalogContext` facade** (queries + `TrainerDirectory` consumption) and migrate Learning/Commerce/Live to depend on it instead of `Course::class`.
5. **Split write vs read** folders inside `Contexts\Catalog` (`Write/` commands+aggregate, `Read/` read repos+projections) — internal refactor, API unchanged.
6. **Deptrac** rules enforcing the Dependency Rules; **cache** rollout for public reads; optional **search index** last.

Each step is independently shippable, verified by `php artisan test` + `route:list` (URIs unchanged) + `/admin` resource check — the same gate as the 5x chunks. No schema change; no URL change; no business-logic change.

---

## Future Evolution

Design horizon: **5 years, additive-only** — every evolution below is introduced behind the existing public contracts (`CatalogContext`, public read models, domain events) so consumers never break. New capabilities appear as new fields/events/read-models, never as changes to existing ones (semantic-versioned contracts; see External Integration Contracts).

- **Future modularization.** `Contexts\Catalog` splits internally into stable sub-modules — `Catalog\Course`, `Catalog\Taxonomy`, `Catalog\Edition`, `Catalog\Version`, `Catalog\Read` — each behind the same facade. Extraction to a separate service later is a deployment change, not a contract change, because all access is already via `CatalogContext` + events (never Eloquent). The publish-readiness `CoursePublishGuard` port already decouples Instructor/Authoring, so authoring can become its own service independently.
- **Multi-brand.** Add an optional `BrandId` value object on `Course`/`Category`/`Edition` and a `brandId` filter on every read model + query. Absence = the default brand (backward compatible). Brand identity/theme is **Marketing/Administration's** (`BrandSetting`), not Catalog's; Catalog only tags entities with a brand for scoping and emits `brandId` in events. Public URLs may gain a brand host/prefix without changing slugs.
- **Multi-region.** Add `RegionAvailability` (a set of allowed regions + a default) to Course/Edition as a value object; the public read model filters by the caller's region. Region does not fork the aggregate — it is an availability facet. Pricing-per-region stays **Commerce's**; Catalog exposes only *where a course is offered*, Commerce exposes *at what price there*.
- **Multi-language content.** Promote `title/subtitle/description/seo` to a `LocalizedText` value object (`{ locale => value }`) with a required default locale (backward compatible: today's single value becomes the default-locale entry). The public read model resolves to the requested locale with fallback. Slug may become per-locale (`slug[locale]`) with the default-locale slug remaining the canonical/immutable one for SEO. Translation *workflow* (who translates) is Instructor/Administration; Catalog stores the localized values.
- **Enterprise editions.** Realized via **Course Editions** (next section): visibility=`private`/`unlisted`, region/brand scoping, org-restricted availability. Enterprise entitlement/seat logic stays in **Organization**; Catalog only marks an edition as enterprise-scoped and emits availability events.
- **Marketplace readiness.** Add an optional `PublisherId`/`OwnerOrgId` to Course so third-party creators can own catalog entries; add `ListingStatus` (submitted/approved/rejected) layered on top of `status` for a marketplace moderation queue (Administration owns moderation). Revenue-share/payout is **Commerce's**. Public discovery, search, and read models already support arbitrary publishers because they key on `courseId`, not on a single-tenant assumption.

**Invariant across all of the above:** new dimensions (brand, region, locale, edition, publisher) are **facets/optional fields**, never new required inputs on existing commands; every added event field is additive; consumers opt in.

---

## Versioning Strategy

Introduces **Course Versions** as a sub-concept **inside the Course aggregate** (does not change the existing `status` model — it refines what "published" points at). A Course has at most one **Published Version** (what students and the public see) and at most one **Draft Version** (what the instructor edits); superseded versions become **Archived Versions** (immutable history).

### Version lifecycle
```
Draft(vN+1)  --submit-->  InReview(vN+1)  --publish(guard ok)-->  Published(vN+1)
     ^                                                                   |
     | (instructor keeps editing a NEW draft)                            v
Published(vN)  ----------------------------- becomes ------------> Archived(vN)
```
- Exactly one Published Version at a time = the **course's public/enrollment target**.
- Editing a published course **forks a new Draft Version** (copy-on-write) — the Published Version stays frozen and served.
- Publishing a Draft Version **atomically** promotes it to Published and archives the prior Published Version.

### Publishing flow
`PublishCourseVersion(courseId, versionId)` → `CoursePublishGuard.canPublish(versionId)` (Instructor/Authoring validates that version's curriculum) → atomic swap (new Published, old Archived) → emit `CourseVersionPublished(courseId, versionId, previousVersionId)` in addition to `CoursePublished` (kept for compatibility; `CoursePublished` continues to mean "there is now a published version").

### Rollback strategy
`RollbackCourseVersion(courseId, toVersionId)` re-promotes an Archived Version to Published (and archives the current). Because versions are immutable snapshots, rollback is a pointer swap, not a data migration. Emits `CourseVersionRolledBack`. Learners mid-course keep the version they enrolled against unless policy pins them to "latest" (see Student experience).

### Migration strategy (introducing versioning without breaking today)
Today's single mutable `Course` becomes **Published Version v1** on introduction (backfill). Existing consumers that read "the course" transparently read the Published Version via the same read models/contract — no consumer change. Draft/version tables are additive; no existing column changes.

### Storage strategy
- `course_versions` (additive table): `course_id`, `version`, `state(draft|in_review|published|archived)`, immutable snapshot of versioned fields (title/subtitle/description/seo/taxonomy assignments/thumbnail ref), `published_at`, `created_by`.
- The `courses` row keeps identity + `published_version_id` + `draft_version_id` pointers (thin), so `courseId` and slug remain stable.
- Snapshots store **references** (media by asset id, curriculum by version pointer) not copies — media lives in the Media Platform; curriculum version lives in Authoring and is referenced by id.

### Backward compatibility
- `CoursePublished`/`CourseUpdated` events remain; new `CourseVersion*` events are additive.
- Public URL/slug unchanged (points at course identity; resolves to Published Version).
- Read models unchanged in shape (they render the Published Version by default; an optional `?version=` is admin/instructor-only).

### Experiences
- **Student:** always studies the version they were enrolled against (pinned by default for continuity — no mid-course surprises); optional "update available → opt in to latest" prompt. Certificates reference the version completed.
- **Instructor:** edits a Draft Version freely while students continue the Published Version; sees a diff/preview; submits for review; can roll back.
- **Administration:** moderates version publishes, can force-rollback, sees full version history + audit (who published/rolled back which version).

---

## Course Editions

An **Edition** is a distinct, scoped offering of the same Course (same identity/curriculum lineage) — e.g. Spring, Summer, Enterprise, Corporate, Government, Partner. Editions are **owned by Catalog** as a child of the Course aggregate; each edition points at a **Published Version** and layers scope + associations owned by other contexts.

### Model
- `CourseEdition` (Catalog-owned): `edition_public_id`, `courseId`, `key` (spring-2026 / enterprise / gov), `label` (LocalizedText), `versionId` (which published version this edition serves), `visibility`, `availabilityWindow` (start/end), `RegionAvailability`, `brandId?`, `audienceScope` (public | org-restricted | partner), `status` (draft|active|retired).

### Edition-specific ownership & lifecycle
| Edition facet | Owner | Notes |
|---------------|-------|-------|
| **Pricing** | **Commerce** | a `Product` references `(courseId, editionKey)`; Catalog carries no price |
| **Availability / window / visibility / region / brand** | **Catalog** | edition state + scope; emits availability events |
| **Certificates** | **Certification** | edition→certificate-template binding; issued on completion of that edition's version |
| **Learning paths** | **Learning** | edition may map to a specific path/sequence over the version |
| **Cohorts** | **Live** | cohort/session series bound to an edition (dates, capacity) |
| **Teachers** | **Catalog assignment + Identity** | per-edition trainer assignment (assignment in Catalog, identity in Identity) |
| **Enrollment eligibility** | **Learning + Organization** | org-restricted editions gated by seat/entitlement (Organization) |

Lifecycle: `CreateEdition(draft)` → `ActivateEdition` (must reference a Published Version; emits `EditionActivated`) → `RetireEdition` (`EditionRetired`; existing learners unaffected, new enrollment blocked). Editions never fork the curriculum — they reference a Course Version. Default behavior: a Course with no explicit editions has an implicit "standard" edition = its Published Version (fully backward compatible).

Events (additive): `EditionCreated`, `EditionActivated`, `EditionRetired`, `EditionAvailabilityChanged` — consumed by Marketing (list/detail per edition), Commerce (price per edition), Live (cohorts), Certification (templates), Learning (paths/eligibility).

---

## Asset Ownership

Principle: **Catalog owns asset *references* (ids/keys + role), never the bytes.** All binary assets live in the **Media Platform** (a supporting/generic context — like Notifications on the Platform layer — that stores, processes, transcodes, and serves media via CDN and signed URLs). Each context owns the *association* between its entity and a media asset id.

| Asset | Owning context (association) | Bytes/processing/serving | Notes |
|-------|------------------------------|--------------------------|-------|
| Thumbnail | **Catalog** (course.thumbnailAssetId) | Media Platform | rendered by Marketing |
| Cover | **Catalog** | Media Platform | course/edition cover |
| Gallery | **Catalog** | Media Platform | ordered asset id list |
| Promo images | **Marketing** | Media Platform | campaign creative, not catalog canon |
| Promo videos | **Marketing** | Media Platform (Mux) | marketing trailer; distinct from lesson video |
| Lesson media (video/audio) | **Instructor/Authoring** (lesson.mediaAssetId) | Media Platform (Mux, signed) | Learning gates playback by enrollment |
| Downloads / PDFs (course resources) | **Instructor/Authoring** | Media Platform (S3, signed) | attached to lessons/course |
| SCORM packages | **Instructor/Authoring** | Media Platform (packaged, sandboxed) | referenced by a lesson |
| Captions / Subtitles / Transcripts | **Instructor/Authoring** (per lesson media) | Media Platform | localized; tied to lesson media asset |
| Certificates (rendered PDF) | **Certification** | Media Platform (private, signed) | issued artifact, not catalog |
| Brand assets (logo/theme) | **Administration/Marketing** (`BrandSetting`) | Media Platform | platform/brand scope, not per-course |
| SEO images (OG) | **Catalog owns ref (SeoMeta.ogImageAssetId)**, **Marketing renders** | Media Platform | canonical OG lives with the course |

Rules: Catalog stores `assetId` + `role` + `position` only; validation of mime/size/processing status is the **Media Platform's**; access control (signed URL, enrollment gate) is enforced by the **owning consumer** (Learning for lesson media, Certification for certificates). Catalog never reads bytes and never depends on S3/Mux directly — it references a Media asset id and subscribes to `MediaAssetReady`/`MediaAssetFailed` to know when a referenced asset is usable.

---

## External Integration Contracts

**Absolute rule:** Catalog has **zero** direct dependency on any external system. Catalog only (a) emits domain events and (b) exposes read models/contracts. Every external system is reached through an **adapter owned by another context/the Platform**, driven by a **transactional outbox** that publishes Catalog's events; consumers dedupe by event id (idempotency) and retry with backoff (consistent with the webhook pattern in audit 05).

For each: **Owner** = who implements the adapter · **Direction** relative to Catalog · **Transport** · **Event/trigger** · **Contract** · **Failure / Retry / Idempotency / Future**.

| Integration | Owner (adapter) | Direction | Transport | Trigger/Event | Contract | Failure | Retry | Idempotency | Future |
|-------------|-----------------|-----------|-----------|---------------|----------|---------|-------|-------------|--------|
| **Search engine** (Meili/Typesense) | Search adapter (Catalog Read side) | outbound | async worker via outbox | `CoursePublished/Updated/Unpublished/Archived/VisibilityChanged`, `Edition*` | upsert/delete public course doc by `courseId` | index unavailable → mark dirty | exp. backoff + reindex job | doc id = courseId; last-event-wins | swap engine behind same doc contract |
| **AI recommendation** | Recommendation context | outbound (signals) + inbound (results) | async events + read API | Catalog events + Learning signals | `Recommend(userId, context) -> courseIds[]` | fall back to `RelatedCoursesService` | n/a (best-effort) | recommendation cache keyed by user+context | pluggable model providers |
| **Semantic search** | Search adapter | outbound | async | same as Search | embed(title+desc) → vector upsert | skip on embed failure, retry | queue backoff | vector id = courseId+version | rerankers/hybrid search |
| **Vector database** | Search/Recommendation adapter | outbound | worker | `CourseVersionPublished` | upsert embedding(courseId, versionId) | dead-letter | backoff | (courseId, versionId) key | multi-namespace per brand/region |
| **Analytics** | Analytics context | outbound | domain events (DTO only) | all Catalog + Edition + Version events | `MetricEvent(name, dims, ts)` | buffered; drop-safe | n/a (fire-and-forget) | event id dedup in read model | new metrics = new subscribers |
| **CDN** | Media Platform | outbound (config) | HTTP/edge | asset publish | signed-URL + cache-control contract | origin fallback | edge retry | URL is content-addressed | multi-CDN |
| **Media processing** | Media Platform | inbound to Catalog (readiness) | events | `MediaAssetReady/Failed` | `assetId, status, variants` | Catalog keeps ref "pending" | processor retries | assetId | new asset types (SCORM/HLS) |
| **Email** | Notifications (Platform) | outbound | events → job | `CoursePublished` etc. | `Notify(template, audience, data)` | dead-letter (audit 05/JOB) | tries+backoff | notification id | new templates additive |
| **Notifications** | Notifications (Platform) | outbound | events | Catalog/Edition events | channel-agnostic `Notify` contract | dead-letter | tries+backoff | dedupe key per (user,event) | new channels |
| **Webhook consumers** (external partners) | Integration/Outbox context | outbound | signed HTTP POST | subscribed Catalog events | versioned JSON payload + signature | store-and-retry queue | exp. backoff, cap | delivery id + event id; consumer dedups | subscription mgmt UI |
| **ERP** | Integration adapter | bidirectional | batch/API via adapter | course lifecycle / edition availability | mapping contract (courseId↔ERP sku) | reconcile job | scheduled retry | external id map | more entities |
| **CRM** | CRM context | outbound (course refs) | events/API | `CoursePublished`, `Edition*` | course/edition ref DTO | best-effort | adapter retry | courseId map | lead↔course linking |
| **Commerce** | Commerce context | **internal** (contract, not external) | in-process events + contract | `CourseArchived/Unpublished`, `Edition*` | `Product -> (courseId, editionKey)`; Commerce emits `ProductPriceChanged` | order gating (audit 05) | job retry | event id | edition/region pricing |

**Cross-cutting guarantees:**
- **Outbox + at-least-once delivery** for all outbound integrations; **consumers are idempotent** (dedupe by event id / natural key).
- **Failure isolation:** an external outage never blocks a Catalog write (the write commits; the outbox drains later).
- **Contract versioning:** every outbound payload is semver'd; new fields additive; a breaking change ships a new payload version alongside the old for a deprecation window.
- **Future extensibility:** adding an integration = adding a subscriber/adapter to existing events — **no change to Catalog**.

---

## Acceptance Criteria

- **AC1 (single owner):** Catalog owns only Course/Category/taxonomy + lifecycle; grep shows no pricing/enrollment/curriculum logic inside Catalog.
- **AC2 (no leaked models):** No context outside Catalog imports `Catalog\Models\*`; all cross-context access is via `CatalogContext` + read models (Deptrac green).
- **AC3 (read/write split):** Public/instructor/admin reads use read models; writes go through commands + policies + events; a query never returns an Eloquent aggregate.
- **AC4 (publish port):** Publishing calls `CoursePublishGuard` (Instructor/Authoring); Catalog contains no lesson/curriculum inspection.
- **AC5 (events are DTOs):** All Catalog events carry ids/primitives; Analytics/Notifications consume them without importing Catalog.
- **AC6 (SEO/URL):** Slug is Catalog-owned and immutable post-publish; public URL `/courses/{slug}` is stable; Marketing renders SEO from Catalog's `SeoMeta`.
- **AC7 (pricing boundary):** Catalog emits no pricing event and stores no price; the public course page composes price from Commerce's contract.
- **AC8 (cache):** Public list/detail are cache-backed with event-driven invalidation; a publish/unpublish flushes the correct tags (test proves cache hit + invalidation).
- **AC9 (permissions):** Instructor can publish own courses only when ready; only admin can moderate/feature/force-unpublish.
- **AC10 (no behavior/URL/DB change during migration):** every migration step keeps `route:list` URIs identical, `php artisan test` green, `/admin` resources visible, and the DB schema untouched.
- **AC11 (Filament via commands):** Moderation actions call Catalog commands (invariants + events fire), not raw model saves.
- **AC12 (traceability):** every current artifact (actions/events/services/resources listed in "Current Problems"/"Services") maps to a target command/query/event/read-model here.
