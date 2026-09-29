import type { APIRoute } from 'astro';
import { wantsJson } from '../../../../lib/http.ts';
import { moveLead } from '../../../../lib/leads.ts';
import { ValidationError, int } from '../../../../lib/validate.ts';

/** Kanban drag-and-drop (JSON) or the per-card select fallback (form). */
export const POST: APIRoute = async ({ params, request, locals, redirect }) => {
  const json = wantsJson(request);
  let status = '';
  const type = request.headers.get('content-type') ?? '';
  if (type.includes('form')) status = String((await request.formData()).get('status') ?? '');
  try {
    await moveLead(int(params.id), status, locals.user!.id);
  } catch (err) {
    if (!(err instanceof ValidationError)) throw err;
    if (json) return Response.json({ ok: false, error: err.message }, { status: 422 });
    locals.session.flash('error', err.message);
  }
  return json ? Response.json({ ok: true }) : redirect('/admin/leads', 303);
};
