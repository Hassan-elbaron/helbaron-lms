/**
 * Course Builder — block registry.
 *
 * Single source of truth for every block kind's presentation (icon, label, group) and capability
 * (whether the backend accepts it today). The tree "add" menu, the block picker, editors and
 * validation all read from here so behaviour stays consistent and nothing is duplicated.
 */
import {
  Activity,
  Award,
  Boxes,
  ClipboardCheck,
  ClipboardList,
  Download,
  ExternalLink,
  File,
  FileText,
  ListChecks,
  MessagesSquare,
  Music,
  Package,
  Radio,
  Video,
  type LucideIcon,
} from "lucide-react";
import type { BlockContent, BlockKind } from "./types";

export type BlockGroup = "content" | "media" | "interactive" | "package" | "engagement";

export interface BlockDef {
  kind: BlockKind;
  icon: LucideIcon;
  /** Local authoring-i18n keys (see authoring-i18n.ts). */
  labelKey: string;
  descriptionKey: string;
  group: BlockGroup;
  /** True when the backend LessonType enum accepts this kind (create/update/publish work). */
  supported: boolean;
  /** True when the block carries Mux/S3 media metadata. */
  usesMedia: boolean;
  /** Factory for a fresh content payload when the block is created. */
  defaultContent: () => BlockContent;
}

const def = (d: BlockDef): BlockDef => d;

/** Ordered registry — order drives the "add block" menu grouping. */
export const BLOCK_DEFS: readonly BlockDef[] = [
  // ── Content ──────────────────────────────────────────────
  def({ kind: "article", icon: FileText, labelKey: "block.article.label", descriptionKey: "block.article.desc", group: "content", supported: true, usesMedia: false, defaultContent: () => ({ html: "" }) }),
  def({ kind: "pdf", icon: File, labelKey: "block.pdf.label", descriptionKey: "block.pdf.desc", group: "content", supported: true, usesMedia: true, defaultContent: () => ({}) }),
  def({ kind: "download", icon: Download, labelKey: "block.download.label", descriptionKey: "block.download.desc", group: "content", supported: true, usesMedia: true, defaultContent: () => ({}) }),
  def({ kind: "external_link", icon: ExternalLink, labelKey: "block.external_link.label", descriptionKey: "block.external_link.desc", group: "content", supported: true, usesMedia: false, defaultContent: () => ({ url: "", label: "" }) }),
  // ── Media ────────────────────────────────────────────────
  def({ kind: "video", icon: Video, labelKey: "block.video.label", descriptionKey: "block.video.desc", group: "media", supported: true, usesMedia: true, defaultContent: () => ({}) }),
  def({ kind: "audio", icon: Music, labelKey: "block.audio.label", descriptionKey: "block.audio.desc", group: "media", supported: true, usesMedia: true, defaultContent: () => ({}) }),
  def({ kind: "live_session", icon: Radio, labelKey: "block.live_session.label", descriptionKey: "block.live_session.desc", group: "media", supported: false, usesMedia: false, defaultContent: () => ({ starts_at: null, join_url: "" }) }),
  // ── Interactive / assessment ─────────────────────────────
  def({ kind: "quiz_placeholder", icon: ListChecks, labelKey: "block.quiz_placeholder.label", descriptionKey: "block.quiz_placeholder.desc", group: "interactive", supported: true, usesMedia: false, defaultContent: () => ({ note: "" }) }),
  def({ kind: "quiz", icon: ListChecks, labelKey: "block.quiz.label", descriptionKey: "block.quiz.desc", group: "interactive", supported: false, usesMedia: false, defaultContent: () => ({ questions: [] }) }),
  def({ kind: "assignment", icon: ClipboardCheck, labelKey: "block.assignment.label", descriptionKey: "block.assignment.desc", group: "interactive", supported: false, usesMedia: false, defaultContent: () => ({ instructions: "", due_at: null }) }),
  def({ kind: "survey", icon: ClipboardList, labelKey: "block.survey.label", descriptionKey: "block.survey.desc", group: "interactive", supported: false, usesMedia: false, defaultContent: () => ({ questions: [] }) }),
  // ── Packages (e-learning standards) ──────────────────────
  def({ kind: "scorm", icon: Package, labelKey: "block.scorm.label", descriptionKey: "block.scorm.desc", group: "package", supported: false, usesMedia: false, defaultContent: () => ({ package_key: "" }) }),
  def({ kind: "xapi", icon: Activity, labelKey: "block.xapi.label", descriptionKey: "block.xapi.desc", group: "package", supported: false, usesMedia: false, defaultContent: () => ({ endpoint: "" }) }),
  def({ kind: "cmi5", icon: Boxes, labelKey: "block.cmi5.label", descriptionKey: "block.cmi5.desc", group: "package", supported: false, usesMedia: false, defaultContent: () => ({ au_index: 0 }) }),
  // ── Engagement ───────────────────────────────────────────
  def({ kind: "discussion", icon: MessagesSquare, labelKey: "block.discussion.label", descriptionKey: "block.discussion.desc", group: "engagement", supported: false, usesMedia: false, defaultContent: () => ({ prompt: "" }) }),
  def({ kind: "certificate", icon: Award, labelKey: "block.certificate.label", descriptionKey: "block.certificate.desc", group: "engagement", supported: false, usesMedia: false, defaultContent: () => ({ template_id: null }) }),
];

const BY_KIND: Record<BlockKind, BlockDef> = BLOCK_DEFS.reduce(
  (acc, d) => {
    acc[d.kind] = d;
    return acc;
  },
  {} as Record<BlockKind, BlockDef>,
);

export function blockDef(kind: BlockKind): BlockDef {
  return BY_KIND[kind];
}

export function isBackendSupported(kind: BlockKind): boolean {
  return BY_KIND[kind].supported;
}

export const BLOCK_GROUP_ORDER: readonly BlockGroup[] = ["content", "media", "interactive", "package", "engagement"];

export function blocksByGroup(): Record<BlockGroup, BlockDef[]> {
  const out = { content: [], media: [], interactive: [], package: [], engagement: [] } as Record<BlockGroup, BlockDef[]>;
  for (const d of BLOCK_DEFS) out[d.group].push(d);
  return out;
}
