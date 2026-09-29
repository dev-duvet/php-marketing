/**
 * Database access for SQLite (node:sqlite, local + demo) and PostgreSQL (Supabase/Neon via DATABASE_URL).
 * Queries use `?` placeholders and a portable SQL subset; `now_local()` exists on both engines.
 */
import { AsyncLocalStorage } from 'node:async_hooks';
import { mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import { DatabaseSync } from 'node:sqlite';
import postgres from 'postgres';
import { env } from './env.ts';
import { nowLocal } from './format.ts';
import { MIGRATIONS } from './migrations.ts';

export type Row = Record<string, any>;
type Param = string | number | null | undefined | boolean;

let sqlite: DatabaseSync | null = null;
let pg: postgres.Sql | null = null;
let ready: Promise<void> | null = null;
const txScope = new AsyncLocalStorage<postgres.TransactionSql>();
// Queries issued while the schema is being set up (i.e. by the seeder) must not wait on setup itself.
const setupScope = new AsyncLocalStorage<boolean>();

function awaitReady(): Promise<void> {
  return setupScope.getStore() ? Promise.resolve() : ensureReady();
}

export function databaseUrl(): string {
  return env('DATABASE_URL') || env('POSTGRES_URL');
}

export function driver(): 'pgsql' | 'sqlite' {
  return databaseUrl() ? 'pgsql' : 'sqlite';
}

function sqlitePath(): string {
  return env('DB_PATH', process.env.VERCEL ? '/tmp/createza.sqlite' : 'storage/createza.sqlite');
}

function connectSqlite(): DatabaseSync {
  if (!sqlite) {
    const path = sqlitePath();
    if (path !== ':memory:') mkdirSync(dirname(path), { recursive: true });
    sqlite = new DatabaseSync(path);
    sqlite.exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    if (path !== ':memory:') sqlite.exec('PRAGMA journal_mode = WAL;');
    sqlite.function('now_local', () => nowLocal());
  }
  return sqlite;
}

function connectPg(): postgres.Sql {
  if (!pg) {
    // Drop query params (e.g. Supabase's `supa=base-pooler.x`, `sslmode`) so they aren't sent as server settings.
    const url = new URL(databaseUrl());
    const local = ['localhost', '127.0.0.1'].includes(url.hostname);
    url.search = '';
    pg = postgres(url.toString(), {
      ssl: local ? false : 'require',
      prepare: false, // required for Supabase/pgBouncer transaction pooling
      max: PG_MAX,
      idle_timeout: 20,
      connect_timeout: 10,
      onnotice: () => {},
    });
  }
  return pg;
}

/*
 * postgres.js pipelines extra queries onto busy connections once all `max` are in use.
 * Supabase's transaction pooler (Supavisor) stalls on pipelined queries, so cap in-flight
 * work at the pool size and let the rest wait here instead. Transactions hold one slot.
 */
const PG_MAX = 3;
let activeSlots = 0;
const slotWaiters: (() => void)[] = [];

function acquireSlot(): Promise<void> {
  if (activeSlots < PG_MAX) {
    activeSlots++;
    return Promise.resolve();
  }
  return new Promise((resolve) => slotWaiters.push(resolve)); // slot is handed over on release
}

function releaseSlot(): void {
  const next = slotWaiters.shift();
  if (next) next();
  else activeSlots--;
}

async function limited<T>(fn: () => Promise<T>): Promise<T> {
  if (txScope.getStore()) return fn(); // already inside a transaction's reserved connection
  await acquireSlot();
  try {
    return await fn();
  } finally {
    releaseSlot();
  }
}

function toPg(sql: string): string {
  let i = 0;
  return sql.replace(/\?/g, () => `$${++i}`);
}

function clean(params: Param[]): (string | number | null)[] {
  return params.map((p) => (p === undefined ? null : typeof p === 'boolean' ? (p ? 1 : 0) : p));
}

// int8/numeric come back as strings from postgres.js; normalise them to numbers.
const NUMERIC_OIDS = new Set([20, 1700, 700, 701]);
function normalise(result: postgres.RowList<Row[]>): Row[] {
  const numeric = (result.columns ?? []).filter((c) => NUMERIC_OIDS.has(c.type)).map((c) => c.name);
  if (!numeric.length) return [...result];
  return result.map((row) => {
    for (const name of numeric) if (row[name] !== null) row[name] = Number(row[name]);
    return row;
  });
}

async function rawAll(sql: string, params: Param[] = []): Promise<Row[]> {
  if (driver() === 'pgsql') {
    return limited(async () => normalise(await (txScope.getStore() ?? connectPg()).unsafe(toPg(sql), clean(params) as any[])));
  }
  return connectSqlite().prepare(sql).all(...clean(params)).map((r) => ({ ...r }));
}

async function rawRun(sql: string, params: Param[] = []): Promise<number> {
  if (driver() === 'pgsql') {
    return limited(async () => (await (txScope.getStore() ?? connectPg()).unsafe(toPg(sql), clean(params) as any[])).count);
  }
  return Number(connectSqlite().prepare(sql).run(...clean(params)).changes);
}

async function execScript(sql: string): Promise<void> {
  if (driver() === 'pgsql') {
    const conn = txScope.getStore() ?? connectPg();
    await conn.unsafe(sql);
  } else {
    connectSqlite().exec(sql);
  }
}

/** Build the schema on first use and seed demo data when there are no users yet. */
export function ensureReady(): Promise<void> {
  if (!ready) {
    ready = (async () => {
      const { seed } = await import('./seed.ts');
      if (env('DB_AUTO_SETUP', 'true') !== 'true') return;
      await setupScope.run(true, () =>
        transaction(async () => {
          if (driver() === 'pgsql') await rawAll('SELECT pg_advisory_xact_lock(727274)');
          await migrate();
          const users = await rawAll('SELECT COUNT(*) AS n FROM users');
          if (Number(users[0].n) === 0) await seed();
        }),
      );
    })().catch((err) => {
      ready = null;
      throw err;
    });
  }
  return ready;
}

export async function migrate(): Promise<void> {
  await execScript('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY, ran_at TEXT)');
  const done = new Set((await rawAll('SELECT name FROM migrations')).map((r) => r.name));
  for (const m of MIGRATIONS) {
    if (done.has(m.name)) continue;
    await execScript(driver() === 'pgsql' ? m.pgsql : m.sqlite);
    await rawRun('INSERT INTO migrations (name, ran_at) VALUES (?, ?)', [m.name, nowLocal()]);
  }
}

export async function all(sql: string, params: Param[] = []): Promise<Row[]> {
  await awaitReady();
  return rawAll(sql, params);
}

export async function one(sql: string, params: Param[] = []): Promise<Row | null> {
  return (await all(sql, params))[0] ?? null;
}

export async function val<T = any>(sql: string, params: Param[] = []): Promise<T | null> {
  const row = await one(sql, params);
  return row ? (Object.values(row)[0] as T) : null;
}

export async function count(sql: string, params: Param[] = []): Promise<number> {
  return Number((await val(sql, params)) ?? 0);
}

export async function run(sql: string, params: Param[] = []): Promise<number> {
  await awaitReady();
  return rawRun(sql, params);
}

/** INSERT a row and return its new id. */
export async function insert(table: string, data: Record<string, Param>): Promise<number> {
  await awaitReady();
  const cols = Object.keys(data);
  const sql = `INSERT INTO ${table} (${cols.join(', ')}) VALUES (${cols.map(() => '?').join(', ')})`;
  if (driver() === 'pgsql') {
    const rows = await rawAll(`${sql} RETURNING id`, Object.values(data));
    return Number(rows[0].id);
  }
  const info = connectSqlite().prepare(sql).run(...clean(Object.values(data)));
  return Number(info.lastInsertRowid);
}

/** Run fn atomically. Nested calls join the outer transaction. */
export async function transaction<T>(fn: () => Promise<T>): Promise<T> {
  if (driver() === 'pgsql') {
    if (txScope.getStore()) return fn();
    await acquireSlot(); // the transaction reserves one pooled connection for its whole duration
    try {
      return (await connectPg().begin((tx) => txScope.run(tx, fn))) as T;
    } finally {
      releaseSlot();
    }
  }
  const db = connectSqlite();
  if (db.isTransaction) return fn();
  db.exec('BEGIN');
  try {
    const result = await fn();
    db.exec('COMMIT');
    return result;
  } catch (err) {
    db.exec('ROLLBACK');
    throw err;
  }
}

/** String aggregation that works on both engines. */
export function groupConcat(expr: string, separator = ','): string {
  const sep = `'${separator.replace(/'/g, "''")}'`;
  return driver() === 'pgsql' ? `string_agg(${expr}, ${sep})` : `GROUP_CONCAT(${expr}, ${sep})`;
}

/** Test/CLI helper: drop everything so the next call rebuilds from scratch. */
export async function resetDatabase(): Promise<void> {
  if (driver() === 'pgsql') {
    await connectPg().unsafe('DROP SCHEMA public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO postgres, public;');
  } else if (sqlite) {
    const tables = sqlite.prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'").all();
    sqlite.exec('PRAGMA foreign_keys = OFF');
    for (const t of tables) sqlite.exec(`DROP TABLE IF EXISTS "${t.name}"`);
    sqlite.exec('PRAGMA foreign_keys = ON');
  }
  ready = null;
}

export async function closeDatabase(): Promise<void> {
  sqlite?.close();
  sqlite = null;
  await pg?.end({ timeout: 2 });
  pg = null;
  ready = null;
}
