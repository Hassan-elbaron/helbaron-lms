"use client";

import { useCallback } from "react";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import { useFieldAutosave } from "../field-autosave";
import { StatusBadge } from "../status-badge";

export function SectionEditor({ sectionId }: { sectionId: string }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const section = builder.curriculum?.sections.find((s) => s.id === sectionId);

  const commitTitle = useCallback((v: string) => builder.renameSection(sectionId, v), [builder, sectionId]);
  const commitSummary = useCallback((v: string) => builder.setSectionSummary(sectionId, v), [builder, sectionId]);

  const title = useFieldAutosave(section?.title ?? "", commitTitle);
  const summary = useFieldAutosave(section?.summary ?? "", commitSummary);

  if (!section) return null;

  return (
    <div className="mx-auto max-w-2xl space-y-6 p-6">
      <div className="flex items-center justify-between">
        <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{t("node.section")}</span>
        <StatusBadge state={section.publish_state} />
      </div>

      <FormField
        label={t("editor.section.titleLabel")}
        required
        error={title.value.trim() ? undefined : t("validation.sectionTitle")}
      >
        <Input
          value={title.value}
          onChange={(e) => title.setValue(e.target.value)}
          onBlur={title.flush}
          placeholder={t("editor.section.titleLabel")}
        />
      </FormField>

      <FormField label={t("editor.section.summaryLabel")} hint={t("editor.section.summaryHint")}>
        <Textarea
          rows={4}
          value={summary.value}
          onChange={(e) => summary.setValue(e.target.value)}
          onBlur={summary.flush}
          placeholder={t("editor.section.summaryLabel")}
        />
      </FormField>
    </div>
  );
}
