/**
 * Course Builder — store (view state + undo/redo + save orchestration).
 *
 * Composes the data controller (hooks.ts) into high-level actions the UI calls. Reversible,
 * id-stable operations (rename, summary, content, publish, preview, reorder) are recorded as
 * undo/redo commands; structural create/delete are explicit actions (they change ids, so they are
 * not part of the history chain). Save status drives the sticky-toolbar autosave indicator.
 */
"use client";

import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from "react";
import { toast } from "@/components/ui/toast";
import { errorMessage } from "@/lib/api/errors";
import { blockDef } from "./block-registry";
import { useAuthoringController } from "./hooks";
import { validateCurriculum } from "./validation";
import type {
  Block,
  BlockContent,
  BlockKind,
  Curriculum,
  PublishState,
  Section,
  Selection,
  SaveStatus,
  ValidationIssue,
} from "./types";

interface Command {
  label: string;
  redo: () => Promise<void>;
  undo: () => Promise<void>;
}

export interface BuilderContextValue {
  courseId: string;
  curriculum: Curriculum | undefined;
  isLoading: boolean;
  isError: boolean;
  refetch: () => void;

  selection: Selection;
  select: (s: Selection) => void;

  expanded: ReadonlySet<string>;
  toggleExpand: (sectionId: string) => void;
  expandAll: () => void;
  collapseAll: () => void;

  search: string;
  setSearch: (q: string) => void;

  saveStatus: SaveStatus;
  version: number;
  canUndo: boolean;
  canRedo: boolean;
  undo: () => void;
  redo: () => void;

  issues: ValidationIssue[];

  // High-level actions
  addSection: () => Promise<void>;
  renameSection: (sectionId: string, title: string) => Promise<void>;
  setSectionSummary: (sectionId: string, summary: string) => Promise<void>;
  deleteSection: (sectionId: string) => Promise<void>;
  publishSection: (sectionId: string, state: PublishState) => Promise<void>;
  reorderSections: (orderedIds: string[]) => Promise<void>;

  addBlock: (sectionId: string, kind: BlockKind) => Promise<void>;
  renameBlock: (sectionId: string, blockId: string, title: string) => Promise<void>;
  setBlockContent: (sectionId: string, blockId: string, content: BlockContent) => Promise<void>;
  deleteBlock: (sectionId: string, blockId: string) => Promise<void>;
  publishBlock: (sectionId: string, blockId: string, state: PublishState) => Promise<void>;
  previewBlock: (sectionId: string, blockId: string) => Promise<void>;
  reorderBlocks: (sectionId: string, orderedIds: string[]) => Promise<void>;
  moveBlockAcross: (fromSectionId: string, toSectionId: string, blockId: string, toIndex: number) => Promise<void>;
  duplicateSection: (sectionId: string) => Promise<void>;
  duplicateBlock: (sectionId: string, blockId: string) => Promise<void>;
}

const BuilderContext = createContext<BuilderContextValue | null>(null);

export function useBuilder(): BuilderContextValue {
  const ctx = useContext(BuilderContext);
  if (!ctx) throw new globalThis.Error("useBuilder must be used within <BuilderProvider>");
  return ctx;
}

export function BuilderProvider({ courseId, children }: { courseId: string; children: ReactNode }) {
  const { query, actions } = useAuthoringController(courseId);
  const curriculum = query.data;

  const [selection, setSelection] = useState<Selection>({ kind: "course" });
  const [expanded, setExpanded] = useState<ReadonlySet<string>>(new Set());
  const [search, setSearch] = useState("");
  const [saveStatus, setSaveStatus] = useState<SaveStatus>("idle");
  const [past, setPast] = useState<Command[]>([]);
  const [future, setFuture] = useState<Command[]>([]);
  const savedTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const findSection = useCallback(
    (id: string): Section | undefined => curriculum?.sections.find((s) => s.id === id),
    [curriculum],
  );
  const findBlock = useCallback(
    (sectionId: string, blockId: string): Block | undefined => findSection(sectionId)?.blocks.find((b) => b.id === blockId),
    [findSection],
  );

  const flashSaved = useCallback(() => {
    setSaveStatus("saved");
    if (savedTimer.current) clearTimeout(savedTimer.current);
    savedTimer.current = setTimeout(() => setSaveStatus("idle"), 1600);
  }, []);

  /** Execute a side-effect with save-status + error handling. */
  const run = useCallback(
    async (fn: () => Promise<void>) => {
      setSaveStatus("saving");
      try {
        await fn();
        flashSaved();
      } catch (e) {
        setSaveStatus("error");
        toast.error(errorMessage(e, "Couldn't save your changes"));
      }
    },
    [flashSaved],
  );

  /** Run a reversible command and record it for undo. */
  const runCommand = useCallback(
    async (cmd: Command) => {
      setSaveStatus("saving");
      try {
        await cmd.redo();
        setPast((p) => [...p, cmd]);
        setFuture([]);
        flashSaved();
      } catch (e) {
        setSaveStatus("error");
        toast.error(errorMessage(e, "Couldn't save your changes"));
      }
    },
    [flashSaved],
  );

  const undo = useCallback(() => {
    setPast((p) => {
      if (p.length === 0) return p;
      const cmd = p[p.length - 1];
      void run(cmd.undo).then(() => setFuture((f) => [cmd, ...f]));
      return p.slice(0, -1);
    });
  }, [run]);

  const redo = useCallback(() => {
    setFuture((f) => {
      if (f.length === 0) return f;
      const [cmd, ...rest] = f;
      void run(cmd.redo).then(() => setPast((p) => [...p, cmd]));
      return rest;
    });
  }, [run]);

  // ── View state ────────────────────────────────────────────────────────────
  const toggleExpand = useCallback((sectionId: string) => {
    setExpanded((prev) => {
      const next = new Set(prev);
      if (next.has(sectionId)) next.delete(sectionId);
      else next.add(sectionId);
      return next;
    });
  }, []);
  const expandAll = useCallback(() => {
    setExpanded(new Set((curriculum?.sections ?? []).map((s) => s.id)));
  }, [curriculum]);
  const collapseAll = useCallback(() => setExpanded(new Set()), []);

  // ── Sections ───────────────────────────────────────────────────────────────
  const addSection = useCallback(async () => {
    await run(async () => {
      const created = await actions.addSection({ title: "Untitled section" });
      setExpanded((prev) => new Set(prev).add(created.id));
      setSelection({ kind: "section", sectionId: created.id });
      toast.success("Added");
    });
  }, [actions, run]);

  const renameSection = useCallback(
    async (sectionId: string, title: string) => {
      const prev = findSection(sectionId)?.title ?? "";
      if (prev === title) return;
      await runCommand({
        label: "rename section",
        redo: () => actions.updateSection(sectionId, { title }),
        undo: () => actions.updateSection(sectionId, { title: prev }),
      });
    },
    [actions, findSection, runCommand],
  );

  const setSectionSummary = useCallback(
    async (sectionId: string, summary: string) => {
      const prev = findSection(sectionId)?.summary ?? "";
      if (prev === summary) return;
      await runCommand({
        label: "edit section summary",
        redo: () => actions.updateSection(sectionId, { summary }),
        undo: () => actions.updateSection(sectionId, { summary: prev }),
      });
    },
    [actions, findSection, runCommand],
  );

  const deleteSection = useCallback(
    async (sectionId: string) => {
      await run(async () => {
        await actions.removeSection(sectionId);
        setPast([]);
        setFuture([]);
        setSelection({ kind: "course" });
        toast.success("Deleted");
      });
    },
    [actions, run],
  );

  const publishSection = useCallback(
    async (sectionId: string, state: PublishState) => {
      const prev = findSection(sectionId)?.publish_state ?? "draft";
      await runCommand({
        label: "publish section",
        redo: () => actions.publishSection(sectionId, state),
        undo: () => actions.publishSection(sectionId, prev),
      });
    },
    [actions, findSection, runCommand],
  );

  const reorderSections = useCallback(
    async (orderedIds: string[]) => {
      const prevOrder = (curriculum?.sections ?? []).map((s) => s.id);
      await runCommand({
        label: "reorder sections",
        redo: () => actions.reorderSections(orderedIds),
        undo: () => actions.reorderSections(prevOrder),
      });
    },
    [actions, curriculum, runCommand],
  );

  // ── Blocks ─────────────────────────────────────────────────────────────────
  const addBlock = useCallback(
    async (sectionId: string, kind: BlockKind) => {
      const def = blockDef(kind);
      if (!def.supported) {
        toast.error("That block type isn't available to save yet");
        return;
      }
      await run(async () => {
        const created = await actions.addBlock(sectionId, { title: "Untitled lesson", kind, content: def.defaultContent() });
        setExpanded((prev) => new Set(prev).add(sectionId));
        setSelection({ kind: "lesson", sectionId, blockId: created.id });
        toast.success("Added");
      });
    },
    [actions, run],
  );

  const renameBlock = useCallback(
    async (sectionId: string, blockId: string, title: string) => {
      const prev = findBlock(sectionId, blockId)?.title ?? "";
      if (prev === title) return;
      await runCommand({
        label: "rename lesson",
        redo: () => actions.updateBlock(sectionId, blockId, { title }),
        undo: () => actions.updateBlock(sectionId, blockId, { title: prev }),
      });
    },
    [actions, findBlock, runCommand],
  );

  const setBlockContent = useCallback(
    async (sectionId: string, blockId: string, content: BlockContent) => {
      const prev = findBlock(sectionId, blockId)?.content ?? {};
      await runCommand({
        label: "edit lesson",
        redo: () => actions.updateBlock(sectionId, blockId, { content }),
        undo: () => actions.updateBlock(sectionId, blockId, { content: prev }),
      });
    },
    [actions, findBlock, runCommand],
  );

  const deleteBlock = useCallback(
    async (sectionId: string, blockId: string) => {
      await run(async () => {
        await actions.removeBlock(sectionId, blockId);
        setPast([]);
        setFuture([]);
        setSelection({ kind: "section", sectionId });
        toast.success("Deleted");
      });
    },
    [actions, run],
  );

  const publishBlock = useCallback(
    async (sectionId: string, blockId: string, state: PublishState) => {
      const prev = findBlock(sectionId, blockId)?.publish_state ?? "draft";
      await runCommand({
        label: "publish lesson",
        redo: () => actions.publishBlock(sectionId, blockId, state),
        undo: () => actions.publishBlock(sectionId, blockId, prev),
      });
    },
    [actions, findBlock, runCommand],
  );

  const previewBlock = useCallback(
    async (sectionId: string, blockId: string) => {
      await runCommand({
        label: "toggle preview",
        redo: () => actions.previewBlock(sectionId, blockId),
        undo: () => actions.previewBlock(sectionId, blockId),
      });
    },
    [actions, runCommand],
  );

  const reorderBlocks = useCallback(
    async (sectionId: string, orderedIds: string[]) => {
      const prevOrder = (findSection(sectionId)?.blocks ?? []).map((b) => b.id);
      await runCommand({
        label: "reorder lessons",
        redo: () => actions.reorderBlocks(sectionId, orderedIds),
        undo: () => actions.reorderBlocks(sectionId, prevOrder),
      });
    },
    [actions, findSection, runCommand],
  );

  const moveBlockAcross = useCallback(
    async (fromSectionId: string, toSectionId: string, blockId: string, toIndex: number) => {
      const fromOrder = (findSection(fromSectionId)?.blocks ?? []).map((b) => b.id);
      const fromIndex = fromOrder.indexOf(blockId);
      await runCommand({
        label: "move lesson",
        redo: () => actions.moveBlockAcross(fromSectionId, toSectionId, blockId, toIndex),
        undo: () => actions.moveBlockAcross(toSectionId, fromSectionId, blockId, Math.max(0, fromIndex)),
      });
    },
    [actions, findSection, runCommand],
  );

  const duplicateSection = useCallback(
    async (sectionId: string) => {
      const src = findSection(sectionId);
      if (!src) return;
      await run(async () => {
        const created = await actions.addSection({ title: `${src.title} (copy)`, summary: src.summary ?? undefined });
        for (const b of src.blocks) {
          if (blockDef(b.kind).supported) {
            await actions.addBlock(created.id, { title: b.title, kind: b.kind, content: b.content, is_preview: b.is_preview });
          }
        }
        setExpanded((prev) => new Set(prev).add(created.id));
        setSelection({ kind: "section", sectionId: created.id });
        setPast([]);
        setFuture([]);
        toast.success("Added");
      });
    },
    [actions, findSection, run],
  );

  const duplicateBlock = useCallback(
    async (sectionId: string, blockId: string) => {
      const src = findBlock(sectionId, blockId);
      if (!src) return;
      if (!blockDef(src.kind).supported) {
        toast.error("That block type isn't available to save yet");
        return;
      }
      await run(async () => {
        const created = await actions.addBlock(sectionId, {
          title: `${src.title} (copy)`,
          kind: src.kind,
          content: src.content,
          is_preview: src.is_preview,
        });
        setSelection({ kind: "lesson", sectionId, blockId: created.id });
        toast.success("Added");
      });
    },
    [actions, findBlock, run],
  );

  const issues = useMemo(() => (curriculum ? validateCurriculum(curriculum) : []), [curriculum]);

  const value: BuilderContextValue = {
    courseId,
    curriculum,
    isLoading: query.isPending,
    isError: query.isError,
    refetch: () => void query.refetch(),
    selection,
    select: setSelection,
    expanded,
    toggleExpand,
    expandAll,
    collapseAll,
    search,
    setSearch,
    saveStatus,
    version: past.length,
    canUndo: past.length > 0,
    canRedo: future.length > 0,
    undo,
    redo,
    issues,
    addSection,
    renameSection,
    setSectionSummary,
    deleteSection,
    publishSection,
    reorderSections,
    addBlock,
    renameBlock,
    setBlockContent,
    deleteBlock,
    publishBlock,
    previewBlock,
    reorderBlocks,
    moveBlockAcross,
    duplicateSection,
    duplicateBlock,
  };

  return <BuilderContext.Provider value={value}>{children}</BuilderContext.Provider>;
}
