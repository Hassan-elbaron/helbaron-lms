"use client";

import { Download, ExternalLink, FileText } from "lucide-react";
import type { LessonPayload } from "@/lib/learning/api";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/states/empty-state";

function articleText(content: Record<string, unknown> | null): { html?: string; text?: string } {
  if (!content) return {};
  if (typeof content.html === "string") return { html: content.html };
  if (typeof content.body === "string") return { text: content.body };
  if (typeof content.text === "string") return { text: content.text };
  return { text: JSON.stringify(content, null, 2) };
}

/** Renders lesson media/content by type. Media is only ever the signed playback URL. */
export function LessonContent({
  lesson,
  videoRef,
  onVideoPause,
  onVideoLoaded,
}: {
  lesson: LessonPayload;
  videoRef?: React.RefObject<HTMLVideoElement | null>;
  onVideoPause?: (seconds: number) => void;
  onVideoLoaded?: (el: HTMLVideoElement) => void;
}) {
  const { t } = useI18n();
  const url = lesson.playback?.url;

  if (lesson.type === "video") {
    return (
      <div className="space-y-2">
        <div className="aspect-video w-full overflow-hidden rounded-lg bg-black">
          {url ? (
            // eslint-disable-next-line jsx-a11y/media-has-caption
            <video
              ref={videoRef}
              src={url}
              controls
              className="size-full"
              onLoadedMetadata={(e) => onVideoLoaded?.(e.currentTarget)}
              onPause={(e) => onVideoPause?.(Math.floor(e.currentTarget.currentTime))}
            />
          ) : (
            <div className="flex size-full items-center justify-center text-sm text-white/70">{t("learn.lesson.noContent")}</div>
          )}
        </div>
        <p className="text-xs text-muted-foreground">{t("learn.lesson.videoNote")}</p>
      </div>
    );
  }

  if (lesson.type === "pdf") {
    return url ? (
      <div className="space-y-3">
        <iframe title={lesson.title} src={url} className="h-[70vh] w-full rounded-lg border" />
        <Button asChild variant="outline">
          <a href={url} target="_blank" rel="noopener noreferrer">
            <FileText className="size-4" aria-hidden /> {t("learn.lesson.openPdf")}
          </a>
        </Button>
      </div>
    ) : (
      <EmptyState title={t("learn.lesson.noContent")} />
    );
  }

  if (lesson.type === "download") {
    return url ? (
      <Button asChild>
        <a href={url} target="_blank" rel="noopener noreferrer">
          <Download className="size-4" aria-hidden /> {t("learn.lesson.downloadFile")}
        </a>
      </Button>
    ) : (
      <EmptyState title={t("learn.lesson.noContent")} />
    );
  }

  if (lesson.type === "external_link") {
    const href = typeof lesson.content?.url === "string" ? lesson.content.url : url;
    return href ? (
      <Button asChild>
        <a href={href} target="_blank" rel="noopener noreferrer">
          <ExternalLink className="size-4" aria-hidden /> {t("learn.lesson.openLink")}
        </a>
      </Button>
    ) : (
      <EmptyState title={t("learn.lesson.noContent")} />
    );
  }

  if (lesson.type === "quiz_placeholder") {
    return <EmptyState title={t("learn.lesson.quizSoon")} />;
  }

  // article
  const { html, text } = articleText(lesson.content);
  if (html) {
    // Admin-authored trusted content. Sanitize server-side before production exposure.
    return <div className="prose max-w-none dark:prose-invert" dangerouslySetInnerHTML={{ __html: html }} />;
  }
  return text ? (
    <div className="whitespace-pre-line leading-relaxed text-foreground">{text}</div>
  ) : (
    <EmptyState title={t("learn.lesson.noContent")} />
  );
}
