import { cva, type VariantProps } from "class-variance-authority";
import type { HTMLAttributes } from "react";
import { cn } from "@/lib/utils";

const progressIndicator = cva("h-full rounded-full transition-[width] duration-[--duration-normal] ease-[--ease-standard]", {
  variants: {
    variant: {
      default: "bg-primary",
      success: "bg-success",
      warning: "bg-warning",
      destructive: "bg-destructive",
      info: "bg-info",
    },
  },
  defaultVariants: { variant: "default" },
});

export interface ProgressProps
  extends Omit<HTMLAttributes<HTMLDivElement>, "role">,
    VariantProps<typeof progressIndicator> {
  /** 0–100. Clamped internally. */
  value?: number;
  label?: string;
}

/**
 * Direction-agnostic progress bar (fills from the inline start under RTL/LTR).
 * Token-driven track + indicator; exposes role="progressbar" with aria value attributes.
 */
export function Progress({ value = 0, variant, className, label, ...props }: ProgressProps) {
  const pct = Math.max(0, Math.min(100, Math.round(value)));
  // A progressbar must have an accessible name (WCAG / axe aria-progressbar-name). Prefer the
  // caller-supplied label; otherwise fall back to the percentage, which is language-neutral.
  const ariaLabel = label ?? `${pct}%`;
  return (
    <div
      role="progressbar"
      aria-valuenow={pct}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-label={ariaLabel}
      className={cn("h-2 w-full overflow-hidden rounded-full bg-muted", className)}
      {...props}
    >
      <div className={cn(progressIndicator({ variant }))} style={{ width: `${pct}%` }} />
    </div>
  );
}
