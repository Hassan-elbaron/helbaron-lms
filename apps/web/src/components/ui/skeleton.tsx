import type { HTMLAttributes } from "react";
import { cn } from "@/lib/utils";

export interface SkeletonProps extends HTMLAttributes<HTMLDivElement> {
  /** Preset shapes so loading placeholders stay consistent across the app. */
  variant?: "block" | "text" | "avatar" | "card" | "table-row";
}

const variantClass: Record<NonNullable<SkeletonProps["variant"]>, string> = {
  block: "",
  text: "h-4 w-full rounded",
  avatar: "size-10 rounded-full",
  card: "h-40 w-full rounded-xl",
  "table-row": "h-10 w-full rounded",
};

/** Token-driven loading placeholder. `variant` provides the common shapes; base stays back-compat. */
function Skeleton({ className, variant = "block", ...props }: SkeletonProps) {
  return <div aria-hidden className={cn("motion-pulse rounded-md bg-muted", variantClass[variant], className)} {...props} />;
}

/** Multi-line text placeholder (n lines; last line shortened for realism). */
function SkeletonText({ lines = 3, className }: { lines?: number; className?: string }) {
  return (
    <div className={cn("space-y-2", className)}>
      {Array.from({ length: lines }).map((_, i) => (
        <Skeleton key={i} variant="text" className={i === lines - 1 ? "w-2/3" : undefined} />
      ))}
    </div>
  );
}

export { Skeleton, SkeletonText };
