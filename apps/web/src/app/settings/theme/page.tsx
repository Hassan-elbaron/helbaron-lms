"use client";

import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ThemeToggle } from "@/components/layout/theme-toggle";
import { LangToggle } from "@/components/layout/lang-toggle";

const SWATCHES: { key: keyof typeof brandTheme.colors; label: string }[] = [
  { key: "primary", label: "Primary — deep teal" },
  { key: "copper", label: "Copper accent" },
  { key: "gold", label: "Gold accent" },
  { key: "background", label: "Background — cream" },
  { key: "card", label: "Card" },
  { key: "secondary", label: "Secondary sand" },
  { key: "foreground", label: "Foreground" },
  { key: "border", label: "Border" },
];

const TOKENS = [
  "--background", "--foreground", "--card", "--primary", "--primary-foreground",
  "--secondary", "--muted", "--accent", "--copper", "--gold",
  "--destructive", "--success", "--warning", "--border", "--ring", "--radius",
];

function Preview({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{title}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">{children}</CardContent>
    </Card>
  );
}

export default function ThemeSettingsPage() {
  const { locale } = useI18n();

  return (
    <div className="min-h-dvh bg-background">
      <header className="border-b bg-background/85 backdrop-blur">
        <div className="mx-auto flex h-16 max-w-5xl items-center gap-3 px-4">
          <Button asChild variant="ghost" size="sm">
            <Link href="/"><ArrowLeft className="size-4 rtl:rotate-180" aria-hidden /> {brandTheme.name}</Link>
          </Button>
          <span className="ms-auto flex items-center gap-1">
            <LangToggle />
            <ThemeToggle />
          </span>
        </div>
      </header>

      <main className="mx-auto max-w-5xl space-y-8 px-4 py-10">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-copper">Theme &amp; Identity</p>
          <h1 className="mt-2 font-serif text-3xl font-semibold tracking-tight">{brandTheme.name} brand system</h1>
          <p className="mt-2 text-muted-foreground">
            Read-only preview of the active theme. Values come from the frontend config layer
            (<code className="text-foreground">src/config/theme.ts</code>) and CSS tokens
            (<code className="text-foreground">globals.css</code>). Editing from the dashboard will connect
            once a settings API exists.
          </p>
        </div>

        {/* Identity summary */}
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {([
            ["Brand", brandTheme.name],
            ["Heading font", brandTheme.fonts.heading],
            ["Body font", brandTheme.fonts.body],
            ["Radius", brandTheme.radius],
          ] as const).map(([k, v]) => (
            <Card key={k}>
              <CardContent className="p-5">
                <div className="text-xs text-muted-foreground">{k}</div>
                <div className="mt-1 font-medium">{v}</div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Colors */}
        <section>
          <h2 className="mb-4 font-serif text-xl font-semibold">Brand colors</h2>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {SWATCHES.map((s) => (
              <div key={s.key} className="overflow-hidden rounded-xl border">
                <div className="h-20 w-full" style={{ backgroundColor: brandTheme.colors[s.key] }} />
                <div className="p-3">
                  <div className="text-sm font-medium">{s.label}</div>
                  <div className="text-xs uppercase text-muted-foreground">{brandTheme.colors[s.key]}</div>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Component previews */}
        <div className="grid gap-4 lg:grid-cols-2">
          <Preview title="Typography">
            <h1 className="font-serif text-3xl font-semibold">Master the core.</h1>
            <h2 className="font-serif text-xl font-semibold italic text-primary">Lead the future.</h2>
            <p className="text-sm text-muted-foreground">
              Clean sans body text (Inter) paired with elegant serif headings (Fraunces) for a premium academy feel.
            </p>
          </Preview>

          <Preview title="Buttons">
            <div className="flex flex-wrap gap-2">
              <Button>Primary</Button>
              <Button variant="secondary">Secondary</Button>
              <Button variant="outline">Outline</Button>
              <Button variant="ghost">Ghost</Button>
              <Button variant="destructive">Destructive</Button>
            </div>
          </Preview>

          <Preview title="Badges">
            <div className="flex flex-wrap gap-2">
              <Badge>Default</Badge>
              <Badge variant="secondary">Secondary</Badge>
              <Badge className="bg-copper text-copper-foreground">HOT</Badge>
              <Badge className="bg-gold text-gold-foreground">Gold</Badge>
              <Badge variant="success">Success</Badge>
              <Badge variant="warning">Warning</Badge>
              <Badge variant="outline">Outline</Badge>
            </div>
          </Preview>

          <Preview title="Form">
            <div className="space-y-1.5">
              <Label htmlFor="tp-email">Email</Label>
              <Input id="tp-email" placeholder="name@company.com" />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="tp-name">Full name</Label>
              <Input id="tp-name" placeholder="HElbaron learner" />
            </div>
            <Button className="w-full">Submit</Button>
          </Preview>
        </div>

        {/* Landing section preview */}
        <section>
          <h2 className="mb-4 font-serif text-xl font-semibold">Landing preview</h2>
          <div className="overflow-hidden rounded-2xl border">
            <div className="bg-primary px-6 py-10 text-primary-foreground">
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gold">
                {pickLocale(brandTheme.hero.eyebrow, locale)}
              </p>
              <h3 className="mt-3 font-serif text-3xl font-semibold">
                {pickLocale(brandTheme.hero.headlineLine1, locale)}{" "}
                <span className="italic text-gold">{pickLocale(brandTheme.hero.headlineLine2, locale)}</span>
              </h3>
            </div>
            <div className="grid gap-3 bg-card p-6 sm:grid-cols-3">
              {brandTheme.stats.slice(0, 3).map((s) => (
                <div key={s.display} className="rounded-xl border p-4 text-center">
                  <div className="font-serif text-2xl font-semibold text-primary">{s.display}</div>
                  <div className="text-xs text-muted-foreground">{pickLocale(s.label, locale)}</div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Token list */}
        <section>
          <h2 className="mb-4 font-serif text-xl font-semibold">Theme tokens</h2>
          <div className="grid gap-2 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-3">
            {TOKENS.map((tk) => (
              <div key={tk} className="flex items-center gap-3 text-sm">
                <span className="size-4 rounded border" style={{ backgroundColor: `var(${tk})` }} />
                <code className="text-muted-foreground">{tk}</code>
              </div>
            ))}
          </div>
        </section>
      </main>
    </div>
  );
}
