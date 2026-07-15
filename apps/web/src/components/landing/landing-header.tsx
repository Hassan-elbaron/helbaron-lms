"use client";

import Link from "next/link";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { brandTheme, pickLocale } from "@/config/theme";
import { useBranding } from "@/lib/branding/context";
import { useNavigation } from "@/lib/navigation/hooks";
import { safeRel } from "@/lib/navigation/api";
import { Button } from "@/components/ui/button";
import { LangToggle } from "@/components/layout/lang-toggle";
import { ThemeToggle } from "@/components/layout/theme-toggle";

export function LandingHeader() {
  const { locale } = useI18n();
  const { status } = useAuth();
  const branding = useBranding();
  const authed = status === "authenticated";
  // Brand name comes from the admin Branding settings; falls back to the built-in brand.
  const brandName = pickLocale(branding.identity.brand_name, locale) || brandTheme.name;
  const logo = branding.logos.logo_light;

  // Prefer the admin-managed CMS header nav; fall back to the built-in brandTheme.nav.
  const cmsNav = useNavigation("public-header");
  const navLinks = cmsNav
    ? cmsNav.map((n) => ({
        key: n.id,
        href: n.url,
        label: pickLocale(n.label, locale),
        external: n.url_type === "external",
        target: n.target,
        rel: safeRel(n),
      }))
    : brandTheme.nav.map((l) => ({
        key: l.href,
        href: l.href,
        label: pickLocale(l.label, locale),
        external: false as boolean,
        target: undefined as "_blank" | "_self" | undefined,
        rel: undefined as string | undefined,
      }));

  return (
    <header className="sticky top-0 z-40 border-b bg-background/85 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
        <Link href="/" className="flex items-center gap-2">
          {logo ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={logo} alt={brandName} width={120} height={32} className="h-8 w-auto" decoding="async" />
          ) : (
            <span className="flex size-8 items-center justify-center rounded-lg bg-primary font-serif text-sm font-bold text-primary-foreground">
              {brandName.charAt(0)}
            </span>
          )}
          <span className="font-serif text-lg font-semibold tracking-tight">{brandName}</span>
        </Link>

        <nav className="hidden items-center gap-1 lg:flex" aria-label="Main">
          {navLinks.map((l) =>
            l.external ? (
              <a
                key={l.key}
                href={l.href}
                target={l.target ?? "_blank"}
                rel={l.rel ?? "noopener noreferrer"}
                className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
              >
                {l.label}
              </a>
            ) : (
              <Link
                key={l.key}
                href={l.href}
                className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
              >
                {l.label}
              </Link>
            ),
          )}
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
