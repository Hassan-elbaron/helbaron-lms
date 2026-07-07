# HElbaron — Next.js route-group restructure (Refactor STEP 2)
# Source of truth: docs/refactor/01_DOMAIN_MAP.md, 02_ROUTE_RESTRUCTURE.md, 03_FOLDER_STRUCTURE.md
# History-preserving migration. Run from repo root:
#   powershell -ExecutionPolicy Bypass -File scripts/route-migration.ps1
# Requires git + node/npm. Commit or stash WIP first. If a previous run half-applied, run
#   git reset --hard HEAD   (restores moved pages; leaves untracked docs alone)
# then re-run this script.

$ErrorActionPreference = "Stop"
$repo = Split-Path -Parent $PSScriptRoot
$app  = Join-Path $repo "apps\web\src\app"
$web  = Join-Path $repo "apps\web"
$utf8 = New-Object System.Text.UTF8Encoding($false)   # UTF-8, no BOM (safe for Arabic)
Set-Location $repo

function WriteText($path, $text) {
  $dir = Split-Path -Parent $path
  if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
  [System.IO.File]::WriteAllText($path, $text, $utf8)
}
function ReadText($path) { return [System.IO.File]::ReadAllText($path) }

function GitMove($from, $to) {
  $src = Join-Path $app $from
  $dst = Join-Path $app $to
  if (-not (Test-Path $src)) { Write-Host "skip (missing): $from"; return }
  $dstDir = Split-Path -Parent $dst
  if (-not (Test-Path $dstDir)) { New-Item -ItemType Directory -Force -Path $dstDir | Out-Null }
  git mv -f -- $src $dst
  Write-Host "moved: $from -> $to"
}
function NewFile($rel, $content) { WriteText (Join-Path $app $rel) $content; Write-Host "created: $rel" }
function DelPath($rel) {
  $p = Join-Path $app $rel
  if (Test-Path $p) { git rm -r -f -- $p 2>$null | Out-Null; if (Test-Path $p) { Remove-Item -Recurse -Force $p }; Write-Host "deleted: $rel" }
}

Write-Host "== 1) Create destination directories =="
$dirs = @(
  "(marketing)\(site)","(marketing)\(auth)",
  "(learning)\(app)","(learning)\(player)",
  "(account)","(commerce)","(instructor)","(organization)"
)
foreach ($d in $dirs) { New-Item -ItemType Directory -Force -Path (Join-Path $app $d) | Out-Null }

Write-Host "== 2) Move pages (git mv) =="
GitMove "(public)\courses\page.tsx"                    "(marketing)\(site)\courses\page.tsx"
GitMove "(public)\courses\[public_id]\page.tsx"        "(marketing)\(site)\courses\[public_id]\page.tsx"
GitMove "(public)\categories\page.tsx"                 "(marketing)\(site)\categories\page.tsx"
GitMove "(public)\trainers\page.tsx"                   "(marketing)\(site)\trainers\page.tsx"
GitMove "(public)\products\page.tsx"                   "(marketing)\(site)\products\page.tsx"
GitMove "(public)\cohorts\page.tsx"                    "(marketing)\(site)\cohorts\page.tsx"
GitMove "(public)\workshops\page.tsx"                  "(marketing)\(site)\workshops\page.tsx"
GitMove "(public)\enterprise\page.tsx"                 "(marketing)\(site)\enterprise\page.tsx"
GitMove "(public)\advisory\page.tsx"                   "(marketing)\(site)\advisory\page.tsx"
GitMove "(public)\privacy\page.tsx"                    "(marketing)\(site)\privacy\page.tsx"
GitMove "(public)\terms\page.tsx"                      "(marketing)\(site)\terms\page.tsx"
GitMove "(auth)\login\page.tsx"                        "(marketing)\(auth)\login\page.tsx"
GitMove "(auth)\register\page.tsx"                     "(marketing)\(auth)\register\page.tsx"
GitMove "(auth)\forgot-password\page.tsx"              "(marketing)\(auth)\forgot-password\page.tsx"
GitMove "(auth)\reset-password\page.tsx"               "(marketing)\(auth)\reset-password\page.tsx"
GitMove "(onboarding)\verify-email\page.tsx"           "(marketing)\(auth)\verify-email\page.tsx"
GitMove "(onboarding)\mfa\page.tsx"                    "(marketing)\(auth)\mfa\page.tsx"
GitMove "(student)\dashboard\page.tsx"                 "(learning)\(app)\dashboard\page.tsx"
GitMove "(student)\my-learning\page.tsx"              "(learning)\(app)\my-learning\page.tsx"
GitMove "(student)\continue-learning\page.tsx"       "(learning)\(app)\continue-learning\page.tsx"
GitMove "(student)\certificates\page.tsx"            "(learning)\(app)\certificates\page.tsx"
GitMove "(public)\courses\[public_id]\learn\page.tsx" "(learning)\(player)\learn\[public_id]\page.tsx"
GitMove "(public)\lessons\[public_id]\page.tsx"       "(learning)\(player)\lessons\[public_id]\page.tsx"
GitMove "(student)\profile\page.tsx"                  "(account)\profile\page.tsx"
GitMove "(student)\notifications\page.tsx"           "(account)\notifications\page.tsx"
GitMove "(public)\cart\page.tsx"                      "(commerce)\cart\page.tsx"
GitMove "(public)\checkout\page.tsx"                  "(commerce)\checkout\page.tsx"
GitMove "(public)\checkout\success\page.tsx"          "(commerce)\checkout\success\page.tsx"
GitMove "(public)\checkout\failed\page.tsx"           "(commerce)\checkout\failed\page.tsx"
GitMove "(public)\orders\page.tsx"                    "(commerce)\orders\page.tsx"
GitMove "(public)\contracts\page.tsx"                 "(commerce)\contracts\page.tsx"
GitMove "(org)\org\page.tsx"                          "(organization)\org\page.tsx"
GitMove "(org)\org\organizations\page.tsx"            "(organization)\org\organizations\page.tsx"
GitMove "(org)\org\organizations\[public_id]\page.tsx" "(organization)\org\organizations\[public_id]\page.tsx"
GitMove "(org)\org\consulting\page.tsx"               "(organization)\org\consulting\page.tsx"
GitMove "(crm)\crm\organizations\page.tsx"            "(crm)\crm\accounts\page.tsx"

Write-Host "== 3) Rewrite test imports to new route groups =="
# Lesson URL is preserved (/lessons/[public_id]); no param rename needed. Update test import paths.
$testMap = @{
  '@/app/(public)/courses/[public_id]/learn/page' = '@/app/(learning)/(player)/learn/[public_id]/page';
  '@/app/(public)/courses/[public_id]/page'        = '@/app/(marketing)/(site)/courses/[public_id]/page';
  '@/app/(public)/courses/page'                    = '@/app/(marketing)/(site)/courses/page';
  '@/app/(public)/categories/page'                 = '@/app/(marketing)/(site)/categories/page';
  '@/app/(public)/trainers/page'                   = '@/app/(marketing)/(site)/trainers/page';
  '@/app/(public)/products/page'                   = '@/app/(marketing)/(site)/products/page';
  '@/app/(public)/lessons/[public_id]/page'        = '@/app/(learning)/(player)/lessons/[public_id]/page';
  '@/app/(public)/cart/page'                       = '@/app/(commerce)/cart/page';
  '@/app/(public)/checkout/page'                   = '@/app/(commerce)/checkout/page';
  '@/app/(public)/orders/page'                     = '@/app/(commerce)/orders/page';
  '@/app/(public)/contracts/page'                  = '@/app/(commerce)/contracts/page';
  '@/app/(auth)/login/page'                        = '@/app/(marketing)/(auth)/login/page';
  '@/app/(auth)/register/page'                     = '@/app/(marketing)/(auth)/register/page';
  '@/app/(auth)/forgot-password/page'              = '@/app/(marketing)/(auth)/forgot-password/page';
  '@/app/(auth)/reset-password/page'               = '@/app/(marketing)/(auth)/reset-password/page';
  '@/app/(onboarding)/verify-email/page'           = '@/app/(marketing)/(auth)/verify-email/page';
  '@/app/(onboarding)/mfa/page'                    = '@/app/(marketing)/(auth)/mfa/page';
  '@/app/(student)/dashboard/page'                 = '@/app/(learning)/(app)/dashboard/page';
  '@/app/(student)/my-learning/page'               = '@/app/(learning)/(app)/my-learning/page';
  '@/app/(student)/continue-learning/page'         = '@/app/(learning)/(app)/continue-learning/page';
  '@/app/(student)/certificates/page'              = '@/app/(learning)/(app)/certificates/page';
  '@/app/(student)/profile/page'                   = '@/app/(account)/profile/page';
  '@/app/(student)/notifications/page'             = '@/app/(account)/notifications/page';
  '@/app/(org)/org/organizations/[public_id]/page' = '@/app/(organization)/org/organizations/[public_id]/page';
  '@/app/(org)/org/organizations/page'             = '@/app/(organization)/org/organizations/page';
  '@/app/(org)/org/consulting/page'                = '@/app/(organization)/org/consulting/page';
  '@/app/(org)/org/page'                           = '@/app/(organization)/org/page';
  '@/app/(crm)/crm/organizations/page'             = '@/app/(crm)/crm/accounts/page'
}
$testsDir = Join-Path $web "tests"
if (Test-Path $testsDir) {
  Get-ChildItem -Path $testsDir -Recurse -Include *.tsx,*.ts | ForEach-Object {
    $c = ReadText $_.FullName; $orig = $c
    foreach ($k in $testMap.Keys) { $c = $c.Replace($k, $testMap[$k]) }
    if ($c -ne $orig) { WriteText $_.FullName $c; Write-Host ("test imports updated: " + $_.Name) }
  }
}

Write-Host "== 4) Create shared route-state components =="
WriteText (Join-Path $web "src\components\route\route-loading.tsx") @'
export default function RouteLoading() {
  return (
    <div className="flex min-h-[40vh] w-full items-center justify-center">
      <div className="size-6 animate-spin rounded-full border-2 border-primary border-t-transparent" aria-label="Loading" />
    </div>
  );
}
'@
WriteText (Join-Path $web "src\components\route\route-error.tsx") @'
"use client";

export default function RouteError({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return (
    <div className="flex min-h-[40vh] w-full flex-col items-center justify-center gap-4 p-8 text-center">
      <p className="font-medium">Something went wrong.</p>
      <p className="max-w-md text-sm text-muted-foreground">{error?.message ?? "Unexpected error."}</p>
      <button onClick={reset} className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
        Try again
      </button>
    </div>
  );
}
'@
Write-Host "created: components/route/route-loading.tsx, route-error.tsx"

$loadingBody = 'export { default } from "@/components/route/route-loading";' + "`n"
$errorBody   = '"use client";' + "`n" + 'export { default } from "@/components/route/route-error";' + "`n"

Write-Host "== 5) Create per-group layouts / loading / error =="
NewFile "(marketing)\loading.tsx" $loadingBody
NewFile "(marketing)\error.tsx"   $errorBody

NewFile "(marketing)\(site)\layout.tsx" @'
import type { ReactNode } from "react";
import { AnnouncementBar } from "@/components/landing/announcement-bar";
import { LandingHeader } from "@/components/landing/landing-header";
import { LandingFooter } from "@/components/landing/landing-footer";
import { PageTransition } from "@/components/layout/page-transition";

export default function SiteLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-dvh flex-col">
      <AnnouncementBar />
      <LandingHeader />
      <main className="flex-1">
        <PageTransition className="mx-auto w-full max-w-6xl px-4 py-10">{children}</PageTransition>
      </main>
      <LandingFooter />
    </div>
  );
}
'@
NewFile "(marketing)\(site)\loading.tsx" $loadingBody
NewFile "(marketing)\(site)\error.tsx"   $errorBody

NewFile "(marketing)\(auth)\layout.tsx" @'
import type { ReactNode } from "react";
import { RequireGuest } from "@/lib/auth/guards";

export default function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <RequireGuest redirectTo="/">
      <div className="flex min-h-dvh items-center justify-center p-4">
        <div className="w-full max-w-md">{children}</div>
      </div>
    </RequireGuest>
  );
}
'@
NewFile "(marketing)\(auth)\loading.tsx" $loadingBody
NewFile "(marketing)\(auth)\error.tsx"   $errorBody

NewFile "(learning)\layout.tsx" @'
"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";

export default function LearningLayout({ children }: { children: ReactNode }) {
  return <RequireAuth>{children}</RequireAuth>;
}
'@
NewFile "(learning)\loading.tsx" $loadingBody
NewFile "(learning)\error.tsx"   $errorBody

NewFile "(learning)\(app)\layout.tsx" @'
"use client";

import type { ReactNode } from "react";
import { AppShell } from "@/components/layout/app-shell";
import { learningNav } from "@/config/nav";

export default function LearningAppLayout({ children }: { children: ReactNode }) {
  return <AppShell nav={learningNav}>{children}</AppShell>;
}
'@

NewFile "(learning)\(player)\layout.tsx" @'
import type { ReactNode } from "react";
import { AnnouncementBar } from "@/components/landing/announcement-bar";
import { LandingHeader } from "@/components/landing/landing-header";
import { LandingFooter } from "@/components/landing/landing-footer";

export default function PlayerLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-dvh flex-col">
      <AnnouncementBar />
      <LandingHeader />
      <main className="flex-1">{children}</main>
      <LandingFooter />
    </div>
  );
}
'@

NewFile "(account)\layout.tsx" @'
"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { accountNav } from "@/config/nav";

export default function AccountLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <AppShell nav={accountNav}>{children}</AppShell>
    </RequireAuth>
  );
}
'@
NewFile "(account)\loading.tsx" $loadingBody
NewFile "(account)\error.tsx"   $errorBody
NewFile "(account)\settings\page.tsx" @'
import type { Metadata } from "next";
import { PageHeader } from "@/components/student/page-header";

export const metadata: Metadata = { title: "Settings" };

export default function AccountSettingsPage() {
  return (
    <div className="space-y-6">
      <PageHeader eyebrow="Account" title="Settings" subtitle="Manage your account preferences." icon="Settings" />
      <p className="text-sm text-muted-foreground">Account settings will appear here.</p>
    </div>
  );
}
'@

NewFile "(commerce)\layout.tsx" @'
import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AnnouncementBar } from "@/components/landing/announcement-bar";
import { LandingHeader } from "@/components/landing/landing-header";
import { LandingFooter } from "@/components/landing/landing-footer";
import { PageTransition } from "@/components/layout/page-transition";

export default function CommerceLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <div className="flex min-h-dvh flex-col">
        <AnnouncementBar />
        <LandingHeader />
        <main className="flex-1">
          <PageTransition className="mx-auto w-full max-w-6xl px-4 py-10">{children}</PageTransition>
        </main>
        <LandingFooter />
      </div>
    </RequireAuth>
  );
}
'@
NewFile "(commerce)\loading.tsx" $loadingBody
NewFile "(commerce)\error.tsx"   $errorBody

NewFile "(instructor)\layout.tsx" @'
"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { instructorNav } from "@/config/nav";

export default function InstructorLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth roles={["instructor", "admin", "super_admin"]}>
      <AppShell nav={instructorNav}>{children}</AppShell>
    </RequireAuth>
  );
}
'@
NewFile "(instructor)\loading.tsx" $loadingBody
NewFile "(instructor)\error.tsx"   $errorBody

$teach = @(
  @("teach\page.tsx","Teach dashboard","Presentation"),
  @("teach\courses\page.tsx","My courses","BookOpen"),
  @("teach\courses\[public_id]\edit\page.tsx","Edit course","BookOpen"),
  @("teach\sessions\page.tsx","Live sessions","CalendarClock"),
  @("teach\students\page.tsx","Students","Users"),
  @("teach\earnings\page.tsx","Earnings","Wallet"),
  @("teach\apply\page.tsx","Become an instructor","UserPlus")
)
foreach ($t in $teach) {
  $rel = $t[0]; $title = $t[1]; $icon = $t[2]
  $body = @"
import type { Metadata } from "next";
import { PageHeader } from "@/components/student/page-header";

export const metadata: Metadata = { title: "$title" };

export default function InstructorPage() {
  return (
    <div className="space-y-6">
      <PageHeader eyebrow="Instructor" title="$title" subtitle="Coming soon." icon="$icon" />
      <p className="text-sm text-muted-foreground">This area is a placeholder pending the Instructor context build.</p>
    </div>
  );
}
"@
  NewFile "(instructor)\$rel" $body
}

NewFile "(organization)\layout.tsx" @'
"use client";

import type { ReactNode } from "react";
import { RequireAuth } from "@/lib/auth/guards";
import { AppShell } from "@/components/layout/app-shell";
import { organizationNav } from "@/config/nav";

export default function OrganizationLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth roles={["org_manager", "admin", "super_admin"]}>
      <AppShell nav={organizationNav}>{children}</AppShell>
    </RequireAuth>
  );
}
'@
NewFile "(organization)\loading.tsx" $loadingBody
NewFile "(organization)\error.tsx"   $errorBody

NewFile "(crm)\loading.tsx" $loadingBody
NewFile "(crm)\error.tsx"   $errorBody
NewFile "(analytics)\loading.tsx" $loadingBody
NewFile "(analytics)\error.tsx"   $errorBody

NewFile "not-found.tsx" @'
import Link from "next/link";

export default function NotFound() {
  return (
    <div className="flex min-h-dvh flex-col items-center justify-center gap-4 p-8 text-center">
      <p className="font-serif text-2xl font-semibold">Page not found</p>
      <p className="text-sm text-muted-foreground">The page you are looking for does not exist.</p>
      <div className="flex gap-3">
        <Link href="/" className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Home</Link>
        <Link href="/courses" className="rounded-md border px-4 py-2 text-sm font-medium">Browse courses</Link>
      </div>
    </div>
  );
}
'@

Write-Host "== 6) Rewrite nav.ts =="
WriteText (Join-Path $web "src\config\nav.ts") @'
import type { LucideIcon } from "lucide-react";
import {
  LayoutDashboard, GraduationCap, Award, Bell, User, Settings, Building2, Building, Headset,
  Contact, Users, BarChart3, FileText, LayoutGrid, PlayCircle, ShoppingCart, FileSignature,
  Presentation, BookOpen, CalendarClock, Wallet,
} from "lucide-react";

/** labelKey is a dot-path into the i18n dictionary (resolved via useI18n().t). */
export type NavItem = { labelKey: string; href: string; icon: LucideIcon };

export const learningNav: NavItem[] = [
  { labelKey: "nav.dashboard", href: "/dashboard", icon: LayoutDashboard },
  { labelKey: "nav.myLearning", href: "/my-learning", icon: GraduationCap },
  { labelKey: "nav.continueLearning", href: "/continue-learning", icon: PlayCircle },
  { labelKey: "nav.certificates", href: "/certificates", icon: Award },
];

export const accountNav: NavItem[] = [
  { labelKey: "nav.profile", href: "/account/profile", icon: User },
  { labelKey: "nav.notifications", href: "/account/notifications", icon: Bell },
  { labelKey: "nav.settings", href: "/account/settings", icon: Settings },
];

export const commerceNav: NavItem[] = [
  { labelKey: "nav.orders", href: "/orders", icon: ShoppingCart },
  { labelKey: "nav.contracts", href: "/contracts", icon: FileSignature },
];

export const instructorNav: NavItem[] = [
  { labelKey: "nav.teach", href: "/teach", icon: Presentation },
  { labelKey: "nav.courses", href: "/teach/courses", icon: BookOpen },
  { labelKey: "nav.sessions", href: "/teach/sessions", icon: CalendarClock },
  { labelKey: "nav.students", href: "/teach/students", icon: Users },
  { labelKey: "nav.earnings", href: "/teach/earnings", icon: Wallet },
];

export const organizationNav: NavItem[] = [
  { labelKey: "nav.organization", href: "/org", icon: Building2 },
  { labelKey: "nav.organizations", href: "/org/organizations", icon: Building },
  { labelKey: "nav.consulting", href: "/org/consulting", icon: Headset },
  { labelKey: "nav.settings", href: "/account/settings", icon: Settings },
];

export const crmNav: NavItem[] = [
  { labelKey: "nav.crm", href: "/crm", icon: LayoutDashboard },
  { labelKey: "nav.leads", href: "/crm/leads", icon: Contact },
  { labelKey: "nav.consulting", href: "/crm/consulting", icon: Headset },
  { labelKey: "nav.accounts", href: "/crm/accounts", icon: Users },
];

export const analyticsNav: NavItem[] = [
  { labelKey: "nav.analytics", href: "/analytics", icon: BarChart3 },
  { labelKey: "nav.reports", href: "/reports", icon: FileText },
  { labelKey: "nav.dashboards", href: "/dashboards", icon: LayoutGrid },
];
'@
Write-Host "updated: src/config/nav.ts"

Write-Host "== 7) next.config.ts redirects =="
WriteText (Join-Path $web "next.config.ts") @'
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  reactStrictMode: true,
  async redirects() {
    return [
      { source: "/courses/:public_id/learn", destination: "/learn/:public_id", permanent: true },
      { source: "/profile", destination: "/account/profile", permanent: true },
      { source: "/notifications", destination: "/account/notifications", permanent: true },
      { source: "/crm/organizations", destination: "/crm/accounts", permanent: true },
      { source: "/settings/theme", destination: "/login", permanent: false },
    ];
  },
};

export default nextConfig;
'@
Write-Host "updated: apps/web/next.config.ts"

Write-Host "== 8) Remove public /settings/theme links from theme.ts =="
$theme = Join-Path $web "src\config\theme.ts"
if (Test-Path $theme) {
  $t = ReadText $theme
  $t = $t -replace '(?m)^\s*\{\s*label:\s*\{\s*en:\s*"Brand",\s*ar:\s*"[^"]*"\s*\},\s*href:\s*"/settings/theme"\s*\},\r?\n', ''
  $t = $t -replace '(?m)^\s*\{\s*label:\s*\{\s*en:\s*"Brand identity",\s*ar:\s*"[^"]*"\s*\},\s*href:\s*"/settings/theme"\s*\},\r?\n', ''
  WriteText $theme $t
  Write-Host "patched theme.ts"
}

Write-Host "== 9) Delete dead / misplaced structures =="
DelPath "(dashboard)"
DelPath "settings\theme"
DelPath "settings"
DelPath "(public)"
DelPath "(auth)"
DelPath "(onboarding)"
DelPath "(student)"
DelPath "(org)"
$ph = Join-Path $web "src\components\catalog\public-header.tsx"
if (Test-Path $ph) { git rm -f -- $ph 2>$null | Out-Null; if (Test-Path $ph) { Remove-Item -Force $ph }; Write-Host "deleted: components/catalog/public-header.tsx" }

Write-Host "== 10) Typecheck =="
Set-Location $web
npm run typecheck

Write-Host "`n== DONE. Review 'git status', run 'npm run build', then commit. =="
Write-Host "If typecheck flags missing i18n keys, add to src/lib/i18n/dictionaries.ts:"
Write-Host "  nav.continueLearning, nav.orders, nav.contracts, nav.teach, nav.courses, nav.sessions, nav.students, nav.earnings, nav.accounts"
Write-Host "Also update any tests/** imports that referenced old group paths."
