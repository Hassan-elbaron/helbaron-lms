import Link from "next/link";
import { GraduationCap } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ProgressBar } from "./progress-bar";

export interface CourseProgressCardProps {
  title: string;
  progress: number;
  status?: string;
  continueHref?: string;
  continueLabel?: string;
  subtitle?: string;
}

export function CourseProgressCard({ title, progress, status, continueHref = "/continue-learning", continueLabel, subtitle }: CourseProgressCardProps) {
  const { t } = useI18n();
  const pct = Math.round(progress);
  return (
    <Card className="flex flex-col">
      <CardContent className="flex flex-1 flex-col gap-4 p-5">
        <div className="flex items-start gap-3">
          <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <GraduationCap className="size-5" aria-hidden />
          </div>
          <div className="min-w-0 flex-1">
            <h3 className="line-clamp-2 font-semibold leading-tight">{title}</h3>
            {subtitle ? <p className="mt-0.5 truncate text-xs text-muted-foreground">{subtitle}</p> : null}
          </div>
          {status ? <Badge variant={pct >= 100 ? "success" : "secondary"}>{status}</Badge> : null}
        </div>
        <div className="mt-auto space-y-2">
          <div className="flex items-center justify-between text-xs text-muted-foreground">
            <span>{t("student.progress")}</span>
            <span className="tabular-nums">{pct}%</span>
          </div>
          <ProgressBar value={pct} />
        </div>
        <Button asChild className="w-full" variant={pct >= 100 ? "outline" : "default"}>
          <Link href={continueHref}>{continueLabel ?? t("student.continue")}</Link>
        </Button>
      </CardContent>
    </Card>
  );
}
