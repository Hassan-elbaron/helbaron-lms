"use client";

import { useState } from "react";
import { SortableContext, useSortable, verticalListSortingStrategy } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { ChevronRight, Copy, GripVertical, MoreVertical, Pencil, Send, Trash2, Undo2 } from "lucide-react";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import type { Section } from "@/lib/authoring/types";
import { AddBlockMenu } from "./add-block-menu";
import { BlockNode } from "./block-node";
import { StatusBadge } from "./status-badge";

export function SectionNode({ section, query }: { section: Section; query: string }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const [confirmDelete, setConfirmDelete] = useState(false);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: section.id,
    data: { type: "section" },
  });

  const expanded = builder.expanded.has(section.id) || query.length > 0;
  const selected = builder.selection.kind === "section" && builder.selection.sectionId === section.id;
  const published = section.publish_state === "published";
  const count = section.blocks.length;

  const q = query.trim().toLowerCase();
  const blocks = q ? section.blocks.filter((b) => b.title.toLowerCase().includes(q)) : section.blocks;
  if (q && !section.title.toLowerCase().includes(q) && blocks.length === 0) return null;

  return (
    <div
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      className={`rounded-lg border ${isDragging ? "opacity-60" : ""} ${selected ? "border-ring" : "border-border"} bg-card`}
    >
      <div className={`group flex items-center gap-1 rounded-t-lg px-1 py-1.5 ${selected ? "bg-accent" : ""}`}>
        <button
          type="button"
          className="flex size-6 shrink-0 cursor-grab touch-none items-center justify-center rounded text-muted-foreground/60 hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring active:cursor-grabbing"
          aria-label={t("action.dragHandle")}
          {...attributes}
          {...listeners}
        >
          <GripVertical className="size-4" aria-hidden />
        </button>

        <button
          type="button"
          onClick={() => builder.toggleExpand(section.id)}
          className="flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring"
          aria-label={expanded ? t("tree.collapseAll") : t("tree.expandAll")}
          aria-expanded={expanded}
        >
          <ChevronRight className={`size-4 transition-transform rtl:-scale-x-100 ${expanded ? "rotate-90 rtl:-rotate-90" : ""}`} aria-hidden />
        </button>

        <button
          type="button"
          onClick={() => builder.select({ kind: "section", sectionId: section.id })}
          className="flex min-w-0 flex-1 items-center gap-2 rounded py-0.5 text-start focus-visible:outline-2 focus-visible:outline-ring"
          aria-current={selected ? "true" : undefined}
        >
          <span className="min-w-0 flex-1 truncate font-medium">{section.title}</span>
          <span className="shrink-0 text-xs text-muted-foreground">
            {count === 1 ? t("tree.lessons.one") : t("tree.lessons.other", { count })}
          </span>
          <StatusBadge state={section.publish_state} />
        </button>

        <AddBlockMenu
          onAdd={(kind) => void builder.addBlock(section.id, kind)}
          trigger={
            <button
              type="button"
              className="flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground opacity-0 hover:bg-muted focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-ring group-hover:opacity-100"
              aria-label={t("tree.addBlock")}
            >
              <span className="text-lg leading-none">+</span>
            </button>
          }
        />

        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <button
              type="button"
              className="flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground opacity-0 hover:bg-muted focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-ring group-hover:opacity-100"
              aria-label={t("action.edit")}
            >
              <MoreVertical className="size-4" aria-hidden />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-48">
            <DropdownMenuItem onSelect={() => builder.select({ kind: "section", sectionId: section.id })} className="gap-2">
              <Pencil className="size-4" aria-hidden /> {t("action.rename")}
            </DropdownMenuItem>
            <DropdownMenuItem
              onSelect={() => void builder.publishSection(section.id, published ? "draft" : "published")}
              className="gap-2"
            >
              {published ? <Undo2 className="size-4" aria-hidden /> : <Send className="size-4" aria-hidden />}
              {t(published ? "action.unpublish" : "action.publish")}
            </DropdownMenuItem>
            <DropdownMenuItem onSelect={() => void builder.duplicateSection(section.id)} className="gap-2">
              <Copy className="size-4" aria-hidden /> {t("action.duplicate")}
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onSelect={() => setConfirmDelete(true)} className="gap-2 text-destructive">
              <Trash2 className="size-4" aria-hidden /> {t("action.delete")}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      {expanded ? (
        <div className="space-y-0.5 border-t border-border/60 p-1">
          {blocks.length === 0 ? (
            <div className="px-2 py-3 text-center text-xs text-muted-foreground">{t("tree.emptySection")}</div>
          ) : (
            <SortableContext items={blocks.map((b) => b.id)} strategy={verticalListSortingStrategy}>
              {blocks.map((b) => (
                <BlockNode key={b.id} sectionId={section.id} block={b} />
              ))}
            </SortableContext>
          )}
        </div>
      ) : null}

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title={t("confirm.deleteSection.title")}
        description={t("confirm.deleteSection.desc", { title: section.title })}
        confirmLabel={t("confirm.delete")}
        cancelLabel={t("confirm.cancel")}
        confirmVariant="destructive"
        onConfirm={() => builder.deleteSection(section.id)}
      />
    </div>
  );
}
