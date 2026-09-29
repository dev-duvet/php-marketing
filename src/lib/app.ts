import { all, count, insert, run } from './db.ts';
import { slugify } from './format.ts';

export async function getSettings(): Promise<Record<string, string>> {
  const rows = await all('SELECT key, value FROM settings');
  return Object.fromEntries(rows.map((r) => [r.key, r.value ?? '']));
}

export async function setSetting(key: string, value: string): Promise<void> {
  await run('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = excluded.value', [key, value]);
}

export async function logActivity(userId: number | null | undefined, action: string, description: string, ip = ''): Promise<void> {
  await insert('activity_logs', { user_id: userId ?? null, action, description, ip });
}

export async function uniqueSlug(table: string, text: string, ignoreId = 0): Promise<string> {
  const base = slugify(text);
  let slug = base;
  let n = 2;
  while ((await count(`SELECT COUNT(*) FROM ${table} WHERE slug = ? AND id != ?`, [slug, ignoreId])) > 0) {
    slug = `${base}-${n++}`;
  }
  return slug;
}

export function safeHex(value: string, fallback: string): string {
  return /^#[0-9A-Fa-f]{6}$/.test(value) ? value : fallback;
}
