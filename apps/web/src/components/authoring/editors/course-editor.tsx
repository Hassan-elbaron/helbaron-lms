"use client";

import { BookOpen, GraduationCap, Layers } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import { useTeachCourse } from "@/lib/teach/hooks";

/** Course overview — the top of the curriculum. Course meta editing is a backend TODO (see api.ts). */
export function CourseEditor() {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const { data } = useTeachCourse(builder.courseId);

  const sections = builder.curriculum?.sections ?? [];
  const lessonCount = sections.reduce((n, s) => n + s.blocks.length, 0);

  return (
    <div className="mx-auto max-w-2xl p-6">
      <div className="flex items-start gap-3">
        <span className="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <GraduationCap className="size-5" aria-hidden />
        </span>
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="truncate text-xl font-semibold">{data?.title ?? t("editor.course.title")}</h1>
            {data ? (
              <Badge variant={data.status === "published" ? "success" : "secondary"}>{data.status}</Badge>
            ) : null}
          </div>
          <p className="mt-1 text-sm text-muted-foreground">{t("editor.course.desc")}</p>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-2 gap-3">
        <div className="rounded-lg border border-border bg-card p-4">
          <span className="flex items-center gap-2 text-xs text-muted-foreground">
            <Layers className="size-4" aria-hidden /> {t("node.section")}
          </span>
          <p className="mt-1 text-2xl font-semibold tabular-nums">{sections.length}</p>
        </div>
        <div className="rounded-lg border border-border bg-card p-4">
          <span className="flex items-center gap-2 text-xs text-muted-foreground">
            <BookOpen className="size-4" aria-hidden /> {t("node.lesson")}
          </span>
          <p className="mt-1 text-2xl font-semibold tabular-nums">{lessonCount}</p>
        </div>
      </div>
    </div>
  );
}
