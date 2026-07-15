"use client";

import { Slot } from "@radix-ui/react-slot";
import {
  createContext,
  useContext,
  useEffect,
  useRef,
  useState,
  type HTMLAttributes,
  type ReactNode,
} from "react";
import { cn } from "@/lib/utils";

interface PopoverContextValue {
  open: boolean;
  setOpen: (open: boolean) => void;
  rootRef: React.RefObject<HTMLSpanElement | null>;
}

const PopoverContext = createContext<PopoverContextValue | null>(null);

function usePopover() {
  const ctx = useContext(PopoverContext);
  if (!ctx) throw new Error("Popover subcomponents must be used within <Popover>");
  return ctx;
}

export interface PopoverProps {
  children: ReactNode;
  open?: boolean;
  defaultOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
}

export function Popover({ children, open, defaultOpen, onOpenChange }: PopoverProps) {
  const [internal, setInternal] = useState(defaultOpen ?? false);
  const isControlled = open !== undefined;
  const value = isControlled ? open : internal;
  const rootRef = useRef<HTMLSpanElement | null>(null);

  const setOpen = (next: boolean) => {
    if (!isControlled) setInternal(next);
    onOpenChange?.(next);
  };

  useEffect(() => {
    if (!value) return;
    const onDown = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("mousedown", onDown);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onDown);
      document.removeEventListener("keydown", onKey);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value]);

  return (
    <PopoverContext.Provider value={{ open: value, setOpen, rootRef }}>
      <span ref={rootRef} className="relative inline-flex">
        {children}
      </span>
    </PopoverContext.Provider>
  );
}

export function PopoverTrigger({ children, asChild }: { children: ReactNode; asChild?: boolean }) {
  const { open, setOpen } = usePopover();
  const Comp = asChild ? Slot : "button";
  return (
    <Comp aria-expanded={open} aria-haspopup="dialog" onClick={() => setOpen(!open)}>
      {children}
    </Comp>
  );
}

export interface PopoverContentProps extends HTMLAttributes<HTMLDivElement> {
  align?: "start" | "center" | "end";
}

export function PopoverContent({ align = "center", className, children, ...props }: PopoverContentProps) {
  const { open } = usePopover();
  if (!open) return null;
  const alignClass =
    align === "start" ? "start-0" : align === "end" ? "end-0" : "left-1/2 -translate-x-1/2";
  return (
    <div
      role="dialog"
      className={cn(
        "motion-dropdown absolute top-full z-[--z-popover] mt-2 w-72 rounded-md border bg-popover p-4 text-popover-foreground elevation-4",
        alignClass,
        className,
      )}
      {...props}
    >
      {children}
    </div>
  );
}

export function PopoverClose({ children, asChild }: { children: ReactNode; asChild?: boolean }) {
  const { setOpen } = usePopover();
  const Comp = asChild ? Slot : "button";
  return <Comp onClick={() => setOpen(false)}>{children}</Comp>;
}
