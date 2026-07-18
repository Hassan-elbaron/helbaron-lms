"use client";

import { useState } from "react";
import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { Copy, Eye, GripVertical, MoreVertical, Pencil, Send, Trash2, Undo2 } from "lucide-react";
import { Badge } from "@/components/ui/badge";
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
import type { Block } from "@/lib/authoring/types";
import { BlockIcon } from "./block-icon";
import { StatusBadge } from "./status-badge";

export function BlockNode({ sectionId, block }: { sectionId: string; block: Block }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const [confirmDelete, setConfirmDelete] = useState(false);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: block.id,
    data: { type: "block", sectionId },
  });

  const selected = builder.selection.kind === "lesson" && builder.selection.blockId === block.id;
  const published = block.publish_state === "published";

  return (
    <div
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      className={`group flex items-center gap-1 rounded-md ps-1 pe-0.5 py-0.5 text-sm ${
        isDragging ? "opacity-60" : ""
      } ${selected ? "bg-accent" : "hover:bg-muted/60"}`}
    >
      <button
        type="button"
        className="flex size-6 shrink-0 cursor-grab touch-none items-center justify-center rounded text-muted-foreground/60 hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring active:cursor-grabbing"
        aria-label={t("action.dragHandle")}
        {...attributes}
        {...listeners}
      >
        <GripVertical className="size-3.5" aria-hidden />
      </button>

      <button
        type="button"
        onClick={() => builder.select({ kind: "lesson", sectionId, blockId: block.id })}
        className="flex min-w-0 flex-1 items-center gap-2 rounded py-1 text-start focus-visible:outline-2 focus-visible:outline-ring"
        aria-current={selected ? "true" : undefined}
      >
        <span className="text-muted-foreground">
          <BlockIcon kind={block.kind} />
        </span>
        <span className="min-w-0 flex-1 truncate">{block.title}</span>
        {block.is_preview ? (
          <Badge variant="outline" className="gap-1 text-[0.65rem]">
            <Eye className="size-3" aria-hidden />
            {t("status.preview")}
          </Badge>
        ) : null}
        <StatusBadge state={block.publish_state} />
      </button>

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
          <DropdownMenuItem onSelect={() => builder.select({ kind: "lesson", sectionId, blockId: block.id })} className="gap-2">
            <Pencil className="size-4" aria-hidden /> {t("action.edit")}
          </DropdownMenuItem>
          <DropdownMenuItem onSelect={() => void builder.previewBlock(sectionId, block.id)} className="gap-2">
            <Eye className="size-4" aria-hidden /> {t("action.preview")}
          </DropdownMenuItem>
          <DropdownMenuItem
            onSelect={() => void builder.publishBlock(sectionId, block.id, published ? "draft" : "published")}
            className="gap-2"
          >
            {published ? <Undo2 className="size-4" aria-hidden /> : <Send className="size-4" aria-hidden />}
            {t(published ? "action.unpublish" : "action.publish")}
          </DropdownMenuItem>
          <DropdownMenuItem onSelect={() => void builder.duplicateBlock(sectionId, block.id)} className="gap-2">
            <Copy className="size-4" aria-hidden /> {t("action.duplicate")}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem onSelect={() => setConfirmDelete(true)} className="gap-2 text-destructive">
            <Trash2 className="size-4" aria-hidden /> {t("action.delete")}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        title={t("confirm.deleteBlock.title")}
        description={t("confirm.deleteBlock.desc", { title: block.title })}
        confirmLabel={t("confirm.delete")}
        cancelLabel={t("confirm.cancel")}
        confirmVariant="destructive"
        onConfirm={() => builder.deleteBlock(sectionId, block.id)}
      />
    </div>
  );
}
