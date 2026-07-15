"use client";

import { cloneElement, isValidElement, useId, type ReactElement, type ReactNode } from "react";
import { CheckCircle2 } from "lucide-react";
import { Label } from "./label";
import { cn } from "@/lib/utils";

/** Props injected onto the field control (spread onto your input/select/etc.). */
export interface FieldControlProps {
  id: string;
  "aria-invalid"?: true;
  "aria-describedby"?: string;
  "aria-required"?: true;
}

export interface FormFieldProps {
  /** Visible label text (associated to the control via htmlFor/id). */
  label: ReactNode;
  /** Control id. Auto-generated when omitted. */
  id?: string;
  /** Validation error — rendered with role="alert" and wires aria-invalid. */
  error?: ReactNode;
  /** Helper text — wired into aria-describedby. */
  hint?: ReactNode;
  /** Success confirmation — rendered with role="status" (suppressed while an error is shown). */
  success?: ReactNode;
  /** Marks the field required (visible marker + aria-required). */
  required?: boolean;
  /** Visually hide the label while keeping it for assistive tech. */
  hideLabel?: boolean;
  /** Reflects an in-flight state (sets aria-busy on the group). */
  loading?: boolean;
  className?: string;
  labelClassName?: string;
  /**
   * The control. Either a single element (cloned with the wiring props) or a render function
   * receiving the wiring props — use the function form for controls that don't forward props
   * (e.g. RadioGroup, custom Select).
   */
  children: ReactElement<Record<string, unknown>> | ((props: FieldControlProps) => ReactNode);
}

/**
 * Canonical labelled form control: label (+ required marker) · control · hint · error · success.
 * Wires `aria-invalid`, `aria-describedby` (hint + error + success), `aria-required`, `role=alert`
 * on errors and `role=status` on success. Token-driven, RTL-safe (logical props), usable across
 * auth / teach / crm / checkout. `auth/field` delegates here, so existing forms keep working.
 */
export function FormField({
  label,
  id,
  error,
  hint,
  success,
  required,
  hideLabel,
  loading,
  className,
  labelClassName,
  children,
}: FormFieldProps) {
  const reactId = useId();
  const fieldId = id ?? reactId;
  const errorId = `${fieldId}-error`;
  const hintId = `${fieldId}-hint`;
  const successId = `${fieldId}-success`;
  const showSuccess = Boolean(success) && !error;

  const describedBy =
    [hint ? hintId : null, error ? errorId : null, showSuccess ? successId : null].filter(Boolean).join(" ") ||
    undefined;

  const wiring: FieldControlProps = {
    id: fieldId,
    "aria-invalid": error ? true : undefined,
    "aria-describedby": describedBy,
    "aria-required": required ? true : undefined,
  };

  const control =
    typeof children === "function"
      ? children(wiring)
      : isValidElement(children)
        ? cloneElement(children, {
            ...wiring,
            id: (children.props as { id?: string }).id ?? fieldId,
          })
        : children;

  return (
    <div className={cn("space-y-1.5", className)} aria-busy={loading || undefined}>
      <Label htmlFor={fieldId} className={cn(hideLabel && "sr-only", labelClassName)}>
        {label}
        {required ? (
          <>
            <span aria-hidden className="ms-0.5 text-destructive">
              *
            </span>
            <span className="sr-only"> (required)</span>
          </>
        ) : null}
      </Label>
      {control}
      {hint ? (
        <p id={hintId} className="text-xs text-muted-foreground">
          {hint}
        </p>
      ) : null}
      {error ? (
        <p id={errorId} role="alert" className="text-xs font-medium text-destructive">
          {error}
        </p>
      ) : null}
      {showSuccess ? (
        <p id={successId} role="status" className="flex items-center gap-1 text-xs font-medium text-success">
          <CheckCircle2 className="size-3.5 shrink-0" aria-hidden />
          {success}
        </p>
      ) : null}
    </div>
  );
}
