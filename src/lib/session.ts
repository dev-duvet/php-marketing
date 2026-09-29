import { randomBytes } from 'node:crypto';
import { one, run } from './db.ts';

export const SESSION_COOKIE = 'createza_session';
const LIFETIME_SECONDS = 60 * 60 * 8;

export interface Flash {
  type: 'success' | 'error';
  message: string;
}

export interface SessionData {
  userId?: number;
  csrf: string;
  flash?: Flash[];
  old?: Record<string, any>;
  cart?: Record<string, number>;
  orders?: string[];
  intended?: string;
}

export class Session {
  id: string;
  data: SessionData;
  private snapshot: string;
  isNew: boolean;
  /** Set when a CSRF token was rendered, so the session must persist for the form to post back. */
  touched = false;
  destroyed = false;

  csrfToken(): string {
    this.touched = true;
    return this.data.csrf;
  }

  constructor(id: string, data: SessionData, isNew: boolean) {
    this.id = id;
    this.data = data;
    this.isNew = isNew;
    this.snapshot = isNew ? '' : JSON.stringify(data);
  }

  flash(type: Flash['type'], message: string): void {
    (this.data.flash ??= []).push({ type, message });
  }

  takeFlashes(): Flash[] {
    const f = this.data.flash ?? [];
    delete this.data.flash;
    return f;
  }

  remember(input: Record<string, any>): void {
    const { _token, password, current_password, new_password, ...rest } = input;
    this.data.old = rest;
  }

  old(key: string, fallback: any = ''): any {
    return this.data.old?.[key] ?? fallback;
  }

  /** New id and fresh CSRF token (on login/logout) to prevent session fixation. */
  async regenerate(): Promise<void> {
    await run('DELETE FROM sessions WHERE id = ?', [this.id]);
    this.id = newId();
    this.data.csrf = newId();
    this.isNew = true;
  }

  async save(): Promise<void> {
    const json = JSON.stringify(this.data);
    if (json === this.snapshot) return;
    await run(
      'INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?) ON CONFLICT (id) DO UPDATE SET data = excluded.data, last_activity = excluded.last_activity',
      [this.id, json, Math.floor(Date.now() / 1000)],
    );
    this.snapshot = json;
    // Occasionally prune expired sessions.
    if (Math.random() < 0.02) {
      await run('DELETE FROM sessions WHERE last_activity < ?', [Math.floor(Date.now() / 1000) - LIFETIME_SECONDS]);
    }
  }
}

function newId(): string {
  return randomBytes(32).toString('hex');
}

export async function loadSession(id: string | undefined): Promise<Session> {
  if (id && /^[a-f0-9]{64}$/.test(id)) {
    const row = await one('SELECT data FROM sessions WHERE id = ? AND last_activity > ?', [
      id,
      Math.floor(Date.now() / 1000) - LIFETIME_SECONDS,
    ]);
    if (row) {
      try {
        return new Session(id, JSON.parse(row.data), false);
      } catch {
        /* fall through to a fresh session */
      }
    }
  }
  return new Session(newId(), { csrf: newId() }, true);
}

export const cookieOptions = (secure: boolean) => ({
  path: '/',
  httpOnly: true,
  sameSite: 'lax' as const,
  secure,
  maxAge: LIFETIME_SECONDS,
});
