"use client";

import Link from "next/link";
import { PlayCircle } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useContinueLearning } from "@/lib/student/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { ProgressBar } from "@/components/student/progress-bar";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/states/empty-state";

export default function ContinueLearningPage() {
  const { t } = useI18n();
  const query = useContinueLearning();

  return (
    <div>
      <PageHeader eyebrow="RESUME" icon="PlayCircle" title={t("student.continuePage.title")} subtitle={t("student.continuePage.subtitle")} />
      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState title={t("student.continuePage.empty")} />}
      >
        {(items) => (
          <div className="space-y-4">
            {items.map((it) => (
              <Card key={it.course.id}>
                <CardContent className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                  <div className="min-w-0 flex-1 space-y-2">
                    <h3 className="font-semibold">{it.course.title}</h3>
                    {it.next_lesson ? (
                      <p className="flex items-center gap-2 text-sm text-muted-foreground">
                        <PlayCircle className="size-4" aria-hidden />
                        <span className="truncate">{t("student.continuePage.nextLesson")}: {it.next_lesson.title}</span>
                        <Badge variant="outline">{it.next_lesson.type}</Badge>
                      </p>
                    ) : null}
                    <div className="flex items-center gap-3">
                      <ProgressBar value={it.progress_percentage} className="max-w-xs" />
                      <span className="text-xs tabular-nums text-muted-foreground">{Math.round(it.progress_percentage)}%</span>
                    </div>
                  </div>
                  <Button asChild className="sm:w-40">
                    <Link href={`/learn/${it.course.id}`}>{t("student.resume")}</Link>
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </QueryState>
    </div>
  );
}
