"use client";

import { useState } from "react";
import Link from "next/link";
import { Presentation } from "lucide-react";
import { errorMessage } from "@/lib/api/errors";
import { useI18n } from "@/lib/i18n/i18n-context";
import {
  useArchiveCourse,
  usePublishCourse,
  useTeachCourses,
  useUnpublishCourse,
} from "@/lib/teach/hooks";
import type { CourseStatus, TeachCourse } from "@/lib/teach/api";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "@/components/ui/toast";

type Tab = "all" | CourseStatus;

const STATUS_VARIANT: Record<string, "success" | "secondary" | "outline"> = {
  published: "success",
  draft: "secondary",
  archived: "outline",
};

export default function TeachCoursesPage() {
  const { t } = useI18n();
  const [tab, setTab] = useState<Tab>("all");
  const query = useTeachCourses(tab === "all" ? undefined : tab);

  const publish = usePublishCourse();
  const unpublish = useUnpublishCourse();
  const archive = useArchiveCourse();
  const [archiveId, setArchiveId] = useState<string | null>(null);

  const run = (
    mutation: ReturnType<typeof usePublishCourse>,
    id: string,
    successKey: string,
  ) =>
    mutation.mutate(id, {
      onSuccess: () => toast.success(t(successKey)),
      onError: (e) => toast.error(errorMessage(e, t("common.error"))),
    });

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="INSTRUCTOR"
        icon="GraduationCap"
        title={t("teach.courses.title")}
        subtitle={t("teach.courses.subtitle")}
      />

      <Tabs value={tab} onValueChange={(v) => setTab(v as Tab)}>
        <TabsList>
          <TabsTrigger value="all">{t("teach.courses.all")}</TabsTrigger>
          <TabsTrigger value="draft">{t("teach.courses.draft")}</TabsTrigger>
          <TabsTrigger value="published">{t("teach.courses.published")}</TabsTrigger>
          <TabsTrigger value="archived">{t("teach.courses.archived")}</TabsTrigger>
        </TabsList>
      </Tabs>

      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<Presentation className="size-8" />} title={t("teach.courses.empty")} />}
      >
        {(courses) => (
          <div className="grid gap-4 lg:grid-cols-2">
            {courses.map((course: TeachCourse) => {
              const s = course.stats;
              return (
                <Card key={course.id} className="flex flex-col">
                  <CardContent className="flex flex-1 flex-col gap-4 p-5">
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <h3 className="line-clamp-2 font-semibold leading-tight">{course.title}</h3>
                        {course.subtitle ? (
                          <p className="mt-0.5 line-clamp-1 text-xs text-muted-foreground">{course.subtitle}</p>
                        ) : null}
                      </div>
                      <Badge variant={STATUS_VARIANT[course.status] ?? "secondary"}>
                        {t(`teach.courses.${course.status}`)}
                      </Badge>
                    </div>

                    {s ? (
                      <div className="flex flex-wrap gap-2 text-xs">
                        <Badge variant="secondary">{t("teach.courses.enrollments")}: {s.enrollments}</Badge>
                        <Badge variant="secondary">{t("teach.courses.completions")}: {s.completions}</Badge>
                        <Badge variant="secondary">{t("teach.courses.avgProgress")}: {s.avg_progress}%</Badge>
                        <Badge variant="outline">{t("teach.courses.sections")}: {s.sections}</Badge>
                        <Badge variant="outline">{t("teach.courses.lessons")}: {s.lessons}</Badge>
                      </div>
                    ) : null}

                    <div className="mt-auto flex flex-wrap gap-2">
                      <Button asChild size="sm" variant="outline">
                        <Link href={`/teach/courses/${course.id}`}>{t("teach.courses.view")}</Link>
                      </Button>
                      {course.status === "draft" ? (
                        <Button
                          size="sm"
                          loading={publish.isPending && publish.variables === course.id}
                          onClick={() => run(publish, course.id, "teach.courses.publishedToast")}
                        >
                          {t("teach.courses.publish")}
                        </Button>
                      ) : null}
                      {course.status === "published" ? (
                        <Button
                          size="sm"
                          variant="secondary"
                          loading={unpublish.isPending && unpublish.variables === course.id}
                          onClick={() => run(unpublish, course.id, "teach.courses.unpublishedToast")}
                        >
                          {t("teach.courses.unpublish")}
                        </Button>
                      ) : null}
                      {course.status !== "archived" ? (
                        <Button
                          size="sm"
                          variant="outline"
                          loading={archive.isPending && archive.variables === course.id}
                          onClick={() => setArchiveId(course.id)}
                        >
                          {t("teach.courses.archive")}
                        </Button>
                      ) : null}
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        )}
      </QueryState>

      <ConfirmDialog
        open={archiveId !== null}
        onOpenChange={(open) => {
          if (!open) setArchiveId(null);
        }}
        title={t("teach.courses.archiveConfirmTitle")}
        description={t("teach.courses.archiveConfirmBody")}
        confirmLabel={t("teach.courses.archive")}
        loading={archive.isPending}
        onConfirm={() => {
          if (!archiveId) return;
          archive.mutate(archiveId, {
            onSuccess: () => {
              toast.success(t("teach.courses.archivedToast"));
              setArchiveId(null);
            },
            onError: (e) => {
              toast.error(errorMessage(e, t("common.error")));
              setArchiveId(null);
            },
          });
        }}
      />
    </div>
  );
}
