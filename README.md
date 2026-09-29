# CreateZA — website & Studio dashboard (MVP)

## 1. Project overview

CreateZA is a South African creator-led media company (photography, video, websites, social content, events and creative products). This repo contains:

- **The public website**: Home, About, Services, Gallery, Work, Store, Events and Contact.
- **CreateZA Studio** at `/admin`: a protected dashboard for content planning, approvals, projects, leads, assets, analytics and settings.

Built with **Astro 7** (server-rendered) and deployed on **Vercel**. Data lives in **PostgreSQL (Supabase)** in production and in Node's built-in SQLite locally.

All seeded clients, leads, projects and figures are **clearly labelled demo placeholders**. Replace them before going live.

## 2. Features

**Public website**
- Header navigation: Home | About | Services | Gallery | Work | Store | Events | Contact, plus a "Start a project" button
- **Home:** hero collage, social ribbon, services grid, social content mosaic, selected work, creator community, store preview, upcoming events, call to action and footer
- **Services:** six services, each with a summary, visual and enquiry link (pre-selects the service on the contact form)
- **Gallery:** filterable (All / Photography / Video / Social / Events / Websites / Behind the scenes), managed from the asset library
- **Work:** case studies from projects marked public (overview, deliverables, outcomes, gallery)
- **Store:** catalogue, product pages, cart with quantity controls, checkout, order confirmation. Orders are saved; payment is labelled *coming soon*.
- **Events:** upcoming events, detail pages, registration with capacity and duplicate checks, event archive
- **Contact:** project enquiry form. Every submission creates a lead in the dashboard (honeypot spam trap).
- `/health`: JSON health check including the database

**Studio dashboard (`/admin`)**
- **Overview:** stat cards, week calendar, needs-your-attention list, active projects, recent assets, lead pipeline, performance chart, recent orders and registrations
- **Content calendar:** week grid (Instagram, TikTok, YouTube, Facebook, LinkedIn) with navigation, and a filterable list
- **Content editor:** title, project, platforms, format, caption counter, upload or pick assets, publish date and time, status, assignee, internal notes, Save draft / Send for approval
- **Approvals:** preview, comment thread, version history with snapshots, Approve / Request changes (note required), activity log; resubmitting bumps the version
- **Projects:** client (existing or new), service, team, dates, status, deliverables, progress, notes, public case-study toggle
- **Leads:** Kanban (New → Contacted → Discovery → Proposal → Won → Onboarding → Lost) with drag-and-drop and a keyboard-friendly fallback; detail page with notes, follow-up date, assignee and activity history
- **Asset library:** multi-file upload, search, type/project/tag filters, preview, tags, attach to content, show in public gallery, delete with confirmation
- **Analytics:** editable monthly figures per platform, plus computed published count, monthly volume and project completion rate
- **Settings:** company details, brand colours (applied site-wide), logo upload, social links, notifications, team and roles, store orders, event registrations, password change, activity log

**Roles:** Admin (everything), Team member (all except settings/users), Client (sees and approves their own client's content, projects and assets only).

## 3. Technology stack

- Astro 7 (`output: 'server'`) with `@astrojs/vercel` (Node 24 functions)
- PostgreSQL via `postgres` (Supabase, transaction pooler) — or `node:sqlite` locally, no native modules
- Server-rendered pages with vanilla CSS and JS in `public/assets/` (no client framework)
- Passwords hashed with Node's scrypt; sessions and uploaded files stored in the database (works on serverless hosts with no disk)

## 4. Local setup

Requires **Node 22.13+** (Node 24 recommended).

```bash
npm install
npm run dev
```

Open http://localhost:4321. The SQLite database (`storage/createza.sqlite`) is created and seeded on first request.

## 5. Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `DATABASE_URL` / `POSTGRES_URL` | — | PostgreSQL connection string. When set, Postgres is used instead of SQLite. |
| `DEMO_PASSWORD` | `CreateZA-demo-2026` | Password for the seeded demo accounts (applies when the database is first seeded) |
| `DB_AUTO_SETUP` | `true` | Create the schema and seed demo data automatically when the database is empty |
| `DB_PATH` | `storage/createza.sqlite` (`/tmp/…` on Vercel) | SQLite file when no Postgres URL is set |
| `UPLOAD_MAX_MB` | `4` on Vercel, `25` locally | Maximum size per uploaded file |
| `APP_TIMEZONE` | `Africa/Johannesburg` | Timezone for dates |

Copy `.env.example` to `.env` for local overrides. `.env` is never committed.

## 6. Database, migrations and seed

The schema lives in `src/lib/migrations.ts` and is written once for both SQLite and PostgreSQL. It's applied automatically on first use, along with the demo data from `src/lib/seed.ts`.

```bash
npm run db:fresh             # wipe and rebuild local SQLite with demo data
npm run db:fresh -- --yes    # same against DATABASE_URL (PostgreSQL) — destructive
```

On PostgreSQL every table has **row-level security enabled with no policies**, so Supabase's public Data API (anon key) can't read anything; only the app's server connection can.

## 7. Demo login credentials

Sign in at `/admin`. All accounts use the `DEMO_PASSWORD` value (default `CreateZA-demo-2026`).

| Role | Email |
|---|---|
| Admin | `admin@createza.test` |
| Team member | `team@createza.test` |
| Team member | `designer@createza.test` |
| Client (Coastal Café demo client) | `client@createza.test` |

**Change the password (Settings → My account) or remove these accounts before sharing the live site.**

## 8. Deploying (GitHub → Vercel, Supabase)

1. Push to `main` on GitHub; the Vercel project (`createza`) deploys automatically. `vercel.json` sets the Astro framework and the `dub1` (Dublin) region next to the database.
2. **Database:** in Supabase create the project **`createzadb`** (region *West EU (Ireland)*). Then either:
   - Supabase → Project settings → **Integrations → Vercel** → connect the `createza` project (sets `POSTGRES_URL` automatically), or
   - copy **Connect → Transaction pooler** (port 6543) into the Vercel project as `DATABASE_URL`.
3. Redeploy. The first request builds the schema and loads the demo data.

Without a database URL the site runs in **demo mode** (SQLite in `/tmp`, resets whenever Vercel recycles the function).

## 9. Docker

Not required for Vercel. To run elsewhere: `npm ci && npm run build`, then serve with any Node 24 host, or swap the adapter for `@astrojs/node`.

## 10. Testing

```bash
npm test                                       # 19 unit tests (auth, leads, content/approvals, projects, store, uploads)
npm run smoke -- http://localhost:4321         # 109 end-to-end checks against a running site
```

The smoke test creates records (lead, order, project, upload…), so run `npm run db:fresh` afterwards for clean demo data.

## 11. Deployment checklist

- [ ] Supabase `createzadb` created and connected (`POSTGRES_URL`/`DATABASE_URL` set in Vercel)
- [ ] `/health` returns `"status":"ok"` and `"driver":"pgsql"`
- [ ] Demo account passwords changed or accounts removed; demo clients/leads/projects replaced
- [ ] Company details, brand colours, logo and social links set in **Settings**
- [ ] Supabase advisors show no security warnings (RLS enabled on all tables)
- [ ] Custom domain added in Vercel (HTTPS is automatic)
- [ ] Payment provider integrated before taking real payments (checkout currently only records orders)

## 12. Security notes

- Passwords: scrypt with per-user salt, constant-time comparison
- Sessions: random 256-bit IDs in HttpOnly, SameSite=Lax, Secure cookies; stored server-side; ID rotated on login and logout
- Login rate limiting: 5 failed attempts per email or IP in 15 minutes
- CSRF: every POST needs the session token, plus Astro's origin check
- SQL: parameterised queries everywhere; output escaped by Astro
- Access control: role checks in middleware and handlers; client users only ever get queries scoped to their client
- Uploads: file type detected from content (magic bytes), allow-list only (no SVG/HTML/scripts), size limit, random names, served with fixed content types
- Headers: CSP (no inline scripts, no third-party origins), X-Frame-Options, nosniff, Referrer-Policy; dashboard responses are `no-store`
- Supabase: RLS on every table blocks the public Data API

## 13. Folder structure

```
├── public/assets/        CSS, JS, fonts, logo, media, product images
├── src/
│   ├── components/       shared UI (cards, forms, calendar, chart, icons)
│   ├── layouts/          public site and dashboard layouts
│   ├── lib/              database, auth, sessions, business logic, seed, migrations
│   ├── pages/            routes: public site, /admin/*, /health, /uploads/*
│   └── middleware.ts     sessions, CSRF, auth guard, security headers
├── scripts/db-fresh.ts   rebuild the database
├── tests/                unit tests (node:test) and end-to-end smoke test
├── astro.config.mjs
└── vercel.json
```

Earlier versions (the Annike template and a PHP build of this MVP) are archived in the workspace under `Admin/Archive/` and remain in git history.
