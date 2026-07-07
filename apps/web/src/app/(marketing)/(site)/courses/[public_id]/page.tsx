"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { ArrowLeft, GraduationCap, Languages, Star } from "lucide-react";
import { errorMessage } from "@/lib/api/errors";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useCourse, useEnroll } from "@/lib/catalog/hooks";
import { QueryState } from "@/components/student/query-state";
import { CourseCard } from "@/components/catalog/course-card";
import { CourseMedia } from "@/components/catalog/course-media";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { toast } from "@/components/ui/toast";

export default function CourseDetailsPage() {
  const { t } = useI18n();
  const params = useParams<{ public_id: string }>();
  const publicId = params.public_id;
  const { status } = useAuth();
  const query = useCourse(publicId);
  const enroll = useEnroll();

  const onEnroll = () =>
    enroll.mutate(publicId, {
      onSuccess: () => toast.success(t("catalog.course.enrolled")),
      onError: (e) => toast.error(errorMessage(e, t("common.error"))),
    });

  return (
    <div>
      <Button asChild variant="ghost" size="sm" className="mb-4">
        <Link href="/courses">
          <ArrowLeft className="size-4 rtl:rotate-180" aria-hidden /> {t("catalog.course.back")}
        </Link>
      </Button>

      <QueryState query={query}>
        {(course) => (
          <div className="grid gap-8 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
              <div>
                <div className="flex flex-wrap items-center gap-2">
                  <h1 className="text-3xl font-bold tracking-tight">{course.title}</h1>
                  {course.is_featured ? (
                    <Badge variant="warning" className="gap-1">
                      <Star className="size-3" aria-hidden /> {t("catalog.course.featured")}
                    </Badge>
                  ) : null}
                </div>
                {course.subtitle ? <p className="mt-2 text-lg text-muted-foreground">{course.subtitle}</p> : null}
              </div>

              <div className="flex flex-wrap gap-2">
                {course.level ? (
                  <Badge variant="secondary" className="gap-1">
                    <GraduationCap className="size-3" aria-hidden /> {course.level.name}
                  </Badge>
                ) : null}
                {course.language ? (
                  <Badge variant="outline" className="gap-1">
                    <Languages className="size-3" aria-hidden /> {course.language.name}
                  </Badge>
                ) : null}
                {course.categories.map((c) => <Badge key={c.id} variant="secondary">{c.name}</Badge>)}
              </div>

              {course.description ? (
                <div>
                  <h2 className="mb-2 text-xl font-semibold">{t("catalog.course.about")}</h2>
                  <p className="whitespace-pre-line text-muted-foreground">{course.description}</p>
                </div>
              ) : null}

              {course.trainers.length > 0 ? (
                <div>
                  <h2 className="mb-3 text-xl font-semibold">{t("catalog.course.trainers")}</h2>
                  <div className="flex flex-wrap gap-4">
                    {course.trainers.map((tr) => (
                      <div key={tr.id} className="flex items-center gap-3">
                        <Avatar>
                          <AvatarFallback>{tr.name.split(" ").map((p) => p[0]).slice(0, 2).join("").toUpperCase()}</AvatarFallback>
                        </Avatar>
                        <div>
                          <p className="font-medium">{tr.name}</p>
                          {tr.headline ? <p className="text-xs text-muted-foreground">{tr.headline}</p> : null}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}

              {course.tags.length > 0 ? (
                <div>
                  <h2 className="mb-2 text-sm font-semibold text-muted-foreground">{t("catalog.course.tags")}</h2>
                  <div className="flex flex-wrap gap-1.5">
                    {course.tags.map((tag) => <Badge key={tag.id} variant="outline">{tag.name}</Badge>)}
                  </div>
                </div>
              ) : null}
            </div>

            <aside className="lg:col-span-1">
              <Card className="sticky top-20 overflow-hidden">
                <CourseMedia title={course.title} />
                <CardContent className="space-y-4 p-5">
                  {status === "authenticated" ? (
                    <Button className="w-full" loading={enroll.isPending} onClick={onEnroll}>
                      {t("catalog.course.enroll")}
                    </Button>
                  ) : (
                    <Button asChild className="w-full">
                      <Link href={`/login?redirect=/courses/${course.id}`}>{t("catalog.course.signInToEnroll")}</Link>
                    </Button>
                  )}
                </CardContent>
              </Card>
            </aside>

            {course.related.length > 0 ? (
              <div className="lg:col-span-3">
                <Separator className="my-4" />
                <h2 className="mb-4 text-xl font-semibold">{t("catalog.course.related")}</h2>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  {course.related.map((c) => <CourseCard key={c.id} course={c} />)}
                </div>
              </div>
            ) : null}
          </div>
        )}
      </QueryState>
    </div>
  );
}
