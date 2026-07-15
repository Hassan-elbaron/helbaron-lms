# Demo Scenarios & Walkthroughs

Scripted, role-based walkthroughs for demoing the **enterprise** HElbaron dataset (`DEMO_SCALE=enterprise php artisan demo:seed`). Every scenario lists the login, navigation, talking points, expected results (from the measured enterprise counts), and an approximate duration.

Accounts (all password **`password`**): super admin `admin@helbaron.local`; instructor `yara.adel@demo.helbaron.local`; student `student01@demo.helbaron.local`; org manager = an owner/manager from `organization_members`. See `docs/demo/DEMO_ACCOUNTS.md`.

Dataset headline (enterprise, measured): 616 users · 56 courses · 1,859 lessons · 4,783 enrollments (1,298 completed) · 1,298 certificates · 1,500 orders · 20,151 audit records · 365 days of analytics.

---

## 1. Investor demo (~8 min)

- **Login:** `admin@helbaron.local` / `password`.
- **Navigation:** Landing `/` → Catalog `/courses` → Admin `/admin` → Analytics/Dashboards → Reports → Audit log.
- **Talking points:** a bilingual academy operating at scale; a full commerce + certification + CRM + analytics stack; a year of trend data; governance via audit trail.
- **Expected results:** populated catalog (56 courses), admin KPIs and 365-day charts filled, 1,500 orders with revenue trends, 20k+ audit rows.
- **Duration:** 8 minutes.

## 2. Enterprise customer demo (~10 min)

- **Login:** org manager (from `organization_members`), then `admin@helbaron.local` for admin depth.
- **Navigation:** `/org` → `/org/organizations/[id]` (teams, members, seats) → learner progress → Admin → Orders/Invoices.
- **Talking points:** manage learning for a whole company — departments, teams, seat pools; track team progress; consolidated invoicing.
- **Expected results:** 40 organizations, 120 departments, 160 teams, seat pools, 612 members with real enrollments/progress; org purchase history in commerce.
- **Duration:** 10 minutes.

## 3. University demo (~8 min)

- **Login:** `admin@helbaron.local`, plus `student01@demo.helbaron.local` for the learner view.
- **Navigation:** Catalog → Course details (curriculum) → Lesson player → Certificates → Admin certificate management.
- **Talking points:** structured multi-section curricula, bilingual content, verifiable certificates, cohort/live sessions.
- **Expected results:** courses with 5–7 sections and dozens of lessons; 1,298 issued certificates with verification codes; 60 live sessions.
- **Duration:** 8 minutes.

## 4. Corporate training demo (~9 min)

- **Login:** org manager + `admin@helbaron.local`.
- **Navigation:** `/org` team learning → Live sessions (upcoming/completed) → Attendance → Analytics (completion metrics).
- **Talking points:** assign learning to teams, run live cohorts, track attendance and completion, report to leadership.
- **Expected results:** 2,346 registrations, 888 attendance records, completion analytics over 365 days.
- **Duration:** 9 minutes.

## 5. Sales demo (~7 min)

- **Login:** `admin@helbaron.local`.
- **Navigation:** CRM `/crm` → Leads `/crm/leads` → Accounts `/crm/accounts` → an opportunity → activities.
- **Talking points:** built-in CRM — pipeline, leads, companies, opportunities, activity history; sell seats and courses without a second tool.
- **Expected results:** 800 leads across stages, 250 companies, 1,000 contacts, 400 opportunities, 5,000 activities, 4,000 notes.
- **Duration:** 7 minutes.

## 6. Support demo (~6 min)

- **Login:** `admin@helbaron.local`.
- **Navigation:** Users (find `student01`) → their orders/enrollments → Orders (refund action) → Certificates (revoke/reissue) → Audit log.
- **Talking points:** resolve a learner issue end-to-end — inspect account, refund an order, reissue a certificate, and see it all in the audit trail.
- **Expected results:** refund action on a paid order (PaymentTransaction type=refund), certificate reissue, corresponding audit entries.
- **Duration:** 6 minutes.

## 7. Student demo (~6 min)

- **Login:** `student01@demo.helbaron.local` / `password`.
- **Navigation:** `/dashboard` → `/my-learning` → Lesson player (bookmark + note) → `/certificates` → `/notifications`.
- **Talking points:** a learner's daily flow — continue learning, progress, bookmarks/notes, certificates, notification preferences (locale/digest/timezone).
- **Expected results:** multiple enrollments in varied states, progress bars, at least one certificate, in-app notifications.
- **Duration:** 6 minutes.

## 8. Instructor demo (~5 min)

- **Login:** `admin@helbaron.local` (instructor/course management lives in the admin panel; the `/teach` area is an honest "coming soon").
- **Navigation:** Admin → Courses/Catalog (an instructor's courses) → Sections/Lessons → publish state.
- **Talking points:** content operations — courses, curriculum, publishing, media; instructors as course owners across the catalog.
- **Expected results:** 56 courses owned across 16 instructors; curriculum management.
- **Note:** the learner-facing `/teach` dashboard is deferred (Instructor backend context not built) — demonstrate via admin.
- **Duration:** 5 minutes.

## 9. Organization demo (~8 min)

- **Login:** org manager + `admin@helbaron.local`.
- **Navigation:** `/org/organizations/[id]` → departments/teams/members → seats → team learning progress → org purchases.
- **Talking points:** the full B2B account — structure, seats, assigned learning, progress, and commerce history in one place.
- **Expected results:** a demo org with departments, teams, ~15 members, seat pool, member enrollments, and orders.
- **Duration:** 8 minutes.

## 10. Admin demo (~9 min)

- **Login:** `admin@helbaron.local` / `password` → `/admin`.
- **Navigation:** Users → Roles → Courses → Orders/Invoices/Coupons/Redemptions → Certificates → Notification & Email Templates → Certificate Settings → Audit Log.
- **Talking points:** operate the whole business from one panel — identity, catalog, commerce, certificates, messaging, and a complete audit trail.
- **Expected results:** every resource paginated and populated; refund/revoke/reissue actions; 39 templates (25 email); 20k+ audit rows.
- **Duration:** 9 minutes.

## 11. Analytics demo (~6 min)

- **Login:** `admin@helbaron.local`.
- **Navigation:** `/analytics` → `/dashboards` → `/reports` → a report.
- **Talking points:** a year of trends — signups, enrollments, completions, revenue, certificates — with KPI widgets and reports ready for the board.
- **Expected results:** 2,920 metric snapshots (365 days × metrics) driving filled charts and KPIs; report definitions with history.
- **Duration:** 6 minutes.

## 12. CRM demo (~7 min)

- **Login:** `admin@helbaron.local`.
- **Navigation:** `/crm` → pipeline/stages → Leads → Companies → Opportunities → activities/notes timeline.
- **Talking points:** manage the commercial side — pipeline, accounts, deals, and a rich activity history — natively in the LMS.
- **Expected results:** 800 leads, 250 companies, 1,000 contacts, 400 opportunities, 5,000 activities, 4,000 notes.
- **Duration:** 7 minutes.

---

## Reset between demos

To restore a pristine dataset: `DEMO_RESET_ALLOWED=true DEMO_SCALE=enterprise php artisan demo:seed --reset`. For byte-identical reproduction, run from a fresh migration (`php artisan migrate:fresh` then `demo:seed`) — reset-on-populated rebuilds the same **shape** and is itself idempotent, but RNG-driven leaf counts can vary slightly because database identity sequences are not rewound (documented in the expansion report).
