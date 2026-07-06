"use client";

import Link from "next/link";
import { CheckCircle2, Circle, Lock, PlayCircle } from "lucide-react";
import type { LearnSection } from "@/lib/learning/api";
import { useI18n } from "@/lib/i18n/i18n-context";
import { cn } from "@/lib/utils";

export function CurriculumSidebar({ sections, activeLessonId }: { sections: LearnSection[]; activeLessonId?: string }) {
  const { t } = useI18n();
  return (
    <nav className="space-y-4" aria-label={t("learn.curriculum")}>
      {sections.map((section) => (
        <div key={section.id}>
          <h3 className="mb-1 px-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{section.title}</h3>
          <ul className="space-y-0.5">
            {section.lessons.map((lesson) => {
              const active = lesson.id === activeLessonId;
              const Icon = lesson.completed ? CheckCircle2 : lesson.locked ? Lock : active ? PlayCircle : Circle;
              const body = (
                <span
                  className={cn(
                    "flex items-center gap-2 rounded-md px-2 py-2 text-sm",
                    active && "bg-accent text-accent-foreground",
                    lesson.locked ? "text-muted-foreground" : "hover:bg-accent hover:text-accent-foreground",
                    lesson.completed && !active && "text-muted-foreground",
                  )}
                >
                  <Icon className={cn("size-4 shrink-0", lesson.completed && "text-success")} aria-hidden />
                  <span className="line-clamp-1 flex-1">{lesson.title}</span>
                  {lesson.is_preview && lesson.locked ? (
                    <span className="text-[10px] uppercase text-muted-foreground">{t("learn.preview")}</span>
                  ) : null}
                </span>
              );
              return (
                <li key={lesson.id}>
                  {lesson.locked ? (
                    <div title={t("learn.locked")} aria-disabled className="cursor-not-allowed opacity-70">{body}</div>
                  ) : (
                    <Link href={`/lessons/${lesson.id}`}>{body}</Link>
                  )}
                </li>
              );
            })}
          </ul>
        </div>
      ))}
    </nav>
  );
}
