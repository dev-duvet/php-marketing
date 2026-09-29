import type { AstroGlobal } from 'astro';
import { ValidationError, formToObject, type Input } from './validate.ts';

type Ctx = Pick<AstroGlobal, 'locals' | 'redirect' | 'request'>;

export async function readForm(ctx: Ctx): Promise<{ form: FormData; data: Input }> {
  const form = await ctx.request.formData();
  return { form, data: formToObject(form) };
}

/** Run a form action; on ValidationError keep the input, flash the message and go back to `back`. */
export async function handle(ctx: Ctx, data: Input, back: string, action: () => Promise<Response>): Promise<Response> {
  try {
    return await action();
  } catch (err) {
    if (err instanceof ValidationError) {
      ctx.locals.session.remember(data);
      ctx.locals.session.flash('error', err.message);
      return ctx.redirect(back, 303);
    }
    throw err;
  }
}

export function done(ctx: Ctx, message: string, to: string): Response {
  ctx.locals.session.flash('success', message);
  return ctx.redirect(to, 303);
}

/** Read a remembered input value (after a failed submit), then fall back. */
export function oldValue(ctx: Pick<AstroGlobal, 'locals'>, key: string, fallback: any = ''): string {
  const v = ctx.locals.session.old(key, fallback);
  return v == null ? '' : String(v);
}

/** Clear remembered input once a page has rendered it. */
export function clearOld(ctx: Pick<AstroGlobal, 'locals'>): void {
  delete ctx.locals.session.data.old;
}
