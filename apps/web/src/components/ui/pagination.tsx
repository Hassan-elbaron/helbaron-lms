"use client";

import { ChevronLeft, ChevronRight } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { cn } from "@/lib/utils";
import { Button } from "./button";

export interface PaginationProps {
  page: number;
  lastPage: number;
  onPageChange: (page: number) => void;
  className?: string;
}

/** Direction-aware pagination: chevrons flip automatically under RTL via CSS logical layout. */
export function Pagination({ page, lastPage, onPageChange, className }: PaginationProps) {
  const { dir } = useI18n();
  const Prev = dir === "rtl" ? ChevronRight : ChevronLeft;
  const Next = dir === "rtl" ? ChevronLeft : ChevronRight;

  return (
    <nav className={cn("flex items-center justify-between gap-2", className)} aria-label="Pagination">
      <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
        <Prev className="size-4" /> {page - 1 >= 1 ? page - 1 : ""}
      </Button>
      <span className="text-sm text-muted-foreground">
        {page} / {lastPage}
      </span>
      <Button variant="outline" size="sm" disabled={page >= lastPage} onClick={() => onPageChange(page + 1)}>
        {page + 1 <= lastPage ? page + 1 : ""} <Next className="size-4" />
      </Button>
    </nav>
  );
}
