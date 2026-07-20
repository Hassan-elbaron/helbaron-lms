"use client";

import { useState } from "react";
import Link from "next/link";
import { AlertTriangle, CheckCircle2, Info, Loader2, RefreshCw } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Progress } from "@/components/ui/progress";
import { Spinner } from "@/components/ui/spinner";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import {
  useArchiveCourse,
  useCourseReadiness,
  usePublishCourse,
  useTeachCourse,
  useUnpublishCourse,
} from "@/lib/teach/hooks";
import type { ReadinessIssue } from "@/lib/teach/api";

/**
 * Publish readiness and lifecycle actions for a course.
 *
 * AUTHORITY: every verdict shown here is the server's. `is_publishable` is read from the response,
 * never recomputed from the issue list — the backend derives its own publish guard from the same
 * evaluation, and a second rule set in the client is exactly how a panel starts lying to authors.
 *
 * Actions are never optimistic. A course is shown as published only after the server says so,
 * because "published" is a claim about what learners can see and being wrong about it briefly is
 * worse than a moment of latency.
 */
export function PublishReadinessPanel({
  courseId,
  open,
  onOpenChange,
}: {
  courseId: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const { t } = useAuthoringI18n();
  // Only fetched while the dialog is open: readiness is a several-query evaluation on the server,
  // and the builder should not pay for it on every mount.
  const readiness = useCourseReadiness(courseId, open);
  const course = useTeachCourse(courseId);

  const publish = usePublishCourse();
  const unpublish = useUnpublishCourse();
  const archive = useArchiveCourse();

  const [confirming, setConfirming] = useState<null | "publish" | "unpublish" | "archive">(null);

  const status = course.data?.status;
  const report = readiness.data;
  const busy = publish.isPending || unpublish.isPending || archive.isPending;

  // Surfaced verbatim: a guard refusal explains something the author must fix, and paraphrasing it
  // into a generic "could not publish" throws away the only useful part.
  const actionError =
    publish.error instanceof Error
      ? publish.error.message
      : unpublish.error instanceof Error
        ? unpublish.error.message
        : archive.error instanceof Error
          ? archive.error.message
          : null;

  function run(action: "publish" | "unpublish" | "archive") {
    setConfirming(null);
    const mutation = action === "publish" ? publish : action === "unpublish" ? unpublish : archive;
    mutation.mutate(courseId, { onSuccess: () => void readiness.refetch() });
  }

  return (
    <>
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{t("publish.title")}</DialogTitle>
        </DialogHeader>

        {readiness.isPending ? (
          <div className="flex min-h-[12rem] items-center justify-center" role="status" aria-live="polite">
            <Spinner />
          </div>
        ) : readiness.isError || !report ? (
          <div className="flex min-h-[12rem] flex-col items-center justify-center gap-3 text-center">
            <AlertTriangle className="size-8 text-destructive" aria-hidden />
            <p className="text-sm text-muted-foreground">{t("publish.loadError")}</p>
            <Button variant="outline" onClick={() => void readiness.refetch()}>
              {t("builder.retry")}
            </Button>
          </div>
        ) : (
          <div className="space-y-5">
            <section className="space-y-2" aria-labelledby="readiness-score">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 id="readiness-score" className="text-sm font-semibold">
                  {t("publish.readiness")}
                </h3>
                <span className="text-sm tabular-nums text-muted-foreground">
                  {t("publish.scoreValue", { score: report.score })}
                </span>
              </div>
              <Progress value={report.score} />
              <p className="text-xs text-muted-foreground">
                {report.is_publishable ? t("publish.ready") : t("publish.notReady")}
              </p>
            </section>

            <IssueList
              titleId="readiness-blockers"
              heading={t("publish.blockers")}
              emptyLabel={t("publish.noBlockers")}
              issues={report.blockers}
              tone="destructive"
              courseId={courseId}
            />

            <IssueList
              titleId="readiness-warnings"
              heading={t("publish.warnings")}
              emptyLabel={t("publish.noWarnings")}
              issues={report.warnings}
              tone="warning"
              courseId={courseId}
            />

            {report.passed_checks.length > 0 ? (
              <section aria-labelledby="readiness-passed" className="space-y-2">
                <h3 id="readiness-passed" className="text-sm font-semibold">
                  {t("publish.passed")}
                </h3>
                <ul className="space-y-1">
                  {report.passed_checks.map((code) => (
                    <li key={code} className="flex items-center gap-2 text-sm text-muted-foreground">
                      <CheckCircle2 className="size-4 shrink-0 text-success" aria-hidden />
                      {t(`publish.check.${code}`)}
                    </li>
                  ))}
                </ul>
              </section>
            ) : null}

            <footer className="space-y-3 border-t border-border pt-4">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-xs text-muted-foreground">
                  {t("publish.evaluatedAt", { at: new Date(report.evaluated_at).toLocaleTimeString() })}
                </span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => void readiness.refetch()}
                  disabled={readiness.isFetching}
                >
                  <RefreshCw className={readiness.isFetching ? "size-4 animate-spin" : "size-4"} aria-hidden />
                  {t("publish.revalidate")}
                </Button>
              </div>

              {actionError ? (
                <p role="alert" className="text-sm text-destructive">
                  {actionError}
                </p>
              ) : null}

              <div className="flex flex-wrap gap-2">
                {status !== "published" ? (
                  <Button
                    onClick={() => setConfirming("publish")}
                    // Disabled on the SERVER's verdict. The backend refuses regardless; this only
                    // spares the author a pointless round trip.
                    disabled={!report.is_publishable || busy}
                  >
                    {publish.isPending ? <Loader2 className="size-4 animate-spin" aria-hidden /> : null}
                    {t("publish.publish")}
                  </Button>
                ) : (
                  <Button variant="outline" onClick={() => setConfirming("unpublish")} disabled={busy}>
                    {t("publish.unpublish")}
                  </Button>
                )}

                {status !== "archived" ? (
                  <Button variant="outline" onClick={() => setConfirming("archive")} disabled={busy}>
                    {t("publish.archive")}
                  </Button>
                ) : null}
              </div>
            </footer>
          </div>
        )}

      </DialogContent>
    </Dialog>

    {/* Sibling, not nested: two Radix dialogs stacked inside one another fight over the focus
        trap, and the inner one loses its restore target when the outer closes. */}
    <ConfirmDialog
      open={confirming !== null}
      onOpenChange={(next) => {
        if (!next) setConfirming(null);
      }}
      title={confirming ? t(`publish.confirm.${confirming}.title`) : ""}
      description={confirming ? t(`publish.confirm.${confirming}.body`) : ""}
      confirmLabel={confirming ? t(`publish.${confirming}`) : ""}
      // Publishing is not destructive; unpublish and archive take things away from learners.
      confirmVariant={confirming === "publish" ? "default" : "destructive"}
      loading={busy}
      onConfirm={() => {
        if (confirming) run(confirming);
      }}
    />
    </>
  );
}

function IssueList({
  titleId,
  heading,
  emptyLabel,
  issues,
  tone,
  courseId,
}: {
  titleId: string;
  heading: string;
  emptyLabel: string;
  issues: ReadinessIssue[];
  tone: "destructive" | "warning";
  courseId: string;
}) {
  const { t } = useAuthoringI18n();

  return (
    <section aria-labelledby={titleId} className="space-y-2">
      <div className="flex items-center gap-2">
        <h3 id={titleId} className="text-sm font-semibold">
          {heading}
        </h3>
        {issues.length > 0 ? <Badge variant="outline">{issues.length}</Badge> : null}
      </div>

      {issues.length === 0 ? (
        <p className="flex items-center gap-2 text-sm text-muted-foreground">
          <CheckCircle2 className="size-4 shrink-0 text-success" aria-hidden />
          {emptyLabel}
        </p>
      ) : (
        <ul className="space-y-2">
          {issues.map((issue, i) => (
            <li
              key={`${issue.code}-${issue.entity_id ?? i}`}
              className="rounded-md border border-border p-3"
            >
              <div className="flex items-start gap-2">
                {tone === "destructive" ? (
                  <AlertTriangle className="mt-0.5 size-4 shrink-0 text-destructive" aria-hidden />
                ) : (
                  <Info className="mt-0.5 size-4 shrink-0 text-warning" aria-hidden />
                )}
                <div className="min-w-0 flex-1 space-y-1">
                  <p className="text-sm font-medium">{issue.title}</p>
                  <p className="text-sm text-muted-foreground">{issue.explanation}</p>
                  <p className="text-sm">{issue.recommended_action}</p>

                  {/* Only lessons get a deep link: the builder routes to a lesson, while course-level
                      issues are fixed in settings, which is where the author already is. */}
                  {issue.entity_type === "lesson" && issue.entity_id ? (
                    <Button asChild variant="link" size="sm" className="h-auto p-0">
                      <Link href={`/teach/courses/${courseId}/edit?lesson=${issue.entity_id}`}>
                        {t("publish.openLesson")}
                      </Link>
                    </Button>
                  ) : null}
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
