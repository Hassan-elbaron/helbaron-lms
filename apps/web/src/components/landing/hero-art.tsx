/**
 * Editorial "academy" hero illustration — pure SVG, themed via CSS variables (adapts to dark mode).
 * Decorative: an open book, floating graduation cap, a growth chart, and a certificate seal.
 */
export function HeroArt({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 520 480" className={className} role="img" aria-label="HElbaron academy illustration">
      {/* soft backdrop */}
      <circle cx="270" cy="240" r="210" fill="var(--primary)" opacity="0.06" />
      <circle cx="270" cy="240" r="150" fill="none" stroke="var(--gold)" strokeOpacity="0.25" strokeDasharray="3 9" className="animate-[hb-spin_60s_linear_infinite]" style={{ transformOrigin: "270px 240px" }} />

      {/* growth chart card */}
      <g className="animate-float-slow" style={{ transformOrigin: "150px 330px" }}>
        <rect x="70" y="290" width="180" height="120" rx="16" fill="var(--card)" stroke="var(--border)" />
        <rect x="92" y="360" width="20" height="30" rx="4" fill="var(--primary)" opacity="0.85" />
        <rect x="122" y="345" width="20" height="45" rx="4" fill="var(--copper)" />
        <rect x="152" y="330" width="20" height="60" rx="4" fill="var(--gold)" />
        <rect x="182" y="315" width="20" height="75" rx="4" fill="var(--primary)" />
        <path d="M92 350 L132 335 L172 320 L212 300" fill="none" stroke="var(--copper)" strokeWidth="3" strokeLinecap="round" />
        <circle cx="212" cy="300" r="4" fill="var(--copper)" />
      </g>

      {/* open book */}
      <g className="animate-float" style={{ transformOrigin: "300px 220px" }}>
        <path d="M180 160 Q300 120 420 160 L420 300 Q300 260 180 300 Z" fill="var(--card)" stroke="var(--border)" />
        <path d="M300 138 L300 278" stroke="var(--primary)" strokeWidth="4" />
        <g stroke="var(--muted-foreground)" strokeOpacity="0.4" strokeWidth="3" strokeLinecap="round">
          <path d="M210 178 H280" /><path d="M210 200 H280" /><path d="M210 222 H270" />
          <path d="M320 178 H390" /><path d="M320 200 H390" /><path d="M320 222 H380" />
        </g>
      </g>

      {/* graduation cap */}
      <g className="animate-float-slow" style={{ transformOrigin: "360px 110px" }}>
        <path d="M300 100 L360 78 L420 100 L360 122 Z" fill="var(--primary)" />
        <path d="M336 114 L336 140 Q360 152 384 140 L384 114" fill="none" stroke="var(--primary)" strokeWidth="6" />
        <line x1="420" y1="100" x2="420" y2="132" stroke="var(--gold)" strokeWidth="3" />
        <circle cx="420" cy="136" r="6" fill="var(--gold)" />
      </g>

      {/* certificate seal */}
      <g className="animate-float" style={{ transformOrigin: "410px 340px" }}>
        <circle cx="410" cy="340" r="30" fill="var(--copper)" />
        <circle cx="410" cy="340" r="30" fill="none" stroke="var(--gold)" strokeWidth="2" strokeDasharray="4 4" />
        <path d="M402 340 l6 6 l12 -14" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
        <path d="M398 366 l6 20 l6 -10 l6 10 l6 -20" fill="var(--copper)" />
      </g>

      {/* sparkles */}
      <g fill="var(--gold)">
        <circle cx="120" cy="150" r="4" className="animate-float" />
        <circle cx="460" cy="220" r="5" className="animate-float-slow" />
        <circle cx="150" cy="250" r="3" className="animate-float-slow" />
      </g>
    </svg>
  );
}
