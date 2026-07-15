"use client";

import { Slot } from "@radix-ui/react-slot";
import {
  createContext,
  useContext,
  useId,
  useState,
  type HTMLAttributes,
  type ReactNode,
} from "react";
import { cn } from "@/lib/utils";

type Side = "top" | "bottom" | "start" | "end";

interface TooltipContextValue {
  open: boolean;
  setOpen: (open: boolean) => void;
  id: string;
}

const TooltipContext = createContext<TooltipContextValue | null>(null);

function useTooltip() {
  const ctx = useContext(TooltipContext);
  if (!ctx) throw new Error("Tooltip subcomponents must be used within <Tooltip>");
  return ctx;
}

/** No-op provider kept for shadcn API compatibility (delay handling is per-trigger). */
export function TooltipProvider({ children }: { children: ReactNode }) {
  return <>{children}</>;
}

export function Tooltip({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const id = useId();
  return (
    <TooltipContext.Provider value={{ open, setOpen, id }}>
      <span className="relative inline-flex">{children}</span>
    </TooltipContext.Provider>
  );
}

export interface TooltipTriggerProps {
  children: ReactNode;
  asChild?: boolean;
}

export function TooltipTrigger({ children, asChild }: TooltipTriggerProps) {
  const { setOpen, id, open } = useTooltip();
  const Comp = asChild ? Slot : "span";
  return (
    <Comp
      aria-describedby={open ? id : undefined}
      onMouseEnter={() => setOpen(true)}
      onMouseLeave={() => setOpen(false)}
      onFocus={() => setOpen(true)}
      onBlur={() => setOpen(false)}
      onKeyDown={(e: React.KeyboardEvent) => {
        if (e.key === "Escape") setOpen(false);
      }}
    >
      {children}
    </Comp>
  );
}

const sideClasses: Record<Side, string> = {
  top: "bottom-full left-1/2 mb-2 -translate-x-1/2",
  bottom: "top-full left-1/2 mt-2 -translate-x-1/2",
  start: "end-full top-1/2 me-2 -translate-y-1/2",
  end: "start-full top-1/2 ms-2 -translate-y-1/2",
};

export interface TooltipContentProps extends HTMLAttributes<HTMLDivElement> {
  side?: Side;
}

export function TooltipContent({ side = "top", className, children, ...props }: TooltipContentProps) {
  const { open, id } = useTooltip();
  if (!open) return null;
  return (
    <div
      role="tooltip"
      id={id}
      className={cn(
        "motion-fade-in absolute z-[--z-tooltip] w-max max-w-xs rounded-md border bg-popover px-3 py-1.5 text-xs text-popover-foreground elevation-3",
        sideClasses[side],
        className,
      )}
      {...props}
    >
      {children}
    </div>
  );
}
