"use client";

import { createContext, forwardRef, useContext, useId, useState, type HTMLAttributes, type InputHTMLAttributes } from "react";
import { cn } from "@/lib/utils";

interface RadioGroupContextValue {
  name: string;
  value?: string;
  onValueChange?: (value: string) => void;
}

const RadioGroupContext = createContext<RadioGroupContextValue | null>(null);

export interface RadioGroupProps extends Omit<HTMLAttributes<HTMLDivElement>, "onChange"> {
  value?: string;
  defaultValue?: string;
  onValueChange?: (value: string) => void;
  name?: string;
}

/** Accessible radio group (role="radiogroup") backed by native inputs. Controllable or uncontrolled. */
const RadioGroup = forwardRef<HTMLDivElement, RadioGroupProps>(
  ({ className, value, defaultValue, onValueChange, name, ...props }, ref) => {
    const autoName = useId();
    const [internal, setInternal] = useState(defaultValue);
    const isControlled = value !== undefined;
    const current = isControlled ? value : internal;

    return (
      <RadioGroupContext.Provider
        value={{
          name: name ?? autoName,
          value: current,
          onValueChange: (v) => {
            if (!isControlled) setInternal(v);
            onValueChange?.(v);
          },
        }}
      >
        <div ref={ref} role="radiogroup" className={cn("grid gap-2", className)} {...props} />
      </RadioGroupContext.Provider>
    );
  },
);
RadioGroup.displayName = "RadioGroup";

export interface RadioGroupItemProps extends Omit<InputHTMLAttributes<HTMLInputElement>, "type" | "name"> {
  value: string;
}

const RadioGroupItem = forwardRef<HTMLInputElement, RadioGroupItemProps>(({ className, value, ...props }, ref) => {
  const ctx = useContext(RadioGroupContext);
  return (
    <input
      ref={ref}
      type="radio"
      value={value}
      name={ctx?.name}
      checked={ctx ? ctx.value === value : undefined}
      onChange={() => ctx?.onValueChange?.(value)}
      className={cn(
        "size-4 shrink-0 cursor-pointer appearance-none rounded-full border border-input transition-colors duration-[--duration-fast]",
        "checked:border-primary checked:bg-primary checked:shadow-[inset_0_0_0_3px_var(--background)]",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
        "disabled:cursor-not-allowed disabled:opacity-[--opacity-disabled]",
        className,
      )}
      {...props}
    />
  );
});
RadioGroupItem.displayName = "RadioGroupItem";

export { RadioGroup, RadioGroupItem };
