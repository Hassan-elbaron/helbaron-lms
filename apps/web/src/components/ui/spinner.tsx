import { Loader2 } from "lucide-react";
import { cva, type VariantProps } from "class-variance-authority";
import type { HTMLAttributes } from "react";
import { cn } from "@/lib/utils";

const spinnerVariants = cva("motion-spin text-current", {
  variants: {
    size: {
      sm: "size-4",
      md: "size-6",
      lg: "size-8",
      icon: "size-5",
    },
  },
  defaultVariants: { size: "md" },
});

export interface SpinnerProps
  extends Omit<HTMLAttributes<SVGSVGElement>, "color">,
    VariantProps<typeof spinnerVariants> {
  /** Accessible label. When omitted the spinner is treated as decorative (aria-hidden). */
  label?: string;
}

/**
 * Token-driven loading spinner. Uses the consolidated `.motion-spin` utility so it honours
 * reduced-motion. Decorative by default; pass `label` to expose it to assistive tech.
 */
export function Spinner({ size, className, label, ...props }: SpinnerProps) {
  return (
    <Loader2
      className={cn(spinnerVariants({ size }), className)}
      strokeWidth={2}
      role={label ? "status" : undefined}
      aria-label={label}
      aria-hidden={label ? undefined : true}
      {...props}
    />
  );
}
