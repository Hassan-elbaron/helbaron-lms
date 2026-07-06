import { AlertTriangle, CheckCircle2 } from "lucide-react";
import { cn } from "@/lib/utils";

export function FormAlert({ variant = "error", children }: { variant?: "error" | "success"; children: React.ReactNode }) {
  const Icon = variant === "success" ? CheckCircle2 : AlertTriangle;
  return (
    <div
      role="alert"
      className={cn(
        "flex items-start gap-2 rounded-md border p-3 text-sm",
        variant === "success"
          ? "border-success/40 bg-success/10 text-foreground"
          : "border-destructive/30 bg-destructive/10 text-destructive",
      )}
    >
      <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
      <span>{children}</span>
    </div>
  );
}
