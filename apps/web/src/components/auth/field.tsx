import { isValidElement, type ReactElement, type ReactNode } from "react";
import { FormField } from "@/components/ui/form-field";

export interface FieldProps {
  id: string;
  label: string;
  error?: string;
  children: ReactNode;
  className?: string;
  hint?: string;
  /** Additive: renders a required marker + wires aria-required. */
  required?: boolean;
}

/**
 * Labelled form control with inline validation. Thin back-compat wrapper over the canonical
 * `FormField` (label + control + hint + error + required + aria wiring). Props are unchanged so
 * every existing auth/checkout/crm form keeps working; new code can use `FormField` directly.
 */
export function Field({ id, label, error, children, className, hint, required }: FieldProps) {
  // Single element → let FormField clone the aria wiring onto it; otherwise render as-is.
  const control = isValidElement(children)
    ? (children as ReactElement<Record<string, unknown>>)
    : () => children;

  return (
    <FormField id={id} label={label} error={error} hint={hint} required={required} className={className}>
      {control}
    </FormField>
  );
}
