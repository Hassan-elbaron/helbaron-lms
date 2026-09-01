"use client";

import Link from "next/link";
import { useBranding } from "@/lib/branding/context";
import { Play, Bookmark } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { pickLocale } from "@/config/theme";
import { CoverArt } from "./cover-art";
import { CarvedSurface } from "./carved-surface";
import { InstructorAvatars } from "./instructor-avatars";
import { usePointerDepth } from "./use-pointer-depth";
import { FAMILY_FIELD } from "./palette";
import { hashString } from "./seed";
import { derivePressCode, deriveTier, toRoman } from "./adapter";
import type { CoverCourse, CoverWave } from "./types";

/** Zero-pad a number to `len` digits, e.g. pad(7, 2) -> "07". */
const pad = (n: number, len: number): string => String(n).padStart(len, "0");

/**
 * Course cover — the course thumbnail (or a branded generative field when none is set)
 * with a carved cream wave, the instructor avatars riding the wave (each a link to that
 * instructor), and the course title + faculty names on the cream. The card body is one course
 * link; avatars sit above it as separate links. Bilingual + RTL-aware; motion off under
 * `prefers-reduced-motion`.
 */
export function CourseCover({
  course,
  wave = "cradle",
  index,
  onPreview,
  minimal = false,
  className,
}: {
  course: CoverCourse;
  wave?: CoverWave;
  /** 1-based card position, drives the large Roman numeral. Defaults to a stable hash of the id. */
  index?: number;
  onPreview?: (id: string) => void;
  /** Compact treatment: shows only the title + instructors (no masthead / subtitle / footer), shorter card. */
  minimal?: boolean;
  className?: string;
}) {
  const { locale } = useI18n();
  const { onPointerMove, onPointerLeave } = usePointerDepth();

  // These marks are rendered into EVERY generated cover image, so a vendor string here is
  // baked into the artwork itself rather than merely displayed beside it.
  const coverBrand = useBranding().identity.brand_name.en.toUpperCase();
  const coverSigil = coverBrand.replace(/[^A-Z]/g, "").slice(0, 3) || "ACA";

  const title = pickLocale(course.title, locale);
  const level = course.level ? pickLocale(course.level, locale) : null;
  const subtitle = course.subtitle ? pickLocale(course.subtitle, locale) : null;
  const school = course.school ? pickLocale(course.school, locale) : null;
  const field = FAMILY_FIELD[course.family];
  const folio = course.folio ?? ((hashString(course.id) % 60) + 1);
  const label = level ? `${title} — ${level}` : title;
  const names = course.instructors.map((i) => i.name).join(" · ");

  // Editorial masthead values — all pure + deterministic per course.
  const pressCode = derivePressCode(course.title.en, course.id, coverSigil);
  const tier = deriveTier(course.level?.en ?? level);
  const idx = index ?? ((hashString(course.id) % 20) + 1);
  const roman = toRoman(idx);
  const h = hashString(course.id);
  const readoutX = ((h % 100) / 100).toFixed(2);
  const readoutY = (((h >>> 8) % 100) / 100).toFixed(2);
  const readoutRef = pad(h % 1000, 3);

  return (
    <article className={`hb-cover-root hb-cover-${wave} ${minimal ? "hb-cover-minimal" : ""} ${className ?? ""}`}>
      <div
        onPointerMove={onPointerMove}
        onPointerLeave={onPointerLeave}
        className="hb-cover-stage"
        style={{ backgroundColor: field.to }}
      >
        {/* image field: real thumbnail, else a branded generative field */}
        <div className="hb-cover-image">
          {course.thumbnail ? (
            <span
              className="hb-cover-photo"
              style={{ backgroundImage: `url("${course.thumbnail}")` }}
              aria-hidden="true"
            />
          ) : (
            <>
              <span
                className="hb-cover-photo"
                style={{
                  backgroundImage: `radial-gradient(130% 90% at 72% -6%, ${field.from} 0%, ${field.to} 60%, #0b1422 100%)`,
                }}
                aria-hidden="true"
              />
              <CoverArt
                family={course.family}
                seed={course.id}
                className="hb-cover-art hb-cover-layer absolute inset-0 h-full w-full"
              />
            </>
          )}
          {/* navy scrim over the top of the photo so the light press text stays legible */}
          <span className="hb-cover-scrim" aria-hidden="true" />

          {/* editorial masthead — brand Press / Institute of Practice (hidden in minimal) */}
          {!minimal && (
          <div className="hb-cover-mast">
            <div className="hb-cover-mast-left">
              <span className="hb-cover-mast-code">{pressCode}</span>
              <span className="hb-cover-mast-tier">{tier}</span>
              <span className="hb-cover-mast-readout">
                <span>{`x:${readoutX} y:${readoutY} h:—`}</span>
                <span>{`${coverSigil}·STR·${readoutRef}`}</span>
                <span>MISSION · MMXXVI</span>
              </span>
            </div>
            <div className="hb-cover-mast-right">
              <Bookmark className="hb-cover-mast-mark" aria-hidden="true" />
              <span className="hb-cover-mast-press">{`${coverBrand} · PRESS`}</span>
              <span className="hb-cover-mast-edition">FIRST EDITION · N° 1/1200</span>
              <span className="hb-cover-mast-vol">IN THREE VOLUMES</span>
            </div>
          </div>
          )}

          {/* large faint Roman numeral, mid-right of the upper zone (hidden in minimal) */}
          {!minimal && <span className="hb-cover-roman" aria-hidden="true">{roman}</span>}
        </div>

        {/* carved cream wave */}
        <CarvedSurface wave={wave} className="hb-cover-carve" />

        {/* cream body: course title + subtitle + faculty names + press footer */}
        <div className="hb-cover-body">
          <h3 className="hb-cover-title">{title}</h3>
          {!minimal && subtitle ? <p className="hb-cover-subtitle">{subtitle}</p> : null}
          {names ? <p className="hb-cover-names">{names}</p> : null}
          {/* Price sits with the faculty line so the editorial frame keeps its typographic rhythm. */}
          {course.price ? <p className="hb-cover-price">{course.price}</p> : null}
          {!minimal && (
          <p className="hb-cover-footer">
            <span className="hb-cover-footer-l">{`${coverBrand} · INSTITUTE OF PRACTICE`}</span>
            <span className="hb-cover-footer-r">{school ?? `MMXXVI · fol. ${pad(folio, 2)}`}</span>
          </p>
          )}
        </div>

        {/* the whole card links to the course (below the avatars) */}
        <Link href={course.href} aria-label={label} className="hb-cover-hit" />

        {/* instructor avatars riding the wave (above the course link) */}
        <InstructorAvatars instructors={course.instructors} className="hb-cover-avatars" />

        {/* pointer specular sheen */}
        <span className="hb-cover-sheen" aria-hidden="true" />

        {onPreview ? (
          <button
            type="button"
            onClick={() => onPreview(course.id)}
            className="hb-cover-play"
            aria-label={locale === "ar" ? `تشغيل معاينة ${title}` : `Play preview of ${title}`}
          >
            <Play className="size-5 translate-x-0.5 fill-current" aria-hidden="true" />
          </button>
        ) : null}
      </div>
    </article>
  );
}
