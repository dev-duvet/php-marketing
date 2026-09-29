import type { APIRoute } from 'astro';

export const POST: APIRoute = async ({ locals, redirect }) => {
  await locals.session.regenerate();
  delete locals.session.data.userId;
  locals.session.flash('success', 'You have been signed out.');
  return redirect('/admin/login', 303);
};
