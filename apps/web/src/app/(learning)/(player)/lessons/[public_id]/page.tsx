"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { Bookmark, Check, ChevronLeft, ChevronRight } from "lucide-react";
import { errorMessage } from "@/lib/api/errors";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useLesson, useRecordProgress, useToggleBookmark, useUpsertNote } from "@/lib/learning/hooks";
import { RequireAuth } from "@/lib/auth/guards";
import { LessonContent } from "@/components/learning/lesson-content";
import { PageHeader } from "@/components/student/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { LoadingState } from "@/components/states/loading-state";
import { ErrorState } from "@/components/states/error-state";
import { toast } from "@/components/ui/toast";
import { cn } from "@/lib/utils";

function LessonInner() {
  const { t, dir } = useI18n();
  const params = useParams<{ public_id: string }>();
  const lessonId = params.public_id;

  const query = useLesson(lessonId);
  const progress = useRecordProgress(lessonId);
  const bookmark = useToggleBookmark(lessonId);
  const note = useUpsertNote(lessonId);

  const videoRef = useRef<HTMLVideoElement | null>(null);
  const startedRef = useRef(false);
  const [noteText, setNoteText] = useState("");

  const data = query.data;

  // Mark as started once, when a not-started lesson loads.
  useEffect(() => {
    if (data && !startedRef.current && data.progress.status === "not_started") {
      startedRef.current = true;
      progress.mutate({ status: "in_progress" });
    }
  }, [data, progress]);

  // Sync the note editor with the loaded note.
  useEffect(() => {
    if (data) setNoteText(data.note ?? "");
  }, [data]);

  if (query.isPending) return <LoadingState />;
  if (query.isError) return <ErrorState message={errorMessage(query.error, t("common.error"))} onRetry={() => query.refetch()} />;

  const lesson = data!;
  const done = lesson.progress.status === "completed";
  const Prev = dir === "rtl" ? ChevronRight : ChevronLeft;
  const Next = dir === "rtl" ? ChevronLeft : ChevronRight;

  const markComplete = () =>
    progress.mutate(
      { status: "completed" },
      {
        onSuccess: (res) => toast.success(`${t("learn.lesson.progressSaved")} (${Math.round(res.data.course_progress_percentage)}%)`),
        onError: (e) => toast.error(errorMessage(e, t("common.error"))),
      },
    );

  return (
    <div className="space-y-6">
      <PageHeader
        title={lesson.title}
        action={
          <div className="flex items-center gap-2">
            <Badge variant={done ? "success" : "secondary"}>{done ? t("learn.lesson.completed") : t("learn.lesson.started")}</Badge>
            <Button
              variant="ghost"
              size="icon"
              aria-label={t("learn.lesson.bookmark")}
              loading={bookmark.isPending}
              onClick={() => bookmark.mutate()}
            >
              <Bookmark className={cn("size-5", lesson.bookmarked && "fill-current text-primary")} aria-hidden />
            </Button>
          </div>
        }
      />

      <LessonContent
        lesson={lesson}
        videoRef={videoRef}
        onVideoLoaded={(el) => {
          if (lesson.progress.position_seconds) el.currentTime = lesson.progress.position_seconds;
        }}
        onVideoPause={(seconds) => progress.mutate({ status: "in_progress", position_seconds: seconds })}
      />

      <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
        <div className="flex gap-2">
          {lesson.navigation.previous ? (
            <Button asChild variant="outline">
              <Link href={`/lessons/${lesson.navigation.previous}`}>
                <Prev className="size-4" aria-hidden /> {t("learn.lesson.previous")}
              </Link>
            </Button>
          ) : null}
          {lesson.navigation.next ? (
            <Button asChild variant="outline">
              <Link href={`/lessons/${lesson.navigation.next}`}>
                {t("learn.lesson.next")} <Next className="size-4" aria-hidden />
              </Link>
            </Button>
          ) : null}
        </div>
        <Button onClick={markComplete} loading={progress.isPending} disabled={done} variant={done ? "outline" : "default"}>
          <Check className="size-4" aria-hidden /> {done ? t("learn.lesson.completed") : t("learn.lesson.markComplete")}
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">{t("learn.lesson.notes")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <textarea
            rows={4}
            value={noteText}
            onChange={(e) => setNoteText(e.target.value)}
            placeholder={t("learn.lesson.notePlaceholder")}
            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
          <Button
            size="sm"
            loading={note.isPending}
            disabled={!noteText.trim()}
            onClick={() =>
              note.mutate(noteText.trim(), {
                onSuccess: () => toast.success(t("learn.lesson.noteSaved")),
                onError: (e) => toast.error(errorMessage(e, t("common.error"))),
              })
            }
          >
            {t("learn.lesson.saveNote")}
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}

export default function LessonPage() {
  return (
    <RequireAuth>
      <LessonInner />
    </RequireAuth>
  );
}
