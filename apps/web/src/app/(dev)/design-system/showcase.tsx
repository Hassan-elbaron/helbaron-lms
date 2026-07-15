"use client";

/**
 * Internal Design-System Showcase (Part 15) — renders every design-system surface for review.
 * Token-driven and self-contained. Gated to dev / explicit env flag by the parent page.
 *
 * In-page controls: a light/dark toggle (drives next-themes — the real Theme Manager, never
 * bypassed) and an LTR/RTL toggle (scoped `dir` on the showcase root so logical properties mirror).
 */
import { useState } from "react";
import { useTheme } from "next-themes";
import {
  Home, Search, Bell, Settings, User, Heart, Star, Check, X as XIcon, ChevronRight,
  Download, Calendar, Mail, Play, Plus, Trash2, Pencil, Eye, Lock, Sun, Moon,
} from "lucide-react";

import {
  Button, Badge, Input, Textarea, Label, Checkbox, Switch, RadioGroup, RadioGroupItem,
  Select, SelectTrigger, SelectValue, SelectContent, SelectItem,
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter, Separator,
  Tabs, TabsList, TabsTrigger, TabsContent, Accordion, AccordionItem, AccordionTrigger, AccordionContent,
  Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogClose,
  Drawer, DrawerTrigger, DrawerContent, DrawerHeader, DrawerTitle, DrawerDescription, DrawerFooter, DrawerClose,
  DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator,
  Tooltip, TooltipTrigger, TooltipContent, Popover, PopoverTrigger, PopoverContent,
  Breadcrumb, Pagination, Spinner, Progress, Avatar, AvatarImage, AvatarFallback,
  Skeleton, SkeletonText, Icon, toast,
  BarChart, LineChart, Sparkline, DonutChart, ProgressRing, ChartLegend,
  DataGrid, type ColumnDef, type SortState,
  FormField, Form, FormSection, FormActions, FormAlert,
} from "@/components/ui";
import {
  LoadingState, EmptyState, ErrorState, SuccessState, OfflineBanner, ComingSoon,
} from "@/components/states";
import { BlockRenderer } from "@/components/homepage/registry";
import { sampleSections } from "./sample-blocks";

/* ─────────────────────────── layout helpers ─────────────────────────── */

function Section({ id, title, description, children }: { id: string; title: string; description?: string; children: React.ReactNode }) {
  return (
    <section id={id} className="scroll-mt-24 border-t border-border py-12">
      <div className="mb-6">
        <h2 className="text-h3 font-serif">{title}</h2>
        {description ? <p className="mt-1 text-body text-muted-foreground">{description}</p> : null}
      </div>
      {children}
    </section>
  );
}

function Row({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="mb-6">
      <div className="mb-2 text-label uppercase tracking-wide text-muted-foreground">{label}</div>
      <div className="flex flex-wrap items-center gap-3">{children}</div>
    </div>
  );
}

/* ─────────────────────────── token data ─────────────────────────── */

const COLOR_TOKENS: { name: string; fg?: string }[] = [
  { name: "background", fg: "foreground" }, { name: "card", fg: "card-foreground" },
  { name: "popover", fg: "popover-foreground" }, { name: "primary", fg: "primary-foreground" },
  { name: "secondary", fg: "secondary-foreground" }, { name: "muted", fg: "muted-foreground" },
  { name: "accent", fg: "accent-foreground" }, { name: "copper", fg: "copper-foreground" },
  { name: "gold", fg: "gold-foreground" }, { name: "destructive", fg: "destructive-foreground" },
  { name: "success", fg: "success-foreground" }, { name: "warning", fg: "warning-foreground" },
  { name: "info", fg: "info-foreground" }, { name: "surface", fg: "surface-foreground" },
  { name: "sidebar", fg: "sidebar-foreground" }, { name: "header", fg: "header-foreground" },
  { name: "footer", fg: "footer-foreground" }, { name: "border" }, { name: "input" }, { name: "ring" }, { name: "overlay" },
];

const TYPO_ROLES = ["display", "h1", "h2", "h3", "h4", "h5", "h6", "subtitle", "body", "caption", "label", "button"] as const;
const SPACING = ["0-5", "1", "1-5", "2", "3", "4", "5", "6", "8", "10", "12", "16", "20", "24"] as const;
const RADII: { name: string; cls: string }[] = [
  { name: "sm", cls: "rounded-sm" }, { name: "md", cls: "rounded-md" }, { name: "lg", cls: "rounded-lg" },
  { name: "xl", cls: "rounded-xl" }, { name: "2xl", cls: "rounded-2xl" }, { name: "full", cls: "rounded-full" },
];
const ICONS = [
  { icon: Home, name: "Home" }, { icon: Search, name: "Search" }, { icon: Bell, name: "Bell" },
  { icon: Settings, name: "Settings" }, { icon: User, name: "User" }, { icon: Heart, name: "Heart" },
  { icon: Star, name: "Star" }, { icon: Check, name: "Check" }, { icon: Calendar, name: "Calendar" },
  { icon: Mail, name: "Mail" }, { icon: Download, name: "Download" }, { icon: Play, name: "Play" },
  { icon: Plus, name: "Plus" }, { icon: Trash2, name: "Trash2" }, { icon: Pencil, name: "Edit" },
  { icon: Eye, name: "Eye" }, { icon: Lock, name: "Lock" }, { icon: ChevronRight, name: "Chevron" },
];

function Swatch({ name, fg }: { name: string; fg?: string }) {
  return (
    <div className="overflow-hidden rounded-lg border border-border">
      <div
        className="flex h-16 items-end p-2"
        style={{ backgroundColor: `var(--${name})`, color: fg ? `var(--${fg})` : "var(--foreground)" }}
      >
        <span className="text-caption font-medium">{fg ? "Aa" : ""}</span>
      </div>
      <div className="bg-card px-2 py-1.5">
        <div className="text-caption font-medium text-card-foreground">--{name}</div>
      </div>
    </div>
  );
}

function ColorGrid() {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
      {COLOR_TOKENS.map((c) => <Swatch key={c.name} name={c.name} fg={c.fg} />)}
    </div>
  );
}

/* ─────────────────────────── sample chart / grid data ─────────────────────────── */

const barData = [
  { label: "Jan", value: 32 }, { label: "Feb", value: 51 }, { label: "Mar", value: 44 },
  { label: "Apr", value: 68 }, { label: "May", value: 59 }, { label: "Jun", value: 74 },
];
const donutData = [
  { label: "Business", value: 42 }, { label: "Tech", value: 33 }, { label: "Design", value: 25 },
];

type Enrollment = { id: string; learner: string; course: string; progress: number; status: "active" | "completed" | "pending" };
const enrollments: Enrollment[] = [
  { id: "1", learner: "Layla Hassan", course: "Product Strategy", progress: 82, status: "active" },
  { id: "2", learner: "Omar Khaled", course: "Data Foundations", progress: 100, status: "completed" },
  { id: "3", learner: "Sara Nabil", course: "Leadership Lab", progress: 40, status: "pending" },
  { id: "4", learner: "Yusuf Adel", course: "Design Systems", progress: 66, status: "active" },
];

/* ─────────────────────────── the showcase ─────────────────────────── */

export function DesignShowcase() {
  const { theme, setTheme } = useTheme();
  const [dir, setDir] = useState<"ltr" | "rtl">("ltr");
  const [page, setPage] = useState(2);
  const [sort, setSort] = useState<SortState | null>({ key: "progress", direction: "desc" });
  const isDark = theme === "dark";

  const gridColumns: ColumnDef<Enrollment>[] = [
    { key: "learner", header: "Learner", cell: (r) => r.learner, sortable: true, sortValue: (r) => r.learner, sticky: "start" },
    { key: "course", header: "Course", cell: (r) => r.course, sortable: true, sortValue: (r) => r.course },
    {
      key: "progress", header: "Progress", align: "end", sortable: true, sortValue: (r) => r.progress,
      cell: (r) => <span className="tabular-nums">{r.progress}%</span>,
    },
    {
      key: "status", header: "Status", cell: (r) => (
        <Badge variant={r.status === "completed" ? "success" : r.status === "pending" ? "warning" : "secondary"}>{r.status}</Badge>
      ),
    },
  ];

  return (
    <div dir={dir} className="min-h-screen bg-background text-foreground">
      {/* Toolbar */}
      <header className="sticky top-0 z-10 border-b border-border bg-header/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-6 py-3">
          <div>
            <h1 className="text-h5 font-serif text-header-foreground">Design System Showcase</h1>
            <p className="text-caption text-muted-foreground">Internal — not part of the public site.</p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={() => setTheme(isDark ? "light" : "dark")}>
              {isDark ? <Moon className="size-4" aria-hidden /> : <Sun className="size-4" aria-hidden />}
              {isDark ? "Dark" : "Light"}
            </Button>
            <Button variant="outline" size="sm" onClick={() => setDir(dir === "ltr" ? "rtl" : "ltr")}>
              {dir.toUpperCase()}
            </Button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 pb-24">
        {/* Colors */}
        <Section id="colors" title="Color tokens" description="Semantic color families. Toggle Light/Dark above; a forced-dark mirror is shown for side-by-side comparison.">
          <ColorGrid />
          <div className="mt-8">
            <div className="mb-2 text-label uppercase tracking-wide text-muted-foreground">Forced dark preview</div>
            <div className="dark rounded-xl border border-border bg-background p-4">
              <ColorGrid />
            </div>
          </div>
        </Section>

        {/* Typography */}
        <Section id="typography" title="Typography scale" description="Fluid type roles. English (sans/serif) + Arabic (IBM Plex Sans Arabic) samples.">
          <div className="space-y-4">
            {TYPO_ROLES.map((role) => (
              <div key={role} className="grid items-baseline gap-2 border-b border-border pb-3 md:grid-cols-[6rem_1fr_1fr]">
                <code className="text-caption text-muted-foreground">.text-{role}</code>
                <div className={`text-${role}`}>The quick brown fox</div>
                <div className={`text-${role}`} dir="rtl" lang="ar">أكاديمية عربية احترافية</div>
              </div>
            ))}
          </div>
        </Section>

        {/* Spacing */}
        <Section id="spacing" title="Spacing scale" description="rem-based scale mirroring Tailwind rhythm (--space-*).">
          <div className="space-y-2">
            {SPACING.map((s) => (
              <div key={s} className="flex items-center gap-3">
                <code className="w-24 text-caption text-muted-foreground">--space-{s}</code>
                <div className="h-4 rounded bg-primary" style={{ width: `var(--space-${s})` }} />
              </div>
            ))}
          </div>
        </Section>

        {/* Radius + shadow/elevation */}
        <Section id="radius" title="Radius, shadow & elevation" description="Radius scale, box-shadow tokens, and semantic elevation.">
          <Row label="Radius">
            {RADII.map((r) => (
              <div key={r.name} className="flex flex-col items-center gap-1">
                <div className={`size-16 border border-border bg-card ${r.cls}`} />
                <code className="text-caption text-muted-foreground">{r.name}</code>
              </div>
            ))}
          </Row>
          <Row label="Elevation">
            {[1, 2, 3, 4, 5].map((e) => (
              <div key={e} className="flex flex-col items-center gap-1">
                <div className={`grid size-20 place-items-center rounded-lg bg-card elevation-${e}`}>
                  <span className="text-caption text-muted-foreground">{e}</span>
                </div>
                <code className="text-caption text-muted-foreground">elevation-{e}</code>
              </div>
            ))}
          </Row>
        </Section>

        {/* Icons */}
        <Section id="icons" title="Icons" description="lucide-react via the Icon primitive. Consistent stroke, size scale xs–xl.">
          <Row label="Size scale (Home)">
            {(["xs", "sm", "md", "lg", "xl"] as const).map((size) => (
              <div key={size} className="flex flex-col items-center gap-1">
                <Icon icon={Home} size={size} />
                <code className="text-caption text-muted-foreground">{size}</code>
              </div>
            ))}
          </Row>
          <Row label="Sample set">
            {ICONS.map((i) => (
              <div key={i.name} className="flex flex-col items-center gap-1" title={i.name}>
                <Icon icon={i.icon} size="md" />
              </div>
            ))}
          </Row>
        </Section>

        {/* Buttons */}
        <Section id="buttons" title="Buttons" description="Variants, sizes, and states.">
          <Row label="Variants">
            {(["default", "secondary", "outline", "ghost", "link", "destructive", "success", "info"] as const).map((v) => (
              <Button key={v} variant={v}>{v}</Button>
            ))}
          </Row>
          <Row label="Sizes">
            <Button size="sm">Small</Button>
            <Button size="default">Default</Button>
            <Button size="lg">Large</Button>
            <Button size="icon" aria-label="Add"><Plus className="size-4" aria-hidden /></Button>
          </Row>
          <Row label="States">
            <Button loading>Loading</Button>
            <Button disabled>Disabled</Button>
            <Button variant="outline"><Download className="size-4" aria-hidden /> With icon</Button>
          </Row>
        </Section>

        {/* Badges */}
        <Section id="badges" title="Badges">
          <Row label="Variants">
            {(["default", "secondary", "destructive", "success", "warning", "info", "outline"] as const).map((v) => (
              <Badge key={v} variant={v}>{v}</Badge>
            ))}
          </Row>
        </Section>

        {/* Form controls */}
        <Section id="inputs" title="Form controls" description="Inputs, selection controls, and interaction states side by side.">
          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-3">
              <div><Label htmlFor="d-in">Default</Label><Input id="d-in" placeholder="Type here…" /></div>
              <div><Label htmlFor="d-val">With value</Label><Input id="d-val" defaultValue="Hello world" /></div>
              <div><Label htmlFor="d-ro">Read-only</Label><Input id="d-ro" readOnly defaultValue="Read-only value" /></div>
              <div><Label htmlFor="d-dis">Disabled</Label><Input id="d-dis" disabled placeholder="Disabled" /></div>
              <div><Label htmlFor="d-inv">Invalid (validation)</Label><Input id="d-inv" aria-invalid defaultValue="not-an-email" /></div>
              <div><Label htmlFor="d-ta">Textarea</Label><Textarea id="d-ta" placeholder="Longer text…" /></div>
            </div>
            <div className="space-y-4">
              <div className="flex items-center gap-2"><Checkbox id="cb1" defaultChecked /><Label htmlFor="cb1">Checked</Label></div>
              <div className="flex items-center gap-2"><Checkbox id="cb2" /><Label htmlFor="cb2">Unchecked</Label></div>
              <div className="flex items-center gap-2"><Checkbox id="cb3" disabled /><Label htmlFor="cb3">Disabled</Label></div>
              <div className="flex items-center gap-3"><Switch defaultChecked /><span className="text-body">Switch on</span></div>
              <div className="flex items-center gap-3"><Switch /><span className="text-body">Switch off</span></div>
              <RadioGroup defaultValue="a" className="space-y-2">
                <div className="flex items-center gap-2"><RadioGroupItem id="r-a" value="a" /><Label htmlFor="r-a">Option A</Label></div>
                <div className="flex items-center gap-2"><RadioGroupItem id="r-b" value="b" /><Label htmlFor="r-b">Option B</Label></div>
              </RadioGroup>
              <Select defaultValue="business">
                <SelectTrigger className="w-full"><SelectValue placeholder="Pick a category" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="business">Business</SelectItem>
                  <SelectItem value="tech">Technology</SelectItem>
                  <SelectItem value="design">Design</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </Section>

        {/* Cards */}
        <Section id="cards" title="Cards">
          <div className="grid gap-4 md:grid-cols-3">
            <Card>
              <CardHeader><CardTitle>Course title</CardTitle><CardDescription>Short supporting description.</CardDescription></CardHeader>
              <CardContent><p className="text-body text-muted-foreground">Body content sits here with muted supporting text.</p></CardContent>
              <CardFooter><Button size="sm">Action</Button></CardFooter>
            </Card>
            <Card className="elevation-3"><CardHeader><CardTitle>Elevated</CardTitle><CardDescription>elevation-3 shadow token.</CardDescription></CardHeader><CardContent><Progress value={64} label="Progress" /></CardContent></Card>
            <Card><CardHeader><CardTitle>With avatar</CardTitle></CardHeader><CardContent className="flex items-center gap-3"><Avatar><AvatarImage src="" alt="" /><AvatarFallback>LH</AvatarFallback></Avatar><div><div className="text-body font-medium">Layla Hassan</div><div className="text-caption text-muted-foreground">Product Manager</div></div></CardContent></Card>
          </div>
        </Section>

        {/* Tabs + Accordion */}
        <Section id="disclosure" title="Tabs & accordion">
          <div className="grid gap-8 md:grid-cols-2">
            <Tabs defaultValue="t1">
              <TabsList>
                <TabsTrigger value="t1">Overview</TabsTrigger>
                <TabsTrigger value="t2">Curriculum</TabsTrigger>
                <TabsTrigger value="t3">Reviews</TabsTrigger>
              </TabsList>
              <TabsContent value="t1"><p className="text-body text-muted-foreground">Overview content.</p></TabsContent>
              <TabsContent value="t2"><p className="text-body text-muted-foreground">Curriculum content.</p></TabsContent>
              <TabsContent value="t3"><p className="text-body text-muted-foreground">Reviews content.</p></TabsContent>
            </Tabs>
            <Accordion type="single" collapsible defaultValue="a1">
              <AccordionItem value="a1"><AccordionTrigger>Is there a free trial?</AccordionTrigger><AccordionContent>Yes, the Starter plan is free forever.</AccordionContent></AccordionItem>
              <AccordionItem value="a2"><AccordionTrigger>Are certificates verifiable?</AccordionTrigger><AccordionContent>Every certificate has a public verify page.</AccordionContent></AccordionItem>
            </Accordion>
          </div>
        </Section>

        {/* Overlays */}
        <Section id="overlays" title="Overlays" description="Dialog, drawer, dropdown, tooltip, popover, and toast.">
          <Row label="Triggers">
            <Dialog>
              <DialogTrigger asChild><Button variant="outline">Open dialog</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader><DialogTitle>Confirm enrollment</DialogTitle><DialogDescription>You are about to enroll in Product Strategy.</DialogDescription></DialogHeader>
                <DialogFooter><DialogClose asChild><Button variant="ghost">Cancel</Button></DialogClose><Button>Confirm</Button></DialogFooter>
              </DialogContent>
            </Dialog>
            <Drawer>
              <DrawerTrigger asChild><Button variant="outline">Open drawer</Button></DrawerTrigger>
              <DrawerContent>
                <DrawerHeader><DrawerTitle>Filters</DrawerTitle><DrawerDescription>Refine the catalog.</DrawerDescription></DrawerHeader>
                <div className="p-4"><p className="text-body text-muted-foreground">Drawer body content.</p></div>
                <DrawerFooter><DrawerClose asChild><Button>Apply</Button></DrawerClose></DrawerFooter>
              </DrawerContent>
            </Drawer>
            <DropdownMenu>
              <DropdownMenuTrigger asChild><Button variant="outline">Menu</Button></DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuLabel>Account</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem>Profile</DropdownMenuItem>
                <DropdownMenuItem>Settings</DropdownMenuItem>
                <DropdownMenuItem>Sign out</DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
            <Tooltip><TooltipTrigger asChild><Button variant="outline">Hover me</Button></TooltipTrigger><TooltipContent>Helpful hint</TooltipContent></Tooltip>
            <Popover>
              <PopoverTrigger asChild><Button variant="outline">Popover</Button></PopoverTrigger>
              <PopoverContent><p className="text-body">Popover panel content.</p></PopoverContent>
            </Popover>
            <Button variant="outline" onClick={() => toast.success("Saved successfully")}>Toast success</Button>
            <Button variant="outline" onClick={() => toast.error("Something went wrong")}>Toast error</Button>
          </Row>
        </Section>

        {/* Navigation + feedback */}
        <Section id="navigation" title="Navigation & feedback">
          <Row label="Breadcrumb">
            <Breadcrumb items={[{ label: "Home", href: "#" }, { label: "Courses", href: "#" }, { label: "Product Strategy" }]} />
          </Row>
          <Row label="Pagination">
            <Pagination page={page} lastPage={8} onPageChange={setPage} />
          </Row>
          <Row label="Progress & spinner">
            <div className="w-48"><Progress value={72} label="Loading" /></div>
            <Progress value={40} variant="success" label="Success" className="w-48" />
            <Spinner size="sm" /><Spinner size="md" /><Spinner size="lg" />
          </Row>
          <Row label="Separator">
            <div className="flex h-6 items-center gap-3 text-body">A<Separator orientation="vertical" />B<Separator orientation="vertical" />C</div>
          </Row>
        </Section>

        {/* States */}
        <Section id="states" title="States" description="Canonical loading / skeleton / empty / error / success / offline / coming-soon.">
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card><CardContent className="p-4"><LoadingState label="Loading…" /></CardContent></Card>
            <Card><CardContent className="space-y-3 p-4"><Skeleton variant="avatar" /><SkeletonText lines={3} /></CardContent></Card>
            <Card><CardContent className="p-4"><EmptyState title="No results" description="Try adjusting your filters." icon={<Search className="size-6" aria-hidden />} /></CardContent></Card>
            <Card><CardContent className="p-4"><ErrorState title="Failed to load" message="Please try again." onRetry={() => toast("Retrying…")} /></CardContent></Card>
            <Card><CardContent className="p-4"><SuccessState title="All done" message="Your changes were saved." /></CardContent></Card>
            <Card><CardContent className="p-4"><ComingSoon eyebrow="Soon" title="Instructor analytics" /></CardContent></Card>
          </div>
          <div className="mt-4"><OfflineBanner message="You are offline. Changes will sync when reconnected." /></div>
        </Section>

        {/* Charts */}
        <Section id="charts" title="Charts" description="Dependency-free, token-driven SVG charts.">
          <div className="grid gap-6 md:grid-cols-2">
            <Card><CardHeader><CardTitle className="text-h5">Bar</CardTitle></CardHeader><CardContent><BarChart data={barData} label="Monthly enrollments" showValues /></CardContent></Card>
            <Card><CardHeader><CardTitle className="text-h5">Line (area)</CardTitle></CardHeader><CardContent><LineChart data={barData} label="Active learners" area showDots /></CardContent></Card>
            <Card><CardHeader><CardTitle className="text-h5">Donut + legend</CardTitle></CardHeader><CardContent className="flex items-center gap-6"><DonutChart data={donutData} label="Category mix" /><ChartLegend items={donutData.map((d) => ({ label: d.label }))} /></CardContent></Card>
            <Card>
              <CardHeader><CardTitle className="text-h5">Sparkline & ring</CardTitle></CardHeader>
              <CardContent className="flex items-center gap-8">
                <div className="w-40"><Sparkline data={barData} label="Trend" /></div>
                <ProgressRing value={68} label="Completion" centerContent={<span className="text-h5 font-semibold">68%</span>} />
              </CardContent>
            </Card>
          </div>
        </Section>

        {/* Tables */}
        <Section id="tables" title="Data grid" description="Sortable, selectable, sticky header, responsive cards, plus empty & loading states.">
          <DataGrid
            columns={gridColumns}
            data={enrollments}
            rowKey={(r) => r.id}
            sort={sort}
            onSortChange={setSort}
            selectable
            stickyHeader
            density="comfortable"
            responsiveCards
            bulkActions={({ selectedRows, clear }) => (
              <div className="flex items-center gap-2"><span className="text-caption">{selectedRows.length} selected</span><Button size="sm" variant="ghost" onClick={clear}>Clear</Button></div>
            )}
          />
          <div className="mt-6 grid gap-6 md:grid-cols-2">
            <div><div className="mb-2 text-label uppercase tracking-wide text-muted-foreground">Empty</div><DataGrid columns={gridColumns} data={[]} rowKey={(r) => r.id} empty={<EmptyState title="No enrollments yet" />} /></div>
            <div><div className="mb-2 text-label uppercase tracking-wide text-muted-foreground">Loading</div><DataGrid columns={gridColumns} data={[]} rowKey={(r) => r.id} loading /></div>
          </div>
        </Section>

        {/* Forms */}
        <Section id="forms" title="Forms" description="FormField with hint / error / success / required / disabled / read-only, plus alerts.">
          <div className="grid gap-8 md:grid-cols-2">
            <Form>
              <FormSection title="Account" description="Manage your profile details.">
                <FormField label="Full name" required hint="As it appears on your certificate."><Input placeholder="Layla Hassan" /></FormField>
                <FormField label="Email" error="Enter a valid email address."><Input aria-invalid defaultValue="not-an-email" /></FormField>
                <FormField label="Username" success="This username is available."><Input defaultValue="layla" /></FormField>
                <FormField label="Referral code" hint="Optional."><Input disabled placeholder="Disabled field" /></FormField>
                <FormField label="Member ID" hint="System-assigned."><Input readOnly defaultValue="HLB-00421" /></FormField>
              </FormSection>
              <FormActions><Button variant="ghost">Cancel</Button><Button>Save changes</Button></FormActions>
            </Form>
            <div className="space-y-3">
              <FormAlert variant="error">Your session has expired. Please sign in again.</FormAlert>
              <FormAlert variant="success">Your profile was updated successfully.</FormAlert>
              <FormAlert variant="warning">Some fields still need attention.</FormAlert>
              <FormAlert variant="info">Certificates are issued within 24 hours.</FormAlert>
            </div>
          </div>
        </Section>

        {/* Homepage blocks */}
        <Section id="blocks" title="Homepage blocks" description="Every CMS block rendered with representative sample data.">
          <div className="space-y-2 overflow-hidden rounded-xl border border-border">
            {sampleSections.map((section) => (
              <div key={section.key} className="border-b border-border last:border-b-0">
                <div className="bg-muted px-4 py-1.5 text-caption font-medium text-muted-foreground">{section.type}</div>
                <BlockRenderer section={section} />
              </div>
            ))}
          </div>
        </Section>
      </main>
    </div>
  );
}
