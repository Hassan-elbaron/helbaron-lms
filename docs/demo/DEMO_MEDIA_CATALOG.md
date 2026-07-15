# Demo Media Catalog

How media is represented in the HElbaron demo, what the enterprise profile generates, and the licensing/verification status of every external reference. Governed by `docs/demo/DEMO_CONTENT_SOURCING_POLICY.md`.

## How the repository models media

The platform stores media as **metadata references**, not binary files:

- **`lesson_media`** (`LessonMedia`) — per video lesson: `mux_playback_id` + `mux_asset_id` (Mux video), `s3_key` (S3/CloudFront object), `mime_type`, `duration`, `filesize`. No file bytes are stored by the model; playback is signed at runtime (`LearningMediaService` / CloudFront). The enterprise profile creates **398** of these (one per video lesson).
- **Lesson `content` (JSON)** — reading lessons hold original generated HTML; video lessons additionally hold an external embed reference (`provider`, `video_id`, `attribution`, `license`) **only when `DEMO_EXTERNAL_MEDIA=true`**, plus an original reading fallback.
- **Course `thumbnail_path`** and **`seo`** — course cover/thumbnail path + SEO/OG image references live on the course.
- **Certificate `pdf_path`** — generated lazily on demand (`EnsureCertificatePdfAction`); not pre-rendered by the seed.

Because media is metadata, the demo ships **no copyrighted binaries** and requires no external services to look complete in listings, curriculum, and dashboards.

## Enterprise media inventory (measured)

| Asset type | Representation | Enterprise count |
|---|---|---|
| Lesson video assets | `lesson_media` rows (Mux playback id + S3 key placeholders) | 398 |
| Video lesson embeds | `content.media` reference (opt-in via `DEMO_EXTERNAL_MEDIA`) | ≤ 398 |
| Course covers / thumbnails | `courses.thumbnail_path` + `seo` image ref | 56 |
| Course hero / OG images | `courses.seo` (per course) | 56 |
| Instructor avatars | `user_profiles.avatar_path` (path ref) | 16 |
| Certificate backgrounds | `certificate_templates.html` + `certificate_settings.signature_image_path` | template + settings |
| Lesson PDFs / downloads | `pdf` / `download` / `external_link` lessons via `content` | subset of 1,859 lessons |
| Marketing images | Front-end `apps/web/public` static assets (Editorial Academy brand) | shipped in web app |

## Placeholder & poster assets

- `config('demo.media.poster_fallback')` → `demo/posters/course-cover.svg` — a locally-generated fallback poster used when an external embed is unavailable.
- All external-media manifest entries in `config/demo.php` ship as **placeholders** (`video_id: REPLACE_ME`, `license: placeholder`, `embeddable: unverified`). No third-party asset is asserted as licensed.

## External URL validation

**Not verifiable from repository.** The demo does not depend on live external URLs, and this environment has no browser to validate embed/URL availability. As shipped, external media is placeholder-only; before any non-demo use, follow the verification + attribution steps in `DEMO_CONTENT_SOURCING_POLICY.md`, populate the `config/demo.php` `media` manifest with verified Creative-Commons / public-domain / official-embeddable references, and re-run `demo:seed` (idempotent — it updates lesson `content.media` in place).

## Recommended sourcing when replacing placeholders

Per the sourcing policy, permitted royalty-free / open sources include: Pexels, Pixabay, Mixkit, Coverr, Unsplash (images/video), public-domain repositories, and official educational YouTube/Vimeo **embeds** (never downloads). Each replacement must record: `title`, `url`, `author`, `platform`, `license`, `attribution`, `embeddable` (verified), `added_at`, `fallback`.

## Attribution & takedown

Every external asset must carry attribution metadata (recorded in the manifest and the lesson `content.media`). To remove an asset on request, set `DEMO_EXTERNAL_MEDIA=false` (reverts to reading fallbacks) and/or delete the manifest entry, then re-run `demo:seed` — see the takedown procedure in `DEMO_CONTENT_SOURCING_POLICY.md`.
