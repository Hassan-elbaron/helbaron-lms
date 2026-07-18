"use client";

import { useMemo, useState } from "react";
import { Clapperboard, Info, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import type { Block, UpsertMediaInput } from "@/lib/authoring/types";

/**
 * Media metadata editor for video / audio / pdf / download lessons.
 *
 * It writes the real `lesson_media` row via `PUT /admin/lessons/{lesson}/media`
 * (`UpsertLessonMediaRequest`: every field nullable, duration/filesize integer ≥ 0).
 *
 * It does NOT upload files, and it never fabricates an upload result: the backend exposes no
 * direct-upload endpoint (no Mux direct-upload URL creation, no S3 presign) — see
 * `REMAINING_BACKEND` in `lib/authoring/api.ts`. Until that contract exists, an author references
 * an asset that already went through the media pipeline.
 */
interface MediaForm {
  mux_playback_id: string;
  s3_key: string;
  mime_type: string;
  duration: string;
  filesize: string;
}

function formFrom(block: Block): MediaForm {
  const m = block.media;
  return {
    mux_playback_id: m?.mux_playback_id ?? "",
    s3_key: m?.s3_key ?? "",
    mime_type: m?.mime_type ?? "",
    duration: m?.duration != null ? String(m.duration) : "",
    filesize: m?.filesize != null ? String(m.filesize) : "",
  };
}

/** "" ⇒ null (clears the column); otherwise a non-negative integer, or `undefined` when invalid. */
function toInteger(raw: string): number | null | undefined {
  const trimmed = raw.trim();
  if (trimmed === "") return null;
  if (!/^\d+$/u.test(trimmed)) return undefined;
  return Number(trimmed);
}

function blank(value: string): string | null {
  const trimmed = value.trim();
  return trimmed === "" ? null : trimmed;
}

export function MediaEditor({ sectionId, block }: { sectionId: string; block: Block }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();

  // Keyed by lesson id upstream, so remounting on lesson change resets the draft cleanly.
  const [form, setForm] = useState<MediaForm>(() => formFrom(block));
  const [saving, setSaving] = useState(false);

  const duration = toInteger(form.duration);
  const filesize = toInteger(form.filesize);
  const hasSource = form.mux_playback_id.trim() !== "" || form.s3_key.trim() !== "";
  const numbersValid = duration !== undefined && filesize !== undefined;

  const dirty = useMemo(() => {
    const saved = formFrom(block);
    return (Object.keys(saved) as (keyof MediaForm)[]).some((k) => saved[k].trim() !== form[k].trim());
  }, [block, form]);

  const canSave = dirty && hasSource && numbersValid && !saving;
  const attached = Boolean(block.media?.mux_playback_id ?? block.media?.s3_key);

  const set = (key: keyof MediaForm, value: string) => setForm((prev) => ({ ...prev, [key]: value }));

  const submit = async (payload: UpsertMediaInput) => {
    setSaving(true);
    try {
      await builder.setMedia(sectionId, block.id, payload);
    } finally {
      setSaving(false);
    }
  };

  const save = () => {
    if (!canSave || duration === undefined || filesize === undefined) return;
    void submit({
      // Preserved: the asset id is set by the media pipeline, not by the author.
      mux_asset_id: block.media?.mux_asset_id ?? null,
      mux_playback_id: blank(form.mux_playback_id),
      s3_key: blank(form.s3_key),
      mime_type: blank(form.mime_type),
      duration,
      filesize,
    });
  };

  const detach = () => {
    setForm({ mux_playback_id: "", s3_key: "", mime_type: "", duration: "", filesize: "" });
    void submit({
      mux_asset_id: null,
      mux_playback_id: null,
      s3_key: null,
      mime_type: null,
      duration: null,
      filesize: null,
    });
  };

  return (
    <section className="space-y-4 rounded-lg border border-border p-5" aria-labelledby={`media-${block.id}`}>
      <div className="flex items-center justify-between gap-3">
        <h3 id={`media-${block.id}`} className="flex items-center gap-2 text-sm font-medium">
          <Clapperboard className="size-4 text-muted-foreground" aria-hidden />
          {t("media.title")}
        </h3>
        <span className="text-xs text-muted-foreground">{attached ? t("media.attached") : t("media.none")}</span>
      </div>

      <p className="flex items-start gap-1.5 text-xs text-muted-foreground">
        <Info className="mt-0.5 size-3.5 shrink-0" aria-hidden />
        {t("media.desc")}
      </p>

      <FormField label={t("media.playbackId")} error={hasSource ? undefined : t("media.needsSource")}>
        <Input value={form.mux_playback_id} onChange={(e) => set("mux_playback_id", e.target.value)} />
      </FormField>

      <FormField label={t("media.storageKey")}>
        <Input value={form.s3_key} onChange={(e) => set("s3_key", e.target.value)} />
      </FormField>

      <FormField label={t("media.mimeType")}>
        <Input value={form.mime_type} onChange={(e) => set("mime_type", e.target.value)} placeholder="video/mp4" />
      </FormField>

      <div className="grid gap-4 sm:grid-cols-2">
        <FormField label={t("media.duration")} error={duration === undefined ? t("media.numberInvalid") : undefined}>
          <Input inputMode="numeric" value={form.duration} onChange={(e) => set("duration", e.target.value)} />
        </FormField>
        <FormField label={t("media.filesize")} error={filesize === undefined ? t("media.numberInvalid") : undefined}>
          <Input inputMode="numeric" value={form.filesize} onChange={(e) => set("filesize", e.target.value)} />
        </FormField>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <Button size="sm" disabled={!canSave} onClick={save}>
          {t("media.save")}
        </Button>
        {attached ? (
          <Button size="sm" variant="ghost" disabled={saving} onClick={detach}>
            <Trash2 className="size-4" aria-hidden />
            {t("media.remove")}
          </Button>
        ) : null}
      </div>
    </section>
  );
}
