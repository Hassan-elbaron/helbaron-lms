# Learning Media — PlaybackPort Refactor Plan (Media Group)

> Chief Enterprise Architect. **PLANNING ONLY** — no code, port, adapter, API, or schema was created or modified. Scope: the **Media** dependency group only — `Learning → Authoring\Models\LessonMedia` (5 import sites) plus the `LearningMediaService` that consumes media via the `$lesson->media` relation. Every fact below is from a direct read of the six files, `LessonMedia`'s model + migration, `config/learning.php`, and `LearningServiceProvider`, reconciled with `LEARNING_CONTEXT_DEPENDENCY_AUDIT.md` and `DEPENDENCY_CLEANUP_PLAN.md`. Execution-dependent claims are marked **"Not verifiable from repository."**

---

## Executive Summary

Learning issues signed, expiring media URLs by reaching directly into Authoring's `LessonMedia` Eloquent model: the `PlaybackTokenProvider` contract and all four provider adapters (`Mux`, `CloudFront`, `S3`, `Fake`) type-hint `LessonMedia`, and `LearningMediaService` obtains it through the `$lesson->media` relation. That is **5 forbidden `use LessonMedia` sites** (contract + 4 providers) plus one relation-based coupling in the service.

The target is to depend on a **`MediaAssetRef` DTO** through a **`PlaybackPort`**, so Learning holds only a value object and receives an opaque `PlaybackToken` back — never a foreign model. The signing logic already operates on a tiny slice of `LessonMedia` (`mux_playback_id`, `s3_key`, and a fallback id), so the DTO maps cleanly onto real columns.

**Critical architectural finding (drives the whole design):** naively "moving the providers to a Media platform" does **not remove** the coupling — it **relocates** `Learning→LessonMedia` into `Media→LessonMedia`, which is *also* forbidden (the `Media` layer's Deptrac rule is `[Shared, IdentityContracts]`, so it may not read Authoring models either). The coupling only truly disappears when it is **split**:

1. **Signing is infrastructure** → lives in the **Media** platform and operates on `MediaAssetRef` (no `LessonMedia`). The four providers lose their `LessonMedia` import.
2. **The lesson→asset lookup owns `LessonMedia`** → stays in **Authoring** (which owns the model; intra-context read is legal) behind a small Shared port that returns a `MediaAssetRef`.

With that split, Learning depends only on two Shared contracts, Media depends only on Shared, Authoring reads only its own model, and **no new boundary violation is introduced**. This is executed via expand-and-contract (parity adapter first, repoint behind a flag, delete the imports, shrink the Deptrac baseline). **Recommendation: proceed with the split design; do not relocate `LessonMedia` or the providers wholesale into Media.**

---

## Current Media Coupling

| Where | Coupling | Evidence |
|-------|----------|----------|
| `Contracts/PlaybackTokenProvider.php` | `use Authoring\Models\LessonMedia` — port signature `issue(LessonMedia $media, int $ttlSeconds): PlaybackToken` | line 5, 14 |
| `Playback/Providers/MuxPlaybackTokenProvider.php` | `use LessonMedia`; reads `$media->mux_playback_id` | line 5, 21–23, 39, 48 |
| `Playback/Providers/CloudFrontPlaybackTokenProvider.php` | `use LessonMedia`; reads `$media->s3_key` | line 5, 19, 21, 26 |
| `Playback/Providers/S3PlaybackTokenProvider.php` | `use LessonMedia`; reads `$media->s3_key` | line 5, 17, 19, 24 |
| `Playback/Providers/FakePlaybackTokenProvider.php` | `use LessonMedia`; reads `$media->mux_playback_id ?? $media->s3_key ?? $media->id` | line 5, 15, 20, 23 |
| `Services/LearningMediaService.php` | consumes `$lesson->media` (Authoring relation) → passes to `PlaybackTokenProvider::issue()` | line 27, 33 (imports `Lesson`+`User`, **not** `LessonMedia`) |

**Forbidden `use LessonMedia` sites: 5** (contract + 4 providers). `LearningMediaService` adds a 6th coupling via the relation but has no direct import (its `Lesson`/`User` imports belong to the Curriculum/Identity groups, out of scope here).

Fields of `LessonMedia` actually touched by signing (from the model + migration `2025_01_03_000120_create_lesson_media_table`): `mux_playback_id` (nullable string), `s3_key` (nullable string), `id` (fallback ref, Fake only). Never touched by any provider: `mux_asset_id` — the security invariant (documented in `PlaybackToken` and each provider) is that raw storage identifiers (`s3_key`, `mux_asset_id`) **never leave** the service; only the signed URL does.

---

## Current Playback Flow

1. **Controller** — `LessonPlayerController::show(Request, Lesson $lesson, LessonAccessService, LearningMediaService $media)` calls `$media->hasMedia($lesson) ? $media->playbackFor($user, $lesson) : null` (line 25) and serializes the result into the `playback` block of the lesson-player response.
2. **Service** — `LearningMediaService::playbackFor(User, Lesson)` (a) `assertAccess`, (b) `$media = $lesson->media` (Authoring relation), (c) throws `MediaUnavailableException` if null, (d) returns `$this->playback->issue($media, config('learning.playback.ttl_seconds', 600))`. `hasMedia(Lesson)` = `$lesson->media !== null`.
3. **Binding** — `LearningServiceProvider` (line 37) binds `PlaybackTokenProvider::class` → `PlaybackTokenManager->resolve()`.
4. **Manager** — `PlaybackTokenManager::resolve()` matches `config('learning.playback.provider', 'fake')` → `s3` | `cloudfront` (built with `CloudFrontUrlSigner` from `services.cloudfront`) | `mux` (built with `services.mux`) | default `fake`. So **provider selection is global config, per environment — not per asset.**
5. **Provider `issue(LessonMedia, ttl)`** — Mux: RS256 JWT (`Jwt::rs256`) over `mux_playback_id` → `{base}/{playback_id}.m3u8?token=…`, `kind='video'`. CloudFront: `CloudFrontUrlSigner::sign(s3_key, expiry)`, `kind='file'`. S3: `Storage::disk('s3')->temporaryUrl(s3_key, expiry)`, `kind='file'`. Fake: `hash_hmac` over an internal reference → `/media/stream/{sig}?expires=…`, `kind='video'|'file'`. All return `PlaybackToken {url, expiresAt: CarbonInterface, kind}`.

The refactor must reproduce **each of these four outputs byte-for-byte** (same URL construction, same expiry, same `kind`).

---

## Files Involved

Six files (5 with the `LessonMedia` import + the consuming service):

1. `app/Contexts/Learning/Contracts/PlaybackTokenProvider.php`
2. `app/Contexts/Learning/Playback/Providers/MuxPlaybackTokenProvider.php`
3. `app/Contexts/Learning/Playback/Providers/CloudFrontPlaybackTokenProvider.php`
4. `app/Contexts/Learning/Playback/Providers/S3PlaybackTokenProvider.php`
5. `app/Contexts/Learning/Playback/Providers/FakePlaybackTokenProvider.php`
6. `app/Contexts/Learning/Services/LearningMediaService.php`

Supporting (touched by wiring, not by the import): `Playback/PlaybackTokenManager.php`, `Playback/Data/PlaybackToken.php`, `Providers/LearningServiceProvider.php` (binding), `Http/Controllers/Api/V1/LessonPlayerController.php` (consumer), `config/learning.php`.

---

## Required Future DTO — `MediaAssetRef`

Immutable value object (`final readonly`, matching the `PlaybackToken` / A2-S05 event-DTO convention). Lives in a Shared/Media contracts namespace so both Learning and the Media platform may reference it. **Server-side only — never serialized to a client.** Field mapping to real `LessonMedia` columns:

| Field | Type | Source column / derivation | Notes |
|-------|------|----------------------------|-------|
| `id` | `string` | `LessonMedia.public_id` (via `HasPublicId`) | stable, non-sequential asset identifier; used for logging/fallback, not returned to client |
| `provider` | `string` | derived: `mux_playback_id !== null ? 'mux' : 's3'` | per-asset storage backend hint (Mux vs object storage); the signing strategy may use it. Current global config selector is preserved for parity |
| `playbackId` | `?string` | `mux_playback_id` | the PUBLIC Mux playback id (never `mux_asset_id`) |
| `storageKey` | `?string` | `s3_key` | object-storage key; used only server-side to sign |
| `mimeType` | `?string` | `mime_type` | descriptive |
| `durationSeconds` | `?int` | `duration` (cast int, seconds) | descriptive |
| `policy` | `MediaAccessPolicy` (small VO) | derived from `config('learning.playback')` | `{ signed: true, ttlSeconds: int, visibility: 'private' }`; carries signing requirements so the port needs no other config |
| `metadata` | `array<string,mixed>` | `{ filesize }` (+ future) | catch-all; **excludes `mux_asset_id`** (must never leave the Media platform) |

Illustrative shape (design only — not to be created in this phase):

```
final readonly class MediaAssetRef
{
    public function __construct(
        public string  $id,
        public string  $provider,          // 'mux' | 's3'
        public ?string $playbackId,        // mux_playback_id
        public ?string $storageKey,        // s3_key
        public ?string $mimeType,
        public ?int    $durationSeconds,
        public MediaAccessPolicy $policy,   // { signed, ttlSeconds, visibility }
        public array   $metadata = [],      // filesize, etc. (no mux_asset_id)
    ) {}
}
```

Design rules: no Eloquent, no Illuminate coupling, no `mux_asset_id`. `playbackId`/`storageKey` are permitted on the DTO because it never crosses the port→client boundary — only the opaque `PlaybackToken` does, exactly as today.

---

## Required Future Port — `PlaybackPort`

Two responsibilities are separated so the coupling truly dissolves (see Executive Summary): **signing** (Media platform, `MediaAssetRef`-based) and **lesson→asset lookup** (Authoring, `LessonMedia`-owning).

**`PlaybackPort`** — signing; implemented in the **Media** platform; depends on Shared only.

```
interface PlaybackPort
{
    /** Sign an opaque, expiring URL for an asset. Replaces PlaybackTokenProvider::issue(LessonMedia,...). */
    public function issue(MediaAssetRef $asset, int $ttlSeconds): PlaybackToken;
}
```

**`MediaAssetPort`** (companion) — lookup; implemented in **Authoring** (owns `LessonMedia`); depends on Shared only. Required to replace `LearningMediaService`'s `$lesson->media` / `hasMedia` without importing the model.

```
interface MediaAssetPort
{
    /** Resolve the signable media asset for a lesson, or null. Replaces $lesson->media. */
    public function assetForLesson(int $lessonId): ?MediaAssetRef;
}
```

`PlaybackToken` (existing `{url, expiresAt, kind}`) is promoted to the same Shared/Media contract namespace so it can be the port's return type unchanged. `MediaUnavailableException` semantics are preserved: the port implementations throw it (or return null for lookup) on missing `playbackId`/`storageKey`, exactly where the providers/service do today.

Methods required, summary: `PlaybackPort::issue(MediaAssetRef, int): PlaybackToken`; `MediaAssetPort::assetForLesson(int): ?MediaAssetRef`. (A convenience `hasAssetForLesson(int): bool` is optional; `assetForLesson(...) !== null` covers `hasMedia`.)

---

## Migration Strategy — Expand-and-Contract

Per `DEPENDENCY_CLEANUP_PLAN.md` Safe Refactoring Rules: introduce contracts + parity adapters over the *current* logic, prove identical output, repoint behind a flag, delete the direct coupling, shrink the Deptrac baseline. No API/DB/behavior change; one reviewable PR per step.

- **Expand.** Add `MediaAssetRef` + `MediaAccessPolicy` + `PlaybackPort` + `MediaAssetPort` contracts (Shared/Media). Implement `MediaAssetPort` in Authoring (reads `LessonMedia`, maps to `MediaAssetRef` — intra-context, legal). Implement `PlaybackPort` in the Media platform by wrapping the **existing** four signing strategies, now taking `MediaAssetRef`. Bind both; keep reading the same `learning.playback` / `services.mux` / `services.cloudfront` config for parity.
- **Parity.** Golden-output tests assert each provider's signed URL/expiry/kind is identical pre/post for the same fixture (Not verifiable from repository — requires PHP + test run).
- **Contract.** Repoint `LearningMediaService` to the two ports; delete `PlaybackTokenProvider` (Learning) and the `use LessonMedia` from the four providers (relocating the signing strategies into the Media adapter, `MediaAssetRef`-based). Update `LearningServiceProvider` binding. Remove the 5 baseline entries.
- **Reversible.** Behind `config('learning.playback.use_port', false)` (or feature flag) during rollout; revert = flip the flag.

---

## File-by-File Refactor Order

Planning sequence (each step = one PR; nothing executed in this phase):

1. **Add contracts + DTOs** (new, Shared/Media): `MediaAssetRef`, `MediaAccessPolicy`, `PlaybackPort`, `MediaAssetPort`, and relocate `PlaybackToken` to the shared namespace (alias kept for parity). No behavior.
2. **Authoring adapter** — implement `MediaAssetPort::assetForLesson(int): ?MediaAssetRef` reading `LessonMedia` (replicates the `$lesson->media` unique lookup + null semantics). Intra-context; legal. Bind in Authoring provider.
3. **Media signing adapter** — implement `PlaybackPort::issue(MediaAssetRef, ttl)`; move the four strategies (`Mux`/`CloudFront`/`S3`/`Fake`) here, each changed from `issue(LessonMedia)` to consume `MediaAssetRef` (`playbackId`/`storageKey`/`id`). **This removes the 5 `use LessonMedia` sites.** Preserve config reads + `MediaUnavailableException`.
4. **`Services/LearningMediaService.php`** — inject `PlaybackPort` + `MediaAssetPort`; `$lesson->media` → `$asset = $mediaAssetPort->assetForLesson($lesson->id)`; `hasMedia` → `assetForLesson(...) !== null`; `$this->playback->issue($media,…)` → `$playbackPort->issue($asset,…)`. (Its `Lesson`/`User` imports remain — Curriculum/Identity phases.)
5. **`Providers/LearningServiceProvider.php`** — replace the `PlaybackTokenProvider` binding with `PlaybackPort`/`MediaAssetPort` resolution; retire `PlaybackTokenManager` (its config-match logic moves into the Media adapter's strategy selector).
6. **Delete** the old Learning `Contracts/PlaybackTokenProvider.php` and `Playback/*` provider files once callers are repointed (contract step).
7. **Deptrac** — regenerate/shrink the baseline: remove the 5 Media entries; confirm `Learning` has zero `Authoring\Models\LessonMedia` references and no new `Media→Authoring` edge exists.

---

## Backward Compatibility

- **API unchanged.** `LessonPlayerController` still returns the same `playback` block; the port returns the same `PlaybackToken {url, expiresAt, kind}`. OpenAPI diff must be empty.
- **Behavior unchanged.** Same config-driven provider selection, same TTL (`learning.playback.ttl_seconds`, default 600), same URL construction per provider, same `kind`, same `MediaUnavailableException` on missing media.
- **Security invariant preserved.** `mux_asset_id` never enters `MediaAssetRef`; `playbackId`/`storageKey` stay server-side inside the port boundary; only the signed URL is returned — identical to today.
- **DB unchanged.** `lesson_media` table and `LessonMedia` model untouched (the model stays in Authoring; only a read-adapter is added).
- **Rollout flag.** Port path guarded by a flag during migration for instant revert.

---

## Tests Required

- **Provider parity (golden output)** — for each of Mux / CloudFront / S3 / Fake, given a fixed `MediaAssetRef` equivalent to a `LessonMedia` fixture, assert the signed URL, `expiresAt`, and `kind` equal the pre-refactor provider output (Mux JWT claims/`sub`/`kid`, CloudFront signature, S3 `temporaryUrl`, Fake `hash_hmac`).
- **`MediaAssetPort::assetForLesson`** — returns a correctly mapped `MediaAssetRef` for a lesson with media; returns `null` for a lesson without media (parity with `$lesson->media === null`).
- **`LearningMediaService`** — `playbackFor` returns a token; `MediaUnavailableException` thrown when no asset; `hasMedia`/`assetForLesson` null path.
- **`LessonPlayerController` feature test** — the `/lessons/{lesson}` response `playback` block is byte-identical to the current response (URL shape, `expires`, `kind`).
- **Security** — response never contains `mux_asset_id`, `s3_key`, or raw `mux_playback_id` (assert the invariant still holds).
- **Architecture** — Deptrac: zero `Learning→Authoring\Models\LessonMedia`; PHPStan `NoCrossContextModelUsageRule` clean for Learning media files; no `Media→Authoring` edge.

(All runtime results: **Not verifiable from repository** — require PHP + the test suite.)

---

## Risks

- **Coupling relocation trap (highest).** Putting the signing adapter in the `Media` layer while it still reads `LessonMedia` creates a new `Media→Authoring` violation. Mitigation: the split — signing on `MediaAssetRef` (Media), lookup owning `LessonMedia` (Authoring). This is the plan's central design decision.
- **Signing parity.** Mux RS256 JWT and CloudFront signatures must be identical; any dropped/renamed field (e.g., audience from `services.mux`, not the asset) breaks tokens. Mitigation: golden-output parity tests before contract step.
- **Lookup semantics.** `$lesson->media` is a unique `hasOne`-style relation; the Authoring adapter must reproduce exact null/absent behavior. Mitigation: parity test on present/absent.
- **Config ownership.** Providers read `learning.playback` / `services.mux` / `services.cloudfront`; moving them must keep the same keys (or behavior shifts). Mitigation: preserve keys in this phase; rename later.
- **`LessonMedia` true ownership.** Long-term `LessonMedia` arguably belongs to a Media platform, not Authoring; this phase keeps it in Authoring (adapter there) to avoid a model/table relocation. Noted as future work, not this phase.
- **Toolchain.** Deptrac not yet installed and baseline empty (`ARCHITECTURE_FITNESS_READINESS.md`); the "shrink baseline" step assumes it is installed + seeded first.

---

## Success Criteria

1. `grep -rn 'use App\\Domains\\Authoring\\Models\\LessonMedia' app/Contexts/Learning` → **0** (down from 5).
2. No `Media→Authoring` dependency edge; `Learning` depends only on Shared/Media contracts + `IdentityContracts`. Deptrac: 0 Learning media violations; baseline shrinks by the 5 removed entries. *(Not verifiable from repository.)*
3. `LessonPlayerController` `/lessons/{lesson}` `playback` block byte-identical; OpenAPI diff empty. *(Not verifiable from repository.)*
4. All four providers' signed URLs/expiry/kind identical pre/post (parity tests green); `MediaUnavailableException` preserved. *(Not verifiable from repository.)*
5. Security invariant intact: no raw `mux_asset_id`/`s3_key` in any response.
6. Learning forbidden outbound import sites **57 → 52** (Media group cleared).

---

## Final Recommendation

**Proceed with the split-port design; do not relocate the providers (or `LessonMedia`) wholesale into a Media layer.** The single most important decision is recognizing that signing and lesson→asset lookup are different responsibilities living in different legal homes: signing (pure infra) → **Media** on `MediaAssetRef`; lookup (owns the model) → **Authoring** behind `MediaAssetPort`. Executed expand-and-contract — contracts + parity adapters first, golden-output tests, repoint `LearningMediaService`, then delete the 5 `LessonMedia` imports and shrink the Deptrac baseline — this removes the entire Media coupling with zero API/DB/behavior change and introduces no new boundary violation. Sequence it **after** Deptrac is installed and the baseline seeded (fitness readiness precondition), and keep the port path behind a rollout flag for instant revert. This is the lowest-risk, highest-parity path and cleanly precedes the larger Curriculum (`CurriculumReadPort`) and Identity (`IdentityContracts`) phases.

---

## Validation

- All couplings, fields, methods, and flow steps derive from direct reads of the six in-scope files, `LessonMedia` (model + migration `2025_01_03_000120`), `PlaybackToken`, `PlaybackTokenManager`, `LearningServiceProvider`, `LessonPlayerController`, and `config/learning.php`, reconciled with `LEARNING_CONTEXT_DEPENDENCY_AUDIT.md` and `DEPENDENCY_CLEANUP_PLAN.md`.
- Execution-dependent outcomes (test results, Deptrac counts, OpenAPI diff, signing parity) are marked **"Not verifiable from repository."**
- **No code, port, adapter, API, or schema was created or modified.** Only this file was created.
