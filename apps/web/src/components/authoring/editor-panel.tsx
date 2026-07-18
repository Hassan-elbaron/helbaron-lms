"use client";

import { MousePointerClick } from "lucide-react";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import { CourseEditor } from "./editors/course-editor";
import { LessonEditor } from "./editors/lesson-editor";
import { SectionEditor } from "./editors/section-editor";

/** Center pane — renders the editor for whatever is selected in the curriculum tree. */
export function EditorPanel() {
  const { t } = useAuthoringI18n();
  const { selection } = useBuilder();

  if (selection.kind === "course") return <CourseEditor />;
  if (selection.kind === "section") return <SectionEditor key={selection.sectionId} sectionId={selection.sectionId} />;
  if (selection.kind === "lesson") {
    return <LessonEditor key={selection.blockId} sectionId={selection.sectionId} blockId={selection.blockId} />;
  }

  return (
    <div className="flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
      <MousePointerClick className="size-10 text-muted-foreground/40" aria-hidden />
      <div>
        <p className="text-base font-medium">{t("editor.empty.title")}</p>
        <p className="mt-1 text-sm text-muted-foreground">{t("editor.empty.desc")}</p>
      </div>
    </div>
  );
}
