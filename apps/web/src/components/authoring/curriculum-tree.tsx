"use client";

import { ChevronsDownUp, ChevronsUpDown, ListTree, Plus, Search } from "lucide-react";
import {
  closestCenter,
  DndContext,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core";
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from "@dnd-kit/sortable";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { useBuilder } from "@/lib/authoring/builder-store";
import { SectionNode } from "./section-node";

interface DragData {
  type?: "section" | "block";
  sectionId?: string;
}

export function CurriculumTree() {
  const { t } = useAuthoringI18n();
  const builder = useBuilder();
  const sections = builder.curriculum?.sections ?? [];

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  );

  function onDragEnd(e: DragEndEvent) {
    const { active, over } = e;
    if (!over) return;
    const a = active.data.current as DragData | undefined;
    const o = over.data.current as DragData | undefined;
    if (!a) return;
    const activeId = String(active.id);
    const overId = String(over.id);

    if (a.type === "section") {
      if (activeId === overId) return;
      const ids = sections.map((s) => s.id);
      const from = ids.indexOf(activeId);
      const to = ids.indexOf(overId);
      if (from === -1 || to === -1) return;
      void builder.reorderSections(arrayMove(ids, from, to));
      return;
    }

    if (a.type === "block" && a.sectionId) {
      const fromSection = a.sectionId;
      const toSection = o?.type === "block" ? o.sectionId : o?.type === "section" ? overId : fromSection;
      if (!toSection) return;
      const target = sections.find((s) => s.id === toSection);
      if (!target) return;
      if (fromSection === toSection) {
        const ids = target.blocks.map((b) => b.id);
        const from = ids.indexOf(activeId);
        const to = o?.type === "block" ? ids.indexOf(overId) : ids.length - 1;
        if (from === -1 || to === -1 || from === to) return;
        void builder.reorderBlocks(fromSection, arrayMove(ids, from, to));
      } else {
        const ids = target.blocks.map((b) => b.id);
        const idx = o?.type === "block" ? ids.indexOf(overId) : ids.length;
        void builder.moveBlockAcross(fromSection, toSection, activeId, idx < 0 ? ids.length : idx);
      }
    }
  }

  return (
    <div className="flex h-full flex-col">
      <div className="border-b border-border p-3">
        <div className="mb-2 flex items-center justify-between">
          <h2 className="flex items-center gap-2 text-sm font-semibold">
            <ListTree className="size-4 text-muted-foreground" aria-hidden />
            {t("tree.curriculum")}
          </h2>
          <div className="flex items-center gap-0.5">
            <Button variant="ghost" size="icon" onClick={builder.expandAll} aria-label={t("tree.expandAll")}>
              <ChevronsUpDown className="size-4" aria-hidden />
            </Button>
            <Button variant="ghost" size="icon" onClick={builder.collapseAll} aria-label={t("tree.collapseAll")}>
              <ChevronsDownUp className="size-4" aria-hidden />
            </Button>
          </div>
        </div>
        <div className="relative">
          <Search className="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
          <Input
            type="search"
            value={builder.search}
            onChange={(e) => builder.setSearch(e.target.value)}
            placeholder={t("tree.search")}
            aria-label={t("tree.search")}
            className="ps-8"
          />
        </div>
      </div>

      <div className="min-h-0 flex-1 overflow-y-auto p-2">
        {builder.isLoading ? (
          <div className="space-y-2">
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
          </div>
        ) : sections.length === 0 ? (
          <div className="flex flex-col items-center gap-3 px-4 py-10 text-center">
            <ListTree className="size-8 text-muted-foreground/50" aria-hidden />
            <div>
              <p className="text-sm font-medium">{t("tree.empty.title")}</p>
              <p className="mt-1 text-xs text-muted-foreground">{t("tree.empty.desc")}</p>
            </div>
            <Button size="sm" onClick={() => void builder.addSection()}>
              <Plus className="size-4" aria-hidden /> {t("tree.addSection")}
            </Button>
          </div>
        ) : (
          <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
            <SortableContext items={sections.map((s) => s.id)} strategy={verticalListSortingStrategy}>
              <div className="space-y-2">
                {sections.map((s) => (
                  <SectionNode key={s.id} section={s} query={builder.search} />
                ))}
              </div>
            </SortableContext>
          </DndContext>
        )}
      </div>

      <div className="border-t border-border p-2">
        <Button variant="outline" size="sm" className="w-full" onClick={() => void builder.addSection()}>
          <Plus className="size-4" aria-hidden /> {t("tree.addSection")}
        </Button>
      </div>
    </div>
  );
}
