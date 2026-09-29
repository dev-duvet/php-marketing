import { count, one, run } from './db.ts';
import { nowLocal, toDateTimeString } from './format.ts';
import { hashPassword, verifyPassword } from './password.ts';

export const MAX_ATTEMPTS = 5;
export const LOCKOUT_MINUTES = 15;

export type Role = 'admin' | 'team' | 'client';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
  client_id: number | null;
  title: string | null;
  password_hash: string;
  is_active: number;
}

export async function findActiveUser(id: number): Promise<User | null> {
  return (await one('SELECT * FROM users WHERE id = ? AND is_active = 1', [id])) as User | null;
}

async function isLockedOut(email: string, ip: string): Promise<boolean> {
  const since = toDateTimeString(new Date(Date.now() - LOCKOUT_MINUTES * 60_000));
  const attempts = await count('SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip = ?) AND attempted_at >= ?', [email, ip, since]);
  return attempts >= MAX_ATTEMPTS;
}

/** Returns the signed-in user, or an error message. */
export async function attemptLogin(emailInput: string, password: string, ip: string): Promise<{ user?: User; error?: string }> {
  const email = emailInput.trim().toLowerCase();
  if (await isLockedOut(email, ip)) {
    return { error: `Too many failed attempts. Please wait ${LOCKOUT_MINUTES} minutes and try again.` };
  }
  const user = (await one('SELECT * FROM users WHERE LOWER(email) = ? AND is_active = 1', [email])) as User | null;
  if (!user || !(await verifyPassword(password, user.password_hash))) {
    await run('INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)', [email, ip, nowLocal()]);
    return { error: 'Those details do not match an active account.' };
  }
  await run('DELETE FROM login_attempts WHERE email = ?', [email]);
  await run('UPDATE users SET last_login_at = ? WHERE id = ?', [nowLocal(), user.id]);
  return { user };
}

export async function changePassword(user: User, current: string, next: string): Promise<string | null> {
  if (!(await verifyPassword(current, user.password_hash))) return 'Your current password is incorrect.';
  if (next.length < 10) return 'New passwords must be at least 10 characters.';
  await run('UPDATE users SET password_hash = ? WHERE id = ?', [await hashPassword(next), user.id]);
  return null;
}

/** SQL fragment + params that restrict client users to their own client's projects. */
export function clientScope(user: User | null, column = 'p.client_id'): [string, (number | string)[]] {
  if (user?.role === 'client') return [` AND ${column} = ?`, [Number(user.client_id ?? 0)]];
  return ['', []];
}

export const isStaff = (user: User | null) => user?.role === 'admin' || user?.role === 'team';
