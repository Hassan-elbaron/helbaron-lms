# Demo Accounts

All demo accounts share the password **`password`** (configurable via `DEMO_USER_PASSWORD`). Demo users use the **`@demo.helbaron.local`** email domain; the platform super-admin uses `@helbaron.local` (created by the base `IdentitySeeder`). Counts below are for the **enterprise** profile (measured); the **showcase** profile has 6 instructors + 24 students.

> Never use these accounts, emails, or organizations as real identities — every person and company here is fictional.

## Platform administrator

| Role | Email | Password | Where |
|---|---|---|---|
| Super admin | `admin@helbaron.local` | `password` | Filament admin at `/admin` (+ full app) |

The super admin can manage users/roles, courses, orders (incl. refunds), certificates (revoke/reissue), notification & email templates, certificate settings, coupons/invoices/redemptions, the audit log, and analytics.

## Instructors (16, enterprise)

All at `<localpart>@demo.helbaron.local`, role `instructor`, with a professional bio on their profile.

| Name | Login local-part | Focus |
|---|---|---|
| Yara Adel | `yara.adel` | Program/project management (PMP) |
| Omar Farouk | `omar.farouk` | Leadership & operations |
| Nour Hassan | `nour.hassan` | AI product strategy |
| Laila Mansour | `laila.mansour` | Growth marketing |
| Karim Saleh | `karim.saleh` | Finance & analysis (CFA) |
| Huda Rashid | `huda.rashid` | Agile / Scrum |
| Tariq Nabil | `tariq.nabil` | Entrepreneurship |
| Salma Idris | `salma.idris` | B2B sales |
| Rami Fouad | `rami.fouad` | Corporate strategy |
| Dina Wahba | `dina.wahba` | People operations |
| Hani Mostafa | `hani.mostafa` | Data visualization |
| Mona Kassem | `mona.kassem` | Negotiation |
| Ziad Halabi | `ziad.halabi` | Product operations |
| Rasha Amin | `rasha.amin` | Brand & content |
| Fadi Costa | `fadi.costa` | Investment / personal finance |
| Aya Sobhi | `aya.sobhi` | Change management |

Example login: `yara.adel@demo.helbaron.local` / `password`.

> The instructor **product area** (`/teach/*`) ships as an honest "coming soon" surface because the Instructor backend context is not built (see `docs/reviews/FINAL_PRODUCT_COMPLETION_REPORT.md`). Instructors above own courses (via the course-trainer link) and appear across the catalog, course pages, and analytics; use the **admin panel** to demonstrate instructor/course management.

## Students (600 enterprise / 24 showcase)

Deterministic accounts `student01@demo.helbaron.local` … `student600@demo.helbaron.local`, role `student`, each with a profile, enrollments (varied dates/progress), some completions + certificates, bookmarks, notes, and orders.

Recommended demo students (varied states — verify live, as exact per-student state is RNG-deterministic):

- `student01@demo.helbaron.local` — an active learner with multiple enrollments and at least one certificate.
- `student05@…`, `student12@…`, `student27@…` — mixed in-progress / completed states for showing progress bars and "continue learning".
- Any `studentNN@…` up to the profile's student count.

## Organizations & organization managers

The enterprise profile creates **40 demo organizations** across Technology / Healthcare / Education / Government / Manufacturing / Retail / Finance / Logistics, each with departments, teams, a seat pool, and members. Organization members carry a CRM `MemberRole` (owner / admin / manager / member) — the **managers/owners** are your "organization manager" personas for the org dashboard and B2B story. Members are linked to demo student users, so an org manager's team shows real enrollments and progress.

To find a specific org manager account live: in the admin panel open **CRM → Organizations → (a demo org) → members**, or query `organization_members` for `role in ('owner','admin','manager')` joined to the member's user email.

## CRM & sales personas (data, not logins)

CRM entities are demo **data** for the sales/CRM screens (they are not all login accounts): 250 companies, 1,000 contacts, 800 leads across pipeline stages, 400 opportunities (open/won/lost), 5,000 activities, 4,000 notes. Drive these from the admin/CRM screens under the super-admin login.

## Quick reference

| Persona | Login | Password |
|---|---|---|
| Super admin / everything | `admin@helbaron.local` | `password` |
| Instructor (course owner) | `yara.adel@demo.helbaron.local` | `password` |
| Student (learner) | `student01@demo.helbaron.local` | `password` |
| Org manager | an `organization_members` owner/manager's user email | `password` |

All logins go through the standard web login; the super admin additionally reaches Filament at `/admin`.
