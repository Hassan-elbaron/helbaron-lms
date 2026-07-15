import { forwardRef, type InputHTMLAttributes } from "react";
import { cn } from "@/lib/utils";

export type InputProps = InputHTMLAttributes<HTMLInputElement>;

const Input = forwardRef<HTMLInputElement, InputProps>(({ className, type, ...props }, ref) => (
  <input
    type={type}
    ref={ref}
    className={cn(
      "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors duration-[--duration-fast] file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground",
      "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
      // States: disabled / read-only / validation (aria-invalid) — all token-driven.
      "disabled:cursor-not-allowed disabled:opacity-[--opacity-disabled] read-only:bg-muted/40 read-only:cursor-default",
      "aria-[invalid=true]:border-destructive aria-[invalid=true]:focus-visible:ring-destructive",
      className,
    )}
    {...props}
  />
));
Input.displayName = "Input";

export { Input };
