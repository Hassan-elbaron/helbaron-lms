/**
 * Course Builder — typed accessors for the lesson `content` JSON.
 *
 * These mirror the schema the BACKEND already uses; no new schema is invented:
 *   • `html`       — the ONLY key rendered as rich HTML by the learner
 *                    (`components/learning/lesson-content.tsx`) and the only key (with `body`)
 *                    sanitized server-side by `HtmlSanitizer::sanitizeArray(['html','body'])`.
 *                    All rich text therefore lives in `html`.
 *   • `url`        — read by the learner for `external_link` lessons.
 *   • `transcript` — read by the learner for `audio` lessons. NOT sanitized server-side,
 *                    so it must stay PLAIN TEXT (never HTML).
 *
 * Binary media (video/audio/pdf/download) is NOT stored in `content` — it lives in the
 * `lesson_media` table via `PUT /admin/lessons/{lesson}/media`. `content.html` may still carry
 * supplementary reading text for those types (the DemoSeeder does exactly this).
 */
import type { BlockContent } from "./types";

/** Safe string read from a loosely-typed content bag. */
export function readString(content: BlockContent, key: string): string {
  const value = content[key];
  return typeof value === "string" ? value : "";
}

/** Safe boolean read (absent ⇒ false). */
export function readBoolean(content: BlockContent, key: string): boolean {
  return content[key] === true;
}

/** Immutably set a key, dropping it when the value is empty (keeps payloads clean). */
export function withValue(content: BlockContent, key: string, value: string | boolean): BlockContent {
  const next: BlockContent = { ...content };
  if (value === "" || value === false) {
    delete next[key];
  } else {
    next[key] = value;
  }
  return next;
}

// ── Per-kind shapes (documentation + narrowing for editors) ────────────────

/** Article / any rich-text-bearing lesson. */
export interface ArticleContent extends BlockContent {
  html?: string;
}

/**
 * External link lesson. `url` is the ONLY key the learner renderer reads; `label` is the
 * author-facing name carried in the block registry's default content. The player always opens the
 * link in a new tab and renders its own link text, so no other metadata is offered.
 */
export interface ExternalLinkContent extends BlockContent {
  url?: string;
  label?: string;
  html?: string;
}

/** Audio lesson — transcript is plain text (unsanitized server-side). */
export interface AudioContent extends BlockContent {
  transcript?: string;
  html?: string;
}

// ── URL safety ─────────────────────────────────────────────────────────────

const SAFE_PROTOCOLS = ["http:", "https:"] as const;

/**
 * True when the value is a well-formed URL on a safe protocol. Explicitly rejects
 * `javascript:`, `data:`, `file:` and anything else outside http/https — mirroring the backend
 * sanitizer's `URI.AllowedSchemes` (http, https, mailto) minus mailto, which is not a lesson link.
 */
export function isSafeUrl(value: string): boolean {
  const trimmed = value.trim();
  if (trimmed === "") return false;
  try {
    const url = new URL(trimmed);
    return (SAFE_PROTOCOLS as readonly string[]).includes(url.protocol);
  } catch {
    return false;
  }
}

// ── Reading metrics (article editor footer) ────────────────────────────────

/** Average adult reading speed used for the estimate. */
const WORDS_PER_MINUTE = 200;

export interface ReadingStats {
  characters: number;
  words: number;
  minutes: number;
}

/** Compute counts from the editor's PLAIN TEXT (never from HTML markup). */
export function readingStats(text: string): ReadingStats {
  const trimmed = text.trim();
  const words = trimmed === "" ? 0 : trimmed.split(/\s+/u).length;
  return {
    characters: text.length,
    words,
    minutes: words === 0 ? 0 : Math.max(1, Math.round(words / WORDS_PER_MINUTE)),
  };
}
