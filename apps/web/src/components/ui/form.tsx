import type { FormHTMLAttributes, HTMLAttributes, ReactNode } from "react";
import { AlertTriangle, CheckCircle2, Info } from "lucide-react";
import { cn } from "@/lib/utils";

/** Form wrapper: `noValidate` (we render our own accessible errors) + consistent rhythm. */
export function Form({ className, ...props }: FormHTMLAttributes<HTMLFormElement>) {
  return <form noValidate className={cn("space-y-5", className)} {...props} />;
}

export interface FormSectionProps extends Omit<HTMLAttributes<HTMLDivElement>, "title"> {
  title?: ReactNode;
  description?: ReactNode;
}

/** Groups related fields under an optional heading + description. */
export function FormSection({ title, description, className, children, ...props }: FormSectionProps) {
  return (
    <section className={cn("space-y-4", className)} {...props}>
      {(title || description) && (
        <div className="space-y-1">
          {title ? <h3 className="text-h6">{title}</h3> : null}
          {description ? <p className="text-sm text-muted-foreground">{description}</p> : null}
        </div>
      )}
      {children}
    </section>
  );
}

/** Consistent action row (submit/cancel). Buttons align to the inline-end by default. */
export function FormActions({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn("flex flex-wrap items-center justify-end gap-3 pt-2", className)} {...props} />;
}

export type FormAlertVariant = "error" | "success" | "warning" | "info";

const ALERT_STYLES: Record<FormAlertVariant, { icon: typeof AlertTriangle; className: string; live: "alert" | "status" }> = {
  error: { icon: AlertTriangle, className: "border-destructive/30 bg-destructive/10 text-destructive", live: "alert" },
  warning: { icon: AlertTriangle, className: "border-warning/40 bg-warning/10 text-foreground", live: "alert" },
  success: { icon: CheckCircle2, className: "border-success/40 bg-success/10 text-foreground", live: "status" },
  info: { icon: Info, className: "border-info/40 bg-info/10 text-foreground", live: "status" },
};

export interface FormAlertProps {
  variant?: FormAlertVariant;
  children: ReactNode;
  className?: string;
}

/**
 * Canonical form-level alert. Token-driven, with the correct live-region role per variant
 * (error/warning → alert, success/info → status). Supersedes the auth-specific alert, which
 * now delegates here.
 */
export function FormAlert({ variant = "error", children, className }: FormAlertProps) {
  const { icon: Icon, className: variantClass, live } = ALERT_STYLES[variant];
  return (
    <div role={live} className={cn("flex items-start gap-2 rounded-md border p-3 text-sm", variantClass, className)}>
      <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
      <span>{children}</span>
    </div>
  );
}
