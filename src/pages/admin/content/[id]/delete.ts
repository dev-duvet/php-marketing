import type { APIRoute } from 'astro';
import { logActivity } from '../../../../lib/app.ts';
import { run } from '../../../../lib/db.ts';
import { forbidden } from '../../../../lib/http.ts';
import { int } from '../../../../lib/validate.ts';

export const POST: APIRoute = async ({ params, request, locals, redirect }) => {
  if (locals.user?.role === 'client') return forbidden();
  const id = int(params.id);
  const form = await request.formData();
  if (form.get('confirm') !== 'yes') {
    locals.session.flash('error', 'Please confirm the deletion.');
    return redirect(`/admin/content/${id}/edit`, 303);
  }
  await run('DELETE FROM content_items WHERE id = ?', [id]);
  await logActivity(locals.user?.id, 'content.deleted', `Content #${id} deleted`, locals.ip);
  locals.session.flash('success', 'Content deleted. Attached assets remain in the library.');
  return redirect('/admin/content', 303);
};
