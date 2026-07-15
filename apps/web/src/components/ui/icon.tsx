import type { LucideIcon, LucideProps } from "lucide-react";
import { cn } from "@/lib/utils";

/**
 * Icon convention for the design system. lucide-react is the single icon family.
 *
 *   Size tokens:  xs=14  sm=16  md=20  lg=24  xl=32   (px)
 *   Stroke width: 1.75 by default (aligns with the token typography weight)
 *   A11y:         decorative icons get aria-hidden; pass `label` for meaningful icons,
 *                 which switches the icon to role="img" with an accessible name.
 *
 * Prefer this wrapper (or the documented size values) when adding icons so sizing,
 * stroke, and aria semantics stay consistent across the app.
 */
export const ICON_SIZES = { xs: 14, sm: 16, md: 20, lg: 24, xl: 32 } as const;

export type IconSize = keyof typeof ICON_SIZES;

export interface IconProps extends Omit<LucideProps, "ref" | "size"> {
  icon: LucideIcon;
  size?: IconSize;
  /** Accessible name. Omit for decorative icons (they become aria-hidden). */
  label?: string;
}

export function Icon({ icon: LucideGlyph, size = "md", strokeWidth = 1.75, label, className, ...props }: IconProps) {
  return (
    <LucideGlyph
      width={ICON_SIZES[size]}
      height={ICON_SIZES[size]}
      strokeWidth={strokeWidth}
      role={label ? "img" : undefined}
      aria-label={label}
      aria-hidden={label ? undefined : true}
      className={cn("shrink-0", className)}
      {...props}
    />
  );
}
