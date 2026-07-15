# Demo Content Sourcing Policy

Applies to: the HElbaron LMS demo data system (`config/demo.php`, `database/seeders/DemoSeeder.php`, `php artisan demo:seed`).
Purpose: guarantee that the demo environment is realistic and complete while using **only** content the project is legally permitted to use. This policy is binding for anyone adding or replacing demo assets.

## Golden rule

> Never assume that a publicly accessible asset (video, image, PDF, description, thumbnail) is free to reuse. If its license and embed permission cannot be verified, it must not ship — use an original or public-domain/CC0 asset instead, or a documented placeholder with a reading fallback.

The demo is designed to be **safe by construction**: all lesson text, course titles/descriptions, marketing copy, quizzes, and notes are **original generated content**. External media is optional (`DEMO_EXTERNAL_MEDIA`), always carries a reading fallback, and ships as replaceable placeholders — the repository contains **no unverified third-party asset**.

## Permitted sources

- Original content generated for this demo (default; always safe).
- Public-domain (PD) / CC0 content.
- Creative Commons content **with commercial reuse allowed** (CC BY, CC BY-SA) — attribution recorded (see below).
- Official embed links whose platform terms explicitly allow embedding (YouTube/Vimeo embeds only — never downloads).
- Royalty-free media from repository-approved libraries: Pexels, Pixabay, Mixkit, Coverr, Unsplash.
- Official product/open documentation links.
- Locally generated placeholder files (SVG posters, sample PDFs).

## Prohibited sources

- Copyrighted content without clear, recorded permission.
- Paid/proprietary courses, their descriptions, thumbnails, PDFs, quizzes, or lesson content.
- Downloaded YouTube/Vimeo video files (embeds only; never download).
- Any asset obtained by scraping a site in violation of its terms.
- Any asset whose license or embed permission is unknown/unverifiable.

## Attribution & license recording (required per external asset)

Every external asset must record, in the `config/demo.php` `media` manifest (and in the lesson `content.media` payload the seeder writes):

| Field | Meaning |
|---|---|
| `title` | Human title of the asset |
| `url` | Canonical source URL |
| `author` | Author / channel / photographer |
| `platform` | youtube / vimeo / pexels / pixabay / … |
| `license` | PD / CC0 / CC BY / CC BY-SA / royalty-free / placeholder |
| `attribution` | The exact attribution string to display |
| `embeddable` | verified / unverified — embed-permission status |
| `added_at` | Date added (YYYY-MM-DD) |
| `fallback` | Fallback asset/behavior (always `reading` for video lessons) |

## Embedding rules

**YouTube:** embeds only; never download. Prefer official educational channels, public-domain, or Creative Commons videos. Record the video ID and source URL, verify embedding is allowed (the uploader has not disabled embedding and the video is not restricted), and always provide a fallback poster + reading lesson. Set `embeddable: verified` only after checking.

**Vimeo:** public, embeddable videos only. Never download restricted content. Store source and attribution metadata.

**Royalty-free images/video (Pexels/Pixabay/Mixkit/Coverr/Unsplash):** follow each library's license (generally free for commercial use, no attribution required, but do not resell as-is). Record the source URL and author.

## Replacement procedure

1. Choose a verified PD/CC0/CC-BY/official-embeddable asset.
2. Fill every manifest field above; set `embeddable: verified` and the real `license`.
3. Replace the `REPLACE_ME` `video_id` (and poster if applicable) in `config/demo.php`.
4. Re-run `php artisan demo:seed` (idempotent — it updates the lesson `content.media` in place).
5. Confirm the reading fallback still renders if the embed is later removed.

## Copyright takedown procedure

If a rights-holder requests removal of a demo asset:
1. Immediately set `DEMO_EXTERNAL_MEDIA=false` (video lessons revert to reading fallback) and/or remove the offending entry from the `config/demo.php` `media` manifest.
2. Re-run `php artisan demo:seed` to purge the reference from lesson content.
3. Record the request (date, asset, requester) and, if applicable, replace with a verified asset per the replacement procedure.

## Environment & production restrictions

- Demo mode is **disabled by default** (`DEMO_MODE=false`).
- `php artisan demo:seed` **refuses to run in the `production` environment** regardless of `DEMO_MODE`.
- Production must never auto-seed demo data; the demo seeder is not wired into `DatabaseSeeder` and only runs via the explicit `demo:seed` command.
- A destructive reset additionally requires `DEMO_RESET_ALLOWED=true` and a non-production environment.
- All demo accounts use the `@demo.helbaron.local` email domain and demo slug/coupon prefixes so they are unmistakably identifiable and never confused with real data.

## Current status

The shipped manifest contains **placeholder** external-media entries only (`license: placeholder`, `embeddable: unverified`). No third-party asset is asserted as licensed. The demo is fully functional and realistic using original reading content; external embeds are opt-in and must be verified per this policy before any non-demo use.
