import Link from "next/link";
import { Star } from "lucide-react";
import type { CourseListItem } from "@/lib/catalog/api";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CourseMedia } from "./course-media";

export function CourseCard({ course }: { course: CourseListItem }) {
  const { t } = useI18n();
  return (
    <Link href={`/courses/${course.id}`} className="group block focus-visible:outline-none">
      <Card className="card-hover h-full overflow-hidden group-hover:border-primary/30 group-hover:elevation-4 group-focus-visible:ring-2 group-focus-visible:ring-ring">
        <CourseMedia title={course.title} />
        <CardContent className="space-y-2 p-4">
          <div className="flex items-start justify-between gap-2">
            <h3 className="line-clamp-2 font-serif font-semibold leading-tight">{course.title}</h3>
            {course.is_featured ? (
              <Badge variant="warning" className="shrink-0 gap-1">
                <Star className="size-3" aria-hidden /> {t("catalog.course.featured")}
              </Badge>
            ) : null}
          </div>
          {course.subtitle ? <p className="line-clamp-2 text-sm text-muted-foreground">{course.subtitle}</p> : null}
          <div className="flex flex-wrap gap-1.5 pt-1">
            {course.level ? <Badge variant="secondary">{course.level}</Badge> : null}
            {course.language ? <Badge variant="outline">{course.language}</Badge> : null}
          </div>
        </CardContent>
      </Card>
    </Link>
  );
}
