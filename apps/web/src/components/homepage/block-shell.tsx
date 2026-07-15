"use client";

import type { CSSProperties, ReactNode } from "react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import { cn } from "@/lib/utils";
import { Reveal } from "@/components/landing/reveal";
import type { HomepageSection } from "@/lib/homepage/api";

/**
 * Shared presentational wrapper for the expansion homepage blocks. Applies the CMS presentation
 * metadata dynamically — spacing, alignment, container width, theme variant, background
 * (color/image/overlay) and animation — plus the accessibility label (as the section's aria-label)
 * and the responsive device-visibility flags (Tailwind `hidden` variants). RTL-safe: alignment uses
 * logical text-start/end so it flips automatically with `dir`.
 *
 * Colors come exclusively from theme tokens / admin-provided values — no hardcoded palette here.
 */

const SPACING: Record<string, string> = {
  none: "py-0",
  compact: "py-10 sm:py-12",
  normal: "py-20 sm:py-24",
  spacious: "py-24 sm:py-32",
};

const CONTAINER: Record<string, string> = {
  narrow: "max-w-3xl",
  normal: "max-w-6xl",
  wide: "max-w-7xl",
  full: "max-w-none",
};

const ALIGN: Record<string, string> = {
  start: "text-start",
  center: "text-center",
  end: "text-end",
};

const THEME: Record<string, string> = {
  inverted: "bg-foreground text-background",
  muted: "bg-card/40",
  primary: "bg-primary/5",
};

/** Tailwind classes that hide a block on the device sizes the admin turned off. */
function deviceHiddenClasses(v: HomepageSection["visibility"]): string {
  if (!v) return "";
  return cn(
    !v.mobile && "max-md:hidden",
    !v.tablet && "md:max-lg:hidden",
    !v.desktop && "lg:hidden",
  );
}

export function BlockShell({
  section,
  id,
  children,
  className,
}: {
  section: HomepageSection;
  id?: string;
  children: ReactNode;
  className?: string;
}) {
  const { locale } = useI18n();
  const p = section.presentation ?? {};
  const bg = p.background ?? null;

  const spacing = SPACING[p.spacing ?? "normal"] ?? SPACING.normal;
  const container = CONTAINER[p.container_width ?? "normal"] ?? CONTAINER.normal;
  const align = ALIGN[p.alignment ?? ""] ?? "";
  const theme = THEME[p.theme_variant ?? ""] ?? "";
  const animated = p.animation && p.animation !== "none";

  const ariaLabel = section.accessibility_label ? pickLocale(section.accessibility_label, locale) : undefined;

  const style: CSSProperties = {};
  if (bg?.color) style.backgroundColor = bg.color;
  if (bg?.image) {
    style.backgroundImage = `url(${bg.image})`;
    style.backgroundSize = "cover";
    style.backgroundPosition = "center";
  }

  const inner = <div className={cn("relative mx-auto w-full px-4", container, align)}>{children}</div>;

  return (
    <section
      id={id}
      aria-label={ariaLabel}
      style={Object.keys(style).length ? style : undefined}
      className={cn("relative", spacing, theme, deviceHiddenClasses(section.visibility), className)}
    >
      {bg?.overlay ? (
        <div className="pointer-events-none absolute inset-0" style={{ background: bg.overlay }} aria-hidden />
      ) : null}
      {animated ? <Reveal className="relative">{inner}</Reveal> : inner}
    </section>
  );
}

/** Bilingual, centered heading + optional subheading used by most expansion blocks. */
export function BlockHeading({
  heading,
  subheading,
}: {
  heading?: { en: string; ar: string };
  subheading?: { en: string; ar: string };
}) {
  const { locale } = useI18n();
  if (!heading && !subheading) return null;

  return (
    <div className="mb-10">
      {heading ? (
        <h2 className="font-serif text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
          {pickLocale(heading, locale)}
        </h2>
      ) : null}
      {subheading ? (
        <p className="mt-3 max-w-2xl text-muted-foreground">{pickLocale(subheading, locale)}</p>
      ) : null}
    </div>
  );
}
