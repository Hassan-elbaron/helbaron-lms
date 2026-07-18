/**
 * Course Builder — API client.
 *
 * Wraps the existing Authoring admin endpoints (`/api/v1/admin/*`, reached via the same-origin BFF
 * proxy through `@/lib/api/client`). Only backend-supported block kinds are ever sent; unsupported
 * kinds are guarded here (never faked) and enumerated in `REMAINING_BACKEND` for the backend team.
 *
 * NOTE (scope): these endpoints are authorized by `authoring.curriculum.manage` / super_admin, not
 * the instructor `teach` scope. Wiring instructor access is a backend task — see REMAINING_BACKEND.
 */
import { api } from "@/lib/api/client";
import { isBackendSupported } from "./block-registry";
import type {
  Block,
  BlockContent,
  BlockKind,
  CreateBlockInput,
  CreateSectionInput,
  Curriculum,
  LessonMedia,
  PublishState,
  ReorderTreeInput,
  Section,
  UpdateBlockInput,
  UpdateSectionInput,
  UpsertMediaInput,
} from "./types";

/** Thrown when a caller tries to persist a block kind the backend does not accept yet. */
export class UnsupportedBlockError extends globalThis.Error {
  constructor(public readonly kind: BlockKind) {
    super(`Block kind "${kind}" is not yet supported by the backend.`);
    this.name = "UnsupportedBlockError";
  }
}

// ── Raw backend shapes ─────────────────────────────────────────────────────
interface RawMedia {
  mux_asset_id: string | null;
  mux_playback_id: string | null;
  s3_key: string | null;
  mime_type: string | null;
  duration: number | null;
  filesize: number | null;
}
interface RawLesson {
  id: string;
  title: string;
  type: string;
  content: unknown;
  position: number;
  publish_state: PublishState;
  is_preview: boolean;
  media: RawMedia | null;
  prerequisites?: { id: string; title: string }[];
  estimated_minutes?: number | null;
}
interface RawSection {
  id: string;
  title: string;
  summary: string | null;
  position: number;
  publish_state: PublishState;
  lessons: RawLesson[];
}

// ── Mappers (backend → builder domain) ─────────────────────────────────────
function asContent(raw: unknown): BlockContent {
  return raw && typeof raw === "object" && !Array.isArray(raw) ? (raw as BlockContent) : {};
}
function toMedia(raw: RawMedia | null): LessonMedia | null {
  return raw ? { ...raw } : null;
}
function toBlock(raw: RawLesson): Block {
  return {
    id: raw.id,
    title: raw.title,
    kind: raw.type as BlockKind,
    content: asContent(raw.content),
    position: raw.position,
    publish_state: raw.publish_state,
    is_preview: raw.is_preview,
    media: toMedia(raw.media),
    prerequisites: raw.prerequisites ?? [],
    estimated_minutes: raw.estimated_minutes ?? null,
  };
}
function toSection(raw: RawSection): Section {
  return {
    id: raw.id,
    title: raw.title,
    summary: raw.summary,
    position: raw.position,
    publish_state: raw.publish_state,
    blocks: (raw.lessons ?? []).map(toBlock),
  };
}

// ── Reads ──────────────────────────────────────────────────────────────────
export async function getCurriculum(courseId: string): Promise<Curriculum> {
  const data = await api.data<{ sections: RawSection[] }>(`admin/courses/${courseId}/curriculum`);
  const sections = Array.isArray(data?.sections) ? data.sections.map(toSection) : [];
  return { course_id: courseId, sections };
}

// ── Sections ────────────────────────────────────────────────────────────────
export async function createSection(courseId: string, input: CreateSectionInput): Promise<Section> {
  const data = await api.data<RawSection>(`admin/courses/${courseId}/sections`, { method: "POST", body: input });
  return toSection(data);
}
export async function updateSection(sectionId: string, input: UpdateSectionInput): Promise<Section> {
  const data = await api.data<RawSection>(`admin/sections/${sectionId}`, { method: "PUT", body: input });
  return toSection(data);
}
export async function deleteSection(sectionId: string): Promise<void> {
  await api.del(`admin/sections/${sectionId}`);
}
export async function setSectionPublish(sectionId: string, state: PublishState): Promise<Section> {
  const data = await api.data<RawSection>(`admin/sections/${sectionId}/publish`, { method: "POST", body: { state } });
  return toSection(data);
}
export async function reorderSections(courseId: string, order: string[]): Promise<void> {
  await api.put(`admin/courses/${courseId}/sections/order`, { order });
}

// ── Blocks (backend "lessons") ───────────────────────────────────────────────
export async function createBlock(sectionId: string, input: CreateBlockInput): Promise<Block> {
  if (!isBackendSupported(input.kind)) throw new UnsupportedBlockError(input.kind);
  const body = { title: input.title, type: input.kind, content: input.content ?? {}, is_preview: input.is_preview ?? false };
  const data = await api.data<RawLesson>(`admin/sections/${sectionId}/lessons`, { method: "POST", body });
  return toBlock(data);
}
export async function updateBlock(blockId: string, input: UpdateBlockInput): Promise<Block> {
  if (input.kind && !isBackendSupported(input.kind)) throw new UnsupportedBlockError(input.kind);
  const body: Record<string, unknown> = {};
  if (input.title !== undefined) body.title = input.title;
  if (input.kind !== undefined) body.type = input.kind;
  if (input.content !== undefined) body.content = input.content;
  const data = await api.data<RawLesson>(`admin/lessons/${blockId}`, { method: "PUT", body });
  return toBlock(data);
}
export async function deleteBlock(blockId: string): Promise<void> {
  await api.del(`admin/lessons/${blockId}`);
}
export async function setBlockPublish(blockId: string, state: PublishState): Promise<Block> {
  const data = await api.data<RawLesson>(`admin/lessons/${blockId}/publish`, { method: "POST", body: { state } });
  return toBlock(data);
}
export async function toggleBlockPreview(blockId: string): Promise<Block> {
  const data = await api.data<RawLesson>(`admin/lessons/${blockId}/preview`, { method: "POST" });
  return toBlock(data);
}
export async function reorderBlocks(sectionId: string, order: string[]): Promise<void> {
  await api.put(`admin/sections/${sectionId}/lessons/order`, { order });
}
export async function setPrerequisites(blockId: string, prerequisites: string[]): Promise<Block> {
  const data = await api.data<RawLesson>(`admin/lessons/${blockId}/prerequisites`, { method: "PUT", body: { prerequisites } });
  return toBlock(data);
}
export async function upsertMedia(blockId: string, input: UpsertMediaInput): Promise<Block> {
  const data = await api.data<RawLesson>(`admin/lessons/${blockId}/media`, { method: "PUT", body: input });
  return toBlock(data);
}

// ── Whole-tree reorder (DnD across sections) ─────────────────────────────────
export async function reorderTree(courseId: string, input: ReorderTreeInput): Promise<void> {
  await api.put(`admin/courses/${courseId}/curriculum/order`, input);
}

/**
 * Endpoints the Course Builder needs but the backend does not expose yet. The UI models these
 * cleanly; persistence is disabled until they exist. (No fake implementations.)
 */
export const REMAINING_BACKEND: readonly string[] = [
  "Direct media upload — there is no endpoint that returns an upload target, so the builder can only reference assets that already exist. Needed: POST /admin/lessons/{lesson}/media/upload returning either a Mux direct-upload URL ({ upload_id, upload_url }) or an S3 presigned POST ({ url, fields, key }), plus a way to learn when Mux finishes ingesting (webhook → lesson_media.mux_playback_id, or GET /admin/lessons/{lesson}/media/status returning { state: 'waiting'|'ready'|'errored' }). Until both exist the builder will NOT display upload progress or claim an asset is ready.",
  "Media delete — DELETE /admin/lessons/{lesson}/media. Detaching currently works by PUTting explicit nulls, which relies on every column being nullable.",
  "Instructor-scoped curriculum access — expose the /admin curriculum endpoints under the `teach` scope (or grant `authoring.curriculum.manage`) so instructors, not just admins, can author.",
  "Block kinds not in LessonType enum: scorm, xapi, cmi5, quiz (full), assignment, discussion, live_session, certificate, survey — need create/update support + content schemas.",
  "Sub-section nesting — curriculum is a flat Section→Lesson tree; nested sub-sections need a schema + endpoints.",
  "Lesson duplicate endpoint — POST /admin/lessons/{lesson}/duplicate (builder duplicates via read-then-create today).",
  "Section duplicate endpoint — POST /admin/sections/{section}/duplicate.",
  "Course-level authoring meta (title/description/access/schedule/completion rules) editable via a course PUT for the builder header + inspector.",
];
