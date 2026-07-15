# Learning Media — Split-Port Refactor Report

> Chief Enterprise Architect. Executes the Media dependency cleanup per `LEARNING_MEDIA_PORT_REFACTOR_PLAN.md` (approved split-port design). No API, database schema, playback URL, token generation, or configuration key was changed. Evidence below is from the edited files + a repository grep sweep. Runtime gate results are marked **"Not verifiable from repository"** (this environment has no PHP/Composer).

---

## Executive Summary

Learning no longer reads Authoring's `LessonMedia` model. The media coupling was **split** into two responsibilities behind Shared contracts, exactly as the plan required:

- **Signing** (infrastructure) moved to the **Media platform** (`App\Platform\Media`), operating on a storage-agnostic `MediaAssetRef` — the four signers (`Mux`/`CloudFront`/`S3`/`Fake`) no longer reference `LessonMedia`.
- **Lesson→asset lookup** (owns the model) moved to **Authoring** behind `MediaAssetPort` (`LessonMediaAssetPort`), an intra-context read.

`LearningMediaService` now depends only on two Shared contracts (`PlaybackPort`, `MediaAssetPort`) and the relocated `PlaybackToken` DTO. The five `use LessonMedia` sites in Learning are gone (Learning forbidden outbound import sites **57 → 52**), and **no new boundary edge** was created: Media → Shared only, Authoring → its own model only. Behavior, URLs, token generation, error code/status, and config keys are unchanged. Gates must be run on a PHP-capable machine to confirm green.

---

## Files Modified

**Created — Shared Media contracts/DTOs (`app/Platform/Shared/Media/`)**
- `Data/MediaAssetRef.php` — immutable ref: `id, provider, playbackId, storageKey, mimeType, durationSeconds, policy, metadata` (no `mux_asset_id`).
- `Data/MediaAccessPolicy.php` — `signed, visibility, ttlSeconds` (descriptive; effective TTL stays the `issue()` argument).
- `Data/PlaybackToken.php` — **relocated** from Learning, shape unchanged (`url, expiresAt, kind`).
- `Contracts/PlaybackPort.php` — `issue(MediaAssetRef, int): PlaybackToken`.
- `Contracts/MediaAssetPort.php` — `assetForLesson(int): ?MediaAssetRef`.
- `Exceptions/MediaUnavailableException.php` — **relocated** from Learning; code `LEARNING_MEDIA_UNAVAILABLE` / status `404` preserved verbatim.

**Created — Authoring (`app/Domains/Authoring/`)**
- `Media/LessonMediaAssetPort.php` — implements `MediaAssetPort`; reads `LessonMedia` (intra-context), maps to `MediaAssetRef`, excludes `mux_asset_id`; null when absent.

**Created — Media platform (`app/Platform/Media/`)**
- `Playback/Providers/MuxPlaybackSigner.php`, `CloudFrontPlaybackSigner.php`, `S3PlaybackSigner.php`, `FakePlaybackSigner.php` — the four signers, now `implements PlaybackPort` and consuming `MediaAssetRef`.
- `Playback/PlaybackTokenManager.php` — **relocated**; config-driven resolver (`fake|s3|cloudfront|mux`), same keys.
- `Providers/MediaServiceProvider.php` — binds `PlaybackPort` → resolved signer.

**Edited**
- `app/Contexts/Learning/Services/LearningMediaService.php` — injects `PlaybackPort` + `MediaAssetPort`; `$lesson->media` → `assetForLesson($lesson->id)`; throws the relocated `MediaUnavailableException`; TTL read unchanged.
- `app/Contexts/Learning/Providers/LearningServiceProvider.php` — removed the `PlaybackTokenProvider` binding + imports (moved to Media).
- `app/Domains/Authoring/Providers/AuthoringServiceProvider.php` — binds `MediaAssetPort` → `LessonMediaAssetPort`.
- `bootstrap/providers.php` — registers `MediaServiceProvider` (after `SharedServiceProvider`).
- `tests/Feature/Integrations/MuxPlaybackTest.php` — retargeted to Media signers + `MediaAssetRef`; same assertions (playback id present, asset id absent, RS256 JWT verifies, config selection).
- `tests/Unit/Media/PlaybackTokenTest.php` — relocated from `tests/Unit/Learning/`; Fake signer + `MediaAssetRef`; same "no raw key in URL" assertion.

**Deleted (obsolete)**
- `app/Contexts/Learning/Contracts/PlaybackTokenProvider.php`
- `app/Contexts/Learning/Playback/Providers/{Mux,CloudFront,S3,Fake}PlaybackTokenProvider.php`
- `app/Contexts/Learning/Playback/PlaybackTokenManager.php`
- `app/Contexts/Learning/Playback/Data/PlaybackToken.php`
- `app/Contexts/Learning/Exceptions/MediaUnavailableException.php`
- `tests/Unit/Learning/PlaybackTokenTest.php`
- Now-empty dirs `Learning/Playback/` and `Learning/Contracts/` removed.

---

## Architecture Changes

- **Coupling split, not relocated.** The plan's central risk (moving providers into Media would create `Media→Authoring`) is avoided: signing operates on `MediaAssetRef` (Media→Shared only), and the model-owning lookup lives in Authoring (intra-context).
- **New dependency edges (all legal):** Learning→Shared (`PlaybackPort`, `MediaAssetPort`, `PlaybackToken`, `MediaUnavailableException`); Media→Shared (same DTOs/contracts + `Support\Jwt`, `Support\CloudFrontUrlSigner`); Authoring→Shared + own `LessonMedia`.
- **DTO/exception relocation to Shared** so the Media-layer port can return/throw them without a `Media→Learning` edge. `PlaybackToken` shape and the `LEARNING_MEDIA_UNAVAILABLE`/404 contract are byte-preserved.
- **DI:** `PlaybackPort` bound in `MediaServiceProvider`; `MediaAssetPort` bound in `AuthoringServiceProvider`; Learning’s old binding removed. Registration order unchanged except `MediaServiceProvider` inserted after Shared.

Grep verification (repository): `Learning → Authoring\Models\LessonMedia` = **0**; `Media → App\Domains|App\Contexts` = **0**; `Shared\Media → non-Shared app` = **0**. (The only residual "former …Provider" strings are docblock comments documenting the move.)

---

## Behavior Verification

Behavior-preserving by construction (confirmed by reading the moved code — identical logic, new input type):

- **Mux:** RS256 JWT over `playbackId` (`sub`), same `kid`/`aud`/`exp`, same `{base}/{playbackId}.m3u8?token=…`, `kind='video'`.
- **CloudFront:** `CloudFrontUrlSigner::sign(storageKey, expiry)`, `kind='file'`.
- **S3:** `Storage::disk('s3')->temporaryUrl(storageKey, expiry)`, `kind='file'`.
- **Fake:** `hash_hmac` over `playbackId ?? storageKey ?? id` → `/media/stream/{sig}?expires=…`; `kind` = video/file by `playbackId`.
- **Unavailability:** null media row → `LearningMediaService` throws `MediaUnavailableException` (404, `LEARNING_MEDIA_UNAVAILABLE`); missing id for configured provider → signer throws the same exception. Same envelope as before.
- **Response shape:** `LearnerLessonResource` unchanged; `playback` block still `{url, kind, expires_at}`; no `s3_key`/`mux_asset_id` exposed (`MediaAssetRef` structurally excludes `mux_asset_id`).
- **Config:** `learning.playback.provider`, `learning.playback.ttl_seconds`, `services.mux`, `services.cloudfront` — all read from the same keys.

**Runtime confirmation: Not verifiable from repository** (no PHP here). The updated tests assert the above; run them to confirm.

One benign, non-behavioral note: `hasMedia()` + `playbackFor()` each call `assetForLesson()`, so the player path issues two lookup queries where the former Eloquent relation memoized one. Output is identical; the extra query is negligible and can be optimized later (e.g., memoization) if desired.

---

## Backward Compatibility

- **API:** unchanged — same endpoint, same JSON, empty OpenAPI diff expected.
- **Database:** untouched — `lesson_media` table and `LessonMedia` model unchanged (model stays in Authoring).
- **Playback URLs / token generation:** identical algorithms and inputs.
- **Configuration keys:** unchanged.
- **Error contract:** `LEARNING_MEDIA_UNAVAILABLE` / 404 preserved (exception relocated, code/status/message identical).

---

## Remaining Learning Violations

Learning forbidden outbound import sites: **57 → 52** (Media group cleared: 5× `Authoring\Models\LessonMedia` removed). Remaining, per `LEARNING_CONTEXT_DEPENDENCY_AUDIT.md`, for later phases:

- **Curriculum (38):** `Authoring\Models\Lesson` (22), `Authoring\Models\Section` (6), `Authoring\Services\CurriculumTreeService` (1), `Catalog\Models\Course` (8), `Catalog\Enums\CourseStatus` (1) — needs `CurriculumReadPort`.
- **Identity (17→ ~14 after this):** `Platform\Identity\Models\User` (16) + `Role` seeder (1 already cleared in Phase 2) — needs `IdentityContracts`. (`LearningMediaService` still imports `Lesson`/`User` for method signatures — Curriculum/Identity phases.)

(52 = Curriculum 38 + Identity 14, matching the audit's post-Media projection.)

---

## Deptrac Impact

- The removed `Learning→LessonMedia` coupling was **never in the baseline** (`deptrac.baseline.yaml` is the empty placeholder `skip_violations: {}`; Deptrac is not yet installed — see `ARCHITECTURE_FITNESS_READINESS.md`). **No baseline edit was required.**
- New edges are all allowed by `deptrac.yaml` rules (`Media: [Shared, IdentityContracts]`, `Authoring: [Shared, IdentityContracts]`, `Learning: [Shared, IdentityContracts]`), so they add **no** new violations.
- Net effect once Deptrac is installed + baselined: Learning shows **5 fewer** boundary violations; the Media layer (previously empty) now legally collects the signers with zero violations.

**Confirmation that Deptrac passes: Not verifiable from repository** (Deptrac not installed here).

---

## Risk Assessment

- **Signing parity (medium).** Logic copied verbatim; only the input type changed (`LessonMedia` → `MediaAssetRef` with the same field values). Mux JWT `sub`/`kid`, CloudFront/S3 signing, and Fake HMAC are unchanged. Guarded by the updated tests. *Residual: not run here.*
- **DI resolution (low).** `LearningMediaService` now needs `PlaybackPort` (MediaServiceProvider) + `MediaAssetPort` (AuthoringServiceProvider); both registered. If a provider were missing, container resolution would fail fast at first use.
- **Exception class change (low).** Class relocated to Shared/Media; error code + status + message identical, so the wire contract is unchanged even though the FQCN differs.
- **Extra query (negligible).** `hasMedia`+`playbackFor` do two lookups vs. one memoized relation; identical output.
- **Toolchain (external).** Deptrac/Rector still not installed and PHPStan baseline empty; gate enforcement depends on the fitness-readiness remediation. Unrelated to this change's correctness.

---

## Recommendation

**Merge after the gate suite is confirmed green on a PHP-capable environment.** The refactor faithfully implements the approved split-port design, removes the entire Media coupling (5 sites, 57→52), preserves API/DB/URL/token/config/error behavior, and introduces no new boundary edge. Before merge, on a machine with PHP 8.3 (or the `helbaron-api` container): run `composer dump-autoload`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (especially `MuxPlaybackTest` + `tests/Unit/Media/PlaybackTokenTest` + any lesson-player feature test), and — once installed — `vendor/bin/deptrac analyse`. Keep the change reversible per PR; the next phases (Curriculum `CurriculumReadPort`, Identity `IdentityContracts`) proceed independently.

---

## Validation

Attempted, per request:

```
composer dump-autoload      -> Not verifiable from repository (php/composer not available in this environment)
vendor/bin/pint             -> Not verifiable from repository
vendor/bin/phpstan analyse  -> Not verifiable from repository
vendor/bin/deptrac analyse  -> Not verifiable from repository (deptrac not installed; baseline empty)
php artisan test            -> Not verifiable from repository
```

Static verification performed here (repository evidence): grep confirms `Learning→LessonMedia` = 0, `Media→Domains/Contexts` = 0, `Shared\Media→non-Shared` = 0; all 9 new files present; the Authoring adapter imports only its own model + Shared; `file(1)` reports every new/changed file as PHP ASCII/UTF-8 text (no NUL corruption); the authoritative file view shows well-formed classes. No API, schema, URL, token, or config-key change was made.

Run the commands above on a PHP-capable environment (or `docker compose exec api …`) to obtain live pass/fail.
