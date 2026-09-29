import { timingSafeEqual } from 'node:crypto';
import { defineMiddleware } from 'astro:middleware';
import './lib/env.ts';
import { getSettings } from './lib/app.ts';
import { findActiveUser } from './lib/auth.ts';
import { isProduction } from './lib/env.ts';
import { forbidden, plainPage } from './lib/http.ts';
import { SESSION_COOKIE, loadSession } from './lib/session.ts';

const CSP =
  "default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; " +
  "script-src 'self'; font-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'";

// Pages client users may never open, and the only POST targets they may use.
const STAFF_ONLY = [/^\/admin\/leads/, /^\/admin\/analytics/, /^\/admin\/content\/new/, /^\/admin\/content\/\d+\/edit/, /^\/admin\/projects\/new/, /^\/admin\/projects\/\d+\/edit/, /^\/admin\/orders/];
const CLIENT_POSTS = [/^\/admin\/approvals\/\d+$/, /^\/admin\/settings$/, /^\/admin\/logout$/];

function sameToken(a: string, b: string): boolean {
  const x = Buffer.from(a);
  const y = Buffer.from(b);
  return x.length === y.length && timingSafeEqual(x, y);
}

export const onRequest = defineMiddleware(async (ctx, next) => {
  const { pathname } = ctx.url;
  const originalId = ctx.cookies.get(SESSION_COOKIE)?.value;
  const session = await loadSession(originalId);
  const forwarded = ctx.request.headers.get('x-forwarded-for');
  let ip = forwarded ? forwarded.split(',')[0].trim() : '';
  if (!ip) {
    try {
      ip = ctx.clientAddress;
    } catch {
      ip = 'unknown';
    }
  }

  ctx.locals.session = session;
  ctx.locals.ip = ip;
  ctx.locals.settings = await getSettings();
  ctx.locals.user = session.data.userId ? await findActiveUser(session.data.userId) : null;
  if (session.data.userId && !ctx.locals.user) delete session.data.userId;

  let response: Response;
  const method = ctx.request.method;

  if (method === 'POST' && !(await csrfValid(ctx.request, session.data.csrf))) {
    response = plainPage(419, 'Please try that again.', 'For your security the form expired. Go back, refresh the page and resubmit.');
  } else if ((pathname === '/admin' || pathname.startsWith('/admin/')) && pathname !== '/admin/login') {
    const user = ctx.locals.user;
    if (!user) {
      if (method === 'GET') session.data.intended = pathname + ctx.url.search;
      response = ctx.redirect('/admin/login', 302);
    } else if (user.role === 'client' && (STAFF_ONLY.some((r) => r.test(pathname)) || (method === 'POST' && !CLIENT_POSTS.some((r) => r.test(pathname))))) {
      response = forbidden();
    } else {
      response = await next();
    }
  } else {
    response = await next();
  }

  // Pages stream; render HTML fully first so components that read/modify the session
  // (flash messages, CSRF fields) have run before the session is saved.
  const isHtml = (response.headers.get('content-type') ?? '').includes('text/html');
  const body: BodyInit | null = isHtml ? await response.text() : response.body;

  // Persist the session only when it holds something worth keeping (avoids a row per anonymous visit).
  const worthKeeping = !session.isNew || session.touched || Object.keys(session.data).some((k) => k !== 'csrf');
  const headers = new Headers(response.headers);
  if (session.destroyed) {
    headers.append('Set-Cookie', `${SESSION_COOKIE}=; Path=/; Max-Age=0; HttpOnly; SameSite=Lax${isProduction() ? '; Secure' : ''}`);
  } else if (worthKeeping) {
    await session.save();
    if (session.id !== originalId) {
      headers.append('Set-Cookie', `${SESSION_COOKIE}=${session.id}; Path=/; Max-Age=28800; HttpOnly; SameSite=Lax${isProduction() ? '; Secure' : ''}`);
    }
  }
  headers.set('X-Content-Type-Options', 'nosniff');
  headers.set('X-Frame-Options', 'SAMEORIGIN');
  headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');
  if (isProduction()) headers.set('Content-Security-Policy', CSP);
  if (pathname.startsWith('/admin')) headers.set('Cache-Control', 'no-store');
  return new Response(body, { status: response.status, statusText: response.statusText, headers });
});

async function csrfValid(request: Request, expected: string): Promise<boolean> {
  const header = request.headers.get('x-csrf-token');
  if (header) return sameToken(header, expected);
  const type = request.headers.get('content-type') ?? '';
  if (!type.includes('form')) return false;
  try {
    const token = (await request.clone().formData()).get('_token');
    return typeof token === 'string' && sameToken(token, expected);
  } catch {
    return false;
  }
}
