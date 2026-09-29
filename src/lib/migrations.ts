/**
 * Schema migrations, written once and expanded for SQLite and PostgreSQL.
 * {{ID}} = auto-increment primary key, {{NOW}} = local timestamp default.
 * On PostgreSQL (Supabase) row-level security is enabled on every table with no policies,
 * so the public Data API cannot read anything; the app connects as the table owner.
 */

const TABLES = `
CREATE TABLE clients (
  id {{ID}},
  name TEXT NOT NULL,
  contact_name TEXT,
  email TEXT,
  phone TEXT,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE users (
  id {{ID}},
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'team' CHECK (role IN ('admin','team','client')),
  client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
  title TEXT,
  is_active INTEGER NOT NULL DEFAULT 1,
  last_login_at TEXT,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE login_attempts (
  id {{ID}},
  email TEXT NOT NULL,
  ip TEXT NOT NULL,
  attempted_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE leads (
  id {{ID}},
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT,
  organisation TEXT,
  service TEXT,
  budget TEXT,
  details TEXT,
  preferred_start TEXT,
  status TEXT NOT NULL DEFAULT 'new' CHECK (status IN ('new','contacted','discovery','proposal','won','onboarding','lost')),
  source TEXT NOT NULL DEFAULT 'website',
  follow_up_date TEXT,
  assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
  notes TEXT,
  created_at TEXT NOT NULL DEFAULT {{NOW}},
  updated_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE lead_activities (
  id {{ID}},
  lead_id INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
  user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  body TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE projects (
  id {{ID}},
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
  description TEXT,
  service_type TEXT,
  start_date TEXT,
  due_date TEXT,
  status TEXT NOT NULL DEFAULT 'planning' CHECK (status IN ('planning','in_progress','in_review','completed','on_hold')),
  deliverables TEXT,
  progress INTEGER NOT NULL DEFAULT 0 CHECK (progress BETWEEN 0 AND 100),
  notes TEXT,
  is_public INTEGER NOT NULL DEFAULT 0,
  outcomes TEXT,
  cover_image TEXT,
  created_at TEXT NOT NULL DEFAULT {{NOW}},
  updated_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE project_members (
  project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  PRIMARY KEY (project_id, user_id)
);

CREATE TABLE content_items (
  id {{ID}},
  title TEXT NOT NULL,
  project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL,
  format TEXT NOT NULL DEFAULT 'Photo',
  caption TEXT,
  publish_at TEXT,
  status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('idea','draft','in_production','awaiting_approval','approved','scheduled','published')),
  assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
  internal_notes TEXT,
  version INTEGER NOT NULL DEFAULT 1,
  created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
  created_at TEXT NOT NULL DEFAULT {{NOW}},
  updated_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE content_platforms (
  content_id INTEGER NOT NULL REFERENCES content_items(id) ON DELETE CASCADE,
  platform TEXT NOT NULL CHECK (platform IN ('instagram','tiktok','youtube','facebook','linkedin')),
  PRIMARY KEY (content_id, platform)
);

CREATE TABLE content_comments (
  id {{ID}},
  content_id INTEGER NOT NULL REFERENCES content_items(id) ON DELETE CASCADE,
  user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  version INTEGER NOT NULL DEFAULT 1,
  body TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE content_approvals (
  id {{ID}},
  content_id INTEGER NOT NULL REFERENCES content_items(id) ON DELETE CASCADE,
  user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  action TEXT NOT NULL CHECK (action IN ('submitted','approved','changes_requested')),
  version INTEGER NOT NULL DEFAULT 1,
  note TEXT,
  snapshot TEXT,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE assets (
  id {{ID}},
  title TEXT NOT NULL,
  path TEXT NOT NULL,
  mime TEXT NOT NULL,
  type TEXT NOT NULL CHECK (type IN ('image','video','design','document')),
  size INTEGER NOT NULL DEFAULT 0,
  project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL,
  public_category TEXT,
  uploaded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE content_assets (
  content_id INTEGER NOT NULL REFERENCES content_items(id) ON DELETE CASCADE,
  asset_id INTEGER NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
  PRIMARY KEY (content_id, asset_id)
);

CREATE TABLE asset_tags (
  id {{ID}},
  name TEXT NOT NULL UNIQUE
);

CREATE TABLE asset_tag_map (
  asset_id INTEGER NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
  tag_id INTEGER NOT NULL REFERENCES asset_tags(id) ON DELETE CASCADE,
  PRIMARY KEY (asset_id, tag_id)
);

CREATE TABLE events (
  id {{ID}},
  title TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  summary TEXT,
  description TEXT,
  location TEXT,
  starts_at TEXT NOT NULL,
  capacity INTEGER NOT NULL DEFAULT 0,
  image TEXT,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE event_registrations (
  id {{ID}},
  event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT,
  guests INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT {{NOW}},
  UNIQUE (event_id, email)
);

CREATE TABLE products (
  id {{ID}},
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  description TEXT,
  price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
  stock INTEGER NOT NULL DEFAULT 0,
  image TEXT,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE orders (
  id {{ID}},
  reference TEXT NOT NULL UNIQUE,
  customer_name TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT,
  address TEXT NOT NULL,
  city TEXT NOT NULL,
  postal_code TEXT NOT NULL,
  subtotal_cents INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'pending_payment',
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE order_items (
  id {{ID}},
  order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
  product_name TEXT NOT NULL,
  unit_price_cents INTEGER NOT NULL,
  quantity INTEGER NOT NULL CHECK (quantity > 0),
  line_total_cents INTEGER NOT NULL
);

CREATE TABLE settings (
  key TEXT PRIMARY KEY,
  value TEXT
);

CREATE TABLE activity_logs (
  id {{ID}},
  user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  action TEXT NOT NULL,
  description TEXT,
  ip TEXT,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE TABLE analytics_metrics (
  id {{ID}},
  period TEXT NOT NULL,
  platform TEXT NOT NULL,
  posts_published INTEGER NOT NULL DEFAULT 0,
  reach INTEGER NOT NULL DEFAULT 0,
  engagement_rate REAL NOT NULL DEFAULT 0,
  top_format TEXT,
  UNIQUE (period, platform)
);

CREATE TABLE sessions (
  id TEXT PRIMARY KEY,
  data TEXT NOT NULL,
  last_activity INTEGER NOT NULL
);
CREATE INDEX idx_sessions_activity ON sessions (last_activity);

CREATE TABLE stored_files (
  name TEXT PRIMARY KEY,
  mime TEXT NOT NULL,
  size INTEGER NOT NULL,
  data TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT {{NOW}}
);

CREATE INDEX idx_leads_status ON leads (status);
CREATE INDEX idx_content_publish ON content_items (publish_at);
CREATE INDEX idx_content_status ON content_items (status);
CREATE INDEX idx_login_attempts ON login_attempts (email, attempted_at);
`;

export const TABLE_NAMES = [...TABLES.matchAll(/CREATE TABLE (\w+)/g)].map((m) => m[1]);

const PG_NOW = "to_char(now() AT TIME ZONE 'Africa/Johannesburg', 'YYYY-MM-DD HH24:MI:SS')";

const sqlite = TABLES.replaceAll('{{ID}}', 'INTEGER PRIMARY KEY AUTOINCREMENT').replaceAll('{{NOW}}', "(datetime('now', 'localtime'))");

const pgsql = `
CREATE OR REPLACE FUNCTION now_local() RETURNS text AS $$ SELECT ${PG_NOW} $$ LANGUAGE sql STABLE SET search_path = '';
${TABLES.replaceAll('{{ID}}', 'SERIAL PRIMARY KEY').replaceAll('{{NOW}}', PG_NOW)}
${[...TABLE_NAMES, 'migrations'].map((t) => `ALTER TABLE ${t} ENABLE ROW LEVEL SECURITY;`).join('\n')}
`;

export const MIGRATIONS = [{ name: '001_createza_schema', sqlite, pgsql }];
