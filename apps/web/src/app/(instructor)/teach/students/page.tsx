"use client";

import Link from "next/link";
import { Users } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useTeachCourses } from "@/lib/teach/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export default function TeachStudentsPage() {
  const { t } = useI18n();
  const query = useTeachCourses();

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="INSTRUCTOR"
        icon="GraduationCap"
        title={t("teach.students.title")}
        subtitle={t("teach.students.subtitle")}
      />

      <p className="text-sm text-muted-foreground">{t("teach.students.perCourse")}</p>

      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<Users className="size-8" />} title={t("teach.students.empty")} />}
      >
        {(courses) => (
          <div className="stagger-in grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {courses.map((course) => (
              <Card key={course.id} className="flex flex-col">
                <CardContent className="flex flex-1 flex-col gap-3 p-5">
                  <div className="flex items-start gap-3">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                      <Users className="size-5" aria-hidden />
                    </div>
                    <h3 className="line-clamp-2 font-semibold leading-tight">{course.title}</h3>
                  </div>
                  <div className="flex flex-wrap gap-2 text-xs">
                    <Badge variant="secondary">
                      {course.stats?.enrollments ?? 0} {t("teach.students.enrolled")}
                    </Badge>
                    <Badge variant="outline">
                      {t("teach.courses.completions")}: {course.stats?.completions ?? 0}
                    </Badge>
                  </div>
                  <div className="mt-auto">
                    <Button asChild size="sm" variant="outline" className="w-full">
                      <Link href={`/teach/courses/${course.id}`}>{t("teach.course.students")}</Link>
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </QueryState>
    </div>
  );
}
