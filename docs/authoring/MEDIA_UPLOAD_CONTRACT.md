# Lesson media — required backend contract

The Course Builder's media editor writes real `lesson_media` rows today, but it cannot upload a
file. This document specifies exactly what the backend must expose before upload UI can ship.
Nothing here is implemented on the frontend yet, and the builder does not pretend otherwise: it
shows no progress bar and never reports an asset as ready.

## What exists today

`PUT /api/v1/admin/lessons/{lesson}/media` (`UpsertLessonMediaRequest`) accepts
`mux_asset_id`, `mux_playback_id`, `s3_key`, `mime_type`, `duration`, `filesize` — every field
`nullable`, with `duration` and `filesize` constrained to `integer|min:0`. Because all columns are
nullable, sending explicit `null`s is how the builder detaches an asset.

The builder therefore lets an author reference an asset that already went through the media
pipeline, and nothing more.

## 1. Create an upload target

`POST /api/v1/admin/lessons/{lesson}/media/upload`

Authorized by the same policy as the media upsert. Request carries the intended `mime_type` and
`filesize` so the backend can enforce type and size limits *before* any bytes move. The response
must discriminate on provider:

For Mux (video, audio): `{ "provider": "mux", "upload_id": "...", "upload_url": "https://..." }`
where `upload_url` is a Mux direct-upload URL the browser PUTs to.

For S3 (pdf, download): `{ "provider": "s3", "url": "https://...", "fields": { ... }, "key": "..." }`
— a presigned POST policy, so the browser never sees credentials and the key is server-chosen.

## 2. Report ingest state

A Mux direct upload does not produce a playback ID synchronously; the asset must be ingested first.
The builder needs one of:

`GET /api/v1/admin/lessons/{lesson}/media/status` returning
`{ "state": "waiting" | "ready" | "errored", "playback_id": string|null, "error": string|null }`,
which the client can poll; or a webhook that writes `lesson_media.mux_playback_id` on
`video.asset.ready` combined with the status endpoint above so the client can stop polling.

Without this, a UI that claimed "upload complete" would be lying — the lesson would still have no
playback ID. That is why the current editor has no upload control at all.

## 3. Delete

`DELETE /api/v1/admin/lessons/{lesson}/media`, so detaching does not depend on the incidental fact
that every column is nullable.

## Frontend readiness

`apps/web/src/lib/authoring/api.ts` already types the media upsert and lists these gaps in
`REMAINING_BACKEND`. When the endpoints above exist, the upload UI slots into
`components/authoring/editors/media-editor.tsx` without changing the lesson content schema.
