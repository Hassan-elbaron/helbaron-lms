import type { ReactNode } from "react";
import { cn } from "@/lib/utils";
import { Reveal } from "./reveal";

export function Section({ id, className, children }: { id?: string; className?: string; children: ReactNode }) {
  return (
    <section id={id} className={cn("px-4 py-20 sm:py-24", className)}>
      <div className="mx-auto w-full max-w-6xl">{children}</div>
    </section>
  );
}

export function Eyebrow({ children }: { children: ReactNode }) {
  return (
    <span className="inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.22em] text-copper">
      <span className="h-px w-8 bg-copper/50" aria-hidden />
      {children}
      <span className="h-px w-8 bg-copper/50" aria-hidden />
    </span>
  );
}

export function SectionHeading({ eyebrow, title1, title2, subtitle }: { eyebrow?: string; title1: string; title2?: string; subtitle?: string }) {
  return (
    <Reveal className="mb-12 text-center">
      {eyebrow ? <div className="mb-4 flex justify-center"><Eyebrow>{eyebrow}</Eyebrow></div> : null}
      <h2 className="mx-auto max-w-3xl font-serif text-3xl font-semibold tracking-tight sm:text-[2.6rem] sm:leading-[1.1]">
        {title1}{" "}
        {title2 ? <span className="italic text-copper">{title2}</span> : null}
      </h2>
      {subtitle ? <p className="mx-auto mt-4 max-w-xl text-muted-foreground">{subtitle}</p> : null}
    </Reveal>
  );
}
