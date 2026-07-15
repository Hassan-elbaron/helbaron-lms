# Demo Screenshot Plan

A shot list for producing marketing/investor screenshots from the **enterprise** demo dataset (`DEMO_SCALE=enterprise`, seeded via `php artisan demo:seed`). Each row gives the role to log in as, viewport, language/direction, the data the page should show (from the measured enterprise counts), and a marketing caption.

Accounts: see `docs/demo/DEMO_ACCOUNTS.md` (super admin `admin@helbaron.local`; instructor `yara.adel@demo.helbaron.local`; student `student01@demo.helbaron.local`; all password `password`). Web login for all; admin panel at `/admin`.

Viewports: **Desktop** 1440×900 · **Tablet** 1024×768 · **Mobile** 390×844. Languages: **EN (LTR)** and **AR (RTL)** — toggle the locale to capture both.

| # | Page / Route | Role | Viewport | Lang | Expected data on screen | Marketing caption |
|---|---|---|---|---|---|---|
| 1 | Landing `/` | Guest | Desktop + Mobile | EN + AR | Hero, featured courses, categories, social proof; RTL mirrors cleanly | "A bilingual academy, built for the region." |
| 2 | Course catalog `/courses` | Guest | Desktop | EN + AR | 56 courses, category filters, pagination, search returning many hits | "56 courses across 12 verticals — and growing." |
| 3 | Categories `/categories` | Guest | Desktop | EN | 12 populated categories with course counts | "Every discipline your teams need." |
| 4 | Course details `/courses/[public_id]` | Guest | Desktop + Mobile | EN + AR | Curriculum (5–7 sections, dozens of lessons), instructor, price + sale, preview lesson, SEO/OG | "Deep, structured curricula — not just videos." |
| 5 | Lesson player `/learn/[public_id]` | Student `student01` | Desktop | EN + AR | Video/reading lesson, sidebar progress, bookmark + notes, sanitized rich content | "Focused, distraction-free learning." |
| 6 | Student dashboard `/dashboard` | Student `student01` | Desktop + Mobile | EN + AR | Continue-learning, enrolled courses, progress bars, certificates earned | "Learners always know their next step." |
| 7 | My learning `/my-learning` | Student `student01` | Desktop | EN | Multiple enrollments in varied states, filters | "A full learning history from day one." |
| 8 | Certificates `/certificates` | Student `student01` | Desktop | EN + AR | Issued certificates with verification codes | "Verifiable certificates learners can share." |
| 9 | Cart / checkout `/cart` → `/checkout` | Student | Desktop | EN | Cart with a course, coupon field, order summary | "Frictionless enrollment and checkout." |
| 10 | Orders `/orders` | Student | Desktop | EN | Paid/pending orders, invoices | "Clear purchase history and invoices." |
| 11 | Instructor area `/teach` | Instructor `yara.adel` | Desktop | EN | Honest "coming soon" surface (documented) | (Skip for GA marketing; use admin panel for instructor/course management instead.) |
| 12 | Org dashboard `/org` + `/org/organizations` | Org manager | Desktop | EN + AR | Organization, teams, members, seats, learning progress | "Manage learning across your whole company." |
| 13 | Admin dashboard `/admin` | Super admin | Desktop | EN | Platform overview widgets, KPIs populated | "Operate the platform from one panel." |
| 14 | Admin · Users `/admin` → Users | Super admin | Desktop | EN | 616 users, roles column, search, pagination, role assignment | "Governance and access control at scale." |
| 15 | Admin · Courses / Catalog | Super admin | Desktop | EN | 56 courses, sections, lessons, sorting/search | "A complete content operation." |
| 16 | Admin · Orders + refund action | Super admin | Desktop | EN | 1,500 orders (paid/pending/refunded), refund action | "Full commerce control, including refunds." |
| 17 | Admin · Invoices / Coupons / Redemptions | Super admin | Desktop | EN | 1,093 invoices, 24 coupons, 536 redemptions | "Finance and promotions, fully visible." |
| 18 | Admin · Certificates (revoke/reissue) | Super admin | Desktop | EN | 1,298 certificates, revoke/reissue actions | "Trust you can manage." |
| 19 | Admin · Notification & Email Templates | Super admin | Desktop | EN | 39 templates incl. 25 email, editable | "Own every message your platform sends." |
| 20 | Admin · Audit Log | Super admin | Desktop | EN | 20k+ audit rows, searchable | "Every privileged action, on the record." |
| 21 | Analytics `/analytics` + `/dashboards` | Super admin | Desktop | EN | Populated KPI widgets, 365-day trend charts | "A year of trends, at a glance." |
| 22 | Reports `/reports` + `/reports/[public_id]` | Super admin | Desktop | EN | Report definitions with meaningful history | "Board-ready reporting out of the box." |
| 23 | CRM `/crm` + `/crm/leads` + `/crm/accounts` | Super admin | Desktop | EN | 800 leads, 250 companies, pipeline stages, activities | "Sell and serve — CRM built in." |
| 24 | Live sessions `/…live…` (admin) | Super admin | Desktop | EN | 60 sessions (upcoming/completed/cancelled), registrations, attendance | "Blend self-paced and live cohorts." |
| 25 | Notifications `/notifications` | Student `student01` | Desktop + Mobile | EN + AR | In-app notifications, preferences (locale/digest/timezone) | "Learners stay in the loop." |
| 26 | RTL showcase | any | Desktop + Mobile | AR | Any of the above in Arabic — verify mirrored layout, logical spacing | "Truly bilingual — Arabic-first when you need it." |

## Capture tips

- Seed enterprise first (`DEMO_SCALE=enterprise php artisan demo:seed`) so tables paginate and charts fill.
- For each page, capture both **EN (LTR)** and **AR (RTL)** where the caption implies bilingual value (rows 1, 4, 5, 6, 8, 12, 25, 26).
- Use `student01` for a rich, populated learner; pick an org owner/manager from `organization_members` for the B2B shots.
- The instructor product area is "coming soon" by design — demonstrate instructor/course management from the **admin panel** (rows 14–15) rather than `/teach`.
