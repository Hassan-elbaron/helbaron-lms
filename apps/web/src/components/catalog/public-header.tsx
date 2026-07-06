"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ShoppingCart } from "lucide-react";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { siteConfig } from "@/config/site";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { LangToggle } from "@/components/layout/lang-toggle";
import { ThemeToggle } from "@/components/layout/theme-toggle";

export function PublicHeader() {
  const { t } = useI18n();
  const { status } = useAuth();
  const pathname = usePathname();

  const links = [
    { href: "/courses", label: t("catalog.nav.courses") },
    { href: "/products", label: t("commerce.nav.products") },
    { href: "/categories", label: t("catalog.nav.categories") },
    { href: "/trainers", label: t("catalog.nav.trainers") },
  ];

  return (
    <header className="sticky top-0 z-40 border-b bg-background/80 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
        <Link href="/" className="flex items-center gap-2">
          <span className="flex size-8 items-center justify-center rounded-lg bg-primary font-serif text-sm font-bold text-primary-foreground">H</span>
          <span className="font-serif text-lg font-semibold tracking-tight">{siteConfig.name}</span>
        </Link>
        <nav className="hidden items-center gap-1 md:flex" aria-label="Catalog">
          {links.map((l) => {
            const active = pathname === l.href || pathname.startsWith(`${l.href}/`);
            return (
              <Link
                key={l.href}
                href={l.href}
                className={cn(
                  "rounded-md px-3 py-2 text-sm font-medium transition-colors",
                  active ? "bg-accent text-accent-foreground" : "text-muted-foreground hover:text-foreground",
                )}
              >
                {l.label}
              </Link>
            );
          })}
        </nav>
        <div className="ms-auto flex items-center gap-1">
          <Button asChild size="icon" variant="ghost" aria-label={t("commerce.nav.cart")}>
            <Link href="/cart"><ShoppingCart className="size-5" aria-hidden /></Link>
          </Button>
          <LangToggle />
          <ThemeToggle />
          <Button asChild size="sm" variant={status === "authenticated" ? "outline" : "default"}>
            <Link href={status === "authenticated" ? "/dashboard" : "/login"}>
              {status === "authenticated" ? t("nav.dashboard") : t("catalog.nav.signIn")}
            </Link>
          </Button>
        </div>
      </div>
    </header>
  );
}
