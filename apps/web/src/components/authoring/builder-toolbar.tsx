"use client";

import Link from "next/link";
import { ArrowLeft, Eye, PanelLeft, PanelRight, Redo2, TriangleAlert, Undo2 } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import { errorCount } from "@/lib/authoring/validation";
import { useTeachCourse } from "@/lib/teach/hooks";
import { AutosaveIndicator } from "./autosave-indicator";

export function BuilderToolbar({ onOpenTree, onOpenInspector }: { onOpenTree: () => void; onOpenInspector: () => void }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const { data } = useTeachCourse(builder.courseId);
  const errors = errorCount(builder.issues);

  return (
    <header className="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-1.5 border-b border-border bg-background/95 px-2 backdrop-blur sm:px-3">
      <Button asChild variant="ghost" size="icon" aria-label={t("toolbar.backToCourse")}>
        <Link href={`/teach/courses/${builder.courseId}`}>
          <ArrowLeft className="size-4 rtl:-scale-x-100" aria-hidden />
        </Link>
      </Button>
      <Button variant="ghost" size="icon" className="lg:hidden" onClick={onOpenTree} aria-label={t("tree.curriculum")}>
        <PanelLeft className="size-4" aria-hidden />
      </Button>

      <p className="min-w-0 flex-1 truncate text-sm font-semibold">{data?.title ?? "…"}</p>

      <AutosaveIndicator status={builder.saveStatus} />
      <span className="mx-1 hidden text-xs tabular-nums text-muted-foreground md:inline">
        {t("toolbar.version", { n: builder.version })}
      </span>
      {errors > 0 ? (
        <Badge variant="outline" className="gap-1 text-destructive">
          <TriangleAlert className="size-3" aria-hidden />
          {errors}
        </Badge>
      ) : null}

      <div className="flex items-center gap-0.5">
        <Button variant="ghost" size="icon" disabled={!builder.canUndo} onClick={builder.undo} aria-label={t("toolbar.undo")}>
          <Undo2 className="size-4" aria-hidden />
        </Button>
        <Button variant="ghost" size="icon" disabled={!builder.canRedo} onClick={builder.redo} aria-label={t("toolbar.redo")}>
          <Redo2 className="size-4" aria-hidden />
        </Button>
      </div>

      <Button asChild variant="outline" size="sm" className="hidden sm:inline-flex">
        <Link href={`/courses/${builder.courseId}`} target="_blank" rel="noopener noreferrer">
          <Eye className="size-4" aria-hidden />
          {t("toolbar.preview")}
        </Link>
      </Button>

      <Button variant="ghost" size="icon" className="xl:hidden" onClick={onOpenInspector} aria-label={t("inspector.title")}>
        <PanelRight className="size-4" aria-hidden />
      </Button>
    </header>
  );
}
