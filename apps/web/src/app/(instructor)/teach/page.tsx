"use client";

import Link from "next/link";
import { BookOpen, GraduationCap, CheckCircle2, Presentation, Users } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useTeachDashboard } from "@/lib/teach/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { StatCard } from "@/components/student/stat-card";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export default function TeachDashboardPage() {
  const { t } = useI18n();
  const query = useTeachDashboard();

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="INSTRUCTOR"
        icon="LayoutDashboard"
        title={t("teach.dashboard.title")}
        subtitle={t("teach.dashboard.subtitle")}
        action={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
              <Link href="/teach/courses">{t("nav.teachCourses")}</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href="/teach/students">{t("nav.teachStudents")}</Link>
            </Button>
          </div>
        }
      />

      <QueryState query={query} isEmpty={(d) => d.courses.total === 0} empty={<EmptyState title={t("teach.dashboard.empty")} />}>
        {(d) => (
          <div className="space-y-6">
            <div className="stagger-in grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <StatCard label={t("teach.dashboard.courses")} value={d.courses.total} icon={BookOpen} />
              <StatCard label={t("teach.dashboard.students")} value={d.students} icon={Users} />
              <StatCard label={t("teach.dashboard.completions")} value={d.completions} icon={CheckCircle2} />
            </div>

            <div className="flex flex-wrap gap-2" aria-label={t("teach.dashboard.courses")}>
              <Badge variant="success">{t("teach.dashboard.published")}: {d.courses.published}</Badge>
              <Badge variant="secondary">{t("teach.dashboard.drafts")}: {d.courses.draft}</Badge>
              <Badge variant="outline">{t("teach.dashboard.archived")}: {d.courses.archived}</Badge>
            </div>

            <Card>
              <CardContent className="p-5">
                <div className="mb-4 flex items-center gap-2">
                  <GraduationCap className="size-5 text-primary" aria-hidden />
                  <h2 className="font-serif text-lg font-semibold">{t("teach.dashboard.recent")}</h2>
                </div>
                {d.recent_enrollments.length === 0 ? (
                  <p className="text-sm text-muted-foreground">{t("teach.dashboard.recentEmpty")}</p>
                ) : (
                  <ul className="divide-y">
                    {d.recent_enrollments.map((e, i) => (
                      <li key={`${e.course.id}-${i}`} className="flex flex-wrap items-center justify-between gap-2 py-3">
                        <div className="min-w-0">
                          <p className="truncate font-medium">{e.student.name ?? "—"}</p>
                          <Link href={`/teach/courses/${e.course.id}`} className="truncate text-sm text-primary hover:underline">
                            {e.course.title}
                          </Link>
                        </div>
                        <div className="flex items-center gap-3">
                          <Badge variant={e.status === "completed" ? "success" : "secondary"}>{e.status}</Badge>
                          <span className="text-xs tabular-nums text-muted-foreground">
                            {e.enrolled_at ? new Date(e.enrolled_at).toLocaleDateString() : ""}
                          </span>
                        </div>
                      </li>
                    ))}
                  </ul>
                )}
              </CardContent>
            </Card>

            <div className="flex flex-wrap gap-3">
              <Button asChild variant="outline">
                <Link href="/teach/courses">
                  <Presentation className="size-4" aria-hidden /> {t("nav.teachCourses")}
                </Link>
              </Button>
              <Button asChild variant="outline">
                <Link href="/teach/students">
                  <Users className="size-4" aria-hidden /> {t("nav.teachStudents")}
                </Link>
              </Button>
            </div>
          </div>
        )}
      </QueryState>
    </div>
  );
}
