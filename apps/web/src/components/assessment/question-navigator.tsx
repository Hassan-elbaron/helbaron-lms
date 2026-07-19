"use client";

import { memo, useMemo } from "react";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import { cn } from "@/lib/utils";

/**
 * The question palette, shared by the instructor preview and the learner player.
 *
 * WINDOWING: a 500-question assessment would otherwise mount 500 buttons, each re-rendering on
 * every answer. Above WINDOW_THRESHOLD the palette renders only a slice around the current
 * question. That is enough for navigation-by-proximity, and the "review unanswered / flagged"
 * filters cover the long-range case better than scrolling a wall of numbers would.
 *
 * Memoised because it re-renders on every keystroke otherwise — its props are all primitives or
 * stable sets, so a shallow compare is genuinely effective here.
 */

const WINDOW_THRESHOLD = 120;
const WINDOW_RADIUS = 40;

export interface NavigatorItem {
  id: string;
  answered: boolean;
  flagged: boolean;
}

export const QuestionNavigator = memo(function QuestionNavigator({
  items,
  currentIndex,
  onJump,
  filter = "all",
}: {
  items: NavigatorItem[];
  currentIndex: number;
  onJump: (index: number) => void;
  /** Narrows the palette to the questions a learner is reviewing. */
  filter?: "all" | "unanswered" | "flagged";
}) {
  const { t } = useAuthoringI18n();

  const visible = useMemo(() => {
    const withIndex = items.map((item, index) => ({ ...item, index }));

    if (filter === "unanswered") return withIndex.filter((i) => !i.answered);
    if (filter === "flagged") return withIndex.filter((i) => i.flagged);
    if (withIndex.length <= WINDOW_THRESHOLD) return withIndex;

    // Window around the current question, clamped to the ends so the slice is always full-width.
    const start = Math.max(0, Math.min(currentIndex - WINDOW_RADIUS, withIndex.length - WINDOW_RADIUS * 2));
    return withIndex.slice(start, start + WINDOW_RADIUS * 2);
  }, [items, currentIndex, filter]);

  if (visible.length === 0) {
    return (
      <p className="py-2 text-sm text-muted-foreground">
        {filter === "flagged" ? t("player.noFlagged") : t("player.noUnanswered")}
      </p>
    );
  }

  return (
    <nav aria-label={t("assessment.questions")}>
      <ul className="flex flex-wrap gap-1">
        {visible.map((item) => (
          <li key={item.id}>
            <button
              type="button"
              onClick={() => onJump(item.index)}
              aria-current={item.index === currentIndex ? "true" : undefined}
              // The label carries state as text, because the colour and ring alone are invisible
              // to a screen reader and ambiguous to anyone who cannot distinguish them.
              aria-label={
                t("preview.jumpTo", { n: item.index + 1 }) +
                (item.answered ? `, ${t("player.answered")}` : `, ${t("player.unanswered")}`) +
                (item.flagged ? `, ${t("preview.flagged")}` : "")
              }
              className={cn(
                "size-9 rounded border border-border text-xs tabular-nums transition-colors",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring",
                item.answered && "bg-muted font-medium",
                item.flagged && "ring-1 ring-warning",
                item.index === currentIndex && "border-primary bg-primary text-primary-foreground",
              )}
            >
              {item.index + 1}
            </button>
          </li>
        ))}
      </ul>

      {filter === "all" && items.length > WINDOW_THRESHOLD ? (
        <p className="mt-2 text-xs text-muted-foreground">
          {t("player.windowed", { shown: visible.length, total: items.length })}
        </p>
      ) : null}
    </nav>
  );
});
