"use client";

import Link from "next/link";
import { Award, BookOpen, Gauge, PlayCircle } from "lucide-react";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useContinueLearning, useMyCertificates, useMyLearning, useNotifications } from "@/lib/student/hooks";
import { PageHeader } from "@/components/student/page-header";
import { StatCard } from "@/components/student/stat-card";
import { ProgressBar } from "@/components/student/progress-bar";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";

function avg(nums: number[]): number {
  return nums.length === 0 ? 0 : Math.round(nums.reduce((a, b) => a + b, 0) / nums.length);
}

export default function DashboardPage() {
  const { t } = useI18n();
  const { user } = useAuth();
  const learning = useMyLearning();
  const resume = useContinueLearning();
  const certs = useMyCertificates();
  const notifications = useNotifications(1);

  const courses = learning.data ?? [];
  const resumeItems = resume.data ?? [];
  const recent = (notifications.data?.data ?? []).slice(0, 4);

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="OVERVIEW" icon="LayoutDashboard"
        title={`${t("student.dashboard.welcome")}${user?.name ? `, ${user.name}` : ""}`}
        subtitle={t("student.dashboard.welcomeSub")}
      />

      <div className="stagger-in grid gap-4 sm:grid-cols-3">
        {learning.isPending ? (
          <>
            <Skeleton className="h-24" />
            <Skeleton className="h-24" />
            <Skeleton className="h-24" />
          </>
        ) : (
          <>
            <StatCard label={t("student.dashboard.myCourses")} value={courses.length} icon={BookOpen} />
            <StatCard label={t("student.dashboard.avgProgress")} value={`${avg(courses.map((c) => c.progress_percentage))}%`} icon={Gauge} />
            <StatCard label={t("student.dashboard.certificates")} value={certs.data?.length ?? 0} icon={Award} />
          </>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>{t("student.dashboard.continueLearning")}</CardTitle>
            <Button asChild variant="ghost" size="sm">
              <Link href="/my-learning">{t("student.viewAll")}</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-4">
            {resume.isPending ? (
              <Skeleton className="h-16" />
            ) : resumeItems.length === 0 ? (
              <p className="text-sm text-muted-foreground">{t("student.dashboard.nothingToResume")}</p>
            ) : (
              resumeItems.slice(0, 3).map((it) => (
                <div key={it.course.id} className="flex items-center gap-3">
                  <div className="min-w-0 flex-1 space-y-1">
                    <p className="truncate text-sm font-medium">{it.course.title}</p>
                    <ProgressBar value={it.progress_percentage} />
                  </div>
                  <Button asChild size="sm" variant="outline">
                    <Link href={`/learn/${it.course.id}`}>
                      <PlayCircle className="size-4" aria-hidden /> {t("student.resume")}
                    </Link>
                  </Button>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>{t("student.dashboard.recentNotifications")}</CardTitle>
            <Button asChild variant="ghost" size="sm">
              <Link href="/notifications">{t("student.viewAll")}</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-3">
            {notifications.isPending ? (
              <Skeleton className="h-16" />
            ) : recent.length === 0 ? (
              <p className="text-sm text-muted-foreground">{t("student.notifications.empty")}</p>
            ) : (
              recent.map((n) => (
                <div key={n.id} className="flex items-start gap-2">
                  {!n.read ? <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" aria-hidden /> : <span className="mt-1.5 size-2 shrink-0" />}
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium">{n.title}</p>
                    <p className="line-clamp-1 text-xs text-muted-foreground">{n.body}</p>
                  </div>
                  <Badge variant="outline" className="ms-auto shrink-0">{n.category}</Badge>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
