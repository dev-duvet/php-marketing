# CreateZA — website & Studio dashboard (MVP)

## 1. Project overview

CreateZA is a South African creator-led media company (photography, video, websites, social content, events and creative products). This repository contains:

- **The public website**: Home, About, Services, Gallery, Work, Store, Events and Contact.
- **CreateZA Studio** at `/admin`: a protected dashboard for content planning, approvals, projects, leads, assets, analytics and settings.

It's plain PHP 8.2+ on SQLite, with no framework and no Composer dependencies. All seeded clients, leads, projects and figures are **clearly labelled demo placeholders**. Replace them before going live.

## 2. Features

**Public website**
- Header navigation: Home | About | Services | Gallery | Work | Store | Events | Contact, plus a "Start a project" button
- **Home:** hero collage, social ribbon, services grid, latest social content, selected work, creator community, store preview, upcoming events, call to action and footer
- **Services:** six services, each with a summary, a visual and an enquiry link that pre-selects the service on the contact form
- **Gallery:** filterable (All / Photography / Video / Social / Events / Websites / Behind the scenes). The team controls it from the asset library.
- **Work:** case studies built from projects marked public, with overview, deliverables, outcomes and a gallery
- **Store:** catalogue, product pages, a cart with quantity controls, checkout and an order confirmation. Orders are saved to the database. Payments are labelled *coming soon*.
- **Events:** upcoming events, detail pages, registration with a capacity check and duplicate-email protection, plus an event archive
- **Contact:** the project enquiry form. Every submission creates a lead in the dashboard. There's a honeypot field against spam.
- `/health`: a JSON health check that includes a database check

**Studio dashboard (`/admin`)**
- **Overview:** stat cards, this week's calendar, the needs-your-attention list, active projects, recent assets, the lead pipeline, a performance chart, and recent orders and registrations
- **Content calendar:** a weekly platform × day grid for Instagram, TikTok, YouTube, Facebook and LinkedIn, with week navigation and a filterable list of all content
- **Content editor:** title, project, platforms, format, caption with a character counter, upload or pick assets, publish date and time, status, assignee, internal notes, Save draft and Send for approval
- **Approvals:** preview, comment thread, version history with snapshots, Approve and Request changes (a note is required), and an activity log. Resubmitting bumps the version.
- **Projects:** create, edit and view projects with client (existing or new), service, team members, dates, status, deliverables, a progress slider, notes and a public case-study toggle
- **Leads:** a Kanban board (New → Contacted → Discovery → Proposal → Won → Onboarding → Lost) with drag-and-drop and a keyboard-friendly select fallback. Each lead has a detail page with contact details, service, budget, notes, follow-up date, assignee and activity history.
- **Asset library:** multi-file upload, search, filters by type, project and tag, previews, tags, attach to content, show in the public gallery, and delete with confirmation
- **Analytics:** monthly figures you edit by hand per platform (posts, reach, engagement, top format), plus computed content-published count, monthly volume and project completion rate
- **Settings:** company details, brand colours (applied to the site), logo upload, social links, notification preferences, team members and roles, store orders, event registrations, a password change form and the activity log

**Roles**
- **Admin:** everything, including settings and user management
- **Team member:** content, projects, leads, assets and analytics
- **Client:** sees and approves content, projects and assets for their own client only

## 3. Technology stack

- PHP 8.2+ (tested on 8.3) with the `pdo_sqlite`, `fileinfo` and `mbstring` extensions
- SQLite through PDO, using prepared statements everywhere
- Server-rendered PHP views, modern CSS and vanilla JavaScript. There's no build step and nothing loads from a CDN.
- Self-hosted Poppins font (OFL licence in `public/assets/fonts/OFL.txt`)

## 4. Local setup

```bash
cp .env.example .env
php -S localhost:8000 -t public public/router.php
```

Open http://localhost:8000. On the first request the database is created in `storage/createza.sqlite` and the demo data is loaded automatically.

**Windows (PHP installed with winget):** PHP ships without an active `php.ini`. Copy `php.ini-development` to `php.ini` in the PHP folder and add:

```ini
extension_dir = "ext"
extension = pdo_sqlite
extension = fileinfo
extension = mbstring
upload_max_filesize = 50M
post_max_size = 60M
```

## 5. Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `APP_ENV` | `local` | `production` or `local` (informational; `APP_DEBUG` controls error output) |
| `APP_DEBUG` | `false` | `true` shows error details. Keep it `false` in production. |
| `APP_TIMEZONE` | `Africa/Johannesburg` | Timezone for dates |
| `DB_PATH` | `storage/createza.sqlite` | SQLite file (relative to the project root, or absolute) |
| `DATABASE_URL` | — | PostgreSQL URL (`postgres://user:pass@host/db`). When set, it is used instead of SQLite. `POSTGRES_URL` also works. |
| `UPLOAD_DRIVER` | `local` | `local` saves files to `public/uploads/`; `database` stores them in the database (used on Vercel) |
| `DB_AUTO_SETUP` | `true` | Build and seed the database automatically if the file is missing |
| `DEMO_PASSWORD` | `CreateZA-demo-2026` | Password for the seeded demo accounts |
| `UPLOAD_MAX_MB` | `50` | Maximum size per uploaded file |
| `SESSION_SECURE` | `false` | Set to `true` behind HTTPS so the session cookie is Secure-only |

Real environment variables take precedence over `.env`.

## 6. Database migration and seed commands

```bash
php database/migrate.php            # run pending migrations
php database/migrate.php --seed     # migrate, then load demo data into an empty database
php database/migrate.php --fresh    # delete the database, rebuild it and reload demo data
```

The schema is in `database/migrations/sqlite/` (local) and `database/migrations/pgsql/` (PostgreSQL). The demo data comes from `app/Services/Seeder.php`.

## 7. Demo login credentials

Sign in at http://localhost:8000/admin. Every account uses the `DEMO_PASSWORD` value (default `CreateZA-demo-2026`).

| Role | Email |
|---|---|
| Admin | `admin@createza.test` |
| Team member | `team@createza.test` |
| Team member | `designer@createza.test` |
| Client (Coastal Café demo client) | `client@createza.test` |

Change or remove these accounts, and set a new `DEMO_PASSWORD`, before deploying.

## 8. Deploying to Vercel

The repo deploys to Vercel as is. `vercel.json` serves `public/` as static files and sends every other request to `api/index.php`, which runs on the community [`vercel-php`](https://github.com/vercel-community/php) runtime (PHP 8.3).

1. Import the GitHub repo into Vercel. There's no build command, and pushes to `main` deploy automatically.
2. **Persistent data:** open the Vercel project → **Storage** → **Create database** → **Neon (Postgres)** → **Connect** to this project. That sets `DATABASE_URL` automatically.
3. Redeploy. On the first request the app creates the Postgres schema and loads the demo data.

Without a database connected, the site runs in **demo mode**: SQLite in `/tmp`, which resets whenever Vercel recycles the function. On Vercel, sessions and uploads are stored in the database, and uploads are capped at 4 MB because Vercel's request body limit is 4.5 MB.

Optional project environment variables: `DEMO_PASSWORD` (set it before the first deploy with a database), `APP_DEBUG`, `UPLOAD_MAX_MB`.

## 9. Docker instructions

Docker packaging (a `Dockerfile` and `docker-compose.yml`) is the next step and isn't in this version yet. The app has no dependencies beyond PHP and its SQLite extension, so any `php:8.3-apache` image will work: set the document root to `public/`, enable `mod_rewrite`, and mount `storage/` and `public/uploads/` as volumes.

## 10. Testing instructions

```bash
php tests/run.php                                   # 19 unit tests: auth, leads, content/approvals, projects (in-memory DB)
php tests/smoke.php http://localhost:8000           # 100 end-to-end checks against a running server
```

Run the smoke test against a freshly seeded database (`php database/migrate.php --fresh`). It creates test records (a lead, an order, a project and so on), so re-seed afterwards if you want clean demo data.

## 11. Deployment checklist

- [ ] PHP 8.2+ with `pdo_sqlite`, `fileinfo` and `mbstring`
- [ ] Web server document root points to `public/` (Apache: `public/.htaccess` handles rewrites; Nginx: `try_files $uri /index.php?$query_string;`)
- [ ] `.env` created from `.env.example`, with `APP_ENV=production`, `APP_DEBUG=false` and `SESSION_SECURE=true`
- [ ] HTTPS enabled
- [ ] `storage/` and `public/uploads/` writable by the web server and **not** web-accessible except `public/uploads/`
- [ ] Nginx only: deny script execution in `/uploads/` (Apache uses `public/uploads/.htaccess`)
- [ ] PHP `upload_max_filesize` and `post_max_size` at least `UPLOAD_MAX_MB`
- [ ] Demo accounts removed or passwords changed, and demo clients, leads and projects replaced with real data
- [ ] Company details, brand colours, logo and social links set in **Settings**
- [ ] `/health` returns `{"status":"ok"}`
- [ ] Back up `storage/createza.sqlite` and `public/uploads/` regularly
- [ ] Payment provider integrated before taking real payments. Checkout currently only records orders.

## 12. Security notes

- Passwords are hashed with `password_hash` and checked with `password_verify`, and hashes are upgraded automatically.
- Sessions use HttpOnly, SameSite=Lax cookies (Secure when `SESSION_SECURE=true`) with strict mode, and the session ID is regenerated at login and logout.
- **Login rate limiting:** 5 failed attempts per email or IP within 15 minutes locks sign-in.
- Every POST requires a CSRF token (a form field or the `X-CSRF-Token` header).
- All SQL goes through PDO prepared statements. All output is escaped with `htmlspecialchars`.
- Routes are protected by role on the server, and client users only ever get queries scoped to their own client.
- **Uploads:**
  - The file type is detected from the content (`finfo`), not the file name, and only a set list of image, video, PDF and design types is allowed.
  - Each file has a size limit and gets a random file name.
  - Script execution is disabled in `public/uploads/`, and SVG uploads are not accepted.
- **Security headers:** Content-Security-Policy (no inline scripts, no third-party origins), `X-Frame-Options`, `X-Content-Type-Options` and `Referrer-Policy`.
- In production, errors go to `storage/logs/app.log` and users see a generic error page with no stack traces.
- Secrets are kept out of git: `.env`, the database, logs and uploads are all in `.gitignore`.
- Deleting content or assets needs an explicit confirmation step.

## 13. Folder structure

```
├── api/index.php           Vercel serverless entry point
├── app/
│   ├── Controllers/        public site controllers + Admin/ dashboard controllers
│   ├── Helpers/            global helper functions (escaping, queries, formatting)
│   ├── Services/           Router, Database, Auth, Upload, Validator, business logic, Seeder
│   ├── Views/              layouts, partials, site/, store/, events/, admin/, errors/
│   └── bootstrap.php       autoloader, .env loading, error handling
├── config/routes.php       every route and the roles allowed on it
├── database/
│   ├── migrations/         SQL schema (sqlite/ and pgsql/)
│   └── migrate.php         migrate / seed CLI
├── public/                 web root: index.php, router.php, assets/, uploads/
├── storage/                SQLite database and logs (not committed)
├── tests/                  run.php + *Test.php unit tests, smoke.php end-to-end check
├── vercel.json             Vercel routing and PHP runtime
└── .env.example
```

The old Annike static template this repo used to hold is archived in `Admin/Archive/php-marketing Annike template (replaced by CreateZA MVP, 2026-09-28)/` in the workspace, and it's also in git history.
