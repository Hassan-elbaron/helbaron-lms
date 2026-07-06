"use client";

import Link from "next/link";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import { Button } from "@/components/ui/button";
import { LangToggle } from "@/components/layout/lang-toggle";
import { ThemeToggle } from "@/components/layout/theme-toggle";

export function LandingHeader() {
  const { locale } = useI18n();
  const { status } = useAuth();
  const authed = status === "authenticated";

  return (
    <header className="sticky top-0 z-40 border-b bg-background/85 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
        <Link href="/" className="flex items-center gap-2">
          <span className="flex size-8 items-center justify-center rounded-lg bg-primary font-serif text-sm font-bold text-primary-foreground">H</span>
          <span className="font-serif text-lg font-semibold tracking-tight">{brandTheme.name}</span>
        </Link>

        <nav className="hidden items-center gap-1 lg:flex" aria-label="Main">
          {brandTheme.nav.map((l) => (
            <Link
              key={l.href}
              href={l.href}
              className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
              {pickLocale(l.label, locale)}
            </Link>
          ))}
        </nav>

        <div className="ms-auto flex items-center gap-1">
          <LangToggle />
          <ThemeToggle />
          <Button asChild size="sm" variant="ghost">
            <Link href={authed ? "/dashboard" : "/login"}>{pickLocale(brandTheme.ctas.signIn, locale)}</Link>
          </Button>
          <Button asChild size="sm">
            <Link href={authed ? "/dashboard" : "/register"}>{pickLocale(brandTheme.ctas.startFree, locale)}</Link>
          </Button>
        </div>
      </div>
    </header>
  );
}
