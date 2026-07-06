"use client";

import Link from "next/link";
import { GraduationCap } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import { Button } from "@/components/ui/button";
import { Reveal } from "./reveal";

export function FinalCta() {
  const { locale } = useI18n();
  const c = brandTheme.finalCta;
  return (
    <section className="px-4 py-24">
      <Reveal className="mx-auto max-w-3xl text-center">
        <span className="mx-auto mb-6 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/20 animate-float-slow">
          <GraduationCap className="size-6" aria-hidden />
        </span>
        <h2 className="font-serif text-3xl font-semibold tracking-tight sm:text-[2.75rem] sm:leading-[1.1]">
          {pickLocale(c.title1, locale)} <span className="italic text-copper">{pickLocale(c.title2, locale)}</span>
        </h2>
        <p className="mx-auto mt-4 max-w-xl text-muted-foreground">{pickLocale(c.subtitle, locale)}</p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          <Button asChild size="lg" className="shine relative overflow-hidden">
            <Link href={c.primary.href}>{pickLocale(c.primary.label, locale)}</Link>
          </Button>
          <Button asChild size="lg" variant="outline">
            <Link href={c.secondary.href}>{pickLocale(c.secondary.label, locale)}</Link>
          </Button>
        </div>
      </Reveal>
    </section>
  );
}
