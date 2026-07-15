import { FormAlert as UiFormAlert } from "@/components/ui/form";

/**
 * Auth form-level alert. Back-compat wrapper delegating to the canonical `ui/form` `FormAlert`
 * (token-driven, correct live-region role per variant). Props are unchanged.
 */
export function FormAlert({ variant = "error", children }: { variant?: "error" | "success"; children: React.ReactNode }) {
  return <UiFormAlert variant={variant}>{children}</UiFormAlert>;
}
