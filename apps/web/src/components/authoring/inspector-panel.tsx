"use client";

import { Clock, Lock, PanelRightClose } from "lucide-react";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import type { Block, PublishState, Section } from "@/lib/authoring/types";
import { StatusBadge } from "./status-badge";

/** A property group whose backend support is pending — shown honestly, never faked. */
function PendingGroup({ value, label }: { value: string; label: string }) {
  const { t } = useAuthoringI18n();
  return (
    <AccordionItem value={value}>
      <AccordionTrigger className="text-sm">{label}</AccordionTrigger>
      <AccordionContent>
        <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
          <Lock className="size-3.5" aria-hidden />
          {t("inspector.todo")}
        </p>
      </AccordionContent>
    </AccordionItem>
  );
}

function PublishRow({ state, onToggle }: { state: PublishState; onToggle: (next: PublishState) => void }) {
  const { t } = useAuthoringI18n();
  const published = state === "published";
  return (
    <div className="flex items-center justify-between gap-2">
      <StatusBadge state={state} />
      <Button size="sm" variant={published ? "outline" : "primary"} onClick={() => onToggle(published ? "draft" : "published")}>
        {t(published ? "action.unpublish" : "action.publish")}
      </Button>
    </div>
  );
}

function SectionInspector({ section }: { section: Section }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  return (
    <Accordion type="multiple" defaultValue={["publishing"]}>
      <AccordionItem value="publishing">
        <AccordionTrigger className="text-sm">{t("inspector.group.publishing")}</AccordionTrigger>
        <AccordionContent>
          <PublishRow state={section.publish_state} onToggle={(next) => void builder.publishSection(section.id, next)} />
        </AccordionContent>
      </AccordionItem>
      <PendingGroup value="visibility" label={t("inspector.group.visibility")} />
      <PendingGroup value="schedule" label={t("inspector.group.schedule")} />
      <PendingGroup value="seo" label={t("inspector.group.seo")} />
      <PendingGroup value="metadata" label={t("inspector.group.metadata")} />
    </Accordion>
  );
}

function LessonInspector({ sectionId, block }: { sectionId: string; block: Block }) {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  return (
    <Accordion type="multiple" defaultValue={["publishing", "visibility"]}>
      <AccordionItem value="publishing">
        <AccordionTrigger className="text-sm">{t("inspector.group.publishing")}</AccordionTrigger>
        <AccordionContent>
          <PublishRow state={block.publish_state} onToggle={(next) => void builder.publishBlock(sectionId, block.id, next)} />
        </AccordionContent>
      </AccordionItem>

      <AccordionItem value="visibility">
        <AccordionTrigger className="text-sm">{t("inspector.group.visibility")}</AccordionTrigger>
        <AccordionContent>
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="text-sm font-medium">{t("inspector.freePreview")}</p>
              <p className="mt-0.5 text-xs text-muted-foreground">{t("inspector.freePreviewHint")}</p>
            </div>
            <Switch
              checked={block.is_preview}
              onCheckedChange={() => void builder.previewBlock(sectionId, block.id)}
              aria-label={t("inspector.freePreview")}
            />
          </div>
        </AccordionContent>
      </AccordionItem>

      <AccordionItem value="completion">
        <AccordionTrigger className="text-sm">{t("inspector.group.completion")}</AccordionTrigger>
        <AccordionContent>
          <p className="flex items-center gap-2 text-sm">
            <Clock className="size-4 text-muted-foreground" aria-hidden />
            <span className="text-muted-foreground">{t("inspector.duration")}:</span>
            <span className="font-medium">
              {typeof block.estimated_minutes === "number"
                ? t("inspector.durationValue", { min: block.estimated_minutes })
                : t("inspector.durationUnknown")}
            </span>
          </p>
        </AccordionContent>
      </AccordionItem>

      <AccordionItem value="access">
        <AccordionTrigger className="text-sm">{t("inspector.group.access")}</AccordionTrigger>
        <AccordionContent>
          <p className="text-xs text-muted-foreground">{t("inspector.prereqs")}:</p>
          {block.prerequisites.length === 0 ? (
            <p className="mt-1 text-sm">{t("inspector.prereqsNone")}</p>
          ) : (
            <ul className="mt-1 list-inside list-disc text-sm">
              {block.prerequisites.map((p) => (
                <li key={p.id} className="truncate">
                  {p.title}
                </li>
              ))}
            </ul>
          )}
        </AccordionContent>
      </AccordionItem>

      <PendingGroup value="schedule" label={t("inspector.group.schedule")} />
      <PendingGroup value="seo" label={t("inspector.group.seo")} />
      <PendingGroup value="resources" label={t("inspector.group.resources")} />
      <PendingGroup value="metadata" label={t("inspector.group.metadata")} />
    </Accordion>
  );
}

export function InspectorPanel() {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const sel = builder.selection;

  let body: React.ReactNode;
  if (sel.kind === "section") {
    const section = builder.curriculum?.sections.find((s) => s.id === sel.sectionId);
    body = section ? <SectionInspector section={section} /> : null;
  } else if (sel.kind === "lesson") {
    const section = builder.curriculum?.sections.find((s) => s.id === sel.sectionId);
    const block = section?.blocks.find((b) => b.id === sel.blockId);
    body = block ? <LessonInspector sectionId={sel.sectionId} block={block} /> : null;
  } else {
    body = <p className="p-4 text-sm text-muted-foreground">{t("inspector.empty")}</p>;
  }

  return (
    <div className="flex h-full flex-col">
      <div className="flex items-center gap-2 border-b border-border p-3">
        <PanelRightClose className="size-4 text-muted-foreground" aria-hidden />
        <h2 className="text-sm font-semibold">{t("inspector.title")}</h2>
      </div>
      <div className="min-h-0 flex-1 overflow-y-auto p-3">{body}</div>
    </div>
  );
}
