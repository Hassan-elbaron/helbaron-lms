import type { Swatch } from "@/config/theme";

const VAR: Record<Swatch, string> = {
  teal: "var(--primary)",
  copper: "var(--copper)",
  gold: "var(--gold)",
  red: "var(--destructive)",
};

/** Designed 16:9 SVG course thumbnail (gradient + code + abstract shapes). Adapts to theme tokens. */
export function CourseThumb({ code, color, className }: { code: string; color: Swatch; className?: string }) {
  const c = VAR[color];
  const gid = `g-${code}-${color}`;
  return (
    <svg viewBox="0 0 400 225" className={className} role="img" aria-hidden preserveAspectRatio="xMidYMid slice">
      <defs>
        <linearGradient id={gid} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stopColor={c} stopOpacity="0.95" />
          <stop offset="1" stopColor="var(--primary)" stopOpacity="0.9" />
        </linearGradient>
      </defs>
      <rect width="400" height="225" fill={`url(#${gid})`} />
      <circle cx="330" cy="40" r="70" fill="#fff" opacity="0.07" />
      <circle cx="60" cy="200" r="90" fill="#000" opacity="0.06" />
      <g stroke="#fff" strokeOpacity="0.14" strokeWidth="1.5" fill="none">
        <path d="M0 170 C 90 140, 150 190, 240 150 S 360 120, 400 150" />
        <path d="M0 195 C 90 165, 150 215, 240 175 S 360 145, 400 175" />
      </g>
      <text x="28" y="150" fontFamily="var(--font-serif, Georgia, serif)" fontSize="112" fontWeight="700" fill="#fff" fillOpacity="0.92">{code}</text>
      <rect x="28" y="176" width="46" height="6" rx="3" fill="var(--gold)" />
    </svg>
  );
}
