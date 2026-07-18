"use client";

import { useCallback, useEffect, useState } from "react";
import { Clapperboard, Info, Sparkles } from "lucide-react";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { blockDef } from "@/lib/authoring/block-registry";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import type { Block, BlockContent } from "@/lib/authoring/types";
import { BlockIcon } from "../block-icon";
import { useFieldAutosave } from "../field-autosave";
import { StatusBadge } from "../status-badge";

function str(v: unknown): string {
  return typeof v === "string" ? v : "";
}

export function LessonEditor({ sectionId, blockId }: { sectionId: string; blockId: string }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const block = builder.curriculum?.sections.find((s) => s.id === sectionId)?.blocks.find((b) => b.id === blockId);

  const commitTitle = useCallback((v: string) => builder.renameBlock(sectionId, blockId, v), [builder, sectionId, blockId]);
  const title = useFieldAutosave(block?.title ?? "", commitTitle);

  const [content, setContent] = useState<BlockContent>(block?.content ?? {});
  const contentJson = JSON.stringify(content);
  useEffect(() => {
    if (!block) return;
    if (contentJson === JSON.stringify(block.content)) return;
    const id = setTimeout(() => void builder.setBlockContent(sectionId, blockId, JSON.parse(contentJson) as BlockContent), 700);
    return () => clearTimeout(id);
  }, [contentJson, block, builder, sectionId, blockId]);

  if (!block) return null;
  const def = blockDef(block.kind);

  return (
    <div className="mx-auto max-w-2xl space-y-6 p-6">
      <div className="flex items-center justify-between">
        <span className="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
          <BlockIcon kind={block.kind} />
          {t(def.labelKey)}
        </span>
        <StatusBadge state={block.publish_state} />
      </div>

      <FormField
        label={t("editor.block.titleLabel")}
        required
        error={title.value.trim() ? undefined : t("validation.blockTitle")}
      >
        <Input value={title.value} onChange={(e) => title.setValue(e.target.value)} onBlur={title.flush} />
      </FormField>

      {!def.supported ? (
        <UnsupportedPanel kindLabel={t(def.labelKey)} />
      ) : def.usesMedia ? (
        <MediaPanel block={block} />
      ) : block.kind === "article" ? (
        <FormField label={t("field.article.body")} hint={t("field.article.hint")}>
          <Textarea rows={12} value={str(content.html)} onChange={(e) => setContent({ ...content, html: e.target.value })} />
        </FormField>
      ) : block.kind === "external_link" ? (
        <div className="space-y-4">
          <FormField label={t("field.link.url")} required error={isUrl(str(content.url)) ? undefined : t("validation.linkUrl")}>
            <Input
              type="url"
              inputMode="url"
              value={str(content.url)}
              onChange={(e) => setContent({ ...content, url: e.target.value })}
              placeholder="https://"
            />
          </FormField>
          <FormField label={t("field.link.label")}>
            <Input value={str(content.label)} onChange={(e) => setContent({ ...content, label: e.target.value })} />
          </FormField>
        </div>
      ) : block.kind === "quiz_placeholder" ? (
        <FormField label={t("field.quiz.note")} hint={t("field.quiz.hint")}>
          <Textarea rows={5} value={str(content.note)} onChange={(e) => setContent({ ...content, note: e.target.value })} />
        </FormField>
      ) : null}
    </div>
  );
}

function isUrl(v: string): boolean {
  if (!v) return false;
  try {
    const u = new URL(v);
    return u.protocol === "http:" || u.protocol === "https:";
  } catch {
    return false;
  }
}

function MediaPanel({ block }: { block: Block }) {
  const { t } = useAuthoringI18n();
  const media = block.media;
  const summary = media?.mux_playback_id
    ? `Mux · ${media.mux_playback_id}`
    : media?.s3_key
      ? `S3 · ${media.s3_key}`
      : null;

  return (
    <div className="rounded-lg border border-dashed border-border bg-muted/30 p-5">
      <div className="flex items-center gap-2 text-sm font-medium">
        <Clapperboard className="size-4 text-muted-foreground" aria-hidden />
        {t("field.media.title")}
      </div>
      <p className="mt-2 text-sm text-muted-foreground">{summary ?? t("field.media.none")}</p>
      <p className="mt-3 flex items-start gap-1.5 text-xs text-muted-foreground">
        <Info className="mt-0.5 size-3.5 shrink-0" aria-hidden />
        {t("field.media.hint")}
      </p>
    </div>
  );
}

function UnsupportedPanel({ kindLabel }: { kindLabel: string }) {
  const { t } = useAuthoringI18n();
  return (
    <div className="rounded-lg border border-dashed border-border bg-muted/30 p-6 text-center">
      <Sparkles className="mx-auto size-6 text-muted-foreground/50" aria-hidden />
      <p className="mt-2 text-sm font-medium">{t("editor.unsupported.title")}</p>
      <p className="mt-1 text-sm text-muted-foreground">{t("editor.unsupported.desc", { kind: kindLabel })}</p>
    </div>
  );
}
