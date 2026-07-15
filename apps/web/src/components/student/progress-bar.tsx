import { Progress } from "@/components/ui/progress";

/**
 * Direction-agnostic progress bar (fills from the inline start under RTL/LTR).
 * Thin back-compat wrapper over the canonical `ui/progress` primitive — one source of truth.
 */
export function ProgressBar({ value, className, label }: { value: number; className?: string; label?: string }) {
  return <Progress value={value} className={className} label={label} />;
}
