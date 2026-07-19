"use client";

import { useMemo, useState } from "react";
import {
  DndContext,
  KeyboardSensor,
  PointerSensor,
  closestCenter,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core";
import {
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { Copy, GripVertical, Plus, Search, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { QUESTION_TYPES } from "@/lib/assessment/question-model";
import type { Question, QuestionType } from "@/lib/assessment/types";
import { cn } from "@/lib/utils";

/**
 * Left rail: the ordered question list.
 *
 * Numbering is by POSITION IN THE LIST, not by a stored field — the number a learner sees is
 * simply where the question sits, so deriving it here keeps the two in step automatically.
 *
 * Drag-and-drop reuses the same dnd-kit setup as the curriculum tree, including the keyboard
 * sensor: reordering must be possible without a mouse.
 */
export function QuestionList({
  questions,
  selectedId,
  onSelect,
  onAdd,
  onDuplicate,
  onDelete,
  onReorder,
  invalidIds,
}: {
  questions: Question[];
  selectedId: string | null;
  onSelect: (id: string) => void;
  onAdd: (type: QuestionType) => void;
  onDuplicate: (id: string) => void;
  onDelete: (id: string) => void;
  onReorder: (orderedIds: string[]) => void;
  /** Ids the editor considers incomplete — surfaced in the list so problems are findable. */
  invalidIds: ReadonlySet<string>;
}) {
  const { t } = useAuthoringI18n();
  const [search, setSearch] = useState("");

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  );

  const filtered = useMemo(() => {
    const needle = search.trim().toLowerCase();
    if (needle === "") return questions;

    return questions.filter((q) => plainText(q.prompt).toLowerCase().includes(needle));
  }, [questions, search]);

  // Dragging is disabled while filtering: a drop position within a filtered subset does not
  // describe a position in the real list, so the resulting order would be a guess.
  const sortable = search.trim() === "";

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event;
    if (!over || active.id === over.id) return;

    const ids = questions.map((q) => q.id);
    const from = ids.indexOf(String(active.id));
    const to = ids.indexOf(String(over.id));
    if (from < 0 || to < 0) return;

    const next = [...ids];
    next.splice(to, 0, ...next.splice(from, 1));
    onReorder(next);
  }

  const totalPoints = questions.reduce((sum, q) => sum + (q.points ?? 0), 0);

  return (
    <div className="flex h-full flex-col">
      <div className="space-y-2 border-b border-border p-3">
        <div className="flex items-center justify-between gap-2">
          <h2 className="text-sm font-semibold">{t("assessment.questions")}</h2>
          <AddQuestionMenu onAdd={onAdd} />
        </div>

        <div className="relative">
          <Search
            className="pointer-events-none absolute inset-inline-start-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden
          />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={t("assessment.searchQuestions")}
            aria-label={t("assessment.searchQuestions")}
            className="ps-8"
          />
        </div>

        <p className="text-xs text-muted-foreground">
          {t("assessment.totalPoints", { n: Math.round(totalPoints * 100) / 100 })}
        </p>
      </div>

      <div className="min-h-0 flex-1 overflow-y-auto p-2">
        {filtered.length === 0 ? (
          <p className="p-4 text-center text-sm text-muted-foreground">
            {questions.length === 0 ? t("assessment.noQuestions") : t("assessment.noMatches")}
          </p>
        ) : (
          <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            // No axis modifier: @dnd-kit/modifiers is not a dependency of this app, and
            // verticalListSortingStrategy already constrains the sort to one column.
            onDragEnd={handleDragEnd}
          >
            <SortableContext items={filtered.map((q) => q.id)} strategy={verticalListSortingStrategy}>
              <ol className="space-y-1">
                {filtered.map((question) => (
                  <QuestionRow
                    key={question.id}
                    question={question}
                    // Number from the FULL list, so filtering never renumbers questions.
                    number={questions.findIndex((q) => q.id === question.id) + 1}
                    selected={question.id === selectedId}
                    invalid={invalidIds.has(question.id)}
                    sortable={sortable}
                    onSelect={() => onSelect(question.id)}
                    onDuplicate={() => onDuplicate(question.id)}
                    onDelete={() => onDelete(question.id)}
                  />
                ))}
              </ol>
            </SortableContext>
          </DndContext>
        )}
      </div>
    </div>
  );
}

function QuestionRow({
  question,
  number,
  selected,
  invalid,
  sortable,
  onSelect,
  onDuplicate,
  onDelete,
}: {
  question: Question;
  number: number;
  selected: boolean;
  invalid: boolean;
  sortable: boolean;
  onSelect: () => void;
  onDuplicate: () => void;
  onDelete: () => void;
}) {
  const { t } = useAuthoringI18n();
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: question.id,
    disabled: !sortable,
  });

  const title = plainText(question.prompt).trim() || t("question.untitled");

  return (
    <li
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      className={cn(
        "group flex items-center gap-1 rounded-md border border-transparent px-1",
        selected && "border-border bg-muted",
        isDragging && "opacity-60",
      )}
    >
      {sortable ? (
        <button
          type="button"
          {...attributes}
          {...listeners}
          // The drag handle is focusable and operable by keyboard via dnd-kit's keyboard sensor.
          className="cursor-grab rounded p-1 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          aria-label={`${t("question.number", { n: number })}: ${title}`}
        >
          <GripVertical className="size-4" aria-hidden />
        </button>
      ) : (
        <span className="w-6" aria-hidden />
      )}

      <button
        type="button"
        onClick={onSelect}
        aria-current={selected ? "true" : undefined}
        className="flex min-w-0 flex-1 items-center gap-2 rounded px-1 py-2 text-start focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <span className="shrink-0 text-xs font-medium tabular-nums text-muted-foreground">
          {t("question.number", { n: number })}
        </span>
        <span className="min-w-0 flex-1 truncate text-sm">{title}</span>
        {invalid ? (
          // Colour is not the only signal — the title attribute and the inline editor errors carry
          // the same information for anyone who cannot distinguish the dot.
          <span
            className="size-2 shrink-0 rounded-full bg-destructive"
            title={t("validation.promptRequired")}
            aria-label={t("validation.promptRequired")}
          />
        ) : null}
        <span className="shrink-0 text-xs tabular-nums text-muted-foreground">{question.points}</span>
      </button>

      <div className="flex shrink-0 opacity-0 transition-opacity focus-within:opacity-100 group-hover:opacity-100">
        <Button variant="ghost" size="icon" className="size-7" onClick={onDuplicate} aria-label={t("question.duplicate")}>
          <Copy className="size-3.5" aria-hidden />
        </Button>
        <Button variant="ghost" size="icon" className="size-7" onClick={onDelete} aria-label={t("question.delete")}>
          <Trash2 className="size-3.5" aria-hidden />
        </Button>
      </div>
    </li>
  );
}

function AddQuestionMenu({ onAdd }: { onAdd: (type: QuestionType) => void }) {
  const { t } = useAuthoringI18n();

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button size="sm" variant="outline">
          <Plus className="size-4" aria-hidden />
          {t("assessment.addQuestion")}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {QUESTION_TYPES.map((type) => (
          <DropdownMenuItem key={type} onSelect={() => onAdd(type)}>
            {t(`qtype.${type}`)}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

/** Prompts are sanitized HTML; the list shows them as text. */
function plainText(html: string): string {
  return html.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ");
}
