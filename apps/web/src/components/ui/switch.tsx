"use client";

import { forwardRef, useState, type ButtonHTMLAttributes } from "react";
import { cn } from "@/lib/utils";

export interface SwitchProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, "onChange" | "value"> {
  checked?: boolean;
  defaultChecked?: boolean;
  onCheckedChange?: (checked: boolean) => void;
}

/**
 * Accessible toggle switch (role="switch", aria-checked). Controllable or uncontrolled.
 * No external dependency — token-driven track/thumb that mirror correctly under RTL.
 */
const Switch = forwardRef<HTMLButtonElement, SwitchProps>(
  ({ className, checked, defaultChecked, onCheckedChange, disabled, ...props }, ref) => {
    const [internal, setInternal] = useState(defaultChecked ?? false);
    const isControlled = checked !== undefined;
    const value = isControlled ? checked : internal;

    return (
      <button
        ref={ref}
        type="button"
        role="switch"
        aria-checked={value}
        disabled={disabled}
        data-state={value ? "checked" : "unchecked"}
        onClick={() => {
          if (!isControlled) setInternal((v) => !v);
          onCheckedChange?.(!value);
        }}
        className={cn(
          "peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-[--duration-fast]",
          "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background",
          "disabled:cursor-not-allowed disabled:opacity-[--opacity-disabled]",
          value ? "bg-primary" : "bg-input",
          className,
        )}
        {...props}
      >
        <span
          aria-hidden
          className={cn(
            "pointer-events-none block size-5 rounded-full bg-background elevation-1 transition-transform duration-[--duration-fast]",
            value ? "translate-x-5 rtl:-translate-x-5" : "translate-x-0",
          )}
        />
      </button>
    );
  },
);
Switch.displayName = "Switch";

export { Switch };
