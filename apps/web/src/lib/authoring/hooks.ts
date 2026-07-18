/**
 * Course Builder — data controller (React Query).
 *
 * `useAuthoringController(courseId)` returns the curriculum query plus a set of async action
 * functions. Each action optimistically updates the query cache (snappy UI), rolls back on error,
 * and reconciles with the server on success. The builder store composes these into undo/redo
 * commands — this layer stays free of view state.
 */
"use client";

import { useMemo } from "react";
import { useQuery, useQueryClient, type QueryKey } from "@tanstack/react-query";
import * as apiClient from "./api";
import type {
  Block,
  CreateBlockInput,
  CreateSectionInput,
  Curriculum,
  LessonMedia,
  PublishState,
  Section,
  UpdateBlockInput,
  UpdateSectionInput,
  UpsertMediaInput,
} from "./types";

export function curriculumKey(courseId: string): QueryKey {
  return ["authoring", "curriculum", courseId];
}

// ── Immutable helpers ───────────────────────────────────────────────────────
function mapSections(c: Curriculum, fn: (s: Section) => Section): Curriculum {
  return { ...c, sections: c.sections.map(fn) };
}
function mapSection(c: Curriculum, sectionId: string, fn: (s: Section) => Section): Curriculum {
  return mapSections(c, (s) => (s.id === sectionId ? fn(s) : s));
}
function reindex<T extends { position: number }>(items: T[]): T[] {
  return items.map((it, i) => ({ ...it, position: i }));
}
function orderBy<T extends { id: string }>(items: T[], orderedIds: string[]): T[] {
  const byId = new Map(items.map((it) => [it.id, it]));
  const ordered = orderedIds.map((id) => byId.get(id)).filter((x): x is T => Boolean(x));
  const rest = items.filter((it) => !orderedIds.includes(it.id));
  return [...ordered, ...rest];
}

export interface AuthoringActions {
  addSection: (input: CreateSectionInput) => Promise<Section>;
  updateSection: (sectionId: string, input: UpdateSectionInput) => Promise<void>;
  removeSection: (sectionId: string) => Promise<void>;
  publishSection: (sectionId: string, state: PublishState) => Promise<void>;
  reorderSections: (orderedIds: string[]) => Promise<void>;

  addBlock: (sectionId: string, input: CreateBlockInput) => Promise<Block>;
  updateBlock: (sectionId: string, blockId: string, input: UpdateBlockInput) => Promise<void>;
  removeBlock: (sectionId: string, blockId: string) => Promise<void>;
  publishBlock: (sectionId: string, blockId: string, state: PublishState) => Promise<void>;
  previewBlock: (sectionId: string, blockId: string) => Promise<void>;
  /** Upsert the lesson's `lesson_media` row. Explicit nulls clear columns (see UpsertMediaInput). */
  setMedia: (sectionId: string, blockId: string, input: UpsertMediaInput) => Promise<void>;
  reorderBlocks: (sectionId: string, orderedIds: string[]) => Promise<void>;
  moveBlockAcross: (
    fromSectionId: string,
    toSectionId: string,
    blockId: string,
    toIndex: number,
  ) => Promise<void>;
}

export function useAuthoringController(courseId: string) {
  const qc = useQueryClient();
  const key = curriculumKey(courseId);

  const query = useQuery({
    queryKey: key,
    queryFn: () => apiClient.getCurriculum(courseId),
    staleTime: 15_000,
  });

  const actions = useMemo<AuthoringActions>(() => {
    const read = () => qc.getQueryData<Curriculum>(key);
    const write = (next: Curriculum) => qc.setQueryData<Curriculum>(key, next);
    const invalidate = () => qc.invalidateQueries({ queryKey: key });

    /** Run an optimistic patch, persist, rollback on failure, reconcile on success. */
    async function optimistic(patch: (c: Curriculum) => Curriculum, persist: () => Promise<unknown>): Promise<void> {
      const prev = read();
      if (prev) write(patch(prev));
      try {
        await persist();
        await invalidate();
      } catch (e) {
        if (prev) write(prev);
        throw e;
      }
    }

    return {
      async addSection(input) {
        const created = await apiClient.createSection(courseId, input);
        await invalidate();
        return created;
      },
      updateSection(sectionId, input) {
        return optimistic(
          (c) =>
            mapSection(c, sectionId, (s) => ({
              ...s,
              title: input.title ?? s.title,
              summary: input.summary !== undefined ? input.summary : s.summary,
            })),
          () => apiClient.updateSection(sectionId, input),
        );
      },
      removeSection(sectionId) {
        return optimistic(
          (c) => ({ ...c, sections: reindex(c.sections.filter((s) => s.id !== sectionId)) }),
          () => apiClient.deleteSection(sectionId),
        );
      },
      publishSection(sectionId, state) {
        return optimistic(
          (c) => mapSection(c, sectionId, (s) => ({ ...s, publish_state: state })),
          () => apiClient.setSectionPublish(sectionId, state),
        );
      },
      reorderSections(orderedIds) {
        return optimistic(
          (c) => ({ ...c, sections: reindex(orderBy(c.sections, orderedIds)) }),
          () => apiClient.reorderSections(courseId, orderedIds),
        );
      },

      async addBlock(sectionId, input) {
        const created = await apiClient.createBlock(sectionId, input);
        await invalidate();
        return created;
      },
      updateBlock(sectionId, blockId, input) {
        return optimistic(
          (c) =>
            mapSection(c, sectionId, (s) => ({
              ...s,
              blocks: s.blocks.map((b) =>
                b.id === blockId
                  ? {
                      ...b,
                      title: input.title ?? b.title,
                      kind: input.kind ?? b.kind,
                      content: input.content ?? b.content,
                    }
                  : b,
              ),
            })),
          () => apiClient.updateBlock(blockId, input),
        );
      },
      removeBlock(sectionId, blockId) {
        return optimistic(
          (c) => mapSection(c, sectionId, (s) => ({ ...s, blocks: reindex(s.blocks.filter((b) => b.id !== blockId)) })),
          () => apiClient.deleteBlock(blockId),
        );
      },
      publishBlock(sectionId, blockId, state) {
        return optimistic(
          (c) => mapSection(c, sectionId, (s) => ({ ...s, blocks: s.blocks.map((b) => (b.id === blockId ? { ...b, publish_state: state } : b)) })),
          () => apiClient.setBlockPublish(blockId, state),
        );
      },
      previewBlock(sectionId, blockId) {
        return optimistic(
          (c) => mapSection(c, sectionId, (s) => ({ ...s, blocks: s.blocks.map((b) => (b.id === blockId ? { ...b, is_preview: !b.is_preview } : b)) })),
          () => apiClient.toggleBlockPreview(blockId),
        );
      },
      setMedia(sectionId, blockId, input) {
        return optimistic(
          (c) =>
            mapSection(c, sectionId, (s) => ({
              ...s,
              blocks: s.blocks.map((b) => (b.id === blockId ? { ...b, media: mediaFromInput(input) } : b)),
            })),
          () => apiClient.upsertMedia(blockId, input),
        );
      },
      reorderBlocks(sectionId, orderedIds) {
        return optimistic(
          (c) => mapSection(c, sectionId, (s) => ({ ...s, blocks: reindex(orderBy(s.blocks, orderedIds)) })),
          () => apiClient.reorderBlocks(sectionId, orderedIds),
        );
      },
      moveBlockAcross(fromSectionId, toSectionId, blockId, toIndex) {
        return optimistic(
          (c) => {
            const from = c.sections.find((s) => s.id === fromSectionId);
            const moving = from?.blocks.find((b) => b.id === blockId);
            if (!from || !moving) return c;
            return {
              ...c,
              sections: c.sections.map((s) => {
                if (s.id === fromSectionId) return { ...s, blocks: reindex(s.blocks.filter((b) => b.id !== blockId)) };
                if (s.id === toSectionId) {
                  const next = s.blocks.filter((b) => b.id !== blockId);
                  next.splice(Math.max(0, Math.min(toIndex, next.length)), 0, moving);
                  return { ...s, blocks: reindex(next) };
                }
                return s;
              }),
            };
          },
          () => {
            const c = read();
            const tree = (c?.sections ?? []).map((s) => ({
              id: s.id,
              lessons: s.id === toSectionId
                ? insertId(s.blocks.map((b) => b.id).filter((id) => id !== blockId), blockId, toIndex)
                : s.blocks.map((b) => b.id).filter((id) => id !== blockId),
            }));
            return apiClient.reorderTree(courseId, { tree });
          },
        );
      },
    };
  }, [qc, key, courseId]);

  return { query, actions };
}

/**
 * Optimistic projection of an upsert payload onto the cached media row. The editor always submits
 * the complete field set, so an omitted field genuinely means "cleared" — matching the backend,
 * where every column is nullable and the request replaces the row.
 */
function mediaFromInput(input: UpsertMediaInput): LessonMedia {
  return {
    mux_asset_id: input.mux_asset_id ?? null,
    mux_playback_id: input.mux_playback_id ?? null,
    s3_key: input.s3_key ?? null,
    mime_type: input.mime_type ?? null,
    duration: input.duration ?? null,
    filesize: input.filesize ?? null,
  };
}

function insertId(ids: string[], id: string, index: number): string[] {
  const next = ids.slice();
  next.splice(Math.max(0, Math.min(index, next.length)), 0, id);
  return next;
}
